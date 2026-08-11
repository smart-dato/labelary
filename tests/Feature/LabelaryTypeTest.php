<?php

use SmartDato\Labelary\Services\LabelaryType;

test('can access properties', function () {
    expect(LabelaryType::PNG)->toBe('image/png')
        ->and(LabelaryType::PDF)->toBe('application/pdf');
});

test('can access the other output types', function () {
    expect(LabelaryType::JSON)->toBe('application/json')
        ->and(LabelaryType::ZPL)->toBe('application/zpl')
        ->and(LabelaryType::IPL)->toBe('application/ipl')
        ->and(LabelaryType::EPL)->toBe('application/epl')
        ->and(LabelaryType::DPL)->toBe('application/dpl')
        ->and(LabelaryType::SBPL)->toBe('application/sbpl')
        ->and(LabelaryType::PCL5)->toBe('application/pcl5')
        ->and(LabelaryType::PCL6)->toBe('application/pcl6');
});
