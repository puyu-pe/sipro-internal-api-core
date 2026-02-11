<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http\Response;

use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ErrorResponse
{
    public function __construct(
        private readonly InternalApiError $error
    ) {
    }

    public static function fromError(InternalApiError $error): self
    {
        return new self($error);
    }

    /**
     * @return array{ok: false, error: array{code: string, message: string, details?: array<string, mixed>}}
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->error->code->value,
            'message' => $this->error->message,
        ];

        if ($this->error->details !== []) {
            $error['details'] = $this->error->details;
        }

        return [
            'ok' => false,
            'error' => $error,
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }
}
