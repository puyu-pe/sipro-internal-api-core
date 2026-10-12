<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Errors;

use RuntimeException;
use Throwable;

final class InternalApiException extends RuntimeException
{
    public function __construct(private readonly InternalApiError $error, ?Throwable $previous = null)
    {
        parent::__construct($error->message, 0, $previous);
    }

    public function error(): InternalApiError
    {
        return $this->error;
    }
}
