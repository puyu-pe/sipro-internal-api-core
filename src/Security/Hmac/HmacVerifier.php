<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

final class HmacVerifier
{
    public function __construct(
        private readonly HmacSigner $signer = new HmacSigner()
    ) {
    }

    public function verifySignature(string $canonicalRequest, string $providedSignature, string $secret): bool
    {
        $expected = $this->signer->sign($canonicalRequest, $secret);

        return hash_equals($expected, trim($providedSignature));
    }

    public function isTimestampFresh(string $timestamp, int $maxSkewSeconds = 300): bool
    {
        if (!is_numeric($timestamp)) {
            return false;
        }

        $sentAt = (int) $timestamp;
        return abs(time() - $sentAt) <= $maxSkewSeconds;
    }

    public function isNonceValid(string $nonce, NonceStoreInterface $nonceStore, int $ttlSeconds = 300): bool
    {
        if ($nonce === '' || $nonceStore->has($nonce)) {
            return false;
        }

        $nonceStore->save($nonce, $ttlSeconds);

        return true;
    }
}
