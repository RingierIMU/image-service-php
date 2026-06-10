<?php

namespace RingierIMU\ImageService;

class ImageResize
{
    /**
     * @var string
     */
    protected $imageServiceUrl;

    /**
     * @var string
     */
    protected $imageServiceKey;

    public function __construct(
        string $imageServiceUrl,
        string $imageServiceKey
    ) {
        $this->imageServiceUrl = $imageServiceUrl;
        $this->imageServiceKey = $imageServiceKey;
    }

    public function buildUrl(
        string $project,
        string $venture,
        string $resizeKey,
        string $sourceImageUrl,
        array $imageParams = []
    ): string {
        $resizePath = sprintf(
            '%s/%s/%s',
            $project,
            $venture,
            $resizeKey
        );

        // strip http(s):// from $sourceImageUrl
        if (mb_substr($sourceImageUrl, 0, 7) === 'http://') {
            $sourceImageUrl = mb_substr($sourceImageUrl, 7);
        } elseif (mb_substr($sourceImageUrl, 0, 8) === 'https://') {
            $sourceImageUrl = mb_substr($sourceImageUrl, 8);
        }
        $sourceImageUrl = implode(
            '/',
            array_map(
                'rawurlencode',
                explode(
                    '/',
                    $sourceImageUrl
                )
            )
        );

        $imageHost = rtrim($this->imageServiceUrl, '/');

        unset($imageParams['s']);
        ksort($imageParams);
        $imageParamsPath = $imageParams ? UrlCodec::compress(self::jsonEncode($imageParams)) : '';

        $imagePathPostfix = implode(
            '/',
            [
                urlencode('-' . $imageParamsPath),
                trim($sourceImageUrl, '/'),
            ]
        );

        $signature = md5(
            $this->imageServiceKey
            . ':'
            . $resizePath
            . '/'
            . $imagePathPostfix
        );

        return $imageHost
            . '/'
            . $resizePath
            . '/'
            . $signature
            . '/'
            . $imagePathPostfix;
    }

    public function buildTemplateUrl(
        string $project,
        string $venture,
        string $resizeKey,
        string $seoFilename,
        array $templateParams,
        array $imageParams = []
    ): string {
        $resizePath = sprintf(
            't/%s/%s/%s',
            $project,
            $venture,
            $resizeKey
        );

        unset($imageParams['s']);
        ksort($imageParams);
        $imageParamsPath = $imageParams ? UrlCodec::compress(self::jsonEncode($imageParams)) : '';

        $imageHost = rtrim($this->imageServiceUrl, '/');
        $imagePathPostfix = implode(
            '/',
            array_map(
                function ($item) {
                    return urlencode($item);
                },
                array_merge(
                    ['-' . $imageParamsPath],
                    [
                        UrlCodec::compress(
                            self::jsonEncode(
                                array_replace_recursive(
                                    [
                                        'template' => null,
                                        'replacements' => [],
                                        'images' => [],
                                    ],
                                    $templateParams
                                )
                            )
                        ),
                    ],
                    [trim($seoFilename, '/')]
                )
            )
        );

        $signature = md5(
            $this->imageServiceKey
            . ':'
            . $resizePath
            . '/'
            . $imagePathPostfix
        );

        return $imageHost
            . '/'
            . $resizePath
            . '/'
            . $signature
            . '/'
            . $imagePathPostfix;
    }

    protected static function jsonEncode(array $params): string
    {
        return json_encode(
            $params,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
