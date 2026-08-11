<?php

namespace SmartDato\Labelary\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @link http://labelary.com/service.html
 */
class Labelary
{
    /**
     * The shared API host used by the free plan. Premium and On-Prem plans
     * receive their own private hostname upon sign-up.
     *
     * @link https://labelary.com/service.html#pricing
     */
    public const DEFAULT_HOST = 'api.labelary.com';

    public const PRINTERS_PATH = '/v1/printers/';

    public const BARCODES_PATH = '/v1/barcodes';

    public const BASE_URL = 'https://'.self::DEFAULT_HOST.self::PRINTERS_PATH;

    public const BARCODE_URL = 'https://'.self::DEFAULT_HOST.self::BARCODES_PATH;

    private string $dpmm;

    private int $width;

    private int $height;

    private ?int $index;

    private ?string $apiKey = null;

    private ?string $host = null;

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
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The converted image/PDF data
     */
    public static function convert(string $zpl, ?string $type = null, ?string $apiKey = null, ?string $host = null): ?string
    {
        $instance = self::getInstance();

        // Set API key if provided
        if ($apiKey) {
            $instance->apiKey = $apiKey;
        } elseif (!$instance->apiKey) {
            $instance->apiKey = self::configString('labelary.api_key');
        }

        // Set API host if provided
        if ($host) {
            $instance->host = $host;
        } elseif (!$instance->host) {
            $instance->host = self::configString('labelary.host');
        }

        $type = $type ?? LabelaryType::PNG;

        // The index may only be omitted for PDF documents, which then contain all labels
        $index = $instance->index ?? ($type === LabelaryType::PDF ? null : 0);

        $url = "{$instance->dpmm}/labels/{$instance->width}x{$instance->height}/";
        if ($index !== null) {
            $url .= "{$index}/";
        }

        return $instance->request($url, $zpl, $type);
    }

    /**
     * @param  string  $zpl  The ZPL code to convert
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The PDF data
     */
    public static function convertToPdf(string $zpl, ?string $apiKey = null, ?string $host = null): ?string
    {
        return self::convert($zpl, LabelaryType::PDF, $apiKey, $host);
    }

    /**
     * @param  string  $zpl  The ZPL code to convert
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The PNG data
     */
    public static function convertToPng(string $zpl, ?string $apiKey = null, ?string $host = null): ?string
    {
        return self::convert($zpl, LabelaryType::PNG, $apiKey, $host);
    }

    /**
     * The base URL of the label conversion endpoint, for the given (or configured) host.
     *
     * @param  string|null  $host
     * @return string
     */
    public static function baseUrl(?string $host = null): string
    {
        return self::resolveHost($host).self::PRINTERS_PATH;
    }

    /**
     * The URL of the barcode endpoint, for the given (or configured) host.
     *
     * @param  string|null  $host
     * @return string
     */
    public static function barcodeUrl(?string $host = null): string
    {
        return self::resolveHost($host).self::BARCODES_PATH;
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
        $client = new Client(['base_uri' => self::baseUrl($this->host)]);
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
            self::log($e);
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
     * The API host to send requests to. Free plans use the shared host, while
     * premium plans receive a private hostname upon sign-up. Bare hostnames
     * are served over HTTPS unless a scheme is included.
     *
     * @param  string|null  $host
     * @return Labelary
     */
    public function setHost(?string $host): Labelary
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Generate a barcode using the Labelary barcode API
     *
     * @param  string  $data  The data to encode in the barcode
     * @param  string  $type  The barcode type (use BarcodeType constants)
     * @param  string|null  $apiKey  Optional API key (uses config if not provided)
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The barcode image as PNG
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function generateBarcode(string $data, string $type = BarcodeType::CODE128, ?string $apiKey = null, ?string $host = null): ?string
    {
        // Try to get API key from config if not provided
        if (!$apiKey) {
            $apiKey = self::configString('labelary.api_key');
        }

        if (!$apiKey) {
            self::log('Labelary API key not configured');

            return null;
        }

        $client = new Client();
        try {
            $response = $client->request('GET', self::barcodeUrl($host), [
                'query' => [
                    'key' => $apiKey,
                    'type' => $type,
                    'data' => $data,
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            self::log($e);
        }

        return null;
    }

    /**
     * Resolve the host to send requests to, falling back to the configured
     * host and finally to the shared host used by the free plan.
     *
     * @param  string|null  $host
     * @return string
     */
    private static function resolveHost(?string $host = null): string
    {
        $host = $host ?? self::configString('labelary.host') ?? self::DEFAULT_HOST;

        $host = rtrim(trim($host), '/');

        if ($host === '') {
            $host = self::DEFAULT_HOST;
        }

        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $host)) {
            $host = "https://{$host}";
        }

        return $host;
    }

    /**
     * @param  string  $key
     * @return string|null
     */
    private static function configString(string $key): ?string
    {
        if (!function_exists('config')) {
            return null;
        }

        try {
            $value = config($key);
        } catch (Throwable $e) {
            // Config not available outside of a Laravel application
            return null;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param  string|Throwable  $message
     * @return void
     */
    private static function log(string|Throwable $message): void
    {
        try {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                Log::error($message);
            }
        } catch (Throwable $e) {
            // Log not available, continue silently
        }
    }
}
