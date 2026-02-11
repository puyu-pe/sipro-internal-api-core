<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\CreateTenantRequest;

final class CreateTenantRequestTest extends TestCase
{
    public function testValidPayloadPassesValidationAndAppliesLocaleDefaults(): void
    {
        $dto = CreateTenantRequest::fromArray([
            'tenant_uuid' => '11111111-1111-4111-8111-111111111111',
            'tenant_name' => 'Demo Tenant',
            'admin_user' => [
                'email' => 'admin@demo.com',
                'name' => 'Admin',
                'temp_password' => 'Temp1234',
            ],
            'locale_config' => [],
        ]);

        $result = $dto->validate();

        self::assertTrue($result->ok());
        self::assertSame('America/Lima', $dto->localeConfig['timezone']);
        self::assertSame('PEN', $dto->localeConfig['currency']);
        self::assertSame(0.18, $dto->localeConfig['igv_rate']);
    }

    public function testInvalidFieldsProduceExpectedErrors(): void
    {
        $dto = CreateTenantRequest::fromArray([
            'tenant_uuid' => 'invalid',
            'tenant_name' => 'AB',
            'ruc' => '1234',
            'admin_user' => [
                'email' => 'bad-email',
                'name' => '',
            ],
        ]);

        $result = $dto->validate();
        $fields = array_column($result->errors(), 'field');

        self::assertFalse($result->ok());
        self::assertContains('tenant_uuid', $fields);
        self::assertContains('tenant_name', $fields);
        self::assertContains('ruc', $fields);
        self::assertContains('admin_user.email', $fields);
        self::assertContains('admin_user.name', $fields);
        self::assertContains('admin_user', $fields);
    }

    public function testPasswordOrTokenRuleIsSatisfiedByToken(): void
    {
        $dto = CreateTenantRequest::fromArray([
            'tenant_uuid' => '11111111-1111-4111-8111-111111111111',
            'tenant_name' => 'Demo Tenant',
            'admin_user' => [
                'email' => 'admin@demo.com',
                'name' => 'Admin',
                'set_password_token' => 'token-123',
            ],
        ]);

        self::assertTrue($dto->validate()->ok());
    }
}
