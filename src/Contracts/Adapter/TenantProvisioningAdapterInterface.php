<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\ProvisionPayloadDTO;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ProvisionResponseDTO;

interface TenantProvisioningAdapterInterface
{
    public function createTenant(ProvisionPayloadDTO $dto): ProvisionResponseDTO;
}
