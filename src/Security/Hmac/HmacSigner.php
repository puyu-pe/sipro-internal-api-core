<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

final class HmacSigner
{
    public function __construct(
        private readonly string $algorithm = 'sha256'
    ) {
    }

    public function sign(string $canonicalRequest, string $secret): string
    {
        return base64_encode(hash_hmac($this->algorithm, $canonicalRequest, $secret, true));
    }
}
