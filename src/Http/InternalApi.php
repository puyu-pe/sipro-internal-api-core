<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Http;

use Closure;
use JsonException;
use PuyuPe\SiproInternalApiCore\Contracts\Adapter\TenantCloneAdapterInterface;
use PuyuPe\SiproInternalApiCore\Contracts\Adapter\TenantImpersonationAdapterInterface;
use PuyuPe\SiproInternalApiCore\Contracts\Adapter\TenantLifecycleAdapterInterface;
use PuyuPe\SiproInternalApiCore\Contracts\Adapter\TenantProvisioningAdapterInterface;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\{ProvisionPayloadDTO, TenantLifecycleRequestDTO, TenantExportRequestDTO, TenantImportRequestDTO, ImpersonationRequestDTO, ImpersonableUserSearchRequestDTO};
use PuyuPe\SiproInternalApiCore\Contracts\Validation\RequestValidation;
use PuyuPe\SiproInternalApiCore\Errors\{ErrorCode, InternalApiError, InternalApiException};
use PuyuPe\SiproInternalApiCore\Http\Response\ApiResponse;
use PuyuPe\SiproInternalApiCore\Security\Hmac\{CanonicalRequest, HmacVerifier, NonceStoreInterface};
use Throwable;

/** Framework-independent request policy. Bridges supply transport and infrastructure. */
final class InternalApi
{
    private readonly Closure $resolveAdapter;
    private readonly Closure $resolveSecret;
    private readonly Closure $report;

    public function __construct(
        callable $resolveAdapter,
        callable $resolveSecret,
        private readonly HmacVerifier $verifier = new HmacVerifier(),
        private readonly ?NonceStoreInterface $nonceStore = null,
        private readonly int $defaultDurationMinutes = 5,
        private readonly int $minDurationMinutes = 5,
        private readonly int $maxDurationMinutes = 60,
        private readonly bool $enabled = true,
        ?callable $report = null
    ) {
        if ($minDurationMinutes < 1 || $minDurationMinutes > $defaultDurationMinutes || $defaultDurationMinutes > $maxDurationMinutes) {
            throw new \InvalidArgumentException('Invalid impersonation duration policy.');
        }
        $this->resolveAdapter = Closure::fromCallable($resolveAdapter);
        $this->resolveSecret = Closure::fromCallable($resolveSecret);
        $this->report = Closure::fromCallable($report ?? static function (Throwable $error): void { error_log($error::class . ': ' . $error->getMessage()); });
    }

    public function handle(string $method, string $path, string $rawBody, array $headers): ApiResponse
    {
        try {
            if (!$this->enabled) {
                return $this->failure(ErrorCode::ROUTE_NOT_FOUND, 'Route not found.');
            }
            CanonicalRequest::assertPath($path);
            $route = $this->route($path);
            if ($route === null) {
                return $this->failure(ErrorCode::ROUTE_NOT_FOUND, 'Route not found.');
            }
            if (strtoupper($method) !== 'POST') {
                return $this->failure(ErrorCode::METHOD_NOT_ALLOWED, 'Only POST is allowed.');
            }
            $verification = $this->verifier->verify($method, $path, $rawBody, $headers, $this->resolveSecret, $this->nonceStore);
            if (!$verification->ok) {
                return ApiResponse::error(new InternalApiError($verification->errorCode, $verification->errorMessage, $verification->details));
            }
            try {
                $decoded = json_decode($rawBody, false, 512, JSON_THROW_ON_ERROR);
                if (!$decoded instanceof \stdClass) {
                    RequestValidation::fail('body', 'type', 'JSON body must be an object.');
                }
                $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                RequestValidation::fail('body', 'json', 'Invalid JSON body.');
            }
            [$interface, $action, $dtoClass, $resolveKey] = $route;
            if ($dtoClass === ImpersonationRequestDTO::class && !isset($payload['durationMinutes'])) {
                $payload['durationMinutes'] = $this->defaultDurationMinutes;
            }
            $dto = $dtoClass::fromArray($payload);
            if ($resolveKey !== null && $resolveKey !== $dto->resolveKey) {
                RequestValidation::fail('resolveKey', 'mismatch', 'resolveKey does not match request path.');
            }
            if ($dto instanceof ImpersonableUserSearchRequestDTO) {
                RequestValidation::assertResult($dto->validate());
            }
            if ($dto instanceof ImpersonationRequestDTO) {
                RequestValidation::assertResult($dto->validateDurationPolicy($this->minDurationMinutes, $this->maxDurationMinutes));
            }
            try {
                $adapter = ($this->resolveAdapter)($interface);
            } catch (Throwable $error) {
                $this->reportFailure($error);
                return $this->failure(ErrorCode::ADAPTER_NOT_CONFIGURED, 'Adapter is not configured.');
            }
            if (!$adapter instanceof $interface) {
                return $this->failure(ErrorCode::ADAPTER_NOT_CONFIGURED, 'Adapter is not configured.');
            }
            $result = $resolveKey === null ? $adapter->$action($dto) : $adapter->$action($resolveKey, $dto);
            return new ApiResponse(200, ['ok' => true, ...$result->toArray()]);
        } catch (InternalApiException $error) {
            return ApiResponse::error($error->error());
        } catch (Throwable $error) {
            $this->reportFailure($error);
            return $this->failure(ErrorCode::INTERNAL_ERROR, 'Internal API request failed.');
        }
    }

    private function failure(ErrorCode $code, string $message): ApiResponse
    {
        return ApiResponse::error(new InternalApiError($code, $message));
    }

    private function reportFailure(Throwable $error): void
    {
        try {
            ($this->report)($error);
        } catch (Throwable) {
            error_log('Internal API error reporting failed.');
        }
    }

    private function route(string $path): ?array
    {
        if ($path === '/internal/v1/tenants') {
            return [TenantProvisioningAdapterInterface::class, 'createTenant', ProvisionPayloadDTO::class, null];
        }
        if (!preg_match('#^/internal/v1/tenants/([^/:]+):(warn|suspend|activate|close|reopen|export|import|impersonate|impersonation-users/search)$#D', $path, $matches)) {
            return null;
        }
        $resolveKey = rawurldecode($matches[1]);
        $action = $matches[2];
        return match ($action) {
            'export' => [TenantCloneAdapterInterface::class, 'exportTenant', TenantExportRequestDTO::class, $resolveKey],
            'import' => [TenantCloneAdapterInterface::class, 'importTenant', TenantImportRequestDTO::class, $resolveKey],
            'impersonate' => [TenantImpersonationAdapterInterface::class, 'impersonateUser', ImpersonationRequestDTO::class, $resolveKey],
            'impersonation-users/search' => [TenantImpersonationAdapterInterface::class, 'searchImpersonableUsers', ImpersonableUserSearchRequestDTO::class, $resolveKey],
            default => [TenantLifecycleAdapterInterface::class, $action . 'Tenant', TenantLifecycleRequestDTO::class, $resolveKey],
        };
    }
}
