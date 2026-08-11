<?php

namespace SmartDato\Labelary\Services;

/**
 * The output formats supported by the label API, sent as an Accept request header.
 *
 * @link https://labelary.com/service.html#intro
 */
class LabelaryType
{
    public const PNG = 'image/png';

    public const PDF = 'application/pdf';

    /**
     * Data extraction: a list of output labels and their data fields.
     *
     * @link https://labelary.com/service.html#data-extraction
     */
    public const JSON = 'application/json';

    /**
     * ZPL transformation: auto-formatting, rotation and print density conversion.
     *
     * @link https://labelary.com/service.html#transformation
     */
    public const ZPL = 'application/zpl';

    public const IPL = 'application/ipl';

    public const EPL = 'application/epl';

    public const DPL = 'application/dpl';

    public const SBPL = 'application/sbpl';

    public const PCL5 = 'application/pcl5';

    public const PCL6 = 'application/pcl6';
}
