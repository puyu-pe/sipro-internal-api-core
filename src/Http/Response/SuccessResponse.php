<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http\Response;

final class SuccessResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data = [],
        private readonly string $message = 'OK'
    ) {
    }

    /**
     * @return array{success: true, message: string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }
}
