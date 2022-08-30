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

    private string $dpmm;

    private int $width;

    private int $height;

    private int $index;

    private static ?Labelary $instance = null;

    public static function getInstance(): Labelary
    {
        if (! self::$instance) {
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
    public function __construct(int $width = 4, int $height = 6, int $index = 0, string $dpmm = null)
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
     */
    public static function convert(string $zpl, string $type = null): ?string
    {
        $instance = self::getInstance();

        $url = "{$instance->dpmm}/labels/{$instance->width}x{$instance->height}/{$instance->index}/";

        return self::getInstance()->request($url, $zpl, $type ?? LabelaryType::PNG);
    }

    /**
     * @param string $zpl
     * @return string|null
     */
    public static function convertToPdf(string $zpl): ?string
    {
        return self::getInstance()->convert($zpl, LabelaryType::PDF);
    }

    /**
     * @param string $zpl
     * @return string|null
     */
    public static function convertToPng(string $zpl): ?string
    {
        return self::getInstance()->convert($zpl, LabelaryType::PNG);
    }

    /**
     * @param string $url
     * @param string $zpl
     * @param string $type
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $url, string $zpl, string $type): ?string
    {
        $client = new Client(['base_uri' => self::BASE_URL]);
        try {
            $response = $client->request('POST', $url, [
                'headers' => ['Accept' => $type],
                'body' => $zpl,
            ]);

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            Log::error($e);
        }

        return null;
    }

    /**
     * @param int $width
     * @return Labelary
     */
    public function setWidth(int $width): Labelary
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @param string $dpmm
     * @return Labelary
     */
    public function setDpmm(string $dpmm): Labelary
    {
        $this->dpmm = $dpmm;

        return $this;
    }

    /**
     * @param int $height
     * @return Labelary
     */
    public function setHeight(int $height): Labelary
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @param int $index
     * @return Labelary
     */
    public function setIndex(int $index): Labelary
    {
        $this->index = $index;

        return $this;
    }
}
