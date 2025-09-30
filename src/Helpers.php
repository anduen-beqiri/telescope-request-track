<?php

namespace BekAnd\TelescopeRequestTrack;

use function config;
use function request;
use function json_decode;
use function json_encode;
use function array_filter;
use function str_contains;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function array_key_exists;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Helpers
{
    public const ATTRIBUTE = '_bekand_telescope_request_id';
    public const SKIP_ATTRIBUTE = '_bekand_telescope_request_id_skip';

    public static function config(string $key, mixed $default = null): mixed
    {
        return config("telescope-track.$key", $default);
    }

    public static function isEnabled(): bool
    {
        return (bool) static::config('enabled', true);
    }

    public static function displayInJson(): bool
    {
        return (bool) static::config('show_in_json', true);
    }

    public static function headerName(): string
    {
        return (string) static::config('header', 'X-Request-Id');
    }

    public static function keyName(): string
    {
        return (string) static::config('key', 'request_id');
    }

    public static function shouldBypass(Request $request): bool
    {
        if (! static::isEnabled()) {
            return true;
        }

        if (static::isExceptedUri($request)) {
            return true;
        }

        return static::isSkipped($request);
    }

    public static function markSkip(Request $request): void
    {
        $request->attributes->set(self::SKIP_ATTRIBUTE, true);
    }

    public static function isSkipped(Request $request): bool
    {
        return (bool) $request->attributes->get(self::SKIP_ATTRIBUTE, false);
    }

    public static function isExceptedUri(Request $request): bool
    {
        $except = array_filter((array) static::config('except', []));

        foreach ($except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function resolveRequestId(Request $request): string
    {
        if ($request->attributes->has(self::ATTRIBUTE)) {
            return (string) $request->attributes->get(self::ATTRIBUTE);
        }

        $header = $request->headers->get(static::headerName());

        if (is_string($header) && $header !== '') {
            static::storeRequestId($request, $header);

            return $header;
        }

        $generated = (string) Str::uuid();

        static::storeRequestId($request, $generated, true);

        return $generated;
    }

    public static function storeRequestId(Request $request, string $requestId, bool $overwriteHeader = false): void
    {
        $request->attributes->set(self::ATTRIBUTE, $requestId);

        $header = static::headerName();

        if ($overwriteHeader || ! $request->headers->has($header)) {
            $request->headers->set($header, $requestId);
        }
    }

    public static function currentRequestId(?Request $request = null): ?string
    {
        $request ??= request();

        if (! $request instanceof Request) {
            return null;
        }

        return $request->attributes->get(self::ATTRIBUTE);
    }

    public static function augmentResponse(mixed $response, string $key, string $requestId): string
    {
        if (! static::displayInJson()) {
            return $requestId;
        }

        $finalId = $requestId;

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $finalId;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (! is_array($data)) {
                return $finalId;
            }

            [$payload, $finalId] = static::preparePayload($data, $key, $requestId);

            $response->setData($payload);

            return $finalId;
        }

        if ($response instanceof Response && static::hasJsonContentType($response)) {
            $content = $response->getContent();

            if ($content === null || $content === '') {
                return $finalId;
            }

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return $finalId;
            }

            [$payload, $finalId] = static::preparePayload($decoded, $key, $requestId);

            $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($encoded === false) {
                return $finalId;
            }

            $response->setContent($encoded);
        }

        return $finalId;
    }

    public static function augmentPayload(array $payload, string $key, string $requestId): array
    {
        [$prepared] = static::preparePayload($payload, $key, $requestId);

        return $prepared;
    }

    protected static function preparePayload(array $payload, string $key, string $requestId): array
    {
        if (! Arr::isAssoc($payload)) {
            return [[
                'data' => $payload,
                $key => $requestId,
            ], $requestId];
        }

        if (array_key_exists($key, $payload)) {
            $value = $payload[$key];

            if (is_scalar($value) && $value !== '') {
                return [$payload, (string) $value];
            }

            return [$payload, $requestId];
        }

        $payload[$key] = $requestId;

        return [$payload, $requestId];
    }

    protected static function hasJsonContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');

        if (! is_string($contentType)) {
            return false;
        }

        return str_contains(strtolower($contentType), 'json');
    }
}
