<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ImpersonationResponseDTO;

interface TenantImpersonationAdapterInterface
{
    public function impersonateUser(string $appKey, ImpersonationRequestDTO $dto): ImpersonationResponseDTO;
}
