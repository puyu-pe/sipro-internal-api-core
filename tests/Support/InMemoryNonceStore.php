<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Support;

use PuyuPe\SiproInternalApiCore\Security\Hmac\NonceStoreInterface;

final class InMemoryNonceStore implements NonceStoreInterface
{
    /** @var array<string, int> */
    private array $entries = [];

    public function has(string $nonce): bool
    {
        return isset($this->entries[$nonce]) && $this->entries[$nonce] >= 0;
    }

    public function put(string $nonce, int $ttlSeconds): void
    {
        $this->entries[$nonce] = $ttlSeconds;
    }
}
