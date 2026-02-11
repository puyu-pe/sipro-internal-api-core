<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\WarnTenantRequest;

final class WarnTenantRequestTest extends TestCase
{
    public function testValidWarnRequestPasses(): void
    {
        $dto = WarnTenantRequest::fromArray([
            'message' => 'Pago pendiente',
            'warn_until' => '2026-10-14',
            'severity' => 'warning',
        ]);

        self::assertTrue($dto->validate()->ok());
    }

    public function testInvalidWarnFieldsFailValidation(): void
    {
        $dto = WarnTenantRequest::fromArray([
            'message' => '',
            'warn_until' => '14-10-2026',
            'severity' => 'critical',
        ]);

        $result = $dto->validate();
        $fields = array_column($result->errors(), 'field');

        self::assertFalse($result->ok());
        self::assertContains('message', $fields);
        self::assertContains('warn_until', $fields);
        self::assertContains('severity', $fields);
    }
}
