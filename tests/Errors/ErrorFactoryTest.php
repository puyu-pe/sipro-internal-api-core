<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Errors;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Validation\ValidationResult;
use PuyuPe\SiproInternalApiCore\Errors\ErrorCode;
use PuyuPe\SiproInternalApiCore\Errors\ErrorFactory;
use PuyuPe\SiproInternalApiCore\Http\Response\ErrorResponse;

final class ErrorFactoryTest extends TestCase
{
    public function testValidationErrorIncludesFieldErrors(): void
    {
        $vr = ValidationResult::failure([
            ['field' => 'tenant_uuid', 'code' => 'invalid_uuid_v4', 'message' => 'bad uuid'],
        ]);

        $error = ErrorFactory::validationError($vr);
        $response = ErrorResponse::fromError($error)->toArray();

        self::assertSame(ErrorCode::VALIDATION_ERROR, $error->code);
        self::assertFalse($response['ok']);
        self::assertSame('VALIDATION_ERROR', $response['error']['code']);
        self::assertCount(1, $response['error']['details']['errors']);
    }

    public function testCommonFactoryMethodsReturnExpectedCodes(): void
    {
        self::assertSame(ErrorCode::INVALID_SIGNATURE, ErrorFactory::invalidSignature()->code);
        self::assertSame(ErrorCode::REQUEST_EXPIRED, ErrorFactory::requestExpired()->code);
        self::assertSame(ErrorCode::NONCE_REPLAY, ErrorFactory::nonceReplay()->code);
        self::assertSame(ErrorCode::TENANT_NOT_FOUND, ErrorFactory::tenantNotFound('t-1')->code);
        self::assertSame(ErrorCode::TENANT_ALREADY_EXISTS, ErrorFactory::tenantAlreadyExists('t-1')->code);
    }

    public function testSensitiveDataIsRedactedInDetails(): void
    {
        $error = ErrorFactory::provisionFailed('x', [
            'connection_string' => 'mysql://secret',
            'nested' => ['api_key' => '123'],
        ]);

        self::assertSame('[redacted]', $error->details['connection_string']);
        self::assertSame('[redacted]', $error->details['nested']['api_key']);
    }
}
