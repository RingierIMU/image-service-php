<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use RingierIMU\ImageService\ImageResize;

final class ImageResizeTest extends TestCase
{
    public function testInstantiate(): void
    {
        $imageResize = new ImageResize(
            'https://i.rimu.ci/',
            'test-key',
        );

        $this->assertInstanceOf(ImageResize::class, $imageResize);
    }

    public function testResizeUrl(): void
    {
        $imageResize = new ImageResize(
            'https://i.rimu.ci/',
            'test-key',
        );

        $url = $imageResize->buildUrl(
            'hz',
            'pi',
            'listing-thumb-360w',
            'horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
        );

        $this->assertEquals(
            'https://i.rimu.ci/hz/pi/listing-thumb-360w/a6c9235a796ac9a4d40a425cf9194e9d/-/horizon-files-prod/pi/picture/qk4mz7j/e6be465dd23e0ef571fcdbd3f46bfe1c8486cd52.jpg',
            $url
        );
    }

    public function testTemplateUrl(): void
    {
        $imageResize = new ImageResize(
            'https://i.rimu.ci/',
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
            'https://i.rimu.ci/t/hz/pi/og-template-1200w-630h/a02345306db7136af765a1b43e648f5b/-/eyJ0ZW1wbGF0ZSI6ImpvYi0wNC0yMDIyIiwicmVwbGFjZW1lbnRzIjp7InRpdGxlMSI6IlRpdGxlIGxpbmUgMSIsInRpdGxlMiI6IlRpdGxlIGxpbmUgMiIsInN1YnRpdGxlIjoiU3VidGl0bGUgbGluZSJ9LCJpbWFnZXMiOnsibG9nbyI6eyJmaWxlIjoiczM6XC9cL2hvcml6b24tZmlsZXMtcHJvZFwvcGlcL3BpY3R1cmVcL3FrNG16N2pcL2U2YmU0NjVkZDIzZTBlZjU3MWZjZGJkM2Y0NmJmZTFjODQ4NmNkNTIuanBnIn0sImJhY2tncm91bmQiOnsiZmlsZSI6InMzOlwvXC9ob3Jpem9uLWZpbGVzLXByb2RcL3BpXC9waWN0dXJlXC9xazRtejdqXC9lNmJlNDY1ZGQyM2UwZWY1NzFmY2RiZDNmNDZiZmUxYzg0ODZjZDUyLmpwZyJ9fX0%3D/pretty-seo-filename.jpg',
            $url
        );
    }
}
