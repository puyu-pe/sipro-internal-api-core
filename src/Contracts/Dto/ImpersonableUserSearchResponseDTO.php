<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ImpersonableUserSearchResponseDTO
{
    /**
     * @param list<ImpersonableUserListItemDTO> $users
     */
    public function __construct(
        public readonly string $resolveKey,
        public readonly string $projectCode,
        public readonly array $users,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly bool $hasNextPage,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resolveKey' => $this->resolveKey,
            'projectCode' => $this->projectCode,
            'users' => array_map(
                static fn (ImpersonableUserListItemDTO $user): array => $user->toArray(),
                $this->users,
            ),
            'pagination' => [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'total' => $this->total,
                'hasNextPage' => $this->hasNextPage,
            ],
        ];
    }
}
