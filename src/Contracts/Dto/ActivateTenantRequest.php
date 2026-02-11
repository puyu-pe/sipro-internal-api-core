<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class ActivateTenantRequest
{
    public function __construct(
        public readonly string $tenantCode,
        public readonly ?string $activatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tenantCode: (string) ($payload['tenant_code'] ?? ''),
            activatedAt: isset($payload['activated_at']) ? (string) $payload['activated_at'] : null
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->tenantCode === '') {
            $errors['tenant_code'][] = 'tenant_code is required.';
        }

        if ($this->activatedAt !== null && strtotime($this->activatedAt) === false) {
            $errors['activated_at'][] = 'activated_at must be a valid datetime string.';
        }

        return $errors === [] ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * @return array{tenant_code: string, activated_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'tenant_code' => $this->tenantCode,
            'activated_at' => $this->activatedAt,
        ];
    }
}
