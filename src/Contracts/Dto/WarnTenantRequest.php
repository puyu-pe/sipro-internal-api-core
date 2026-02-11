<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class WarnTenantRequest
{
    public function __construct(
        public readonly string $tenantCode,
        public readonly string $reason,
        public readonly ?string $warnedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tenantCode: (string) ($payload['tenant_code'] ?? ''),
            reason: (string) ($payload['reason'] ?? ''),
            warnedAt: isset($payload['warned_at']) ? (string) $payload['warned_at'] : null
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->tenantCode === '') {
            $errors['tenant_code'][] = 'tenant_code is required.';
        }

        if ($this->reason === '') {
            $errors['reason'][] = 'reason is required.';
        }

        if ($this->warnedAt !== null && strtotime($this->warnedAt) === false) {
            $errors['warned_at'][] = 'warned_at must be a valid datetime string.';
        }

        return $errors === [] ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * @return array{tenant_code: string, reason: string, warned_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'tenant_code' => $this->tenantCode,
            'reason' => $this->reason,
            'warned_at' => $this->warnedAt,
        ];
    }
}
