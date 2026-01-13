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

The package uses a singleton pattern for the main `Labelary` service (src/Services/Labelary.php:28-35). This service:
- Maintains instance state for label dimensions (width, height), print density (dpmm), label index, and optional API key
- Provides static methods `convert()`, `convertToPng()`, and `convertToPdf()` that work through the singleton
- Makes HTTP POST requests to the Labelary API endpoint (http://api.labelary.com/v1/printers/)
- Supports both authenticated (with API key) and unauthenticated requests for ZPL conversion
- API key can be passed explicitly or read from config

### Configuration Classes

Three constant-only classes define valid API parameters:
- `LabelaryType`: Defines output MIME types (PNG or PDF) for ZPL conversion
- `LabelaryDensity`: Defines valid print densities (6dpmm, 8dpmm, 12dpmm, 24dpmm) for ZPL conversion
- `BarcodeType`: Defines supported barcode types (code128, code39, ean13, ean8, upca, upce, qr, datamatrix)

### Laravel Integration

The package auto-registers via Laravel's package discovery:
- ServiceProvider (src/Providers/LabelaryServiceProvider.php) binds `'labelary'` to the service container
- Facade (src/Facades/Labelary.php) provides static access via `\Labelary::convert()`

### API URL Structure

The Labelary API URL format is: `{dpmm}/labels/{width}x{height}/{index?}/`

Example: `8dpmm/labels/4x6/0/` for 8dpmm density, 4x6 inch label, first label (index 0)

### Multi-Label Support

The `index` parameter (base-0) allows accessing specific labels when ZPL generates multiple labels. When requesting PDFs without an index, all labels are returned (one per page).

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
- Endpoint: `https://api.labelary.com/v1/barcodes`
- Method: GET with query parameters
- Authentication: Requires API key (configured in config/labelary.php)
- Returns: PNG image data

```php
$barcode = Labelary::generateBarcode('12345678', BarcodeType::CODE128);
// Or with explicit API key:
$barcode = Labelary::generateBarcode('12345678', BarcodeType::QR, 'your-api-key');
```

The barcode method returns null if the API key is not configured or if the request fails.

## Test Resources

Tests use sample ZPL files in tests/resources/:
- label.zpl: Source ZPL code
- label.png and label.pdf: Expected output files

Tests verify API calls return non-null responses for all conversion methods.

Barcode tests (tests/Feature/BarcodeTest.php) are conditionally skipped if no API key is configured, allowing the test suite to run without API credentials.
