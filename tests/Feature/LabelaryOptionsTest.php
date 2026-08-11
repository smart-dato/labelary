<?php

use SmartDato\Labelary\Services\Labelary;
use SmartDato\Labelary\Services\LabelaryBorder;
use SmartDato\Labelary\Services\LabelaryDensity;
use SmartDato\Labelary\Services\LabelaryPageAlign;
use SmartDato\Labelary\Services\LabelaryPageOrientation;
use SmartDato\Labelary\Services\LabelaryPageSize;
use SmartDato\Labelary\Services\LabelaryQuality;
use SmartDato\Labelary\Services\LabelaryRotation;
use SmartDato\Labelary\Services\LabelaryType;

function labelaryHeaders(Labelary $labelary, string $type): array
{
    $method = new ReflectionMethod($labelary, 'requestHeaders');
    $method->setAccessible(true);

    return $method->invoke($labelary, $type);
}

function freshLabelary(): Labelary
{
    return new Labelary();
}

afterEach(function () {
    Labelary::getInstance()
        ->setRotation(null)
        ->setPageSize(null)
        ->setPageOrientation(null)
        ->setPageLayout(null)
        ->setPageAlign(null)
        ->setPageVerticalAlign(null)
        ->setLabelBorder(null)
        ->setQuality(null)
        ->setLinter(null)
        ->setFormatter(null)
        ->setTargetDpmm(null);
});

test('sends no advanced headers by default', function () {
    expect(labelaryHeaders(freshLabelary(), LabelaryType::PNG))->toBe([]);
});

test('sends rotation and linter for every output type', function () {
    $labelary = freshLabelary()
        ->setRotation(LabelaryRotation::DEGREES_90)
        ->setLinter(true);

    expect(labelaryHeaders($labelary, LabelaryType::PNG))->toBe(['X-Rotation' => '90', 'X-Linter' => 'On'])
        ->and(labelaryHeaders($labelary, LabelaryType::PDF))->toBe(['X-Rotation' => '90', 'X-Linter' => 'On']);
});

test('sends page headers for pdf requests only', function () {
    $labelary = freshLabelary()
        ->setPageSize(LabelaryPageSize::A4)
        ->setPageOrientation(LabelaryPageOrientation::LANDSCAPE)
        ->setPageLayout('2x3')
        ->setPageAlign(LabelaryPageAlign::CENTER)
        ->setPageVerticalAlign(LabelaryPageAlign::TOP)
        ->setLabelBorder(LabelaryBorder::SOLID);

    expect(labelaryHeaders($labelary, LabelaryType::PDF))->toBe([
        'X-Page-Size' => 'A4',
        'X-Page-Orientation' => 'Landscape',
        'X-Page-Layout' => '2x3',
        'X-Page-Align' => 'Center',
        'X-Page-Vertical-Align' => 'Top',
        'X-Label-Border' => 'Solid',
    ])->and(labelaryHeaders($labelary, LabelaryType::PNG))->toBe([]);
});

test('sends quality for png requests only', function () {
    $labelary = freshLabelary()->setQuality(LabelaryQuality::BITONAL);

    expect(labelaryHeaders($labelary, LabelaryType::PNG))->toBe(['X-Quality' => 'Bitonal'])
        ->and(labelaryHeaders($labelary, LabelaryType::PDF))->toBe([]);
});

test('sends transformation headers for zpl requests only', function () {
    $labelary = freshLabelary()
        ->setFormatter(true)
        ->setTargetDpmm(LabelaryDensity::dpmm12);

    expect(labelaryHeaders($labelary, LabelaryType::ZPL))->toBe(['X-Formatter' => 'On', 'X-Target-Dpmm' => '12'])
        ->and(labelaryHeaders($labelary, LabelaryType::PNG))->toBe([]);
});

test('accepts the target density as a plain number', function () {
    expect(labelaryHeaders(freshLabelary()->setTargetDpmm(24), LabelaryType::ZPL))->toBe(['X-Target-Dpmm' => '24']);
});

test('can disable the linter and the formatter', function () {
    $labelary = freshLabelary()->setLinter(false)->setFormatter(false);

    expect(labelaryHeaders($labelary, LabelaryType::ZPL))->toBe(['X-Linter' => 'Off', 'X-Formatter' => 'Off']);
});

test('parses the warnings response header', function () {
    $header = '303|1|^GB|2|Value 1 is less than minimum value 3; used 3 instead|591|3|||Ignored unrecognized content';

    expect(Labelary::parseWarnings($header))->toBe([
        [
            'index' => 303,
            'size' => 1,
            'command' => '^GB',
            'parameter' => 2,
            'message' => 'Value 1 is less than minimum value 3; used 3 instead',
        ],
        [
            'index' => 591,
            'size' => 3,
            'command' => '',
            'parameter' => null,
            'message' => 'Ignored unrecognized content',
        ],
    ]);
});

test('parses an empty warnings response header', function () {
    expect(Labelary::parseWarnings(''))->toBe([]);
});

test('reports how many labels the zpl generated', function () {
    $zpl = file_get_contents('./tests/resources/label.zpl');

    Labelary::convertToPng($zpl);

    expect(Labelary::getInstance()->totalCount())->toBe(1);
});

test('can lint zpl while rendering it', function () {
    Labelary::getInstance()->setLinter(true);

    Labelary::convertToPng('^XA^FO50,50^GB0,0,0^FS^XZ');

    expect(Labelary::getInstance()->warnings())->not()->toBeEmpty()
        ->and(Labelary::getInstance()->warnings()[0])->toHaveKeys(['index', 'size', 'command', 'parameter', 'message']);
});

test('can extract label data as json', function () {
    $json = Labelary::convertToJson("^XA\n^FT50,50^A0,50^FDField 1^FS\n^XZ");

    expect($json)->not()->toBeNull()
        ->and(json_decode((string) $json, true))->toMatchArray([
            'labels' => [
                ['fields' => [['x' => 50, 'y' => 50, 'data' => 'Field 1']]],
            ],
        ]);
});

test('can transform zpl into zpl', function () {
    Labelary::getInstance()->setFormatter(true);

    $transformed = Labelary::transformZpl("^XA ^FO  50,   50 ^A 0,  50 ^FD Field 1 ^FS    ^XZ");

    expect($transformed)->not()->toBeNull()
        ->and($transformed)->toContain('^XA');
});
