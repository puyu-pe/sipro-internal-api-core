<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Conformance;

use PDO;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

/** Optional cross-package suite against an explicitly supplied clone-db checkout. */
trait CloneEndpointTests
{
    abstract protected function configureCloneFixture(string $directory, array $databases): void;

    public function testRealCloneThroughFrameworkEntrypoint(): void
    {
        if (!getenv('SIPRO_TEST_DB_HOST') || !getenv('SIPRO_CLONE_DB_ROOT')) {
            self::markTestSkipped('Cross-package cloning requires SIPRO_TEST_DB_HOST and SIPRO_CLONE_DB_ROOT.');
        }
        $root = getenv('SIPRO_CLONE_DB_ROOT');
        require_once $root . '/src/Adapters/AbstractTenantCloneAdapter.php';
        require_once $root . '/resources/contracts/v2/DatabaseCloneAdapter.php';
        $pdo = new PDO('mysql:host=' . getenv('SIPRO_TEST_DB_HOST'), 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $suffix = bin2hex(random_bytes(5));
        $source = 'sipro_http_source_' . $suffix;
        $target = 'sipro_http_target_' . $suffix;
        $directory = sys_get_temp_dir() . '/sipro-http-clone-' . $suffix;
        mkdir($directory);
        $request = function (string $key, string $action, array $payload) {
            $path = '/internal/v1/tenants/' . $key . ':' . $action;
            $body = json_encode($payload, JSON_THROW_ON_ERROR);
            return $this->executeRequest('POST', $path, $body, (new HmacSigner())->buildSignedHeaders('POST', $path, $body, 'test', 'secret'));
        };
        try {
            $pdo->exec("CREATE DATABASE `$source`");
            $pdo->exec("CREATE TABLE `$source`.items (id INT PRIMARY KEY, name VARCHAR(100))");
            $pdo->exec("INSERT INTO `$source`.items VALUES (1, 'through framework')");
            $this->configureCloneFixture($directory, ['source' => $source, 'target' => $target]);
            $export = $request('source', 'export', ['resolveKey' => 'source', 'projectCode' => $source]);
            self::assertSame(200, $export->status, json_encode($export->body));
            $payload = ['resolveKey' => 'target', 'projectCode' => $target, 'dumpPath' => $export->body['dumpPath'], 'checksum' => str_repeat('0', 64)];
            self::assertSame(400, $request('target', 'import', $payload)->status);
            $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
            $statement->execute([$target]);
            self::assertSame(0, (int) $statement->fetchColumn());
            $payload['checksum'] = $export->body['checksum'];
            $import = $request('target', 'import', $payload);
            self::assertSame(200, $import->status, json_encode($import->body));
            self::assertSame(['ok' => true, 'resolveKey' => 'target', 'projectCode' => $target, 'database' => $target, 'restored' => true], $import->body);
            self::assertSame('through framework', $pdo->query("SELECT name FROM `$target`.items WHERE id=1")->fetchColumn());
        } finally {
            $pdo->exec("DROP DATABASE IF EXISTS `$source`");
            $pdo->exec("DROP DATABASE IF EXISTS `$target`");
            foreach (glob($directory . '/*') as $file) { unlink($file); }
            rmdir($directory);
        }
    }
}
