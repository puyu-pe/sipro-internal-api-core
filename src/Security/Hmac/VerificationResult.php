<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Security\Hmac;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;

final class VerificationResult
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?ErrorCode $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $details = []
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    /**
     * @param array<string, mixed> $details
     */
    public static function failure(ErrorCode $errorCode, string $errorMessage, array $details = []): self
    {
        return new self(false, $errorCode, $errorMessage, $details);
    }
}
