<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class ActivateTenantRequest
{
    public function __construct(
        public readonly ?string $message = null,
        public readonly mixed $clearWarn = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            message: self::nullableString($payload['message'] ?? null),
            clearWarn: isset($payload['clear_warn']) ? $payload['clear_warn'] : null,
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->clearWarn !== null && !is_bool($this->clearWarn)) {
            $errors[] = self::error('clear_warn', 'invalid_boolean', 'clear_warn must be boolean.');
        }

        return $errors === [] ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * @return array{message: ?string, clear_warn: mixed}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'clear_warn' => $this->clearWarn,
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
