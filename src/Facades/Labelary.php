<?php

namespace SmartDato\Labelary\Facades;

use Illuminate\Support\Facades\Facade;

class Labelary extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'labelary';
    }
}
