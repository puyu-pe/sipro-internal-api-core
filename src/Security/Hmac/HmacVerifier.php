<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;

final class HmacVerifier
{
    /** @var callable(): int */
    private $currentTimeProvider;

    /**
     * @param callable(): int|null $currentTimeProvider
     */
    public function __construct(
        private readonly HmacSigner $signer = new HmacSigner(),
        private readonly int $allowedClockSkewSeconds = 300,
        ?callable $currentTimeProvider = null
    ) {
        if ($allowedClockSkewSeconds < 0 || $allowedClockSkewSeconds > 86400) {
            throw new \InvalidArgumentException('Clock skew must be between 0 and 86400 seconds.');
        }
        $this->currentTimeProvider = $currentTimeProvider ?? static fn (): int => time();
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
        CanonicalRequest::assertPath($path);
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

        $secret = $resolveSecretByKeyId($keyId);
        if (!is_string($secret) || $secret === '') {
            return VerificationResult::failure(
                ErrorCode::INVALID_SIGNATURE,
                'Invalid signature credentials.'
            );
        }

        $canonical = CanonicalRequest::build($method, $path, $timestamp, $nonce, $rawBody);

        $expectedHex = $this->signer->sign($canonical, $secret);
        $valid = hash_equals($expectedHex, $providedSignature);
        if (!$valid) {
            return VerificationResult::failure(ErrorCode::INVALID_SIGNATURE, 'Invalid request signature.');
        }

        $nonceKey = hash('sha256', json_encode([$keyId, $nonce], JSON_THROW_ON_ERROR));
        if ($nonceStore !== null && !$nonceStore->consume($nonceKey, 2 * $this->allowedClockSkewSeconds + 1)) {
            return VerificationResult::failure(ErrorCode::NONCE_REPLAY, 'Replay request detected.');
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

            if (is_array($value)) {
                $value = count($value) === 1 ? reset($value) : null;
            }
            $normalized[strtolower(trim($name))] = is_string($value) ? trim($value) : '';
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
        $now = ($this->currentTimeProvider)();

        return abs($now - $sentAt) <= $this->allowedClockSkewSeconds;
    }
}
