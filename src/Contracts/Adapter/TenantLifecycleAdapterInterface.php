<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleResponseDTO;

interface TenantLifecycleAdapterInterface
{
    public function warnTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function suspendTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function activateTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;
}
