<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Contracts\Validation;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiException;

final class RequestValidation
{
    public static function fail(string $field, string $code, string $message): never
    {
        throw new InternalApiException(new InternalApiError(ErrorCode::VALIDATION_ERROR,
            'Invalid request payload.', ['errors' => [compact('field', 'code', 'message')]]));
    }

    public static function identity(array $payload, string $prefix = ''): void
    {
        foreach (['appKey', 'app_key', 'resolve_key'] as $legacy) {
            if (array_key_exists($legacy, $payload)) {
                self::fail($prefix . $legacy, 'unsupported', 'Use resolveKey.');
            }
        }
        self::requiredString($payload, 'resolveKey', $prefix);
    }

    public static function tenant(array $payload): void
    {
        self::identity($payload);
        self::requiredString($payload, 'projectCode');
        foreach (['reason', 'requestedBy', 'requestedAt'] as $field) {
            self::optionalString($payload, $field);
        }
    }

    public static function requiredString(array $payload, string $field, string $prefix = ''): void
    {
        if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
            self::fail($prefix . $field, 'required', "$field must be a nonempty string.");
        }
    }

    public static function optionalString(array $payload, string $field): void
    {
        if (isset($payload[$field]) && !is_string($payload[$field])) {
            self::fail($field, 'type', "$field must be a string or null.");
        }
    }

    public static function integer(array $payload, string $field, bool $required = false): void
    {
        if (($required && !isset($payload[$field])) || (isset($payload[$field]) && !is_int($payload[$field]))) {
            self::fail($field, 'type', "$field must be an integer.");
        }
    }

    public static function assertResult(ValidationResult $result): void
    {
        if (!$result->ok()) {
            throw new InternalApiException(new InternalApiError(ErrorCode::VALIDATION_ERROR,
                'Invalid request payload.', ['errors' => $result->errors()]));
        }
    }

    /** Validate supplied fields before DTO construction can coerce their types. */
    public static function fields(array $payload, array $types, string $prefix = ''): void
    {
        foreach ($types as $field => $type) {
            if (!array_key_exists($field, $payload)) { continue; }
            $value = $payload[$field];
            if ($value === null && str_starts_with($type, '?')) { continue; }
            $valid = match (ltrim($type, '?')) {
                'string' => is_string($value),
                'int' => is_int($value),
                'number' => is_int($value) || (is_float($value) && is_finite($value)),
                'bool' => is_bool($value),
                'array' => is_array($value),
                default => false,
            };
            if (!$valid) { self::fail($prefix . $field, 'type', "$field has an invalid type."); }
        }
    }

    public static function objectList(array $payload, string $field, string $prefix = ''): void
    {
        if (!array_key_exists($field, $payload)) { return; }
        if (!is_array($payload[$field]) || !array_is_list($payload[$field])) {
            self::fail($prefix . $field, 'type', "$field must be a list.");
        }
        foreach ($payload[$field] as $index => $item) {
            if (!is_array($item) || ($item !== [] && array_is_list($item))) {
                self::fail($prefix . $field . '.' . $index, 'type', 'Expected an object.');
            }
        }
    }
}
