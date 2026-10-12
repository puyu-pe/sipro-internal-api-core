<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Conformance;

use PuyuPe\SiproInternalApiCore\Errors\{ErrorCode, InternalApiError, InternalApiException};
use PuyuPe\SiproInternalApiCore\Http\Response\ApiResponse;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

/** Run unchanged through core, Laravel HTTP, and the CI3 distributed controller. */
trait ConformanceTests
{
    abstract protected function executeRequest(string $method, string $path, string $body, array $headers): ApiResponse;
    abstract protected function recordingAdapter(): RecordingAdapter;

    private function signedRequest(string $path, array|string $payload, string $method = 'POST'): ApiResponse
    {
        $body = is_array($payload) ? json_encode($payload, JSON_THROW_ON_ERROR) : $payload;
        $headers = (new HmacSigner())->buildSignedHeaders($method, '/internal/v1/tenants/tenant:warn', $body, 'test', 'secret');
        if (!str_contains($path, '?') && !str_ends_with($path, '/')) {
            $headers = (new HmacSigner())->buildSignedHeaders($method, $path, $body, 'test', 'secret');
        }
        return $this->executeRequest($method, $path, $body, $headers);
    }

    public function testAllTenOperationsHaveTheCanonicalResponse(): void
    {
        $identity = ['resolveKey' => 'tenant', 'projectCode' => 'PROJ'];
        $created = $this->signedRequest('/internal/v1/tenants', ['project' => ['resolveKey' => 'tenant', 'code' => 'PROJ'], 'client' => [], 'services' => []]);
        self::assertSame(200, $created->status);
        self::assertSame(['ok' => true, 'resolve_key' => 'tenant', 'project_code' => 'PROJ', 'database' => 'tenant_db', 'status' => 'created', 'provisioned_at' => '2026-01-01T00:00:00Z', 'db_host' => null, 'migrated' => true, 'seeded' => true, 'system_parameters_updated' => true, 'users_created' => 1, 'execution_time_ms' => 10, 'warnings' => []], $created->body);
        foreach (['warn', 'suspend', 'activate', 'close', 'reopen'] as $action) {
            $response = $this->signedRequest('/internal/v1/tenants/tenant:' . $action, $identity);
            self::assertSame(200, $response->status);
            self::assertSame(['ok' => true, ...$identity, 'status' => 'ok', 'systemStatus' => $action], $response->body);
        }
        $export = $this->signedRequest('/internal/v1/tenants/tenant:export', $identity);
        self::assertSame(200, $export->status);
        self::assertSame(['ok' => true, ...$identity, 'dumpPath' => '/dumps/tenant.sql.gz', 'checksum' => str_repeat('a', 64), 'createdAt' => '2026-01-01T00:00:00Z'], $export->body);
        $import = $this->signedRequest('/internal/v1/tenants/tenant:import', $identity + ['dumpPath' => '/dumps/tenant.sql.gz', 'checksum' => str_repeat('a', 64)]);
        self::assertSame(200, $import->status);
        self::assertSame(['ok' => true, ...$identity, 'database' => 'tenant_db', 'restored' => true], $import->body);
        $search = $this->signedRequest('/internal/v1/tenants/tenant:impersonation-users/search', $identity);
        self::assertSame(200, $search->status);
        self::assertSame(['ok' => true, ...$identity, 'users' => [], 'pagination' => ['page' => 1, 'perPage' => 20, 'total' => 0, 'hasNextPage' => false]], $search->body);
        $impersonation = $this->signedRequest('/internal/v1/tenants/tenant:impersonate', $identity + ['targetUserId' => 42]);
        self::assertSame(200, $impersonation->status);
        self::assertSame(['ok' => true, ...$identity, 'status' => 'ready', 'accessUrl' => '/support/token', 'effectiveDurationMinutes' => 5], $impersonation->body);
        self::assertCount(10, $this->recordingAdapter()->calls);
    }

    public function testInvalidInputNeverInvokesAnAdapter(): void
    {
        $identity = ['resolveKey' => 'tenant', 'projectCode' => 'PROJ'];
        foreach ([['appKey' => 'tenant', 'projectCode' => 'PROJ'], $identity + ['appKey' => 'tenant'], $identity + ['app_key' => 'tenant'], ['resolveKey' => 'other', 'projectCode' => 'PROJ'], ['resolveKey' => [], 'projectCode' => 'PROJ'], '{}', '[]', 'null', '{broken'] as $body) {
            $response = $this->signedRequest('/internal/v1/tenants/tenant:warn', $body);
            self::assertSame(400, $response->status);
            self::assertSame('VALIDATION_ERROR', $response->body['error']['code']);
            self::assertNotEmpty($response->body['error']['details']['errors']);
        }
        foreach ([['impersonation-users/search', ['page' => 0]], ['impersonation-users/search', ['perPage' => 51]], ['impersonate', ['targetUserId' => 42, 'durationMinutes' => 61]], ['impersonate', ['targetUserId' => '42']]] as [$action, $fields]) {
            self::assertSame(400, $this->signedRequest('/internal/v1/tenants/tenant:' . $action, $identity + $fields)->status);
        }
        self::assertSame([], $this->recordingAdapter()->calls);
    }

    public function testErrorCodesAndUnexpectedFailureAreMappedConsistently(): void
    {
        foreach ([['TENANT_NOT_FOUND', 404], ['USER_NOT_FOUND', 404], ['TENANT_ALREADY_EXISTS', 409], ['VALIDATION_ERROR', 400], ['PROVISION_FAILED', 500]] as [$code, $status]) {
            $this->recordingAdapter()->failure = new InternalApiException(new InternalApiError(ErrorCode::from($code), 'Expected failure.', ['field' => 'resolveKey']));
            $response = $this->signedRequest('/internal/v1/tenants/tenant:warn', ['resolveKey' => 'tenant', 'projectCode' => 'PROJ']);
            self::assertSame($status, $response->status);
            self::assertSame(['ok' => false, 'error' => ['code' => $code, 'message' => 'Expected failure.', 'details' => ['field' => 'resolveKey']]], $response->body);
        }
        $this->recordingAdapter()->failure = new \RuntimeException('private database password');
        $response = $this->signedRequest('/internal/v1/tenants/tenant:warn', ['resolveKey' => 'tenant', 'projectCode' => 'PROJ']);
        self::assertSame(500, $response->status);
        self::assertSame(['ok' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal API request failed.']], $response->body);
    }

    public function testTransportRejectionsAreConsistent(): void
    {
        foreach ([['/internal/v1/unknown', 'POST', 404, 'ROUTE_NOT_FOUND'], ['/internal/v1/tenants', 'GET', 405, 'METHOD_NOT_ALLOWED'], ['/internal/v1/tenants?x=1', 'POST', 400, 'QUERY_NOT_ALLOWED'], ['/internal/v1/tenants/', 'POST', 400, 'INVALID_PATH']] as [$path, $method, $status, $code]) {
            $response = $this->signedRequest($path, '{}', $method);
            self::assertSame($status, $response->status, $path . ': ' . json_encode($response->body));
            self::assertSame($code, $response->body['error']['code']);
            if ($status === 405) { self::assertSame('POST', $response->headers['Allow']); }
        }
        $response = $this->executeRequest('POST', '/internal/v1/tenants', '{}', []);
        self::assertSame(400, $response->status);
        self::assertSame('VALIDATION_ERROR', $response->body['error']['code']);
        self::assertSame([], $this->recordingAdapter()->calls);
    }

    public function testSignaturesBindExactBodyAndRequireNonceAndHex(): void
    {
        $path = '/internal/v1/tenants/tenant:warn';
        $body = '{"resolveKey":"tenant","projectCode":"PROJ"}';
        $headers = (new HmacSigner())->buildSignedHeaders('POST', $path, $body, 'test', 'secret');
        self::assertSame(401, $this->executeRequest('POST', $path, $body . ' ', $headers)->status);
        $headers['X-Internal-Signature'] = base64_encode(hex2bin($headers['X-Internal-Signature']));
        self::assertSame(401, $this->executeRequest('POST', $path, $body, $headers)->status);
        unset($headers['X-Internal-Nonce']);
        self::assertSame(400, $this->executeRequest('POST', $path, $body, $headers)->status);
        self::assertSame([], $this->recordingAdapter()->calls);
    }

    public function testProvisioningRejectsInvalidNestedTypesAndLegacyIdentity(): void
    {
        foreach ([['resolveKey' => 'tenant', 'code' => 'PROJ', 'isActive' => 'false'], ['resolveKey' => 'tenant', 'code' => 'PROJ', 'name' => []], ['appKey' => 'tenant', 'code' => 'PROJ']] as $project) {
            $response = $this->signedRequest('/internal/v1/tenants', ['project' => $project, 'client' => [], 'services' => []]);
            self::assertSame(400, $response->status);
            self::assertSame('VALIDATION_ERROR', $response->body['error']['code']);
        }
        self::assertSame([], $this->recordingAdapter()->calls);
    }
}
