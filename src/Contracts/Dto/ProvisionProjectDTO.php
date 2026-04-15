<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionProjectDTO
{
    /**
     * @param array<string, mixed> $accessUrls
     */
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly string $description,
        public readonly string $billingCycle,
        public readonly float $priceAgreed,
        public readonly string $startDate,
        public readonly ?string $renewalDate,
        public readonly string $execStatus,
        public readonly bool $isActive,
        public readonly ?string $accessUrlCustom,
        public readonly array $accessUrls,
        public readonly string $resolveKey,
        public readonly ?string $logo,
        public readonly ?string $address,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $ubigeo,
        public readonly ?float $latitud,
        public readonly ?float $longitud,
        public readonly ?string $color,
        public readonly ?string $notes
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $accessUrls = is_array($payload['accessUrls'] ?? null) ? $payload['accessUrls'] : [];

        return new self(
            name: (string) ($payload['name'] ?? ''),
            code: (string) ($payload['code'] ?? ''),
            description: (string) ($payload['description'] ?? ''),
            billingCycle: (string) ($payload['billingCycle'] ?? ''),
            priceAgreed: (float) ($payload['priceAgreed'] ?? 0.0),
            startDate: (string) ($payload['startDate'] ?? ''),
            renewalDate: isset($payload['renewalDate']) ? (string) $payload['renewalDate'] : null,
            execStatus: (string) ($payload['execStatus'] ?? ''),
            isActive: (bool) ($payload['isActive'] ?? false),
            accessUrlCustom: isset($payload['accessUrlCustom']) ? (string) $payload['accessUrlCustom'] : null,
            accessUrls: $accessUrls,
            resolveKey: isset($payload['resolveKey']) ? (string) $payload['resolveKey'] : null,
            logo: isset($payload['logo']) ? (string) $payload['logo'] : null,
            address: isset($payload['address']) ? (string) $payload['address'] : null,
            phone: isset($payload['phone']) ? (string) $payload['phone'] : null,
            email: isset($payload['email']) ? (string) $payload['email'] : null,
            ubigeo: isset($payload['ubigeo']) ? (string) $payload['ubigeo'] : null,
            latitud: isset($payload['latitud']) ? (float) $payload['latitud'] : null,
            longitud: isset($payload['longitud']) ? (float) $payload['longitud'] : null,
            color: isset($payload['color']) ? (string) $payload['color'] : null,
            notes: isset($payload['notes']) ? (string) $payload['notes'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'billingCycle' => $this->billingCycle,
            'priceAgreed' => $this->priceAgreed,
            'startDate' => $this->startDate,
            'renewalDate' => $this->renewalDate,
            'execStatus' => $this->execStatus,
            'isActive' => $this->isActive,
            'accessUrlCustom' => $this->accessUrlCustom,
            'accessUrls' => $this->accessUrls,
            'resolveKey' => $this->resolveKey,
            'logo' => $this->logo,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'ubigeo' => $this->ubigeo,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'color' => $this->color,
            'notes' => $this->notes,
        ];
    }
}
