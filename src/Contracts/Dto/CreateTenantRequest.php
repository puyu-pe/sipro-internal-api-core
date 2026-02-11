<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Dto;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class CreateTenantRequest
{
    /**
     * @param array{email?: mixed, name?: mixed, temp_password?: mixed, set_password_token?: mixed} $adminUser
     * @param array{timezone: string, currency: string, igv_rate: float, tax_mode?: string|null} $localeConfig
     * @param array{enabled?: bool}|null $seriesConfig
     * @param array{max_users?: mixed, max_branches?: mixed, max_docs_month?: mixed}|null $limits
     * @param array<string, mixed>|null $features
     */
    public function __construct(
        public readonly string $tenantUuid,
        public readonly string $tenantName,
        public readonly ?string $ruc,
        public readonly ?string $planCode,
        public readonly ?string $billingStatus,
        public readonly array $adminUser,
        public readonly array $localeConfig,
        public readonly ?array $seriesConfig,
        public readonly ?array $limits,
        public readonly ?array $features,
        public readonly ?string $notes
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $adminUser = is_array($payload['admin_user'] ?? null) ? $payload['admin_user'] : [];
        $localePayload = is_array($payload['locale_config'] ?? null) ? $payload['locale_config'] : [];
        $seriesConfig = is_array($payload['series_config'] ?? null) ? $payload['series_config'] : null;
        $limits = is_array($payload['limits'] ?? null) ? $payload['limits'] : null;
        $features = is_array($payload['features'] ?? null) ? $payload['features'] : null;

        return new self(
            tenantUuid: trim((string) ($payload['tenant_uuid'] ?? '')),
            tenantName: trim((string) ($payload['tenant_name'] ?? '')),
            ruc: self::nullableString($payload['ruc'] ?? null),
            planCode: self::nullableString($payload['plan_code'] ?? null),
            billingStatus: self::nullableString($payload['billing_status'] ?? null),
            adminUser: [
                'email' => self::nullableString($adminUser['email'] ?? null),
                'name' => self::nullableString($adminUser['name'] ?? null),
                'temp_password' => self::nullableString($adminUser['temp_password'] ?? null),
                'set_password_token' => self::nullableString($adminUser['set_password_token'] ?? null),
            ],
            localeConfig: [
                'timezone' => self::nullableString($localePayload['timezone'] ?? null) ?? 'America/Lima',
                'currency' => self::nullableString($localePayload['currency'] ?? null) ?? 'PEN',
                'igv_rate' => self::toFloat($localePayload['igv_rate'] ?? 0.18),
                'tax_mode' => self::nullableString($localePayload['tax_mode'] ?? null),
            ],
            seriesConfig: self::normalizeSeriesConfig($seriesConfig),
            limits: self::normalizeLimits($limits),
            features: self::normalizeFeatures($features),
            notes: self::nullableString($payload['notes'] ?? null),
        );
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $this->tenantUuid)) {
            $errors[] = self::error('tenant_uuid', 'invalid_uuid_v4', 'tenant_uuid must be a valid UUID v4.');
        }

        $nameLength = strlen($this->tenantName);
        if ($nameLength < 3 || $nameLength > 150) {
            $errors[] = self::error('tenant_name', 'invalid_length', 'tenant_name must contain between 3 and 150 characters.');
        }

        if ($this->ruc !== null && $this->ruc !== '' && !preg_match('/^\d{11}$/', $this->ruc)) {
            $errors[] = self::error('ruc', 'invalid_ruc', 'ruc must contain exactly 11 digits when present.');
        }

        $adminName = trim((string) ($this->adminUser['name'] ?? ''));
        $adminEmail = trim((string) ($this->adminUser['email'] ?? ''));
        $tempPassword = trim((string) ($this->adminUser['temp_password'] ?? ''));
        $setPasswordToken = trim((string) ($this->adminUser['set_password_token'] ?? ''));

        if ($adminName === '') {
            $errors[] = self::error('admin_user.name', 'required', 'admin_user.name is required.');
        }

        if ($adminEmail === '') {
            $errors[] = self::error('admin_user.email', 'required', 'admin_user.email is required.');
        } elseif (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = self::error('admin_user.email', 'invalid_email', 'admin_user.email must be a valid email address.');
        }

        if ($tempPassword === '' && $setPasswordToken === '') {
            $errors[] = self::error(
                'admin_user',
                'password_or_token_required',
                'admin_user.temp_password or admin_user.set_password_token must be provided.'
            );
        }

        if ($this->seriesConfig !== null && ($this->seriesConfig['enabled'] ?? null) !== null && !is_bool($this->seriesConfig['enabled'])) {
            $errors[] = self::error('series_config.enabled', 'invalid_type', 'series_config.enabled must be a boolean.');
        }

        if ($this->limits !== null) {
            foreach (['max_users', 'max_branches', 'max_docs_month'] as $field) {
                $value = $this->limits[$field] ?? null;
                if ($value !== null && (!is_int($value) || $value < 0)) {
                    $errors[] = self::error("limits.{$field}", 'invalid_integer', "limits.{$field} must be an integer >= 0.");
                }
            }
        }

        if ($this->features !== null) {
            foreach ($this->features as $key => $value) {
                if (!is_bool($value)) {
                    $errors[] = self::error("features.{$key}", 'invalid_boolean', "features.{$key} must be boolean.");
                }
            }
        }

        if ($this->localeConfig['timezone'] === '') {
            $errors[] = self::error('locale_config.timezone', 'required', 'locale_config.timezone cannot be empty.');
        }

        if ($this->localeConfig['currency'] === '') {
            $errors[] = self::error('locale_config.currency', 'required', 'locale_config.currency cannot be empty.');
        }

        if (!is_numeric($this->localeConfig['igv_rate'])) {
            $errors[] = self::error('locale_config.igv_rate', 'invalid_number', 'locale_config.igv_rate must be numeric.');
        }

        return $errors === [] ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_uuid' => $this->tenantUuid,
            'tenant_name' => $this->tenantName,
            'ruc' => $this->ruc,
            'plan_code' => $this->planCode,
            'billing_status' => $this->billingStatus,
            'admin_user' => $this->adminUser,
            'locale_config' => $this->localeConfig,
            'series_config' => $this->seriesConfig,
            'limits' => $this->limits,
            'features' => $this->features,
            'notes' => $this->notes,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    /**
     * @param array<string, mixed>|null $seriesConfig
     * @return array{enabled?: bool}|null
     */
    private static function normalizeSeriesConfig(?array $seriesConfig): ?array
    {
        if ($seriesConfig === null) {
            return null;
        }

        $enabled = $seriesConfig['enabled'] ?? null;

        return ['enabled' => $enabled];
    }

    /**
     * @param array<string, mixed>|null $limits
     * @return array{max_users?: mixed, max_branches?: mixed, max_docs_month?: mixed}|null
     */
    private static function normalizeLimits(?array $limits): ?array
    {
        if ($limits === null) {
            return null;
        }

        return [
            'max_users' => $limits['max_users'] ?? null,
            'max_branches' => $limits['max_branches'] ?? null,
            'max_docs_month' => $limits['max_docs_month'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed>|null $features
     * @return array<string, mixed>|null
     */
    private static function normalizeFeatures(?array $features): ?array
    {
        if ($features === null) {
            return null;
        }

        $normalized = [];
        foreach ($features as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    private static function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return 0.18;
    }

    /**
     * @return array{field: string, code: string, message: string}
     */
    private static function error(string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}
