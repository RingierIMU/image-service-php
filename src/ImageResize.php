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

        if (isset($imageParams['ow'])) {
            $imageParams['ow'] = base64_encode($imageParams['ow']);
        }
        unset($imageParams['s']);
        ksort($imageParams);

        $imageHost = rtrim($this->imageServiceUrl, '/');
        $imagePathParams = http_build_query(
            $imageParams,
            '',
            '~'
        );
        $imagePathPostfix = implode(
            '/',
            [
                urlencode($imagePathParams ?: '-'),
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

        $imageParams = $imageParams ?: [];
        if (isset($imageParams['ow'])) {
            $imageParams['ow'] = base64_encode($imageParams['ow']);
        }
        unset($imageParams['s']);
        ksort($imageParams);

        $imageHost = rtrim($this->imageServiceUrl, '/');
        $imagePathParams = http_build_query(
            $imageParams,
            '',
            '~'
        );
        $imagePathPostfix = implode(
            '/',
            array_map(
                function ($item) {
                    return urlencode($item);
                },
                array_merge(
                    [$imagePathParams ?: '-'],
                    [
                        base64_encode(
                            json_encode(
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
}
