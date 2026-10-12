<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;

final class HmacSigner
{
    public function sign(string $canonicalString, string $secret): string
    {
        return hash_hmac('sha256', $canonicalString, $secret);
    }

    /** @return array<string, string> */
    public function buildSignedHeaders(
        string $method,
        string $path,
        string $rawBody,
        string $keyId,
        string $secret,
        ?string $timestampNow = null,
        ?string $nonce = null
    ): array {
        $timestamp = $timestampNow ?? (string) time();
        $nonceValue = $nonce ?? bin2hex(random_bytes(16));
        return [
            InternalHeaders::KEY_ID => $keyId,
            InternalHeaders::TIMESTAMP => $timestamp,
            InternalHeaders::NONCE => $nonceValue,
            InternalHeaders::SIGNATURE => $this->sign(CanonicalRequest::build($method, $path, $timestamp, $nonceValue, $rawBody), $secret),
        ];
    }
}
