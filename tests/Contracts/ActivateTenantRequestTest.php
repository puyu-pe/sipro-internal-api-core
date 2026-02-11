<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ActivateTenantRequest;

final class ActivateTenantRequestTest extends TestCase
{
    public function testOptionalFieldsAndBooleanValidation(): void
    {
        $valid = ActivateTenantRequest::fromArray(['message' => 'OK', 'clear_warn' => true]);
        $invalid = ActivateTenantRequest::fromArray(['clear_warn' => 'yes']);

        self::assertTrue($valid->validate()->ok());
        self::assertFalse($invalid->validate()->ok());
    }
}
