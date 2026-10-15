<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RingierIMU\ImageService\UrlCodec;

final class UrlCodecTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $text = json_encode(
            [
                'template' => 'job-04-2022',
                'replacements' => [
                    'title1' => 'Überschrift mit Ümlauten — and emoji 🚀',
                    'subtitle' => 'Subtitle line',
                ],
                'images' => [
                    'logo' => [
                        'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
                    ],
                ],
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        $token = UrlCodec::compress($text);

        $this->assertSame($text, UrlCodec::decompress($token));
    }

    public function testTokenIsUrlSafe(): void
    {
        $token = UrlCodec::compress(
            str_repeat('{"key":"value","path":"s3://bucket/some/long/path.jpg"}', 20),
        );

        $this->assertSame($token, urlencode($token));
        $this->assertSame($token, rawurlencode($token));
    }

    public function testCompressesLargePayloadsBelowLegacySize(): void
    {
        $params = ['images' => []];
        for ($i = 0; $i < 10; $i++) {
            $params['images']['image-' . $i] = [
                'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            ];
        }

        $legacy = urlencode(base64_encode(json_encode($params)));
        $token = UrlCodec::compress(
            json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        $this->assertGreaterThan(1024, mb_strlen($legacy));
        $this->assertLessThan(mb_strlen($legacy) / 3, mb_strlen($token));
    }

    public function testDecompressesLegacyBase64Tokens(): void
    {
        $json = json_encode(['fit' => 'crop', 'h' => 240, 'w' => 360]);

        $this->assertSame($json, UrlCodec::decompress(base64_encode($json)));
    }

    public function testDecompressesPlainTokensWithoutDictionary(): void
    {
        $json = '{"fit":"crop","h":240,"w":360}';
        $token = '_0' . rtrim(strtr(base64_encode(gzdeflate($json, 9)), '+/', '-_'), '=');

        $this->assertSame($json, UrlCodec::decompress($token));
    }

    public function testSourcePathKeepsShortPathsPlain(): void
    {
        $this->assertSame(
            'bucket/file.jpg',
            UrlCodec::compressSourcePath('bucket/file.jpg'),
        );
    }

    public function testSourcePathRawurlencodesPlainSegments(): void
    {
        $this->assertSame(
            'b/a%20c.jpg',
            UrlCodec::compressSourcePath('b/a c.jpg'),
        );
    }

    public function testSourcePathCompressesDirectoriesKeepingFilenameVisible(): void
    {
        // high-entropy hash filenames stay plain; the dictionary-friendly
        // directory part becomes the token
        $path = 'prod-property-core-backend-media-imo/4185-1130/62/b2071156961e037f4e0925e75caaf8ec-gallery_image_large.jpg';

        $encoded = UrlCodec::compressSourcePath($path);

        $this->assertStringStartsWith('_1', $encoded);
        $this->assertStringEndsWith('/b2071156961e037f4e0925e75caaf8ec-gallery_image_large.jpg', $encoded);
        $this->assertLessThan(mb_strlen(rawurlencode($path)), mb_strlen($encoded));
        $this->assertSame($path, UrlCodec::decompressSourcePath($encoded));
    }

    public function testSourcePathCompressesWholeDictionaryFriendlyPaths(): void
    {
        $path = 'horizon-files-prod/og-template/job-square-04-2022/background.png';

        $encoded = UrlCodec::compressSourcePath($path);

        $this->assertStringStartsWith('_1', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertSame($path, UrlCodec::decompressSourcePath($encoded));
    }

    public function testSourcePathTokensAreUrlSafe(): void
    {
        $encoded = UrlCodec::compressSourcePath(
            'prod-property-core-backend-media-imo/4185-1130/62/b2071156961e037f4e0925e75caaf8ec.jpg',
        );

        foreach (explode('/', $encoded) as $segment) {
            $this->assertSame($segment, rawurlencode($segment));
        }
    }

    public function testSourcePathRoundTripsSpecialCharacters(): void
    {
        $paths = [
            'bucket/dir/imagine româna 100%+.jpg',
            'prod-property-core-backend-media-brk/2023-11/apartament de vânzare.jpg',
            'bucket/no-directories.png',
        ];

        foreach ($paths as $path) {
            $this->assertSame(
                $path,
                UrlCodec::decompressSourcePath(UrlCodec::compressSourcePath($path)),
            );
        }
    }

    public function testDictionaryIsFrozen(): void
    {
        // wire-protocol contract with image-service-lambda lib/url-codec.js;
        // a changed hash means published urls can no longer be decoded
        $dictionary = (new \ReflectionClass(UrlCodec::class))
            ->getConstant('DICTIONARY_V1');

        $this->assertSame('b6b973310e98eb1286b280d1d65c45ea', md5($dictionary));
    }

    public function testRejectsUnknownTokenVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlCodec::decompress('_9AAAAAAAA');
    }

    public function testRejectsInvalidCompressedToken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlCodec::decompress('_1not!valid*base64url');
    }

    public function testRejectsCorruptDeflatePayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlCodec::decompress('_1AAAAAAAA');
    }

    public function testRejectsInvalidLegacyToken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlCodec::decompress('not valid base64 at all!!!');
    }
}
