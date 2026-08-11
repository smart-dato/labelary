<?php

namespace SmartDato\Labelary\Services;

/**
 * Supported barcode types (symbologies) for the Labelary barcode API
 *
 * @link https://labelary.com/barcodes/api.html#symbologies
 */
class BarcodeType
{
    public const AZTEC = 'aztec';

    public const AZTEC_COMPACT = 'aztecc';

    public const AZTEC_RUNE = 'rune';

    public const CODABAR = 'codabar';

    public const CODE11 = 'code11';

    public const CODE128 = 'code128';

    public const CODE39 = 'code39';

    public const CODE39_EXTENDED = 'code39e';

    public const CODE93 = 'code93';

    public const PDF417_COMPACT = 'pdf417c';

    public const DATAMATRIX = 'datamatrix';

    public const EAN13 = 'ean13';

    public const EAN8 = 'ean8';

    public const IATA25 = 'iata25';

    public const INDUSTRIAL25 = 'industrial25';

    public const INTERLEAVED25 = 'interleaved25';

    public const ITF14 = 'itf14';

    public const MATRIX25 = 'matrix25';

    public const MAXICODE = 'maxicode';

    public const PDF417_MICRO = 'pdf417m';

    public const MICRO_QR = 'microqr';

    public const MSI = 'msi';

    public const PDF417 = 'pdf417';

    public const PHARMACODE = 'pharmacode';

    public const PHARMACODE_TWO_TRACK = 'pharmacode2';

    public const PZN8 = 'pzn8';

    public const QR = 'qr';

    public const SWISS_QR = 'swissqr';

    public const SSCC18 = 'sscc18';

    public const TELEPEN = 'telepen';

    public const TELEPEN_NUMERIC = 'telepenn';

    public const UPCA = 'upca';

    public const UPCE = 'upce';

    public const UPN_QR = 'upnqr';
}
