<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ImpersonableUserListItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $fullname,
    ) {
    }

    /**
     * @return array{id: int, username: string, fullname: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'fullname' => $this->fullname,
        ];
    }
}
