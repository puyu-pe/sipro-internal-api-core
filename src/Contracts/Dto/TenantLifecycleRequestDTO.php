<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class TenantLifecycleRequestDTO
{
    public function __construct(
        public readonly string $appKey,
        public readonly string $projectCode,
        public readonly ?string $reason,
        public readonly ?string $requestedAt,
        public readonly ?string $requestedBy = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            appKey: (string) ($payload['appKey'] ?? ''),
            projectCode: (string) ($payload['projectCode'] ?? ''),
            reason: isset($payload['reason']) ? (string) $payload['reason'] : null,
            requestedAt: isset($payload['requestedAt']) ? (string) $payload['requestedAt'] : null,
            requestedBy: isset($payload['requestedBy']) ? (string) $payload['requestedBy'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'appKey' => $this->appKey,
            'projectCode' => $this->projectCode,
            'reason' => $this->reason,
            'requestedAt' => $this->requestedAt,
            'requestedBy' => $this->requestedBy,
        ];
    }
}
