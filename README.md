# Image Service PHP integration

## Usage examples

Instantiate an instance of the resize helper using the image service base url and security key you were provided
```
$imageResize = new ImageResize(
    'https://i.rimu.ci/',
    'your-security-key',
);
```

### Resize an image
`PROJECT` referes to the integration project
`VENTURE` some projects share configuration but with branding differences (i.e., same brand, different countries)
`RESIZE_KEY` the specific key relating to the resizing rules that will be applied to the `SOURCE_IMAGE_PATH`
`SOURCE_IMAGE_PATH` the full S3 path includinf the filename of the source image that should be resized

```
$url = $imageResize->buildUrl(
    'PROJECT',
    'VENTURE',
    'RESIZE_KEY',
    'SOURCE_IMAGE_PATH',
);
```