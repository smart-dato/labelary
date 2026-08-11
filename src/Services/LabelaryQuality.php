<?php

namespace SmartDato\Labelary\Services;

/**
 * The print quality of generated images (X-Quality).
 *
 * Grayscale generates 8-bit grayscale images for electronic viewing and
 * high-density printers, and is the default. Bitonal generates smaller 1-bit
 * monochrome images for low-density printers.
 *
 * @link https://labelary.com/service.html#quality
 */
class LabelaryQuality
{
    public const GRAYSCALE = 'Grayscale';

    public const BITONAL = 'Bitonal';
}
