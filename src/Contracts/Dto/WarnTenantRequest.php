<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use DateTimeImmutable;
use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class WarnTenantRequest
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $warnUntil = null,
        public readonly ?string $severity = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            message: trim((string) ($payload['message'] ?? '')),
            warnUntil: self::nullableString($payload['warn_until'] ?? null),
            severity: self::nullableString($payload['severity'] ?? null),
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        $length = strlen($this->message);
        if ($length < 1 || $length > 250) {
            $errors[] = self::error('message', 'invalid_length', 'message must contain between 1 and 250 characters.');
        }

        if ($this->warnUntil !== null && !$this->isValidDate($this->warnUntil)) {
            $errors[] = self::error('warn_until', 'invalid_date', 'warn_until must be in YYYY-MM-DD format.');
        }

        if ($this->severity !== null && !in_array($this->severity, ['notice', 'warning'], true)) {
            $errors[] = self::error('severity', 'invalid_value', 'severity must be one of: notice, warning.');
        }

        return $errors === [] ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * @return array{message: string, warn_until: ?string, severity: ?string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'warn_until' => $this->warnUntil,
            'severity' => $this->severity,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
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
