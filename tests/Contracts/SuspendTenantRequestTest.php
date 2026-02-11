<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\SuspendTenantRequest;

final class SuspendTenantRequestTest extends TestCase
{
    public function testMessageIsRequired(): void
    {
        $invalid = SuspendTenantRequest::fromArray(['message' => '']);
        $valid = SuspendTenantRequest::fromArray(['message' => 'Suspensión temporal', 'reason_code' => 'PAYMENT_OVERDUE']);

        self::assertFalse($invalid->validate()->ok());
        self::assertTrue($valid->validate()->ok());
    }
}
