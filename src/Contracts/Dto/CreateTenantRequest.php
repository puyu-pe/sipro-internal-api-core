<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class CreateTenantRequest
{
    public function __construct(
        public readonly string $tenantCode,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tenantCode: (string) ($payload['tenant_code'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            email: (string) ($payload['email'] ?? ''),
            phone: (string) ($payload['phone'] ?? '')
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->tenantCode === '') {
            $errors['tenant_code'][] = 'tenant_code is required.';
        }

        if ($this->name === '') {
            $errors['name'][] = 'name is required.';
        }

        if ($this->email === '' || filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'email must be a valid email address.';
        }

        if ($this->phone === '') {
            $errors['phone'][] = 'phone is required.';
        }

        return $errors === [] ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * @return array{tenant_code: string, name: string, email: string, phone: string}
     */
    public function toArray(): array
    {
        return [
            'tenant_code' => $this->tenantCode,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
