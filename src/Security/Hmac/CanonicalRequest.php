<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

final class CanonicalRequest
{
    public static function build(
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $rawBody
    ): string {
        $pathOnly = explode('?', trim($path), 2)[0];
        $normalizedPath = '/' . ltrim($pathOnly, '/');

        return implode("\n", [
            strtoupper(trim($method)),
            $normalizedPath,
            trim($timestamp),
            trim($nonce),
            self::bodySha256Hex($rawBody),
        ]);
    }

    public static function bodySha256Hex(string $rawBody): string
    {
        return hash('sha256', $rawBody);
    }
}
