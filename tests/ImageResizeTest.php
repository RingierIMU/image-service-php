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

        $this->assertEquals(
            'https://image-service.ringier.tech/hz/pi/listing-thumb-360w/a6c9235a796ac9a4d40a425cf9194e9d/-/horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            $url
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
            ]
        );

        $this->assertEquals(
            'https://image-service.ringier.tech/hz/pi/listing-thumb-360w/69f69a5d4d68f7527396ae69d27817af/-_1g2WtZKDflMBZy8jEAJzPjM0MagE/horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            $url
        );

        $paramsSegment = explode('/', $url)[7];
        $this->assertEquals(
            '{"fit":"crop","h":240,"w":360}',
            UrlCodec::decompress(mb_substr($paramsSegment, 1))
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
            ]
        );

        $this->assertEquals(
            'https://image-service.ringier.tech/t/hz/pi/og-template-1200w-630h/082c612ebc6bb7375093509ce60c770e/-/_1I8fN4FSqkANM-gqGyO5HEjdC9QusGgNLovoCWxbE4n5ggQErPAqzTXKrzLP0U82SUk3MTFNSjIxTDVLTTM0N05JTklKM00zMktJSDZMtTCzMklNMjYBFSzr-TE0L-2prAQ/pretty-seo-filename.jpg',
            $url
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
            json_decode(UrlCodec::decompress($templateSegment), true)
        );
    }
}
