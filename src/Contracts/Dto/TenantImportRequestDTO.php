<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\RequestValidation;

final class TenantImportRequestDTO
{
    public function __construct(
        public readonly string $resolveKey,
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
        RequestValidation::tenant($payload);
        RequestValidation::requiredString($payload, 'dumpPath');
        RequestValidation::requiredString($payload, 'checksum');
        if (!preg_match('/^[a-f0-9]{64}$/D', $payload['checksum'])) {
            RequestValidation::fail('checksum', 'format', 'checksum must be a lowercase SHA-256 digest.');
        }
        return new self(
            resolveKey: (string) ($payload['resolveKey'] ?? ''),
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
            'resolveKey' => $this->resolveKey,
            'projectCode' => $this->projectCode,
            'dumpPath' => $this->dumpPath,
            'checksum' => $this->checksum,
        ];
    }
}
