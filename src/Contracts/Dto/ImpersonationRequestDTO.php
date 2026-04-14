<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ImpersonationRequestDTO
{
    public function __construct(
        public readonly string $appKey,
        public readonly string $projectCode,
        public readonly int $targetUserId,
        public readonly ?string $reason = null,
        public readonly ?string $requestedBy = null,
        public readonly ?string $requestedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $appKey = (string) ($payload['appKey'] ?? '');
        $projectCode = (string) ($payload['projectCode'] ?? '');
        $targetUserId = isset($payload['targetUserId']) ? (int) $payload['targetUserId'] : 0;
        $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;
        $requestedBy = isset($payload['requestedBy']) ? (string) $payload['requestedBy'] : null;
        $requestedAt = isset($payload['requestedAt']) ? (string) $payload['requestedAt'] : null;

        if ($appKey === '') {
            throw new InternalApiError(
                ErrorCode::VALIDATION_ERROR,
                'Invalid request payload.',
                ['errors' => [['field' => 'appKey', 'code' => 'required', 'message' => 'appKey is required.']]]
            );
        }

        if ($projectCode === '') {
            throw new InternalApiError(
                ErrorCode::VALIDATION_ERROR,
                'Invalid request payload.',
                ['errors' => [['field' => 'projectCode', 'code' => 'required', 'message' => 'projectCode is required.']]]
            );
        }

        if ($targetUserId <= 0) {
            throw new InternalApiError(
                ErrorCode::VALIDATION_ERROR,
                'Invalid request payload.',
                ['errors' => [['field' => 'targetUserId', 'code' => 'required', 'message' => 'targetUserId must be a positive integer.']]]
            );
        }

        return new self(
            appKey: $appKey,
            projectCode: $projectCode,
            targetUserId: $targetUserId,
            reason: $reason,
            requestedBy: $requestedBy,
            requestedAt: $requestedAt,
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
            'targetUserId' => $this->targetUserId,
            'reason' => $this->reason,
            'requestedBy' => $this->requestedBy,
            'requestedAt' => $this->requestedAt,
        ];
    }
}
