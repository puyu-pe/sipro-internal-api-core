<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Support;

use PuyuPe\SiproInternalApiCore\Security\Hmac\NonceStoreInterface;

final class InMemoryNonceStore implements NonceStoreInterface
{
    /** @var array<string, int> */
    private array $entries = [];

    public function consume(string $nonceKey, int $ttlSeconds): bool
    {
        if (isset($this->entries[$nonceKey])) {
            return false;
        }
        $this->entries[$nonceKey] = $ttlSeconds;
        return true;
    }
}
