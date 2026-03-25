<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class TenantImportRequestDTO
{
    public function __construct(
        public readonly string $appKey,
        public readonly string $projectCode,
        public readonly string $dumpPath,
        public readonly string $checksum
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
            dumpPath: (string) ($payload['dumpPath'] ?? ''),
            checksum: (string) ($payload['checksum'] ?? '')
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
            'dumpPath' => $this->dumpPath,
            'checksum' => $this->checksum,
        ];
    }
}
