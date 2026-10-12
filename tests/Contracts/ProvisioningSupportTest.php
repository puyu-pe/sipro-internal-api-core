<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use LogicException;
use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Adapter\ProvisioningSupport;

final class ProvisioningSupportTest extends TestCase
{
    public function testAnAdapterCanUseHelpersWithoutDefiningDatabaseNaming(): void
    {
        $adapter = new class {
            use ProvisioningSupport;
            protected function environmentSuffix(): string { return 'dev'; }
            public function name(string $projectCode): string { return $this->buildDatabaseName($projectCode); }
        };

        self::assertIsObject($adapter);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Define databasePrefix()');
        $adapter->name('PROJ');
    }

    public function testApplicationDefinedNamingDoesNotRequireAnUnusedPrefix(): void
    {
        $adapter = new class {
            use ProvisioningSupport;
            protected function environmentSuffix(): string { return 'dev'; }
            protected function buildDatabaseName(string $projectCode): string { return 'application_' . $projectCode; }
            public function name(string $projectCode): string { return $this->buildDatabaseName($projectCode); }
        };

        self::assertSame('application_PROJ', $adapter->name('PROJ'));
    }
}
