<?php

use SmartDato\Labelary\Services\Labelary;
use SmartDato\Labelary\Tests\Support\Cassette;

require __DIR__.'/../vendor/autoload.php';

// Replay the Labelary API from tests/fixtures/http instead of calling it.
// Re-record with: LABELARY_RECORD=1 vendor/bin/pest
Labelary::useHandler(Cassette::handler());
