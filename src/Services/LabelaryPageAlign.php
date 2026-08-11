<?php

namespace SmartDato\Labelary\Services;

/**
 * The horizontal (X-Page-Align) and vertical (X-Page-Vertical-Align) alignment
 * of the labels on a PDF page. Both default to Justify, which distributes the
 * extra whitespace evenly across the page.
 *
 * @link https://labelary.com/service.html#layout
 */
class LabelaryPageAlign
{
    public const LEFT = 'Left';

    public const RIGHT = 'Right';

    public const TOP = 'Top';

    public const BOTTOM = 'Bottom';

    public const CENTER = 'Center';

    public const JUSTIFY = 'Justify';
}
