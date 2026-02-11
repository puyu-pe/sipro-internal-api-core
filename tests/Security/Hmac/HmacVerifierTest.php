<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Security\Hmac;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacVerifier;

final class HmacVerifierTest extends TestCase
{
    private const SECRET = 'test-secret-123';
    private const KEY_ID = 'sipro-2026-01';
    private const METHOD = 'POST';
    private const PATH = '/internal/v1/tenants';
    private const TIMESTAMP_BASE = 1760467200;
    private const NONCE = '00000000-0000-0000-0000-000000000001';
    private const RAW_BODY = '{"tenant_uuid":"11111111-1111-4111-8111-111111111111","tenant_name":"Demo","admin_user":{"email":"admin@demo.com","name":"Admin","temp_password":"Temp1234"}}';

    public function testVerifyReturnsOkOnValidSignatureAndTimestamp(): void
    {
        $signer = new HmacSigner();
        $headers = $signer->buildSignedHeaders(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            self::KEY_ID,
            self::SECRET,
            (string) self::TIMESTAMP_BASE,
            self::NONCE
        );

        $verifier = new HmacVerifier(allowedClockSkewSeconds: 300, currentTimeProvider: fn (): int => self::TIMESTAMP_BASE);

        $result = $verifier->verify(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            $headers,
            fn (string $keyId): ?string => $keyId === self::KEY_ID ? self::SECRET : null
        );

        self::assertTrue($result->ok);
        self::assertNull($result->errorCode);
    }

    public function testVerifyFailsWithInvalidSignature(): void
    {
        $headers = [
            InternalHeaders::KEY_ID => self::KEY_ID,
            InternalHeaders::TIMESTAMP => (string) self::TIMESTAMP_BASE,
            InternalHeaders::NONCE => self::NONCE,
            InternalHeaders::SIGNATURE => 'bad-signature',
        ];

        $verifier = new HmacVerifier(allowedClockSkewSeconds: 300, currentTimeProvider: fn (): int => self::TIMESTAMP_BASE);
        $result = $verifier->verify(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            $headers,
            fn (): ?string => self::SECRET
        );

        self::assertFalse($result->ok);
        self::assertSame(ErrorCode::INVALID_SIGNATURE, $result->errorCode);
    }

    public function testVerifyFailsWhenTimestampTooOldOrTooFarInFuture(): void
    {
        $verifier = new HmacVerifier(allowedClockSkewSeconds: 300, currentTimeProvider: fn (): int => self::TIMESTAMP_BASE);

        $oldHeaders = $this->signedHeadersForTimestamp(self::TIMESTAMP_BASE - 301, self::NONCE . '-old');
        $futureHeaders = $this->signedHeadersForTimestamp(self::TIMESTAMP_BASE + 301, self::NONCE . '-future');

        $oldResult = $verifier->verify(self::METHOD, self::PATH, self::RAW_BODY, $oldHeaders, fn (): ?string => self::SECRET);
        $futureResult = $verifier->verify(self::METHOD, self::PATH, self::RAW_BODY, $futureHeaders, fn (): ?string => self::SECRET);

        self::assertSame(ErrorCode::REQUEST_EXPIRED, $oldResult->errorCode);
        self::assertSame(ErrorCode::REQUEST_EXPIRED, $futureResult->errorCode);
    }

    public function testVerifyFailsWhenRequiredHeadersAreMissing(): void
    {
        $verifier = new HmacVerifier(allowedClockSkewSeconds: 300, currentTimeProvider: fn (): int => self::TIMESTAMP_BASE);

        $result = $verifier->verify(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            [InternalHeaders::KEY_ID => self::KEY_ID],
            fn (): ?string => self::SECRET
        );

        self::assertFalse($result->ok);
        self::assertSame(ErrorCode::VALIDATION_ERROR, $result->errorCode);
    }

    /** @return array<string, string> */
    private function signedHeadersForTimestamp(int $timestamp, string $nonce): array
    {
        $signer = new HmacSigner();

        return $signer->buildSignedHeaders(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            self::KEY_ID,
            self::SECRET,
            (string) $timestamp,
            $nonce
        );
    }
}
