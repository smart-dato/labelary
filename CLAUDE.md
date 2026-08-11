# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Labelary is a Laravel package that provides an HTTP client for:
1. Converting ZPL (Zebra Programming Language) code into PNG or PDF images
2. Generating barcodes in various formats (CODE128, QR, etc.)

Both features use the Labelary API (http://labelary.com/).

## Configuration

### Publishing Config
```bash
php artisan vendor:publish --tag=labelary-config
```

### API Key Setup
Set the `LABELARY_API_KEY` environment variable in your `.env` file:
```
LABELARY_API_KEY=your-api-key-here
```

The API key is required for barcode generation features. ZPL to image conversion can work without an API key, but optionally supports authenticated requests if an API key is provided.

### API Host Setup (Plans and Pricing)
Labelary's free plan uses the shared host `api.labelary.com` and requires no sign-up. Premium (Plus/Business) and On-Prem plans get a private API hostname plus an API key via email; switching plans means pointing the client at that hostname and sending the key (https://labelary.com/service.html#pricing).

Set the host via the `LABELARY_API_HOST` environment variable:
```
LABELARY_API_HOST=your-private-host.labelary.com
```

A bare hostname (optionally with a port) is called over HTTPS. Prefix it with a scheme to override that, e.g. `http://labelary.local:8080` for an on-premise server. The host can also be passed per call or set on the instance:

```php
$png = Labelary::convertToPng($zpl, 'your-api-key', 'your-private-host.labelary.com');
$barcode = Labelary::generateBarcode('12345678', BarcodeType::QR, 'your-api-key', 'your-private-host.labelary.com');

Labelary::getInstance()->setHost('your-private-host.labelary.com');
```

`Labelary::baseUrl()` and `Labelary::barcodeUrl()` return the resolved endpoint URLs (explicit host → `config('labelary.host')` → `api.labelary.com`).

## Common Commands

### Testing
```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage
```

### Code Quality
```bash
# Run static analysis (PHPStan level 9)
composer analyse

# Format code (Laravel Pint with PSR-12 preset)
composer format
```

### Development
```bash
# Install dependencies
composer install

# Build the package
composer build

# Start development server
composer start
```

## Architecture

### Core Service Pattern

The package uses a singleton pattern for the main `Labelary` service (src/Services/Labelary.php). This service:
- Maintains instance state for label dimensions (width, height), print density (dpmm), label index, optional API key, and optional API host
- Provides static methods `convert()`, `convertToPng()`, and `convertToPdf()` that work through the singleton
- Makes HTTP POST requests to the Labelary API endpoint (https://api.labelary.com/v1/printers/ by default)
- Supports both authenticated (with API key) and unauthenticated requests for ZPL conversion
- API key can be passed explicitly or read from config

### Configuration Classes

Constant-only classes define the valid API parameters:
- `LabelaryType`: Output MIME types sent as the Accept header (PNG, PDF, JSON, ZPL, IPL, EPL, DPL, SBPL, PCL5, PCL6)
- `LabelaryDensity`: Print densities (6dpmm, 8dpmm, 12dpmm, 24dpmm)
- `LabelaryRotation`, `LabelaryPageSize`, `LabelaryPageOrientation`, `LabelaryPageAlign`, `LabelaryBorder`, `LabelaryQuality`: Values for the advanced request headers
- `BarcodeType`: All barcode symbologies supported by the barcode API
- `BarcodeOption`: Barcode query parameter names, with `BarcodeMode`, `BarcodeTextPosition` and `BarcodeFont` for their values

### Advanced Request Headers

Conversion options are set on the instance (`setRotation()`, `setQuality()`, `setPageSize()`, `setPageOrientation()`, `setPageLayout()`, `setPageAlign()`, `setPageVerticalAlign()`, `setLabelBorder()`, `setLinter()`, `setFormatter()`, `setTargetDpmm()`) and translated into `X-*` headers by `requestHeaders()`.

Headers are filtered by output type, because the API rejects headers that do not apply: page and border headers are PDF only, `X-Quality` is PNG only, `X-Formatter` and `X-Target-Dpmm` are ZPL only, while `X-Rotation` and `X-Linter` are sent for every type.

The response headers of the last conversion are exposed through `totalCount()` (`X-Total-Count`) and `warnings()` (`X-Warnings`, parsed by the static `parseWarnings()` into 5 attributes per warning).

### Laravel Integration

The package auto-registers via Laravel's package discovery:
- ServiceProvider (src/Providers/LabelaryServiceProvider.php) binds `'labelary'` as a singleton resolving to `Labelary::getInstance()`, so the facade and the static methods share one instance and options set through the facade survive until the next conversion
- Facade (src/Facades/Labelary.php) provides static access via `\Labelary::convert()`

### API URL Structure

The Labelary API URL format is: `{dpmm}/labels/{width}x{height}/{index?}/`

Example: `8dpmm/labels/4x6/0/` for 8dpmm density, 4x6 inch label, first label (index 0)

### Multi-Label Support

The `index` parameter (base-0) allows accessing specific labels when ZPL generates multiple labels. The index may only be omitted for PDF requests, which then return all labels (one per page); image requests without an explicit index default to index 0.

### API Key Support

Both ZPL conversion and barcode generation support optional API keys:

**ZPL Conversion:**
```php
// Without API key (unauthenticated)
$png = Labelary::convertToPng($zplCode);

// With explicit API key
$png = Labelary::convertToPng($zplCode, 'your-api-key');

// With API key from config
$png = Labelary::convertToPng($zplCode); // Uses config('labelary.api_key') if available

// Using convert() method
$pdf = Labelary::convert($zplCode, LabelaryType::PDF, 'your-api-key');
```

The API key is added as a query parameter (`?key=...`) to the POST request when provided.

**Barcode Generation:**
- Endpoint: `https://api.labelary.com/v1/barcodes` (host configurable, see API Host Setup)
- Method: GET with query parameters
- Authentication: Requires API key (configured in config/labelary.php)
- Returns: PNG image data

```php
$barcode = Labelary::generateBarcode('12345678', BarcodeType::CODE128);
// Or with explicit API key:
$barcode = Labelary::generateBarcode('12345678', BarcodeType::QR, 'your-api-key');
// Or with additional barcode parameters:
$barcode = Labelary::generateBarcode('12345678', BarcodeType::QR, null, null, [
    BarcodeOption::XDIM => 3,
    BarcodeOption::TEXT_POSITION => BarcodeTextPosition::NONE,
]);
```

The barcode method returns null if the API key is not configured or if the request fails. Option values that are null are dropped, and unknown option keys are passed through so new API parameters can be used before they are added to `BarcodeOption`.

## Test Resources

Tests use sample ZPL files in tests/resources/:
- label.zpl: Source ZPL code
- label.png and label.pdf: Expected output files

Tests verify API calls return non-null responses for all conversion methods.

Barcode tests (tests/Feature/BarcodeTest.php) are conditionally skipped if no API key is configured, allowing the test suite to run without API credentials.
