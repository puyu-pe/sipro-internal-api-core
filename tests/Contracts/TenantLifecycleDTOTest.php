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
            'requestedBy' => null,
        ];

        $dto = TenantLifecycleRequestDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }

    public function testRequestFromArrayWithRequestedBy(): void
    {
        $payload = [
            'appKey' => 'acme-app-001',
            'projectCode' => 'ACME',
            'reason' => 'CLOSURE_REQUESTED',
            'requestedAt' => '2026-04-10T12:00:00Z',
            'requestedBy' => 'user-42',
        ];

        $dto = TenantLifecycleRequestDTO::fromArray($payload);

        self::assertSame('user-42', $dto->requestedBy);
        self::assertSame($payload, $dto->toArray());
    }

    public function testRequestFromArrayWithoutRequestedBy(): void
    {
        $payload = [
            'appKey' => 'acme-app-001',
            'projectCode' => 'ACME',
            'reason' => null,
            'requestedAt' => null,
        ];

        $dto = TenantLifecycleRequestDTO::fromArray($payload);

        self::assertNull($dto->requestedBy);
        self::assertSame(
            array_merge($payload, ['requestedBy' => null]),
            $dto->toArray()
        );
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
