<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantLifecycleResponseDTO;

interface TenantLifecycleAdapterInterface
{
    public function warnTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function suspendTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function activateTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function closeTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;

    public function reopenTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO;
}
