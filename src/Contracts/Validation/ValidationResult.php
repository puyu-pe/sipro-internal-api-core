<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Validation;

final class ValidationResult
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        private readonly bool $valid,
        private readonly array $errors = []
    ) {
    }

    public static function ok(): self
    {
        return new self(true);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
