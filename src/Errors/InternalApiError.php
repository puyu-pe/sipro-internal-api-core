<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

final class InternalApiError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly ErrorCode $code,
        public readonly string $message,
        public readonly array $details = []
    ) {
    }
}
