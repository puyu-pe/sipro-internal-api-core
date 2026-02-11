<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http;

final class InternalHeaders
{
    public const KEY_ID = 'X-Internal-KeyId';
    public const SIGNATURE = 'X-Internal-Signature';
    public const TIMESTAMP = 'X-Internal-Timestamp';
    public const NONCE = 'X-Internal-Nonce';
}
