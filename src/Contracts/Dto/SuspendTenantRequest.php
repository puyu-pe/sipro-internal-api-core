<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class SuspendTenantRequest
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $reasonCode = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            message: trim((string) ($payload['message'] ?? '')),
            reasonCode: self::nullableString($payload['reason_code'] ?? null),
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->message === '') {
            $errors[] = self::error('message', 'required', 'message is required.');
        }

        return $errors === [] ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * @return array{message: string, reason_code: ?string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'reason_code' => $this->reasonCode,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    /**
     * @return array{field: string, code: string, message: string}
     */
    private static function error(string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}

