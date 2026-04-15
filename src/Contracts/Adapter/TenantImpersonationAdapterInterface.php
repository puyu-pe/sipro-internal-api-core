<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonableUserSearchRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonableUserSearchResponseDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationResponseDTO;

interface TenantImpersonationAdapterInterface
{
    public function searchImpersonableUsers(string $resolveKey, ImpersonableUserSearchRequestDTO $dto): ImpersonableUserSearchResponseDTO;

    public function impersonateUser(string $resolveKey, ImpersonationRequestDTO $dto): ImpersonationResponseDTO;
}
