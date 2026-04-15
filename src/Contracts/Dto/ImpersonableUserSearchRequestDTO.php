<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;
use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ImpersonableUserSearchRequestDTO
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    public function __construct(
        public readonly string $resolveKey,
        public readonly string $projectCode,
        public readonly ?string $query = null,
        public readonly int $page = 1,
        public readonly int $perPage = self::DEFAULT_PER_PAGE,
        public readonly ?string $requestedBy = null,
        public readonly ?string $requestedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $resolveKey = (string) ($payload['resolveKey'] ?? '');
        $projectCode = (string) ($payload['projectCode'] ?? '');
        $query = isset($payload['query']) ? trim((string) $payload['query']) : null;
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;
        $perPage = isset($payload['perPage']) ? (int) $payload['perPage'] : self::DEFAULT_PER_PAGE;
        $requestedBy = isset($payload['requestedBy']) ? (string) $payload['requestedBy'] : null;
        $requestedAt = isset($payload['requestedAt']) ? (string) $payload['requestedAt'] : null;

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

        return new self(
            resolveKey: $resolveKey,
            projectCode: $projectCode,
            query: $query === '' ? null : $query,
            page: $page,
            perPage: $perPage,
            requestedBy: $requestedBy,
            requestedAt: $requestedAt,
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if ($this->page < 1) {
            $errors[] = [
                'field' => 'page',
                'code' => 'min',
                'message' => 'page must be at least 1.',
            ];
        }

        if ($this->perPage < 1) {
            $errors[] = [
                'field' => 'perPage',
                'code' => 'min',
                'message' => 'perPage must be at least 1.',
            ];
        }

        if ($this->perPage > self::MAX_PER_PAGE) {
            $errors[] = [
                'field' => 'perPage',
                'code' => 'max',
                'message' => sprintf('perPage must not exceed %d.', self::MAX_PER_PAGE),
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
            'query' => $this->query,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'requestedBy' => $this->requestedBy,
            'requestedAt' => $this->requestedAt,
        ];
    }
}
