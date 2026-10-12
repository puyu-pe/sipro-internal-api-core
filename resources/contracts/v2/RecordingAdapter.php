<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Conformance;

use PuyuPe\SiproInternalApiCore\Contracts\Adapter\{TenantProvisioningAdapterInterface, TenantLifecycleAdapterInterface, TenantCloneAdapterInterface, TenantImpersonationAdapterInterface};
use PuyuPe\SiproInternalApiCore\Contracts\Dto\{ProvisionPayloadDTO, ProvisionResponseDTO, TenantLifecycleRequestDTO, TenantLifecycleResponseDTO, TenantExportRequestDTO, TenantExportResponseDTO, TenantImportRequestDTO, TenantImportResponseDTO, ImpersonableUserSearchRequestDTO, ImpersonableUserSearchResponseDTO, ImpersonationRequestDTO, ImpersonationResponseDTO};

/** Deterministic application boundary used by every bridge's conformance suite. */
final class RecordingAdapter implements TenantProvisioningAdapterInterface, TenantLifecycleAdapterInterface, TenantCloneAdapterInterface, TenantImpersonationAdapterInterface
{
    public array $calls = [];
    public ?\Throwable $failure = null;

    private function record(string $operation, object $dto): void
    {
        $this->calls[] = [$operation, $dto];
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }

    public function createTenant(ProvisionPayloadDTO $dto): ProvisionResponseDTO
    {
        $this->record('create', $dto);
        return new ProvisionResponseDTO($dto->project->resolveKey, $dto->project->code, 'tenant_db', 'created', '2026-01-01T00:00:00Z', null, true, true, true, 1, 10);
    }

    private function lifecycle(string $operation, string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO
    {
        $this->record($operation, $dto);
        return new TenantLifecycleResponseDTO($resolveKey, $dto->projectCode, 'ok', $operation);
    }

    public function warnTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO { return $this->lifecycle('warn', $resolveKey, $dto); }
    public function suspendTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO { return $this->lifecycle('suspend', $resolveKey, $dto); }
    public function activateTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO { return $this->lifecycle('activate', $resolveKey, $dto); }
    public function closeTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO { return $this->lifecycle('close', $resolveKey, $dto); }
    public function reopenTenant(string $resolveKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO { return $this->lifecycle('reopen', $resolveKey, $dto); }

    public function exportTenant(string $resolveKey, TenantExportRequestDTO $dto): TenantExportResponseDTO
    {
        $this->record('export', $dto);
        return new TenantExportResponseDTO($resolveKey, $dto->projectCode, '/dumps/tenant.sql.gz', str_repeat('a', 64), '2026-01-01T00:00:00Z');
    }

    public function importTenant(string $resolveKey, TenantImportRequestDTO $dto): TenantImportResponseDTO
    {
        $this->record('import', $dto);
        return new TenantImportResponseDTO($resolveKey, $dto->projectCode, 'tenant_db', true);
    }

    public function searchImpersonableUsers(string $resolveKey, ImpersonableUserSearchRequestDTO $dto): ImpersonableUserSearchResponseDTO
    {
        $this->record('search', $dto);
        return new ImpersonableUserSearchResponseDTO($resolveKey, $dto->projectCode, [], $dto->page, $dto->perPage, 0, false);
    }

    public function impersonateUser(string $resolveKey, ImpersonationRequestDTO $dto): ImpersonationResponseDTO
    {
        $this->record('impersonate', $dto);
        return new ImpersonationResponseDTO($resolveKey, $dto->projectCode, 'ready', '/support/token', $dto->durationMinutes);
    }
}
