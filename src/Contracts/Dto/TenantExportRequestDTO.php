<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class TenantExportRequestDTO
{
    public function __construct(
        public readonly string $resolveKey,
        public readonly string $projectCode,
        public readonly ?string $reason
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            resolveKey: (string) ($payload['resolveKey'] ?? ''),
            projectCode: (string) ($payload['projectCode'] ?? ''),
            reason: isset($payload['reason']) ? (string) $payload['reason'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resolveKey' => $this->resolveKey,
            'projectCode' => $this->projectCode,
            'reason' => $this->reason,
        ];
    }
}
