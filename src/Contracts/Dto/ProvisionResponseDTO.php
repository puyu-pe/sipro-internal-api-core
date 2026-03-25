<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

final class ProvisionResponseDTO
{
    /**
     * @param array<string> $warnings
     */
    public function __construct(
        public readonly string $appKey,
        public readonly string $projectCode,
        public readonly string $database,
        public readonly string $status,
        public readonly string $provisionedAt,
        public readonly ?string $dbHost,
        public readonly bool $migrated,
        public readonly bool $seeded,
        public readonly bool $systemParametersUpdated,
        public readonly int $usersCreated,
        public readonly int $executionTimeMs,
        public readonly array $warnings = []
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $warnings = is_array($payload['warnings'] ?? null) ? $payload['warnings'] : [];
        $normalizedWarnings = [];
        foreach ($warnings as $warning) {
            if (is_string($warning)) {
                $normalizedWarnings[] = $warning;
            }
        }

        return new self(
            appKey: (string) ($payload['app_key'] ?? ''),
            projectCode: (string) ($payload['project_code'] ?? ''),
            database: (string) ($payload['database'] ?? ''),
            status: (string) ($payload['status'] ?? ''),
            provisionedAt: (string) ($payload['provisioned_at'] ?? ''),
            dbHost: isset($payload['db_host']) ? (string) $payload['db_host'] : null,
            migrated: (bool) ($payload['migrated'] ?? false),
            seeded: (bool) ($payload['seeded'] ?? false),
            systemParametersUpdated: (bool) ($payload['system_parameters_updated'] ?? false),
            usersCreated: (int) ($payload['users_created'] ?? 0),
            executionTimeMs: (int) ($payload['execution_time_ms'] ?? 0),
            warnings: $normalizedWarnings
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'app_key' => $this->appKey,
            'project_code' => $this->projectCode,
            'database' => $this->database,
            'status' => $this->status,
            'provisioned_at' => $this->provisionedAt,
            'db_host' => $this->dbHost,
            'migrated' => $this->migrated,
            'seeded' => $this->seeded,
            'system_parameters_updated' => $this->systemParametersUpdated,
            'users_created' => $this->usersCreated,
            'execution_time_ms' => $this->executionTimeMs,
            'warnings' => $this->warnings,
        ];
    }
}
