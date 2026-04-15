<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;
use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ImpersonationRequestDTO
{
    public function __construct(
        public readonly string $resolveKey,
        public readonly string $projectCode,
        public readonly int $targetUserId,
        public readonly ?string $reason = null,
        public readonly ?string $requestedBy = null,
        public readonly ?string $requestedAt = null,
        public readonly ?int $durationMinutes = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $resolveKey = (string) ($payload['resolveKey'] ?? '');
        $projectCode = (string) ($payload['projectCode'] ?? '');
        $targetUserId = isset($payload['targetUserId']) ? (int) $payload['targetUserId'] : 0;
        $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;
        $requestedBy = isset($payload['requestedBy']) ? (string) $payload['requestedBy'] : null;
        $requestedAt = isset($payload['requestedAt']) ? (string) $payload['requestedAt'] : null;
        $durationMinutes = isset($payload['durationMinutes']) ? (int) $payload['durationMinutes'] : null;

        if ($resolveKey === '') {
            throw new InternalApiError(
                ErrorCode::VALIDATION_ERROR,
                'Invalid request payload.',
                ['errors' => [['field' => 'resolveKey', 'code' => 'required', 'message' => 'resolveKey is required.']]]
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
            resolveKey: $resolveKey,
            projectCode: $projectCode,
            targetUserId: $targetUserId,
            reason: $reason,
            requestedBy: $requestedBy,
            requestedAt: $requestedAt,
            durationMinutes: $durationMinutes,
        );
    }

    public function validateDurationPolicy(int $minDurationMinutes, int $maxDurationMinutes): ValidationResult
    {
        if ($this->durationMinutes === null) {
            return ValidationResult::success();
        }

        $errors = [];

        if ($this->durationMinutes < $minDurationMinutes) {
            $errors[] = [
                'field' => 'durationMinutes',
                'code' => 'min',
                'message' => sprintf('durationMinutes must be at least %d.', $minDurationMinutes),
            ];
        }

        if ($this->durationMinutes > $maxDurationMinutes) {
            $errors[] = [
                'field' => 'durationMinutes',
                'code' => 'max',
                'message' => sprintf('durationMinutes must not exceed %d.', $maxDurationMinutes),
            ];
        }

        if ($this->durationMinutes <= 0) {
            $errors[] = [
                'field' => 'durationMinutes',
                'code' => 'positive',
                'message' => 'durationMinutes must be a positive integer.',
            ];
        }

        if ($errors === []) {
            return ValidationResult::success();
        }

        return ValidationResult::failure($errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resolveKey' => $this->resolveKey,
            'projectCode' => $this->projectCode,
            'targetUserId' => $this->targetUserId,
            'reason' => $this->reason,
            'requestedBy' => $this->requestedBy,
            'requestedAt' => $this->requestedAt,
            'durationMinutes' => $this->durationMinutes,
        ];
    }
}
