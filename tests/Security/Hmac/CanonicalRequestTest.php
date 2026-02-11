<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Security\Hmac;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Security\Hmac\CanonicalRequest;

final class CanonicalRequestTest extends TestCase
{
    private const METHOD = 'POST';
    private const PATH = '/internal/v1/tenants';
    private const TIMESTAMP = '1760467200';
    private const NONCE = '00000000-0000-0000-0000-000000000001';
    private const RAW_BODY = '{"tenant_uuid":"11111111-1111-4111-8111-111111111111","tenant_name":"Demo","admin_user":{"email":"admin@demo.com","name":"Admin","temp_password":"Temp1234"}}';

    public function testBodySha256HexIsExactForRawBody(): void
    {
        self::assertSame(hash('sha256', self::RAW_BODY), CanonicalRequest::bodySha256Hex(self::RAW_BODY));
    }

    public function testBuildCanonicalStringMatchesExactV1Format(): void
    {
        $expected = self::METHOD . "\n"
            . self::PATH . "\n"
            . self::TIMESTAMP . "\n"
            . self::NONCE . "\n"
            . hash('sha256', self::RAW_BODY);

        $actual = CanonicalRequest::build(self::METHOD, self::PATH . '?ignored=true', self::TIMESTAMP, self::NONCE, self::RAW_BODY);

        self::assertSame($expected, $actual);
    }
}
