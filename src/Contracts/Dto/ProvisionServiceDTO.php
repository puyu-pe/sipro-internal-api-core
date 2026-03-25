<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionServiceDTO
{
    /**
     * @param array<ProvisionModuleDTO> $modules
     */
    public function __construct(
        public readonly string $key,
        public readonly ?string $externalId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $description,
        public readonly float $priceList,
        public readonly string $defaultBillingCycle,
        public readonly string $type,
        public readonly ?string $accessUrl,
        public readonly ?string $logo,
        /**
         * @var array<ProvisionUserCredentialDTO>
         */
        public readonly array $credentials,
        public readonly array $modules
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $modulesPayload = is_array($payload['modules'] ?? null) ? $payload['modules'] : [];
        $modules = [];
        foreach ($modulesPayload as $module) {
            if (is_array($module)) {
                $modules[] = ProvisionModuleDTO::fromArray($module);
            }
        }

        $credentialsPayload = is_array($payload['credentials'] ?? null) ? $payload['credentials'] : [];
        $credentials = [];
        foreach ($credentialsPayload as $credential) {
            if (is_array($credential)) {
                $credentials[] = ProvisionUserCredentialDTO::fromArray($credential);
            }
        }

        return new self(
            key: (string) ($payload['key'] ?? ''),
            externalId: isset($payload['externalId']) ? (string) $payload['externalId'] : null,
            code: (string) ($payload['code'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            description: (string) ($payload['description'] ?? ''),
            priceList: (float) ($payload['priceList'] ?? 0.0),
            defaultBillingCycle: (string) ($payload['defaultBillingCycle'] ?? ''),
            type: (string) ($payload['type'] ?? ''),
            accessUrl: isset($payload['accessUrl']) ? (string) $payload['accessUrl'] : null,
            logo: isset($payload['logo']) ? (string) $payload['logo'] : null,
            credentials: $credentials,
            modules: $modules
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $modules = [];
        foreach ($this->modules as $module) {
            $modules[] = $module->toArray();
        }

        $credentials = [];
        foreach ($this->credentials as $credential) {
            $credentials[] = $credential->toArray();
        }

        return [
            'key' => $this->key,
            'externalId' => $this->externalId,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'priceList' => $this->priceList,
            'defaultBillingCycle' => $this->defaultBillingCycle,
            'type' => $this->type,
            'accessUrl' => $this->accessUrl,
            'logo' => $this->logo,
            'credentials' => $credentials,
            'modules' => $modules,
        ];
    }
}
