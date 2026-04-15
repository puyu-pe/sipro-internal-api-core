<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonableUserListItemDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonableUserSearchRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonableUserSearchResponseDTO;
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
            'durationMinutes' => 30,
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
        self::assertNull($dto->durationMinutes);
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

    public function testImpersonationRequestValidationAcceptsMissingDuration(): void
    {
        $dto = ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
        ]);

        self::assertTrue($dto->validateDurationPolicy(5, 60)->ok());
    }

    public function testImpersonationRequestValidationRejectsDurationBelowPolicy(): void
    {
        $dto = ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
            'durationMinutes' => 4,
        ]);

        $validation = $dto->validateDurationPolicy(5, 60);

        self::assertFalse($validation->ok());
        self::assertSame('durationMinutes', $validation->errors()[0]['field']);
    }

    public function testImpersonationRequestValidationRejectsDurationAbovePolicy(): void
    {
        $dto = ImpersonationRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'targetUserId' => 1,
            'durationMinutes' => 61,
        ]);

        self::assertFalse($dto->validateDurationPolicy(5, 60)->ok());
    }

    public function testSearchRequestTrimsQueryAndUsesDefaultPagination(): void
    {
        $dto = ImpersonableUserSearchRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'query' => '  juan  ',
        ]);

        self::assertSame('juan', $dto->query);
        self::assertSame(1, $dto->page);
        self::assertSame(ImpersonableUserSearchRequestDTO::DEFAULT_PER_PAGE, $dto->perPage);
        self::assertTrue($dto->validate()->ok());
    }

    public function testSearchRequestAllowsEmptyQueryButKeepsBoundedPagination(): void
    {
        $dto = ImpersonableUserSearchRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'query' => '   ',
            'page' => 2,
            'perPage' => 10,
        ]);

        self::assertNull($dto->query);
        self::assertTrue($dto->validate()->ok());
    }

    public function testSearchRequestValidationRejectsInvalidPagination(): void
    {
        $dto = ImpersonableUserSearchRequestDTO::fromArray([
            'appKey' => 'yubus-app-001',
            'projectCode' => 'YUBUS',
            'page' => 0,
            'perPage' => 51,
        ]);

        $validation = $dto->validate();

        self::assertFalse($validation->ok());
        self::assertCount(2, $validation->errors());
    }

    public function testResponseToArray(): void
    {
        $dto = new ImpersonationResponseDTO(
            appKey: 'yubus-app-001',
            projectCode: 'YUBUS',
            status: 'impersonation_ready',
            accessUrl: '/support/enter/abc123',
            effectiveDurationMinutes: 30,
        );

        self::assertSame(
            [
                'appKey' => 'yubus-app-001',
                'projectCode' => 'YUBUS',
                'status' => 'impersonation_ready',
                'accessUrl' => '/support/enter/abc123',
                'effectiveDurationMinutes' => 30,
            ],
            $dto->toArray()
        );
    }

    public function testSearchResponseToArray(): void
    {
        $dto = new ImpersonableUserSearchResponseDTO(
            appKey: 'yubus-app-001',
            projectCode: 'YUBUS',
            users: [
                new ImpersonableUserListItemDTO(42, 'jgarcia', 'Juan Garcia'),
            ],
            page: 1,
            perPage: 20,
            total: 1,
            hasNextPage: false,
        );

        self::assertSame(
            [
                'appKey' => 'yubus-app-001',
                'projectCode' => 'YUBUS',
                'users' => [
                    [
                        'id' => 42,
                        'username' => 'jgarcia',
                        'fullname' => 'Juan Garcia',
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'perPage' => 20,
                    'total' => 1,
                    'hasNextPage' => false,
                ],
            ],
            $dto->toArray()
        );
    }
}
