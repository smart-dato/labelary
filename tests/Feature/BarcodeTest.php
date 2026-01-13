<?php

use SmartDato\Labelary\Services\BarcodeType;
use SmartDato\Labelary\Services\Labelary;

test('can generate barcode with explicit api key', function () {
    // Skip if LABELARY_API_KEY is not set in environment
    $apiKey = getenv('LABELARY_API_KEY');
    if (!$apiKey) {
        expect(true)->toBeTrue(); // Skip gracefully
        return;
    }

    $barcode = Labelary::generateBarcode('12345678', BarcodeType::CODE128, $apiKey);

    expect($barcode)->not()->toBeNull();
});

test('returns null when api key is missing', function () {
    $barcode = Labelary::generateBarcode('12345678', BarcodeType::CODE128, null);

    expect($barcode)->toBeNull();
});

test('can generate different barcode types', function () {
    // Skip if LABELARY_API_KEY is not set in environment
    $apiKey = getenv('LABELARY_API_KEY');
    if (!$apiKey) {
        expect(true)->toBeTrue(); // Skip gracefully
        return;
    }

    $qrCode = Labelary::generateBarcode('https://example.com', BarcodeType::QR, $apiKey);
    $code39 = Labelary::generateBarcode('ABC123', BarcodeType::CODE39, $apiKey);

    expect($qrCode)->not()->toBeNull()
        ->and($code39)->not()->toBeNull();
});
