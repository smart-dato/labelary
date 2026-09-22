<?php

namespace SmartDato\Labelary\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @link http://labelary.com/service.html
 */
class Labelary
{
    /**
     * A Guzzle handler to route requests through, set by the test suite so the
     * API can be replayed from fixtures instead of called over the network.
     *
     * @var callable|null
     */
    private static $handler = null;

    /**
     * Route every request through the given Guzzle handler. Pass null to
     * restore normal network behaviour.
     */
    public static function useHandler(?callable $handler): void
    {
        self::$handler = $handler;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function newClient(array $config = []): Client
    {
        if (self::$handler !== null) {
            $config['handler'] = HandlerStack::create(self::$handler);
        }

        return new Client($config);
    }

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

    private ?int $rotation = null;

    private ?string $pageSize = null;

    private ?string $pageOrientation = null;

    private ?string $pageLayout = null;

    private ?string $pageAlign = null;

    private ?string $pageVerticalAlign = null;

    private ?string $labelBorder = null;

    private ?string $quality = null;

    private ?bool $linter = null;

    private ?bool $formatter = null;

    private ?int $targetDpmm = null;

    private ?int $totalCount = null;

    /** @var array<int, array{index: int, size: int, command: string, parameter: int|null, message: string}> */
    private array $warnings = [];

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
     * Extract the labels and their data fields as JSON.
     *
     * @param  string  $zpl  The ZPL code to extract data from
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The JSON data
     */
    public static function convertToJson(string $zpl, ?string $apiKey = null, ?string $host = null): ?string
    {
        return self::convert($zpl, LabelaryType::JSON, $apiKey, $host);
    }

    /**
     * Transform ZPL into ZPL, applying the formatter, rotation and target print
     * density that are set on the instance.
     *
     * @param  string  $zpl  The ZPL code to transform
     * @param  string|null  $apiKey  Optional API key for authenticated requests
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @return string|null  The transformed ZPL
     */
    public static function transformZpl(string $zpl, ?string $apiKey = null, ?string $host = null): ?string
    {
        return self::convert($zpl, LabelaryType::ZPL, $apiKey, $host);
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
        $this->totalCount = null;
        $this->warnings = [];

        $client = self::newClient(['base_uri' => self::baseUrl($this->host)]);
        try {
            $options = [
                'headers' => ['Accept' => $type] + $this->requestHeaders($type),
                'body' => $zpl,
            ];

            // Add API key as query parameter if set
            if ($this->apiKey) {
                $options['query'] = ['key' => $this->apiKey];
            }

            $response = $client->request('POST', $url, $options);

            $totalCount = $response->getHeaderLine('X-Total-Count');
            $this->totalCount = $totalCount === '' ? null : (int) $totalCount;
            $this->warnings = self::parseWarnings($response->getHeaderLine('X-Warnings'));

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
     * The number of degrees to rotate the label clockwise, see LabelaryRotation.
     * Applies to images and to ZPL transformation.
     *
     * @param  int|null  $degrees
     * @return Labelary
     */
    public function setRotation(?int $degrees): Labelary
    {
        $this->rotation = $degrees;

        return $this;
    }

    /**
     * The PDF page size, see LabelaryPageSize. Defaults to the label size.
     *
     * @param  string|null  $pageSize
     * @return Labelary
     */
    public function setPageSize(?string $pageSize): Labelary
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * The orientation of the PDF page size, see LabelaryPageOrientation.
     *
     * @param  string|null  $pageOrientation
     * @return Labelary
     */
    public function setPageOrientation(?string $pageOrientation): Labelary
    {
        $this->pageOrientation = $pageOrientation;

        return $this;
    }

    /**
     * The tabular layout of the labels on a PDF page, in "<columns>x<rows>"
     * format, e.g. "2x3" for 6 labels per page.
     *
     * @param  string|null  $pageLayout
     * @return Labelary
     */
    public function setPageLayout(?string $pageLayout): Labelary
    {
        $this->pageLayout = $pageLayout;

        return $this;
    }

    /**
     * The horizontal alignment of the labels on a PDF page, see LabelaryPageAlign.
     *
     * @param  string|null  $pageAlign
     * @return Labelary
     */
    public function setPageAlign(?string $pageAlign): Labelary
    {
        $this->pageAlign = $pageAlign;

        return $this;
    }

    /**
     * The vertical alignment of the labels on a PDF page, see LabelaryPageAlign.
     *
     * @param  string|null  $pageVerticalAlign
     * @return Labelary
     */
    public function setPageVerticalAlign(?string $pageVerticalAlign): Labelary
    {
        $this->pageVerticalAlign = $pageVerticalAlign;

        return $this;
    }

    /**
     * The border drawn around each label on a PDF page, see LabelaryBorder.
     *
     * @param  string|null  $labelBorder
     * @return Labelary
     */
    public function setLabelBorder(?string $labelBorder): Labelary
    {
        $this->labelBorder = $labelBorder;

        return $this;
    }

    /**
     * The print quality of generated images, see LabelaryQuality.
     *
     * @param  string|null  $quality
     * @return Labelary
     */
    public function setQuality(?string $quality): Labelary
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Check the ZPL for potential errors while rendering it. Warnings are
     * available through warnings() once the conversion has run.
     *
     * @param  bool|null  $linter
     * @return Labelary
     */
    public function setLinter(?bool $linter): Labelary
    {
        $this->linter = $linter;

        return $this;
    }

    /**
     * Apply automated formatting to the input ZPL. Only used for ZPL transformation.
     *
     * @param  bool|null  $formatter
     * @return Labelary
     */
    public function setFormatter(?bool $formatter): Labelary
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Convert the input ZPL to another print density, e.g. to print ZPL designed
     * for a 6dpmm printer on an 8dpmm printer. Only used for ZPL transformation.
     * Accepts both 8 and LabelaryDensity::dpmm8.
     *
     * @param  int|string|null  $dpmm
     * @return Labelary
     */
    public function setTargetDpmm(int|string|null $dpmm): Labelary
    {
        $this->targetDpmm = $dpmm === null ? null : (int) $dpmm;

        return $this;
    }

    /**
     * The number of labels generated by the last conversion, regardless of how
     * many of them were rendered. Null if the last conversion failed.
     *
     * @return int|null
     */
    public function totalCount(): ?int
    {
        return $this->totalCount;
    }

    /**
     * The linter warnings of the last conversion, if the linter was enabled.
     * Labelary returns at most 20 warnings.
     *
     * @return array<int, array{index: int, size: int, command: string, parameter: int|null, message: string}>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Parse the pipe-delimited X-Warnings response header, which holds 5
     * attributes per warning.
     *
     * @param  string  $header
     * @return array<int, array{index: int, size: int, command: string, parameter: int|null, message: string}>
     */
    public static function parseWarnings(string $header): array
    {
        if (trim($header) === '') {
            return [];
        }

        $warnings = [];

        foreach (array_chunk(explode('|', $header), 5) as $attributes) {
            if (count($attributes) < 5) {
                continue;
            }

            $warnings[] = [
                'index' => (int) $attributes[0],
                'size' => (int) $attributes[1],
                'command' => $attributes[2],
                'parameter' => $attributes[3] === '' ? null : (int) $attributes[3],
                'message' => $attributes[4],
            ];
        }

        return $warnings;
    }

    /**
     * The advanced request headers that apply to the requested output type.
     *
     * @param  string  $type
     * @return array<string, string>
     */
    private function requestHeaders(string $type): array
    {
        $headers = [];

        if ($this->rotation !== null) {
            $headers['X-Rotation'] = (string) $this->rotation;
        }

        if ($this->linter !== null) {
            $headers['X-Linter'] = $this->linter ? 'On' : 'Off';
        }

        if ($type === LabelaryType::PDF) {
            $headers += array_filter([
                'X-Page-Size' => $this->pageSize,
                'X-Page-Orientation' => $this->pageOrientation,
                'X-Page-Layout' => $this->pageLayout,
                'X-Page-Align' => $this->pageAlign,
                'X-Page-Vertical-Align' => $this->pageVerticalAlign,
                'X-Label-Border' => $this->labelBorder,
            ], fn (?string $value): bool => $value !== null);
        }

        if ($type === LabelaryType::PNG && $this->quality !== null) {
            $headers['X-Quality'] = $this->quality;
        }

        if ($type === LabelaryType::ZPL) {
            if ($this->formatter !== null) {
                $headers['X-Formatter'] = $this->formatter ? 'On' : 'Off';
            }

            if ($this->targetDpmm !== null) {
                $headers['X-Target-Dpmm'] = (string) $this->targetDpmm;
            }
        }

        return $headers;
    }

    /**
     * Generate a barcode using the Labelary barcode API
     *
     * @param  string  $data  The data to encode in the barcode
     * @param  string  $type  The barcode type (use BarcodeType constants)
     * @param  string|null  $apiKey  Optional API key (uses config if not provided)
     * @param  string|null  $host  Optional API host (premium plans use a private hostname)
     * @param  array<string, string|int|float|null>  $options  Additional barcode parameters (use BarcodeOption constants)
     * @return string|null  The barcode image as PNG
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function generateBarcode(string $data, string $type = BarcodeType::CODE128, ?string $apiKey = null, ?string $host = null, array $options = []): ?string
    {
        // Try to get API key from config if not provided
        if (!$apiKey) {
            $apiKey = self::configString('labelary.api_key');
        }

        if (!$apiKey) {
            self::log('Labelary API key not configured');

            return null;
        }

        $client = self::newClient();
        try {
            $query = array_filter($options, fn (string|int|float|null $value): bool => $value !== null);

            $response = $client->request('GET', self::barcodeUrl($host), [
                'query' => array_merge($query, [
                    'key' => $apiKey,
                    'type' => $type,
                    'data' => $data,
                ]),
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
