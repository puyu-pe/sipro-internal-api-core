<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http;

final class InternalHeaders
{
    public const API_KEY = 'X-Internal-Api-Key';
    public const SIGNATURE = 'X-Internal-Signature';
    public const TIMESTAMP = 'X-Internal-Timestamp';
    public const NONCE = 'X-Internal-Nonce';
}
