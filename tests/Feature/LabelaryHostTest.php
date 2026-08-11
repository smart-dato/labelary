<?php

use SmartDato\Labelary\Services\Labelary;

test('falls back to the shared host of the free plan', function () {
    expect(Labelary::baseUrl())->toBe('https://api.labelary.com/v1/printers/')
        ->and(Labelary::barcodeUrl())->toBe('https://api.labelary.com/v1/barcodes');
});

test('can use the private host of a premium plan', function () {
    expect(Labelary::baseUrl('acme.labelary.com'))->toBe('https://acme.labelary.com/v1/printers/')
        ->and(Labelary::barcodeUrl('acme.labelary.com'))->toBe('https://acme.labelary.com/v1/barcodes');
});

test('keeps an explicit scheme and port', function () {
    expect(Labelary::baseUrl('http://labelary.local:8080'))->toBe('http://labelary.local:8080/v1/printers/')
        ->and(Labelary::barcodeUrl('acme.labelary.com:8443'))->toBe('https://acme.labelary.com:8443/v1/barcodes');
});

test('ignores trailing slashes and surrounding whitespace', function () {
    expect(Labelary::baseUrl(' https://acme.labelary.com/ '))->toBe('https://acme.labelary.com/v1/printers/');
});

test('falls back to the shared host for an empty host', function () {
    expect(Labelary::baseUrl(''))->toBe('https://api.labelary.com/v1/printers/');
});
