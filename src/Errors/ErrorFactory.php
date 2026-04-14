<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;

final class ErrorFactory
{
    public static function validationError(ValidationResult $vr): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::VALIDATION_ERROR,
            message: 'Validation failed.',
            details: ['errors' => $vr->errors()]
        );
    }

    public static function invalidSignature(): InternalApiError
    {
        return new InternalApiError(ErrorCode::INVALID_SIGNATURE, 'Invalid request signature.');
    }

    public static function requestExpired(): InternalApiError
    {
        return new InternalApiError(ErrorCode::REQUEST_EXPIRED, 'Request timestamp has expired.');
    }

    public static function nonceReplay(): InternalApiError
    {
        return new InternalApiError(ErrorCode::NONCE_REPLAY, 'Replay request detected.');
    }

    public static function tenantNotFound(string $appKey): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::TENANT_NOT_FOUND,
            message: 'Tenant not found.',
            details: ['app_key' => $appKey]
        );
    }

    public static function tenantAlreadyExists(string $appKey): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::TENANT_ALREADY_EXISTS,
            message: 'Tenant already exists.',
            details: ['app_key' => $appKey]
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function provisionFailed(?string $reason = null, array $details = []): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::PROVISION_FAILED,
            message: 'Tenant provisioning failed.',
            details: self::safeDetails($details, $reason)
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function dbCreateFailed(?string $reason = null, array $details = []): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::DB_CREATE_FAILED,
            message: 'Database creation failed.',
            details: self::safeDetails($details, $reason)
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function templateApplyFailed(?string $reason = null, array $details = []): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::TEMPLATE_APPLY_FAILED,
            message: 'Template apply failed.',
            details: self::safeDetails($details, $reason)
        );
    }

    public static function userNotFound(int $userId, string $appKey): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::USER_NOT_FOUND,
            message: 'Target user not found.',
            details: ['target_user_id' => $userId, 'app_key' => $appKey]
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function impersonationFailed(?string $reason = null, array $details = []): InternalApiError
    {
        return new InternalApiError(
            code: ErrorCode::IMPERSONATION_FAILED,
            message: 'Impersonation failed.',
            details: self::safeDetails($details, $reason)
        );
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private static function safeDetails(array $details, ?string $reason): array
    {
        if ($reason !== null && trim($reason) !== '') {
            $details['reason'] = trim($reason);
        }

        return self::sanitize($details);
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private static function sanitize(array $details): array
    {
        $blockedKeys = ['password', 'secret', 'token', 'connection_string', 'dsn', 'api_key'];
        $safe = [];

        foreach ($details as $key => $value) {
            $keyString = (string) $key;
            $lowerKey = strtolower($keyString);

            $isSensitive = false;
            foreach ($blockedKeys as $blockedKey) {
                if (str_contains($lowerKey, $blockedKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $safe[$keyString] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $safe[$keyString] = self::sanitize($value);

                continue;
            }

            if (is_scalar($value) || $value === null) {
                $safe[$keyString] = $value;

                continue;
            }

            $safe[$keyString] = '[non-scalar]';
        }

        return $safe;
    }
}
