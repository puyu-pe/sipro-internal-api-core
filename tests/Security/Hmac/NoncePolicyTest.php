<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Security\Hmac;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Security\Hmac\{HmacSigner, HmacVerifier, NonceStoreInterface};
use PuyuPe\SiproInternalApiCore\Http\InternalHeaders;

final class NoncePolicyTest extends TestCase
{
    public function testInvalidSignatureCannotConsumeNonceAndFutureRequestCannotReplay(): void
    {
        $now = 1000;
        $store = new class implements NonceStoreInterface {
            public int $now = 1000;
            public array $expires = [];
            public function consume(string $nonceKey, int $ttlSeconds): bool
            {
                if (($this->expires[$nonceKey] ?? 0) > $this->now) { return false; }
                $this->expires[$nonceKey] = $this->now + $ttlSeconds;
                return true;
            }
        };
        $verifier = new HmacVerifier(currentTimeProvider: static function () use (&$now) { return $now; });
        $headers = (new HmacSigner())->buildSignedHeaders('POST', '/internal/v1/tenants', '{}', 'key', 'secret', '1300', 'nonce');
        $invalid = $headers;
        $invalid[InternalHeaders::SIGNATURE] = 'invalid';
        self::assertFalse($verifier->verify('POST', '/internal/v1/tenants', '{}', $invalid, fn () => 'secret', $store)->ok);
        self::assertSame([], $store->expires);
        self::assertTrue($verifier->verify('POST', '/internal/v1/tenants', '{}', $headers, fn () => 'secret', $store)->ok);
        $now = $store->now = 1600;
        self::assertSame('NONCE_REPLAY', $verifier->verify('POST', '/internal/v1/tenants', '{}', $headers, fn () => 'secret', $store)->errorCode->value);
        $now = $store->now = 1601;
        self::assertSame('REQUEST_EXPIRED', $verifier->verify('POST', '/internal/v1/tenants', '{}', $headers, fn () => 'secret', $store)->errorCode->value);
    }

    public function testNonceIdentityCannotCollideByConcatenatingKeyIdAndNonce(): void
    {
        $store = new \PuyuPe\SiproInternalApiCore\Tests\Support\InMemoryNonceStore();
        $verifier = new HmacVerifier(currentTimeProvider: fn () => 1000);
        foreach ([['a:b', 'c'], ['a', 'b:c']] as [$key, $nonce]) {
            $headers = (new HmacSigner())->buildSignedHeaders('POST', '/internal/v1/tenants', '{}', $key, 'secret', '1000', $nonce);
            self::assertTrue($verifier->verify('POST', '/internal/v1/tenants', '{}', $headers, fn () => 'secret', $store)->ok);
        }
    }
}
