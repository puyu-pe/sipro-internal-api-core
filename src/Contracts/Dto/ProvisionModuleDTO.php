<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionModuleDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $externalId,
        public readonly string $name,
        public readonly string $description,
        public readonly float $price,
        public readonly bool $isUnlimited,
        public readonly ?float $customPrice,
        public readonly int $quantity
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: isset($payload['id']) ? (int) $payload['id'] : null,
            externalId: isset($payload['externalId']) ? (string) $payload['externalId'] : null,
            name: (string) ($payload['name'] ?? ''),
            description: (string) ($payload['description'] ?? ''),
            price: (float) ($payload['price'] ?? 0.0),
            isUnlimited: (bool) ($payload['isUnlimited'] ?? false),
            customPrice: isset($payload['customPrice']) ? (float) $payload['customPrice'] : null,
            quantity: (int) ($payload['quantity'] ?? 0)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'isUnlimited' => $this->isUnlimited,
            'customPrice' => $this->customPrice,
            'quantity' => $this->quantity,
        ];
    }
}
