<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

interface NonceStoreInterface
{
    /**
     * Atomically reserve a nonce for a positive TTL. False means an unexpired
     * reservation already exists. Storage failures must throw, never return true.
     */
    public function consume(string $nonceKey, int $ttlSeconds): bool;
}
