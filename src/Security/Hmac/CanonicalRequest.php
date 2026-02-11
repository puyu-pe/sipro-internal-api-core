<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

final class CanonicalRequest
{
    /**
     * @param array<string, string> $headers
     */
    public static function build(
        string $method,
        string $path,
        array $headers,
        string $body,
        string $timestamp,
        string $nonce
    ): string {
        $normalizedHeaders = [];
        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower(trim($name))] = trim($value);
        }

        ksort($normalizedHeaders);

        $headerStringParts = [];
        foreach ($normalizedHeaders as $name => $value) {
            $headerStringParts[] = sprintf('%s:%s', $name, $value);
        }

        $headersPart = implode("\n", $headerStringParts);

        return implode("\n", [
            strtoupper(trim($method)),
            '/' . ltrim(trim($path), '/'),
            $headersPart,
            hash('sha256', $body),
            trim($timestamp),
            trim($nonce),
        ]);
    }
}
