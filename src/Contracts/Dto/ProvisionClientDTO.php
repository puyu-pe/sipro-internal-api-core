<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionClientDTO
{
    public function __construct(
        public readonly string $ruc,
        public readonly string $businessName,
        public readonly string $tradeName
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            ruc: (string) ($payload['ruc'] ?? ''),
            businessName: (string) ($payload['businessName'] ?? ''),
            tradeName: (string) ($payload['tradeName'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ruc' => $this->ruc,
            'businessName' => $this->businessName,
            'tradeName' => $this->tradeName,
        ];
    }
}
