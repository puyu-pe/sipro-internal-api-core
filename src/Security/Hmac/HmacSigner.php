<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;

final class HmacSigner
{
    /**
     * @return 'hex'|'base64'
     */
    private function normalizeOutput(string $output): string
    {
        $normalized = strtolower(trim($output));

        return $normalized === 'base64' ? 'base64' : 'hex';
    }

    public function sign(string $canonicalString, string $secret, string $algo = 'sha256', string $output = 'hex'): string
    {
        $encoding = $this->normalizeOutput($output);

        if ($encoding === 'base64') {
            return base64_encode(hash_hmac($algo, $canonicalString, $secret, true));
        }

        return hash_hmac($algo, $canonicalString, $secret, false);
    }

    /**
     * @return array<string, string>
     */
    public function buildSignedHeaders(
        string $method,
        string $path,
        string $rawBody,
        string $keyId,
        string $secret,
        ?string $timestampNow = null,
        ?string $nonce = null,
        string $algo = 'sha256',
        string $output = 'hex'
    ): array {
        $timestamp = $timestampNow ?? (string) time();
        $nonceValue = $nonce ?? bin2hex(random_bytes(16));

        $canonical = CanonicalRequest::build($method, $path, $timestamp, $nonceValue, $rawBody);
        $signature = $this->sign($canonical, $secret, $algo, $output);

        return [
            InternalHeaders::KEY_ID => $keyId,
            InternalHeaders::TIMESTAMP => $timestamp,
            InternalHeaders::NONCE => $nonceValue,
            InternalHeaders::SIGNATURE => $signature,
        ];
    }
}
