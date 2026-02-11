<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

interface NonceStoreInterface
{
    public function has(string $nonce): bool;

    public function save(string $nonce, int $ttlSeconds): void;
}
