<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Security\Hmac;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacVerifier;
use PuyuPe\SiproInternalApiCore\Tests\Support\InMemoryNonceStore;

final class AntiReplayTest extends TestCase
{
    private const SECRET = 'test-secret-123';
    private const KEY_ID = 'sipro-2026-01';
    private const METHOD = 'POST';
    private const PATH = '/internal/v1/tenants';
    private const TIMESTAMP_BASE = 1760467200;
    private const NONCE = '00000000-0000-0000-0000-000000000001';
    private const RAW_BODY = '{"tenant_uuid":"11111111-1111-4111-8111-111111111111","tenant_name":"Demo","admin_user":{"email":"admin@demo.com","name":"Admin","temp_password":"Temp1234"}}';

    public function testFirstRequestPassesAndSecondWithSameNonceFails(): void
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
        $nonceStore = new InMemoryNonceStore();

        $first = $verifier->verify(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            $headers,
            fn (): ?string => self::SECRET,
            $nonceStore
        );

        $second = $verifier->verify(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            $headers,
            fn (): ?string => self::SECRET,
            $nonceStore
        );

        self::assertTrue($first->ok);
        self::assertFalse($second->ok);
        self::assertSame(ErrorCode::NONCE_REPLAY, $second->errorCode);
    }
}
