<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Security\Hmac;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;
use PuyuPe\SiproInternalApiCore\Security\Hmac\CanonicalRequest;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

final class HmacSignerTest extends TestCase
{
    public function testIndependentGoldenVector(): void
    {
        $headers = (new HmacSigner())->buildSignedHeaders('POST', '/internal/v1/tenants/tenant:warn',
            '{"resolveKey":"tenant","projectCode":"PROJ"}', 'test', 'test-secret', '1760467200', 'nonce-vector');
        self::assertSame('1a777e132ac903c84c7e3720e576efe7586ca6e4366add9558908ab088705592', $headers[InternalHeaders::SIGNATURE]);
    }

    private const SECRET = 'test-secret-123';
    private const KEY_ID = 'sipro-2026-01';
    private const METHOD = 'POST';
    private const PATH = '/internal/v1/tenants';
    private const TIMESTAMP = '1760467200';
    private const NONCE = '00000000-0000-0000-0000-000000000001';
    private const RAW_BODY = '{"tenant_uuid":"11111111-1111-4111-8111-111111111111","tenant_name":"Demo","admin_user":{"email":"admin@demo.com","name":"Admin","temp_password":"Temp1234"}}';

    public function testSignUsesSha256Hex(): void
    {
        $canonical = CanonicalRequest::build(self::METHOD, self::PATH, self::TIMESTAMP, self::NONCE, self::RAW_BODY);
        $signer = new HmacSigner();

        $hex = $signer->sign($canonical, self::SECRET);

        self::assertSame(hash_hmac('sha256', $canonical, self::SECRET), $hex);
    }

    public function testBuildSignedHeadersIncludesRequiredHeaders(): void
    {
        $signer = new HmacSigner();

        $headers = $signer->buildSignedHeaders(
            self::METHOD,
            self::PATH,
            self::RAW_BODY,
            self::KEY_ID,
            self::SECRET,
            self::TIMESTAMP,
            self::NONCE
        );

        self::assertSame(self::KEY_ID, $headers[InternalHeaders::KEY_ID]);
        self::assertSame(self::TIMESTAMP, $headers[InternalHeaders::TIMESTAMP]);
        self::assertSame(self::NONCE, $headers[InternalHeaders::NONCE]);
        self::assertNotSame('', $headers[InternalHeaders::SIGNATURE]);
    }
}
