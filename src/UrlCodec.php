<?php

namespace RingierIMU\ImageService;

use InvalidArgumentException;

/**
 * Compresses text into a compact URL-safe token and back.
 *
 * Token format: '_' + version + unpadded URL-safe base64 (RFC 4648 §5) of the
 * raw DEFLATE compressed text:
 *   _0  no preset dictionary
 *   _1  preset dictionary v1
 * Every output character is an RFC 3986 unreserved character, so tokens pass
 * through urlencode() unchanged.
 *
 * Tokens without the '_' prefix are decoded as legacy standard base64, so
 * URLs generated before compression was introduced keep working.
 *
 * Source image paths ('bucket/dir/file.jpg') use the same token format via
 * compressSourcePath(): the whole path, or just its directory part with the
 * filename kept plain, is emitted as a token whenever that is shorter than
 * the rawurlencoded path. S3 bucket names cannot start with '_', so a '_'
 * in the first path segment unambiguously marks a token.
 *
 * DICTIONARY_V1 is a wire-protocol contract shared byte-for-byte with the
 * image service (image-service-lambda lib/url-codec.js). NEVER edit it:
 * published URLs depend on it forever. To improve the vocabulary, add a new
 * dictionary under the next version prefix instead.
 */
class UrlCodec
{
    protected const COMPRESSED_PREFIX = '_';

    protected const VERSION_PLAIN = '0';

    protected const VERSION_DICTIONARY_V1 = '1';

    protected const DICTIONARY_V1 = ''
        . 'backgrounds/'
        . 'overlays/'
        . 'placeholders/'
        . '.svg'
        . '.webp'
        . '","cornerOverlayColor":"'
        . '","cornerStripColor":"'
        . '","brandWebsite":"'
        . '","brandWebsiteColor":"'
        . '","brandWidth":"'
        . '","companyColor":"'
        . '","titleColor":"'
        . '","subtitleColor":"'
        . '","subtitleBorderColor":"'
        . '","ctaColor":"'
        . '","ctaBackgroundColor":"'
        . '","priceColor":"'
        . '","priceBackgroundColor":"'
        . '","priceBackgroundOpacity":"'
        . '","jobTitleColor":"'
        . '","jobFunctionColor":"'
        . '","salaryColor":"'
        . '","salaryIconColor":"'
        . '","locationColor":"'
        . '","locationIconColor":"'
        . '","workTypeColor":"'
        . '","workTypeIconColor":"'
        . '","infoValue1Color":"'
        . '","infoValue2Color":"'
        . '","infoValue3Color":"'
        . '","infoLabel1Color":"'
        . '","infoLabel2Color":"'
        . '","infoLabel3Color":"'
        . '","backgroundGradientFrom":"'
        . '","backgroundGradientTo":"'
        . '","backgroundGradientColor":"'
        . '","sideGradientFrom":"'
        . '","sideGradientTo":"'
        . '","footerBackgroundColor":"'
        . '","brandBackgroundColor":"'
        . '","brandBackgroundStripeColor":"'
        . '","fontFamily":"Roboto'
        . '#ffffff'
        . '#000000'
        . 'transparent'
        . 'aimg.brightermonday.co.ke/'
        . 'imo-static-public-prod/'
        . 'imo-original-imgs-prod/imagini_agentii_originale/'
        . 'mx-files-prod-ritdu-rsmg/entity-list/'
        . 'horizon-files-staging/'
        . 'sdc-bn-production/'
        . 'cde-bn-production/client-8/'
        . 'user-img-gh-jobs-prod/userid'
        . 'advertiser-img-gh-jobs-prod/dealer-images/advid'
        . 'prod-property-core-backend-media-brk/'
        . 'prod-property-core-backend-media-rad/'
        . 'category-rectangle-2022-10'
        . 'category-rectangle-2025-08'
        . 'category-square'
        . 'job-square-04-2022'
        . 'job-square'
        . 'business'
        . '","titleWithoutSubtitle1":"'
        . '","titleWithoutSubtitle2":"'
        . '","title1Large":"'
        . '","title3_1":"'
        . '","title3_2":"'
        . '","title3_3":"'
        . '","location2_1":"'
        . '","location2_2":"'
        . '","subtitle1":"'
        . '","subtitle2":"'
        . '","company":"'
        . '","title":"'
        . 'Internship & Graduate'
        . 'Part Time'
        . 'Contract'
        . 'Unspecified'
        . 'Confidential'
        . 'GHS '
        . 'Management & Business Development'
        . 'Accounting, Auditing & Finance'
        . 'Engineering & Technology'
        . 'Admin & Office'
        . 'Sales'
        . 'Accra & Tema Region'
        . 'Kumasi & Ashanti Region'
        . ' Region'
        . 'Dakar | Temps plein'
        . 'Nairobi | Full time'
        . 'Full Time'
        . 'SUPR. TEREN '
        . 'CIRCULAR'
        . 'parter'
        . 'SEMIDECOMANDAT'
        . 'CASĂ'
        . 'APARTAMENT'
        . 'CAMERĂ'
        . 'BĂI'
        . 'de închiriat'
        . 'de vânzare'
        . '.000 €'
        . '0 €'
        . 'Recrutement ouvert'
        . 'Now Hiring'
        . 'shared-folder-prod/2022-10/square/'
        . 'shared-folder-prod/2024-09/rectangle/'
        . 'shared-folder-prod/2025-09/rectangle/'
        . 'horizon-files-prod/og-template/job-square-04-2022/'
        . '/ed/picture/'
        . '/pi/logo/'
        . '/ed/logo/'
        . '.jpeg"'
        . '.png"'
        . '"embed":'
        . '"resize":{'
        . '{"fit":"'
        . ',"w":'
        . ',"h":'
        . '{"dpr":'
        . '"ow":"'
        . '"oi":'
        . '{"or":180}'
        . '{"or":270}'
        . '{"or":90}'
        . '{"or":0}'
        . '"images":[]}'
        . '"},"logo":{"file":"s3://'
        . '"},"background":{"file":"s3://'
        . '{"template":"business","replacements":{"title":"'
        . '{"template":"job-09-2024","replacements":{"jobTitle":"","jobFunction":"","salary":"","location":"","workType":""},"images":{"background":{"file":"s3://'
        . '-dark'
        . '{"template":"cde-prop-meta-carousel-06-2025-light","replacements":{"title1Regular":"APARTAMENT","title2":"de vânzare","subtitle":"","price":"","infoValue1":"","infoLabel1":"CAMERE","infoValue2":"","infoLabel2":"SUPRAFAȚĂ","infoValue3":"","infoLabel3":"ETAJ"},"images":{"background":{"file":"s3://prod-property-core-backend-media-imo/'
        . '{"template":"job-04-2022","replacements":{"title1":"","title2":"","subtitle":"","cta":"Now Hiring"},"images":{"background":{"file":"s3://horizon-files-prod/og-template/job-04-2022/';

    public static function compress(string $text): string
    {
        $context = deflate_init(
            ZLIB_ENCODING_RAW,
            [
                'level' => 9,
                'dictionary' => self::DICTIONARY_V1,
            ]
        );

        return self::COMPRESSED_PREFIX
            . self::VERSION_DICTIONARY_V1
            . self::base64UrlEncode(deflate_add($context, $text, ZLIB_FINISH));
    }

    /**
     * Encodes a source image path into its shortest URL form: plain
     * rawurlencoded segments, a token for the directory part with the
     * filename kept plain (visible for image SEO), or a token for the whole
     * path. Ties prefer the more readable form. Deterministic per input, so
     * URLs stay stable for caching.
     *
     * @param string $path
     */
    public static function compressSourcePath(string $path): string
    {
        $best = implode(
            '/',
            array_map(
                'rawurlencode',
                explode('/', $path)
            )
        );

        $slash = mb_strrpos($path, '/');

        if ($slash !== false && $slash > 0) {
            $dirsOnly = self::compress(mb_substr($path, 0, $slash))
                . '/'
                . rawurlencode(mb_substr($path, $slash + 1));

            if (mb_strlen($dirsOnly) < mb_strlen($best)) {
                $best = $dirsOnly;
            }
        }

        $full = self::compress($path);

        if (mb_strlen($full) < mb_strlen($best)) {
            $best = $full;
        }

        return $best;
    }

    /**
     * Decodes a source path produced by compressSourcePath() back to the
     * raw path. Mirrors the image service (image-service-lambda
     * lib/url-token-params.js): a '_' first segment is decompressed, any
     * remaining segments are plain and rawurldecoded.
     *
     * @param string $path
     */
    public static function decompressSourcePath(string $path): string
    {
        $segments = explode('/', $path);

        if (mb_substr($segments[0], 0, 1) !== self::COMPRESSED_PREFIX) {
            return implode(
                '/',
                array_map('rawurldecode', $segments)
            );
        }

        $head = self::decompress(array_shift($segments));

        return implode(
            '/',
            array_merge(
                [$head],
                array_map('rawurldecode', $segments)
            )
        );
    }

    public static function decompress(string $token): string
    {
        if (mb_substr($token, 0, 1) !== self::COMPRESSED_PREFIX) {
            $text = base64_decode($token, true);

            if ($text === false) {
                throw new InvalidArgumentException('Invalid legacy base64 token');
            }

            return $text;
        }

        switch (mb_substr($token, 1, 1)) {
            case self::VERSION_PLAIN:
                $options = [];
                break;
            case self::VERSION_DICTIONARY_V1:
                $options = ['dictionary' => self::DICTIONARY_V1];
                break;
            default:
                throw new InvalidArgumentException('Unknown token version');
        }

        $context = inflate_init(ZLIB_ENCODING_RAW, $options);
        $text = @inflate_add(
            $context,
            self::base64UrlDecode(mb_substr($token, 2)),
            ZLIB_FINISH
        );

        if ($text === false) {
            throw new InvalidArgumentException('Invalid DEFLATE payload in token');
        }

        return $text;
    }

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $encoded): string
    {
        $base64 = strtr($encoded, '-_', '+/');
        $bytes = base64_decode(
            str_pad($base64, (int) ceil(mb_strlen($base64) / 4) * 4, '='),
            true
        );

        if ($bytes === false) {
            throw new InvalidArgumentException('Invalid base64url payload in token');
        }

        return $bytes;
    }
}
