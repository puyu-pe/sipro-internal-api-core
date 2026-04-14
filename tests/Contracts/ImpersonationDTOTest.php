<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationResponseDTO;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ImpersonationDTOTest extends TestCase
{
    public function testRequestFromArrayToArrayWithAllFields(): void
    {
        $payload = [
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 42,
            'reason' => 'support investigation',
            'requestedBy' => 'admin-1',
            'requestedAt' => '2026-04-14T10:00:00Z',
        ];

        $dto = ImpersonationRequestDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }

    public function testRequestFromArrayWithOptionalFieldsNull(): void
    {
        $payload = [
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
        ];

        $dto = ImpersonationRequestDTO::fromArray($payload);

        self::assertNull($dto->reason);
        self::assertNull($dto->requestedBy);
        self::assertNull($dto->requestedAt);
    }

    public function testFromArraysThrowsForEmptyAppKey(): void
    {
        $this->expectException(InternalApiError::class);
        ImpersonationRequestDTO::fromArray([
            'appKey' => '',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
        ]);
    }

    public function testFromArraysThrowsForEmptyProjectCode(): void
    {
        $this->expectException(InternalApiError::class);
        ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => '',
            'targetUserId' => 1,
        ]);
    }

    public function testFromArraysThrowsForTargetUserIdZero(): void
    {
        $this->expectException(InternalApiError::class);
        ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 0,
        ]);
    }

    public function testFromArraysThrowsForNegativeTargetUserId(): void
    {
        $this->expectException(InternalApiError::class);
        ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => -1,
        ]);
    }

    public function testFromArraysSucceedsWithTargetUserIdOne(): void
    {
        $dto = ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
        ]);

        self::assertSame(1, $dto->targetUserId);
    }

    public function testResponseToArray(): void
    {
        $dto = new ImpersonationResponseDTO(
            appKey: 'yubus-app-001',
            projectCode: 'YUBUS',
            status: 'impersonation_ready',
            accessUrl: '/support/enter/abc123',
        );

        self::assertSame(
            [
                'appKey' => 'yubus-app-001',
                'projectCode' => 'YUBUS',
                'status' => 'impersonation_ready',
                'accessUrl' => '/support/enter/abc123',
            ],
            $dto->toArray()
        );
    }
}
