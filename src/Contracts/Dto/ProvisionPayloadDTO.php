<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionPayloadDTO
{
    /**
     * @param array<ProvisionServiceDTO> $services
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly ProvisionProjectDTO $project,
        public readonly ProvisionClientDTO $client,
        public readonly array $services,
        public readonly ?array $metadata = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $servicesPayload = is_array($payload['services'] ?? null) ? $payload['services'] : [];
        $services = [];
        foreach ($servicesPayload as $service) {
            if (is_array($service)) {
                $services[] = ProvisionServiceDTO::fromArray($service);
            }
        }

        $metadata = $payload['metadata'] ?? null;

        return new self(
            project: ProvisionProjectDTO::fromArray(is_array($payload['project'] ?? null) ? $payload['project'] : []),
            client: ProvisionClientDTO::fromArray(is_array($payload['client'] ?? null) ? $payload['client'] : []),
            services: $services,
            metadata: is_array($metadata) ? $metadata : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $services = [];
        foreach ($this->services as $service) {
            $services[] = $service->toArray();
        }

        $payload = [
            'project' => $this->project->toArray(),
            'client' => $this->client->toArray(),
            'services' => $services,
        ];

        if ($this->metadata !== null) {
            $payload['metadata'] = $this->metadata;
        }

        return $payload;
    }
}
