<?php

use SmartDato\Labelary\Services\LabelaryType;

test('can access properties', function () {
    expect(LabelaryType::PNG)->toBe('image/png')
        ->and(LabelaryType::PDF)->toBe('application/pdf');
});
