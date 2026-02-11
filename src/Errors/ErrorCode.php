<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

enum ErrorCode: string
{
    case INVALID_REQUEST = 'INVALID_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case CONFLICT = 'CONFLICT';
    case RATE_LIMITED = 'RATE_LIMITED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
}
