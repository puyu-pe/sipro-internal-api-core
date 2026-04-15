<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ImpersonationResponseDTO
{
    public function __construct(
        public readonly string $appKey,
        public readonly string $projectCode,
        public readonly string $status,
        public readonly string $accessUrl,
        public readonly ?int $effectiveDurationMinutes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'appKey' => $this->appKey,
            'projectCode' => $this->projectCode,
            'status' => $this->status,
            'accessUrl' => $this->accessUrl,
            'effectiveDurationMinutes' => $this->effectiveDurationMinutes,
        ];
    }
}
