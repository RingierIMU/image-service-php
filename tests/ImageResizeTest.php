<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use RingierIMU\ImageService\ImageResize;
use RingierIMU\ImageService\UrlCodec;

final class ImageResizeTest extends TestCase
{
    public function testInstantiate(): void
    {
        $imageResize = new ImageResize(
            'https://image-service.ringier.tech/',
            'test-key',
        );

        $this->assertInstanceOf(ImageResize::class, $imageResize);
    }

    public function testResizeUrl(): void
    {
        $imageResize = new ImageResize(
            'https://image-service.ringier.tech/',
            'test-key',
        );

        $url = $imageResize->buildUrl(
            'hz',
            'pi',
            'listing-thumb-360w',
            'horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
        );

        // directories compress into a token, the filename stays readable
        $this->assertEquals(
            'https://image-service.ringier.tech/hz/pi/listing-thumb-360w/WtOn6QZtQBeHwqijej_Cyg/-/_1w6IUmDdh-bQw2yS3yjwLAA/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            $url,
        );

        $this->assertEquals(
            'horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            UrlCodec::decompressSourcePath(
                implode('/', array_slice(explode('/', $url), 8)),
            ),
        );
    }

    public function testResizeUrlKeepsShortSourcePathsPlain(): void
    {
        $imageResize = new ImageResize(
            'https://image-service.ringier.tech/',
            'test-key',
        );

        $url = $imageResize->buildUrl(
            'hz',
            'pi',
            'listing-thumb-360w',
            'bucket/file.jpg',
        );

        $this->assertEquals(
            'https://image-service.ringier.tech/hz/pi/listing-thumb-360w/vRiosggBpO5G9nOcRkk2-g/-/bucket/file.jpg',
            $url,
        );
    }

    public function testResizeUrlWithParams(): void
    {
        $imageResize = new ImageResize(
            'https://image-service.ringier.tech/',
            'test-key',
        );

        $url = $imageResize->buildUrl(
            'hz',
            'pi',
            'listing-thumb-360w',
            'horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            [
                'w' => 360,
                'h' => 240,
                'fit' => 'crop',
            ],
        );

        $this->assertEquals(
            'https://image-service.ringier.tech/hz/pi/listing-thumb-360w/We1ryJtuobglqBVQUKoiMg/-_1g2WtZKDflMBZy8jEAJzPjM0MagE/_1w6IUmDdh-bQw2yS3yjwLAA/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            $url,
        );

        $paramsSegment = explode('/', $url)[7];
        $this->assertEquals(
            '{"fit":"crop","h":240,"w":360}',
            UrlCodec::decompress(mb_substr($paramsSegment, 1)),
        );

        $this->assertEquals(
            'horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            UrlCodec::decompressSourcePath(
                implode('/', array_slice(explode('/', $url), 8)),
            ),
        );
    }

    public function testTemplateUrl(): void
    {
        $imageResize = new ImageResize(
            'https://image-service.ringier.tech/',
            'test-key',
        );

        $url = $imageResize->buildTemplateUrl(
            'hz',
            'pi',
            'og-template-1200w-630h',
            'pretty-seo-filename.jpg',
            [
                'template' => 'job-04-2022',
                'replacements' => [
                    'title1' => 'Title line 1',
                    'title2' => 'Title line 2',
                    'subtitle' => 'Subtitle line',
                ],
                'images' => [
                    'logo' => [
                        'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
                    ],
                    'background' => [
                        'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
                    ],
                ],
            ],
        );

        $this->assertEquals(
            'https://image-service.ringier.tech/t/hz/pi/og-template-1200w-630h/CCxhLrxrtzdQk1Cc5gx3Dg/-/_1I8fN4FSqkANM-gqGyO5HEjdC9QusGgNLovoCWxbE4n5ggQErPAqzTXKrzLP0U82SUk3MTFNSjIxTDVLTTM0N05JTklKM00zMktJSDZMtTCzMklNMjYBFSzr-TE0L-2prAQ/pretty-seo-filename.jpg',
            $url,
        );

        $this->assertLessThan(1024, mb_strlen($url));

        $templateSegment = explode('/', $url)[9];
        $this->assertEquals(
            [
                'template' => 'job-04-2022',
                'replacements' => [
                    'title1' => 'Title line 1',
                    'title2' => 'Title line 2',
                    'subtitle' => 'Subtitle line',
                ],
                'images' => [
                    'logo' => [
                        'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
                    ],
                    'background' => [
                        'file' => 's3://horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
                    ],
                ],
            ],
            json_decode(UrlCodec::decompress($templateSegment), true),
        );
    }
}
