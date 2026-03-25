<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ProvisionResponseDTO;

final class ProvisionResponseDTOTest extends TestCase
{
    public function testFromArrayNormalizesWarnings(): void
    {
        $payload = [
            'app_key' => 'acme-app-001',
            'project_code' => 'ACME',
            'database' => 'acme_20260325',
            'status' => 'ok',
            'provisioned_at' => '2026-03-25T10:00:00Z',
            'db_host' => 'db.internal',
            'migrated' => true,
            'seeded' => true,
            'system_parameters_updated' => false,
            'users_created' => 3,
            'execution_time_ms' => 1200,
            'warnings' => ['low disk', 123, null],
        ];

        $dto = ProvisionResponseDTO::fromArray($payload);

        $expected = $payload;
        $expected['warnings'] = ['low disk'];

        self::assertSame($expected, $dto->toArray());
    }
}
