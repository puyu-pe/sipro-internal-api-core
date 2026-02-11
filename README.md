# puyu-pe/sipro-internal-api-core

Paquete Composer **framework-agnostic** para estandarizar contratos DTO, utilidades de firma HMAC y formato de errores/respuestas para la API interna `/internal/v1` consumida por SIPRO.

## Requisitos

- PHP >= 8.1

## Instalación

```bash
composer require puyu-pe/sipro-internal-api-core
```

## Qué incluye

- DTOs de operaciones de tenant (`CreateTenantRequest`, `WarnTenantRequest`, `SuspendTenantRequest`, `ActivateTenantRequest`)
- Resultado de validación (`ValidationResult`) con validación manual ligera
- Construcción de canonical request para HMAC (`CanonicalRequest`)
- Firma y verificación HMAC (`HmacSigner`, `HmacVerifier`)
- Contrato para almacén de nonce (`NonceStoreInterface`)
- Headers estándar internos (`InternalHeaders`)
- Respuestas estándar (`SuccessResponse`, `ErrorResponse`)
- Estándar de errores (`ErrorCode`, `InternalApiError`, `ErrorFactory`)

## Ejemplo: validar DTO

```php
<?php

use PuyuPe\SiproInternalApiCore\Contracts\Dto\CreateTenantRequest;

$dto = CreateTenantRequest::fromArray($payload);
$result = $dto->validate();

if (! $result->isValid()) {
    // devolver error 422 con $result->errors()
}
```

## Ejemplo: construir canonical string

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\CanonicalRequest;

$canonical = CanonicalRequest::build(
    method: 'POST',
    path: '/internal/v1/tenants/create',
    headers: [
        'Content-Type' => 'application/json',
        'X-Internal-Api-Key' => $apiKey,
    ],
    body: $rawBody,
    timestamp: $timestamp,
    nonce: $nonce,
);
```

La canonical string se arma como líneas separadas por `\n`:
1. Método HTTP en mayúsculas.
2. Path normalizado (`/foo/bar`).
3. Headers relevantes en minúsculas, ordenados alfabéticamente, formato `header:value`.
4. Hash SHA-256 hexadecimal del body.
5. Timestamp.
6. Nonce.

## Ejemplo: firmar request

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

$signer = new HmacSigner();
$signature = $signer->sign($canonical, $sharedSecret);

// enviar en header X-Internal-Signature
```

## Ejemplo: verificar request

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacVerifier;
use PuyuPe\SiproInternalApiCore\Security\Hmac\NonceStoreInterface;

final class InMemoryNonceStore implements NonceStoreInterface {
    private array $nonces = [];

    public function has(string $nonce): bool {
        return isset($this->nonces[$nonce]) && $this->nonces[$nonce] >= time();
    }

    public function save(string $nonce, int $ttlSeconds): void {
        $this->nonces[$nonce] = time() + $ttlSeconds;
    }
}

$verifier = new HmacVerifier();
$nonceStore = new InMemoryNonceStore();

if (! $verifier->isTimestampFresh($timestamp, 300)) {
    // rechazar: timestamp expirado
}

if (! $verifier->isNonceValid($nonce, $nonceStore, 300)) {
    // rechazar: replay detectado
}

if (! $verifier->verifySignature($canonical, $providedSignature, $sharedSecret)) {
    // rechazar: firma inválida
}
```

## Ejemplo: respuestas estándar

```php
<?php

use PuyuPe\SiproInternalApiCore\Errors\ErrorFactory;
use PuyuPe\SiproInternalApiCore\Http\Response\ErrorResponse;
use PuyuPe\SiproInternalApiCore\Http\Response\SuccessResponse;

$ok = new SuccessResponse(['tenant_code' => 'acme'], 'Tenant creado');
$jsonOk = $ok->toJson();

$error = ErrorFactory::invalidRequest('tenant_code es obligatorio');
$errResponse = new ErrorResponse($error, ['tenant_code' => ['tenant_code is required.']]);
$jsonError = $errResponse->toJson();
```

## Convención de headers sugerida

- `X-Internal-Api-Key`
- `X-Internal-Signature`
- `X-Internal-Timestamp`
- `X-Internal-Nonce`

Disponibles como constantes en `PuyuPe\SiproInternalApiCore\Http\InternalHeaders`.
