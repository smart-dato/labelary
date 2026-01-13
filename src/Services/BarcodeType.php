<?php

namespace SmartDato\Labelary\Services;

/**
 * Supported barcode types for Labelary barcode API
 *
 * @link http://labelary.com/service.html
 */
class BarcodeType
{
    public const CODE128 = 'code128';

    public const CODE39 = 'code39';

    public const EAN13 = 'ean13';

    public const EAN8 = 'ean8';

    public const UPCA = 'upca';

    public const UPCE = 'upce';

    public const QR = 'qr';

    public const DATAMATRIX = 'datamatrix';
}
