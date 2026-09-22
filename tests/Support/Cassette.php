<?php

namespace SmartDato\Labelary\Tests\Support;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Replays the Labelary API from recorded responses so the suite needs no network.
 *
 * Re-record after changing a request, or when the API's output changes:
 *
 *     LABELARY_RECORD=1 vendor/bin/pest
 */
final class Cassette
{
    /** Response headers the SDK actually reads. */
    private const KEPT_RESPONSE_HEADERS = ['Content-Type', 'X-Total-Count', 'X-Warnings'];

    public static function handler(): callable
    {
        return static function (RequestInterface $request, array $options) {
            $path = self::path($request);

            if (getenv('LABELARY_RECORD') === '1') {
                return (Utils::chooseHandler())($request, $options)
                    ->then(static function (ResponseInterface $response) use ($path): ResponseInterface {
                        self::write($path, $response);

                        return $response;
                    });
            }

            if (! is_file($path)) {
                throw new RuntimeException(sprintf(
                    'No recorded response for %s %s — re-record with LABELARY_RECORD=1',
                    $request->getMethod(),
                    (string) $request->getUri()
                ));
            }

            /** @var array{status: int, headers: array<string, list<string>>, body: string} $data */
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            return Create::promiseFor(
                new Response($data['status'], $data['headers'], base64_decode($data['body'], true))
            );
        };
    }

    private static function path(RequestInterface $request): string
    {
        return __DIR__.'/../fixtures/http/'.self::key($request).'.json';
    }

    /**
     * Every Labelary option travels as a request header, so they all have to be
     * part of the key — two tests differ only by an X-Rotation.
     */
    private static function key(RequestInterface $request): string
    {
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['host', 'user-agent', 'content-length'], true)) {
                continue;
            }

            $headers[strtolower($name)] = implode(',', $values);
        }

        ksort($headers);

        $body = (string) $request->getBody();
        $request->getBody()->rewind();

        return sha1(implode("\n", [
            $request->getMethod(),
            (string) $request->getUri(),
            json_encode($headers, JSON_THROW_ON_ERROR),
            $body,
        ]));
    }

    private static function write(string $path, ResponseInterface $response): void
    {
        $headers = [];

        foreach (self::KEPT_RESPONSE_HEADERS as $name) {
            if ($response->hasHeader($name)) {
                $headers[$name] = $response->getHeader($name);
            }
        }

        $body = (string) $response->getBody();
        $response->getBody()->rewind();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode([
            'status' => $response->getStatusCode(),
            'headers' => $headers,
            'body' => base64_encode($body),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
