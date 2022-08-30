<?php

use SmartDato\Labelary\Services\Labelary;

test('can convert zpl into png', function () {
    $zpl = file_get_contents('./tests/resources/label.zpl');

    //file_put_contents('./tests/resources/label.png', Labelary::convertToPng($zpl));
    //file_put_contents('./tests/resources/label.pdf', Labelary::convertToPdf($zpl));

    expect(Labelary::convert($zpl))->not()->toBeNull()
        ->and(Labelary::convertToPng($zpl))->not()->toBeNull()
        ->and(Labelary::convertToPdf($zpl))->not()->toBeNull();
});
