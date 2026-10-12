<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Http;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Conformance\{ConformanceTests, RecordingAdapter};
use PuyuPe\SiproInternalApiCore\Http\InternalApi;
use PuyuPe\SiproInternalApiCore\Http\Response\ApiResponse;

require_once __DIR__ . '/../../resources/contracts/v2/RecordingAdapter.php';
require_once __DIR__ . '/../../resources/contracts/v2/ConformanceTests.php';

final class InternalApiTest extends TestCase
{
    use ConformanceTests;
    private RecordingAdapter $adapter;

    protected function setUp(): void { $this->adapter = new RecordingAdapter(); }
    protected function recordingAdapter(): RecordingAdapter { return $this->adapter; }
    protected function executeRequest(string $method, string $path, string $body, array $headers): ApiResponse
    {
        return (new InternalApi(fn () => $this->adapter, fn ($id) => $id === 'test' ? 'secret' : null, report: static function (): void {}))->handle($method, $path, $body, $headers);
    }

    public function testNonceBackendFailureDoesNotReachAdapter(): void
    {
        $store = new class implements \PuyuPe\SiproInternalApiCore\Security\Hmac\NonceStoreInterface {
            public function consume(string $nonceKey, int $ttlSeconds): bool { throw new \RuntimeException('backend unavailable'); }
        };
        $api = new InternalApi(fn () => $this->adapter, fn () => 'secret', nonceStore: $store, report: static function (): void {});
        $path = '/internal/v1/tenants/tenant:warn';
        $body = '{"resolveKey":"tenant","projectCode":"PROJ"}';
        $headers = (new \PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner())->buildSignedHeaders('POST', $path, $body, 'test', 'secret');
        $response = $api->handle('POST', $path, $body, $headers);
        self::assertSame(500, $response->status);
        self::assertSame('INTERNAL_ERROR', $response->body['error']['code']);
        self::assertSame([], $this->adapter->calls);
    }
}
