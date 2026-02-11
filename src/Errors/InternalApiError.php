<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

final class InternalApiError
{
    public function __construct(
        public readonly ErrorCode $code,
        public readonly string $message,
        public readonly int $httpStatus
    ) {
    }
}
