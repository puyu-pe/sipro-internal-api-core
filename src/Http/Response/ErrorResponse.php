<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http\Response;

use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ErrorResponse
{
    /**
     * @param array<string, list<string>> $details
     */
    public function __construct(
        private readonly InternalApiError $error,
        private readonly array $details = []
    ) {
    }

    /**
     * @return array{
     *   success: false,
     *   error: array{code: string, message: string, status: int, details: array<string, list<string>>}
     * }
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->error->code->value,
                'message' => $this->error->message,
                'status' => $this->error->httpStatus,
                'details' => $this->details,
            ],
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }
}
