<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http\Response;

use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiError;

final class ApiResponse
{
    public function __construct(public readonly int $status, public readonly array $body, public readonly array $headers = [])
    {
    }

    public static function error(InternalApiError $error): self
    {
        $status = match ($error->code) {
            ErrorCode::VALIDATION_ERROR, ErrorCode::QUERY_NOT_ALLOWED, ErrorCode::INVALID_PATH => 400,
            ErrorCode::INVALID_SIGNATURE, ErrorCode::REQUEST_EXPIRED, ErrorCode::NONCE_REPLAY => 401,
            ErrorCode::TENANT_NOT_FOUND, ErrorCode::USER_NOT_FOUND, ErrorCode::ROUTE_NOT_FOUND => 404,
            ErrorCode::METHOD_NOT_ALLOWED => 405,
            ErrorCode::TENANT_ALREADY_EXISTS => 409,
            default => 500,
        };
        return new self($status, ErrorResponse::fromError($error)->toArray(), $status === 405 ? ['Allow' => 'POST'] : []);
    }
}
