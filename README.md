# Labelary Client

this repo contains the code for a simple laravel client to convert ZPL files into png/jpg

To do that we use the site [Labelary](http://labelary.com/). We wrote a wrapper for Laravel to interact with their API. There site offers a lot more please check it out.

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
