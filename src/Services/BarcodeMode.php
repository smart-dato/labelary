<?php

namespace SmartDato\Labelary\Services;

/**
 * The way the barcode data is provided (mode).
 *
 * Standard encodes the data literally. Escaped allows ~UHHHH escape sequences
 * and ~FNC1 elements. GS1 allows square brackets to indicate GS1 application
 * identifiers, e.g. [01]12345678901234.
 *
 * @link https://labelary.com/barcodes/api.html#modes
 */
class BarcodeMode
{
    public const STANDARD = 'standard';

    public const ESCAPED = 'escaped';

    public const GS1 = 'gs1';
}
