<?php

use SmartDato\Labelary\Services\LabelaryDensity;

test('can access properties', function () {
    $density = LabelaryDensity::class;
    expect($density::dpmm6)->toBe('6dpmm')
        ->and($density::dpmm8)->toBe('8dpmm')
        ->and($density::dpmm12)->toBe('12dpmm')
        ->and($density::dpmm24)->toBe('24dpmm');
});
