# Labelary Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-dato/labelary.svg?style=flat-square)](https://packagist.org/packages/smart-dato/labelary)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/smart-dato/labelary/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smart-dato/labelary/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/smart-dato/labelary/code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/smart-dato/labelary/actions?query=workflow%3A%22Code+style%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-dato/labelary.svg?style=flat-square)](https://packagist.org/packages/smart-dato/labelary)

A Laravel client for the [Labelary](https://labelary.com/) API: render ZPL to PNG or PDF, lint ZPL, and generate barcodes. Labelary offers a lot more — check it out.

## Configuration

```bash
php artisan vendor:publish --tag=labelary-config
```

```dotenv
# Optional: the private API hostname of a premium or on-premise plan
LABELARY_API_HOST=api.labelary.com

# Optional: required for barcodes, otherwise the images are watermarked
LABELARY_API_KEY=your-api-key-here
```

Labelary's free plan needs neither of these. [Premium plans](https://labelary.com/service.html#pricing) come with a private API hostname and an API key, both of which are delivered via email upon sign-up — set them and everything else stays the same. Hostnames without a scheme are called over HTTPS.

## Usage

```php
use SmartDato\Labelary\Services\BarcodeType;
use SmartDato\Labelary\Services\Labelary;

$png = Labelary::convertToPng($zpl);
$pdf = Labelary::convertToPdf($zpl);

$barcode = Labelary::generateBarcode('12345678', BarcodeType::CODE128);
```

The API key and host fall back to the config, and can also be passed per call or set on the instance:

```php
$png = Labelary::convertToPng($zpl, 'your-api-key', 'your-private-host.labelary.com');

Labelary::getInstance()
    ->setHost('your-private-host.labelary.com')
    ->setApiKey('your-api-key')
    ->setWidth(4)
    ->setHeight(6);
```

### Output formats

Besides PNG and PDF, the API converts ZPL into `LabelaryType::JSON` (data extraction), `LabelaryType::ZPL` (transformation), `IPL`, `EPL`, `DPL`, `SBPL`, `PCL5` and `PCL6`:

```php
$json = Labelary::convertToJson($zpl);
$formatted = Labelary::transformZpl($zpl);
$epl = Labelary::convert($zpl, LabelaryType::EPL);
```

### Conversion options

The advanced request headers are set on the instance and only sent for the output formats they apply to:

```php
Labelary::getInstance()
    ->setRotation(LabelaryRotation::DEGREES_90)   // images and ZPL transformation
    ->setQuality(LabelaryQuality::BITONAL)        // PNG only
    ->setPageSize(LabelaryPageSize::A4)           // the remaining page options are PDF only
    ->setPageOrientation(LabelaryPageOrientation::LANDSCAPE)
    ->setPageLayout('2x3')                        // <columns>x<rows>
    ->setPageAlign(LabelaryPageAlign::CENTER)
    ->setPageVerticalAlign(LabelaryPageAlign::TOP)
    ->setLabelBorder(LabelaryBorder::SOLID)
    ->setFormatter(true)                          // ZPL transformation only
    ->setTargetDpmm(LabelaryDensity::dpmm12);     // ZPL transformation only
```

The instance keeps these options, so they apply to every following conversion until you reset them.

### Label count and linting

`^XA...^XZ` blocks can define multiple labels, and the linter reports potential errors in your ZPL:

```php
Labelary::getInstance()->setLinter(true);

$pdf = Labelary::convertToPdf($zpl);

Labelary::getInstance()->totalCount();  // 3
Labelary::getInstance()->warnings();    // [['index' => 303, 'size' => 1, 'command' => '^GB', 'parameter' => 2, 'message' => '...']]
```

### Barcodes

All [symbologies](https://labelary.com/barcodes/api.html#symbologies) are available as `BarcodeType` constants, and the remaining barcode parameters can be passed as options:

```php
$barcode = Labelary::generateBarcode('[01]12345678901234', BarcodeType::CODE128, null, null, [
    BarcodeOption::MODE => BarcodeMode::GS1,
    BarcodeOption::XDIM => 3,
    BarcodeOption::TEXT_POSITION => BarcodeTextPosition::ABOVE,
    BarcodeOption::FONT => BarcodeFont::OCRB,
]);
```

Unknown option keys are passed through, so parameters that Labelary adds later can be used right away.

## Testing

```bash
composer test
```

The suite replays recorded Labelary responses, so it runs offline. To re-record them against the live API:

```bash
LABELARY_RECORD=1 vendor/bin/pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [SmartDato](https://github.com/smart-dato)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
