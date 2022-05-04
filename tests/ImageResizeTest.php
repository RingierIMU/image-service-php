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
            'https://i.rimu.ci/t/hz/pi/og-template-1200w-630h/a533bf0ac34ea53a0ac4bb9c0d2b7520/-/N4IgLgpgtgDgNgQ0iAXCAVgewEYFoAMALLgEz4kkgA0IAThPAgMbQQB2YAzqqGAJZg4EAIyoQAFQFCABHD5sI00TX6CIlNJLWz5iyjU4BXbKqFiAysdOK5CkAF8afKAgDmEbilBxMrzDxAAMz4zNE4AZhQAHQB6WIALTFo%2BAC9MNlxgoU5cGFpMABNYmD5iviYwQ3pYgEcAa0IoFIB2dFiIADZsCEIOgFYCgpJwiHwIQL7m4UCmAuwC8MDe7ECRJgAOQnWO2b6SADp0GFcHGmxmOtd8wzYCgKyIMQjouJjE5LSMh5y8wrKyipVCC1BpNVrtLo9fqDYajcaTaazeaLZarYQbLY7Ap7Q7HBz2exAA%3D/pretty-seo-filename.jpg',
            $url
        );
    }
}
