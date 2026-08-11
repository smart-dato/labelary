<?php

namespace SmartDato\Labelary\Services;

/**
 * The border drawn around each label on a PDF page (X-Label-Border).
 * Only applied when the PDF page size is customized.
 *
 * @link https://labelary.com/service.html#border
 */
class LabelaryBorder
{
    public const DASHED = 'Dashed';

    public const SOLID = 'Solid';

    public const NONE = 'None';
}
