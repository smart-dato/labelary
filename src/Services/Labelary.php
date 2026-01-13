<?php

namespace SmartDato\Labelary\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * @link http://labelary.com/service.html
 */
class Labelary
{
    public const BASE_URL = 'http://api.labelary.com/v1/printers/';

    public const BARCODE_URL = 'https://api.labelary.com/v1/barcodes';

    private string $dpmm;

    private int $width;

    private int $height;

    private ?int $index;

    private ?string $apiKey = null;

    private static ?Labelary $instance = null;

    public static function getInstance(): Labelary
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Labelary constructor.
     *
     * $width
     * The label width, in inches. Any numeric value may be used.
     *
     * $height
     * The label height, in inches. Any numeric value may be used.
     *
     * $index
     * The label index (base 0).
     * Some ZPL code will generate multiple labels, and this parameter can be used to access these different labels.
     * In general though, the value of this parameter will be 0 (zero).
     * Note that this parameter is optional when requesting PDF documents. If not specified, the resultant PDF document
     * will contain all labels (one label per page).
     *
     * $dpmm
     * The desired print density, in dots per millimeter.
     * Valid values are "6dpmm", "8dpmm", "12dpmm", and "24dpmm". See your printer's documentation for more information.
     *
     */
    public function __construct(int $width = 4, int $height = 6, ?int $index = null, ?string $dpmm = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->index = $index;

        $this->dpmm = $dpmm ?? LabelaryDensity::dpmm8;
    }

    /**
     * $zpl
     * The ZPL code to render.
     * Note that if you are using the GET method and the ZPL contains any hashes (#), they should be encoded (%23) in
     * order to avoid parts of the ZPL being incorrectly interpreted as URL fragments.
     *
     * @param  string  $zpl  The ZPL code to convert
     * @param  string|null  $type  The output type (PNG or PDF)
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @return string|null  The converted image/PDF data
     */
    public static function convert(string $zpl, ?string $type = null, ?string $apiKey = null): ?string
    {
        $instance = self::getInstance();

        // Set API key if provided
        if ($apiKey) {
            $instance->apiKey = $apiKey;
        } elseif (!$instance->apiKey && function_exists('config')) {
            try {
                $configKey = config('labelary.api_key');
                if (is_string($configKey)) {
                    $instance->apiKey = $configKey;
                }
            } catch (Exception $e) {
                // Config not available
            }
        }

        $url = "{$instance->dpmm}/labels/{$instance->width}x{$instance->height}";
        if ($instance->index) {
            $url .= "/{$instance->index}/";
        }
        return $instance->request($url, $zpl, $type ?? LabelaryType::PNG);
    }

    /**
     * @param  string  $zpl  The ZPL code to convert
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @return string|null  The PDF data
     */
    public static function convertToPdf(string $zpl, ?string $apiKey = null): ?string
    {
        return self::convert($zpl, LabelaryType::PDF, $apiKey);
    }

    /**
     * @param  string  $zpl  The ZPL code to convert
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @return string|null  The PNG data
     */
    public static function convertToPng(string $zpl, ?string $apiKey = null): ?string
    {
        return self::convert($zpl, LabelaryType::PNG, $apiKey);
    }

    /**
     * @param  string  $url
     * @param  string  $zpl
     * @param  string  $type
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $url, string $zpl, string $type): ?string
    {
        $client = new Client(['base_uri' => self::BASE_URL]);
        try {
            $options = [
                'headers' => ['Accept' => $type],
                'body' => $zpl,
            ];

            // Add API key as query parameter if set
            if ($this->apiKey) {
                $options['query'] = ['key' => $this->apiKey];
            }

            $response = $client->request('POST', $url, $options);

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            try {
                if (class_exists('Illuminate\Support\Facades\Log')) {
                    Log::error($e);
                }
            } catch (Exception $logException) {
                // Log not available, continue silently
            }
        }

        return null;
    }

    /**
     * @param  int  $width
     * @return Labelary
     */
    public function setWidth(int $width): Labelary
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @param  string  $dpmm
     * @return Labelary
     */
    public function setDpmm(string $dpmm): Labelary
    {
        $this->dpmm = $dpmm;

        return $this;
    }

    /**
     * @param  int  $height
     * @return Labelary
     */
    public function setHeight(int $height): Labelary
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @param ?int  $index
     * @return Labelary
     */
    public function setIndex(?int $index): Labelary
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @param  string|null  $apiKey
     * @return Labelary
     */
    public function setApiKey(?string $apiKey): Labelary
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Generate a barcode using the Labelary barcode API
     *
     * @param  string  $data  The data to encode in the barcode
     * @param  string  $type  The barcode type (use BarcodeType constants)
     * @param  string|null  $apiKey  Optional API key (uses config if not provided)
     * @return string|null  The barcode image as PNG
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function generateBarcode(string $data, string $type = BarcodeType::CODE128, ?string $apiKey = null): ?string
    {
        // Try to get API key from config if not provided
        if (!$apiKey) {
            try {
                if (function_exists('config')) {
                    $configKey = config('labelary.api_key');
                    if (is_string($configKey)) {
                        $apiKey = $configKey;
                    }
                }
            } catch (Exception $e) {
                // Config not available, will return null below
            }
        }

        if (!$apiKey) {
            try {
                if (class_exists('Illuminate\Support\Facades\Log')) {
                    Log::error('Labelary API key not configured');
                }
            } catch (Exception $e) {
                // Log not available, continue silently
            }
            return null;
        }

        $client = new Client();
        try {
            $response = $client->request('GET', self::BARCODE_URL, [
                'query' => [
                    'key' => $apiKey,
                    'type' => $type,
                    'data' => $data,
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            try {
                if (class_exists('Illuminate\Support\Facades\Log')) {
                    Log::error($e);
                }
            } catch (Exception $logException) {
                // Log not available, continue silently
            }
        }

        return null;
    }
}
