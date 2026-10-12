<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiException;

final class CanonicalRequest
{
    public static function build(
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $rawBody
    ): string {
        self::assertPath($path);

        return implode("\n", [
            strtoupper(trim($method)),
            $path,
            trim($timestamp),
            trim($nonce),
            self::bodySha256Hex($rawBody),
        ]);
    }

    public static function bodySha256Hex(string $rawBody): string
    {
        return hash('sha256', $rawBody);
    }

    public static function assertPath(string $path): void
    {
        if (str_contains($path, '?')) {
            throw new InternalApiException(new InternalApiError(ErrorCode::QUERY_NOT_ALLOWED, 'Query strings are not allowed.'));
        }
        if (!str_starts_with($path, '/') || str_ends_with($path, '/') || preg_match('/[\s#]|\/\//', $path)) {
            throw new InternalApiException(new InternalApiError(ErrorCode::INVALID_PATH, 'Invalid request path.'));
        }
    }
}
