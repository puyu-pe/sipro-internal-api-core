<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantExportRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantExportResponseDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantImportRequestDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\TenantImportResponseDTO;

interface TenantCloneAdapterInterface
{
    public function exportTenant(string $appKey, TenantExportRequestDTO $dto): TenantExportResponseDTO;

    public function importTenant(string $appKey, TenantImportRequestDTO $dto): TenantImportResponseDTO;
}
