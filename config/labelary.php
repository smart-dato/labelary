<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Labelary API Host
    |--------------------------------------------------------------------------
    |
    | The hostname of the Labelary API server. The free plan uses the shared
    | host "api.labelary.com". Premium (Plus/Business) and On-Prem plans get
    | their own private hostname, which is provided via email upon sign-up.
    |
    | A bare hostname (optionally with a port) is served over HTTPS. Prefix
    | the value with a scheme to override that, e.g. "http://labelary.local".
    |
    | @see https://labelary.com/service.html#pricing
    |
    */

    'host' => env('LABELARY_API_HOST', 'api.labelary.com'),

    /*
    |--------------------------------------------------------------------------
    | Labelary API Key
    |--------------------------------------------------------------------------
    |
    | Your Labelary API key for accessing barcode generation and other
    | authenticated endpoints. The free label plan requires no API key;
    | premium plans receive one via email upon sign-up. Barcode images are
    | watermarked as long as no key is configured.
    |
    */

    'api_key' => env('LABELARY_API_KEY'),

];
