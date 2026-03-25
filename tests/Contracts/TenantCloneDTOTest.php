<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantExportRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantExportResponseDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantImportRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantImportResponseDTO;

final class TenantCloneDTOTest extends TestCase
{
    public function testExportRequestFromArrayToArray(): void
    {
        $payload = [
            'appKey' => 'acme-app-001',
            'projectCode' => 'ACME',
            'reason' => 'MIGRATION',
        ];

        $dto = TenantExportRequestDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }

    public function testExportResponseToArray(): void
    {
        $dto = new TenantExportResponseDTO(
            appKey: 'acme-app-001',
            projectCode: 'ACME',
            dumpPath: '/mnt/backups/acme-20260325.sql.gz',
            checksum: 'sha256:3f4b8c',
            createdAt: '2026-03-25T10:35:00Z'
        );

        self::assertSame(
            [
                'appKey' => 'acme-app-001',
                'projectCode' => 'ACME',
                'dumpPath' => '/mnt/backups/acme-20260325.sql.gz',
                'checksum' => 'sha256:3f4b8c',
                'createdAt' => '2026-03-25T10:35:00Z',
            ],
            $dto->toArray()
        );
    }

    public function testImportRequestFromArrayToArray(): void
    {
        $payload = [
            'appKey' => 'acme-app-001',
            'projectCode' => 'ACME',
            'dumpPath' => '/mnt/backups/acme-20260325.sql.gz',
            'checksum' => 'sha256:3f4b8c',
        ];

        $dto = TenantImportRequestDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }

    public function testImportResponseToArray(): void
    {
        $dto = new TenantImportResponseDTO(
            appKey: 'acme-app-001',
            projectCode: 'ACME',
            database: 'acme_20260325',
            restored: true
        );

        self::assertSame(
            [
                'appKey' => 'acme-app-001',
                'projectCode' => 'ACME',
                'database' => 'acme_20260325',
                'restored' => true,
            ],
            $dto->toArray()
        );
    }
}
