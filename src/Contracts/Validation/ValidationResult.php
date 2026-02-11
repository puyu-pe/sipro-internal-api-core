<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Validation;

final class ValidationResult
{
    /**
     * @param list<array{field: string, code: string, message: string}> $errors
     */
    public function __construct(
        private readonly bool $ok,
        private readonly array $errors = []
    ) {
    }

    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * @param list<array{field: string, code: string, message: string}> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    public function ok(): bool
    {
        return $this->ok;
    }

    /**
     * @return list<array{field: string, code: string, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array{ok: bool, errors: list<array{field: string, code: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'errors' => $this->errors,
        ];
    }
}
