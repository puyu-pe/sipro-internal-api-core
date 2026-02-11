<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

final class ErrorFactory
{
    public static function invalidRequest(string $message = 'Invalid request payload.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::INVALID_REQUEST, $message, 422);
    }

    public static function unauthorized(string $message = 'Missing or invalid authentication headers.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::UNAUTHORIZED, $message, 401);
    }

    public static function forbidden(string $message = 'You are not allowed to perform this action.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::FORBIDDEN, $message, 403);
    }

    public static function notFound(string $message = 'Resource not found.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::NOT_FOUND, $message, 404);
    }

    public static function conflict(string $message = 'Conflict detected for requested operation.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::CONFLICT, $message, 409);
    }

    public static function rateLimited(string $message = 'Too many requests.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::RATE_LIMITED, $message, 429);
    }

    public static function internal(string $message = 'Unexpected internal error.'): InternalApiError
    {
        return new InternalApiError(ErrorCode::INTERNAL_ERROR, $message, 500);
    }
}
