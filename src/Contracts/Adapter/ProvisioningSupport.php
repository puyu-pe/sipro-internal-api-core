<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Adapter;

use PuyuPe\SiproInternalApiCore\Contracts\Dto\{ProvisionPayloadDTO, ProvisionServiceDTO};

trait ProvisioningSupport
{
    protected function databasePrefix(): string
    {
        throw new \LogicException('Define databasePrefix() or override buildDatabaseName() before generating a database name.');
    }
    abstract protected function environmentSuffix(): string;

    protected function buildDatabaseName(string $projectCode): string
    {
        $name = implode('_', [$this->databasePrefix(), $this->environmentSuffix(), $projectCode]);
        return trim(strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $name)), '_');
    }

    protected function findServiceByKey(ProvisionPayloadDTO $dto, string $key): ?ProvisionServiceDTO
    {
        foreach ($dto->services as $service) {
            if ($service->key === $key) { return $service; }
        }
        return null;
    }
}
