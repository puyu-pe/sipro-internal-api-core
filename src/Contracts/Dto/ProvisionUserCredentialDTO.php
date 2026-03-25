<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionUserCredentialDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly string $username,
        public readonly ?string $email,
        public readonly string $role,
        public readonly string $initialPassword,
        public readonly bool $mustChangePassword
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            username: (string) ($payload['username'] ?? ''),
            email: isset($payload['email']) ? (string) $payload['email'] : null,
            role: (string) ($payload['role'] ?? ''),
            initialPassword: (string) ($payload['initialPassword'] ?? ''),
            mustChangePassword: (bool) ($payload['mustChangePassword'] ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'initialPassword' => $this->initialPassword,
            'mustChangePassword' => $this->mustChangePassword,
        ];
    }
}
