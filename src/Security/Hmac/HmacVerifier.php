<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;

final class HmacVerifier
{
    public function __construct(
        private readonly HmacSigner $signer = new HmacSigner(),
        private readonly int $allowedClockSkewSeconds = 300
    ) {
    }

    /**
     * @param array<string, mixed> $headers
     * @param callable(string): ?string $resolveSecretByKeyId
     */
    public function verify(
        string $method,
        string $path,
        string $rawBody,
        array $headers,
        callable $resolveSecretByKeyId,
        ?NonceStoreInterface $nonceStore = null
    ): VerificationResult {
        $normalizedHeaders = $this->normalizeHeaders($headers);

        $keyId = $this->headerValue($normalizedHeaders, InternalHeaders::KEY_ID);
        $timestamp = $this->headerValue($normalizedHeaders, InternalHeaders::TIMESTAMP);
        $nonce = $this->headerValue($normalizedHeaders, InternalHeaders::NONCE);
        $providedSignature = $this->headerValue($normalizedHeaders, InternalHeaders::SIGNATURE);

        if ($keyId === null || $timestamp === null || $nonce === null || $providedSignature === null) {
            return VerificationResult::failure(
                ErrorCode::VALIDATION_ERROR,
                'Missing required HMAC headers.',
                ['required' => [InternalHeaders::KEY_ID, InternalHeaders::TIMESTAMP, InternalHeaders::NONCE, InternalHeaders::SIGNATURE]]
            );
        }

        if (!$this->isTimestampFresh($timestamp)) {
            return VerificationResult::failure(
                ErrorCode::REQUEST_EXPIRED,
                'Request timestamp has expired or is invalid.',
                ['timestamp' => $timestamp, 'skew_seconds' => $this->allowedClockSkewSeconds]
            );
        }

        if ($nonceStore !== null) {
            $nonceKey = $keyId . ':' . $nonce;
            if ($nonceStore->has($nonceKey)) {
                return VerificationResult::failure(
                    ErrorCode::NONCE_REPLAY,
                    'Replay request detected.',
                    ['nonce' => $nonce]
                );
            }

            $nonceStore->put($nonceKey, $this->allowedClockSkewSeconds);
        }

        $secret = $resolveSecretByKeyId($keyId);
        if (!is_string($secret) || $secret === '') {
            return VerificationResult::failure(
                ErrorCode::INVALID_SIGNATURE,
                'Invalid signature credentials.'
            );
        }

        $canonical = CanonicalRequest::build($method, $path, $timestamp, $nonce, $rawBody);

        $expectedHex = $this->signer->sign($canonical, $secret, 'sha256', 'hex');
        $expectedBase64 = $this->signer->sign($canonical, $secret, 'sha256', 'base64');

        $valid = hash_equals($expectedHex, $providedSignature) || hash_equals($expectedBase64, $providedSignature);
        if (!$valid) {
            return VerificationResult::failure(ErrorCode::INVALID_SIGNATURE, 'Invalid request signature.');
        }

        return VerificationResult::success();
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $normalized[strtolower(trim($name))] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @param array<string, string> $normalizedHeaders
     */
    private function headerValue(array $normalizedHeaders, string $headerName): ?string
    {
        $value = $normalizedHeaders[strtolower($headerName)] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    private function isTimestampFresh(string $timestamp): bool
    {
        if (!preg_match('/^\d+$/', $timestamp)) {
            return false;
        }

        $sentAt = (int) $timestamp;

        return abs(time() - $sentAt) <= $this->allowedClockSkewSeconds;
    }
}
