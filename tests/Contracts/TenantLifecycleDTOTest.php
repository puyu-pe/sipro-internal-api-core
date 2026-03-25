<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleResponseDTO;

final class TenantLifecycleDTOTest extends TestCase
{
    public function testRequestFromArrayToArray(): void
    {
        $payload = [
            'appKey' => 'acme-app-001',
            'projectCode' => 'ACME',
            'reason' => 'PAYMENT_OVERDUE',
            'requestedAt' => '2026-03-25T10:00:00Z',
        ];

        $dto = TenantLifecycleRequestDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }

    public function testResponseToArray(): void
    {
        $dto = new TenantLifecycleResponseDTO(
            appKey: 'acme-app-001',
            projectCode: 'ACME',
            status: 'ok',
            systemStatus: 'suspended'
        );

        self::assertSame(
            [
                'appKey' => 'acme-app-001',
                'projectCode' => 'ACME',
                'status' => 'ok',
                'systemStatus' => 'suspended',
            ],
            $dto->toArray()
        );
    }
}
