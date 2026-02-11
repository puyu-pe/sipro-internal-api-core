<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class SuspendTenantRequest
{
    public function __construct(
        public readonly string $tenantCode,
        public readonly string $reason,
        public readonly ?string $until = null
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
            until: isset($payload['until']) ? (string) $payload['until'] : null
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

        if ($this->until !== null && strtotime($this->until) === false) {
            $errors['until'][] = 'until must be a valid datetime string.';
        }

        return $errors === [] ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * @return array{tenant_code: string, reason: string, until: ?string}
     */
    public function toArray(): array
    {
        return [
            'tenant_code' => $this->tenantCode,
            'reason' => $this->reason,
            'until' => $this->until,
        ];
    }
}
