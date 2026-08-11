<?php

namespace SmartDato\Labelary\Services;

/**
 * The query string parameters of the barcode API, beyond key, type and data.
 * Unknown options are passed through, so parameters added by Labelary can be
 * used before they are listed here.
 *
 * @link https://labelary.com/barcodes/api.html#parameters
 */
class BarcodeOption
{
    /** The data mode used to provide the barcode data, see BarcodeMode. */
    public const MODE = 'mode';

    /** The module width (bar width), in pixels (1-10). */
    public const XDIM = 'xdim';

    /** The module height (bar height), in pixels (1-500), for linear and stacked barcodes. */
    public const YDIM = 'ydim';

    /** The horizontal quiet zone added to each side of the barcode (0-200). */
    public const QUIET_ZONE_HORIZONTAL = 'qzh';

    /** The vertical quiet zone added above and below the barcode (0-200). */
    public const QUIET_ZONE_VERTICAL = 'qzv';

    /** The number of degrees to rotate the barcode clockwise (0, 90, 180 or 270). */
    public const ROTATION = 'rot';

    /** The foreground color used to draw the barcode, in RRGGBB hex format. */
    public const FOREGROUND = 'fg';

    /** The background color over which the barcode is drawn, in RRGGBB hex format. */
    public const BACKGROUND = 'bg';

    /** The position of the human-readable text, see BarcodeTextPosition. */
    public const TEXT_POSITION = 'hrt';

    /** The font used for the human-readable text, see BarcodeFont. */
    public const FONT = 'fn';

    /** The size of the font used for the human-readable text (4-40). */
    public const FONT_SIZE = 'fs';

    /** The wide-to-narrow bar width ratio (2-3), for barcodes with wide and narrow bars. */
    public const RATIO = 'ratio';

    /** The number of check digits to add, for barcodes with customizable check digits. */
    public const CHECK_DIGITS = 'check';

    /** The error correction level, for barcodes with customizable error correction. */
    public const ERROR_CORRECTION = 'ec';

    /** The barcode dimensions, for barcodes with rows and columns. */
    public const SIZE = 'size';

    /** The structured append position, for barcodes which support data splitting. */
    public const APPEND_POSITION = 'sap';

    /** The structured append total, for barcodes which support data splitting. */
    public const APPEND_TOTAL = 'sat';

    /** The structured append file ID, for barcodes which support data splitting. */
    public const APPEND_ID = 'said';

    /** The structured append file name, for barcodes which support data splitting. */
    public const APPEND_NAME = 'san';

    /** The mode to use for maxicode barcodes (2-6). */
    public const MAXICODE_MODE = 'mcm';

    /** The guard pattern height (0-20) for ean13, ean8, upca and upce barcodes. */
    public const GUARD_PATTERN_HEIGHT = 'gph';
}
