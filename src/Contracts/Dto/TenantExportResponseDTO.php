<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class TenantExportResponseDTO
{
    public function __construct(
        public readonly string $resolveKey,
        public readonly string $projectCode,
        public readonly string $dumpPath,
        public readonly string $checksum,
        public readonly string $createdAt
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resolveKey' => $this->resolveKey,
            'projectCode' => $this->projectCode,
            'dumpPath' => $this->dumpPath,
            'checksum' => $this->checksum,
            'createdAt' => $this->createdAt,
        ];
    }
}
