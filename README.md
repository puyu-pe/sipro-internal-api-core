# puyu-pe/sipro-internal-api-core

Paquete Composer **framework-agnostic** para estandarizar contratos DTO, utilidades de firma HMAC y formato de errores/respuestas para la API interna `/internal/v1` consumida por SIPRO.

## Requisitos

- PHP >= 8.1

## Instalación

```bash
composer require puyu-pe/sipro-internal-api-core
```

## DTOs incluidos

- `CreateTenantRequest`
- `WarnTenantRequest`
- `SuspendTenantRequest`
- `ActivateTenantRequest`
- `ValidationResult` (`ok` + `errors`)

## Estructura de ValidationResult

`validate()` retorna un `ValidationResult` sin excepciones:

```php
[
  'ok' => false,
  'errors' => [
    ['field' => 'tenant_uuid', 'code' => 'invalid_uuid_v4', 'message' => '...'],
  ],
]
```

## Ejemplo CreateTenantRequest

```php
<?php

use PuyuPe\SiproInternalApiCore\Contracts\Dto\CreateTenantRequest;

$dto = CreateTenantRequest::fromArray([
    'tenant_uuid' => '6fd22e43-c8a7-4f02-9f8f-31157a4f1b74',
    'tenant_name' => 'Acme SAC',
    'ruc' => '20123456789',
    'admin_user' => [
        'email' => 'admin@acme.pe',
        'name' => 'Admin Acme',
        'temp_password' => 'Temporal123!',
        // o set_password_token
    ],
    // defaults automáticos:
    // timezone => America/Lima
    // currency => PEN
    // igv_rate => 0.18
    'locale_config' => [],
]);

$result = $dto->validate();

if (! $result->ok()) {
    $errors = $result->errors();
}

$payload = $dto->toArray();
```

Reglas principales de `CreateTenantRequest`:
- `tenant_uuid`: requerido, UUID v4.
- `tenant_name`: requerido, 3..150.
- `ruc`: opcional; si viene, 11 dígitos.
- `admin_user.email` y `admin_user.name`: requeridos.
- `admin_user.temp_password` **o** `admin_user.set_password_token`: al menos uno.
- `locale_config` aplica defaults si faltan campos.
- `limits.*`: opcional, entero >= 0.
- `features`: opcional, mapa `string => bool`.

## Ejemplo Warn/Suspend/Activate

```php
<?php

use PuyuPe\SiproInternalApiCore\Contracts\Dto\WarnTenantRequest;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\SuspendTenantRequest;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ActivateTenantRequest;

$warn = WarnTenantRequest::fromArray([
    'message' => 'Pago pendiente',
    'warn_until' => '2026-02-28',
    'severity' => 'warning',
]);

$suspend = SuspendTenantRequest::fromArray([
    'message' => 'Incumplimiento de pago',
    'reason_code' => 'PAYMENT_OVERDUE',
]);

$activate = ActivateTenantRequest::fromArray([
    'message' => 'Reactivación aprobada',
    'clear_warn' => true,
]);
```

## HMAC: canonical string, firma y verificación

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\CanonicalRequest;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacVerifier;

$canonical = CanonicalRequest::build(
    method: 'POST',
    path: '/internal/v1/tenants',
    headers: [
        'Content-Type' => 'application/json',
        'X-Internal-Api-Key' => $apiKey,
    ],
    body: $rawBody,
    timestamp: $timestamp,
    nonce: $nonce,
);

$signer = new HmacSigner();
$signature = $signer->sign($canonical, $sharedSecret);

$verifier = new HmacVerifier();
$isValid = $verifier->verifySignature($canonical, $signature, $sharedSecret);
```

## Error/Success response

```php
<?php

use PuyuPe\SiproInternalApiCore\Errors\ErrorFactory;
use PuyuPe\SiproInternalApiCore\Http\Response\ErrorResponse;
use PuyuPe\SiproInternalApiCore\Http\Response\SuccessResponse;

$ok = new SuccessResponse(['tenant_uuid' => '...'], 'Tenant creado');
$jsonOk = $ok->toJson();

$error = ErrorFactory::tenantNotFound('6fd22e43-c8a7-4f02-9f8f-31157a4f1b74');
$jsonError = ErrorResponse::fromError($error)->toJson();
```


## Estándar mínimo de errores

Formato JSON:

```json
{
  "ok": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed.",
    "details": {
      "errors": []
    }
  }
}
```

Códigos incluidos: `INVALID_SIGNATURE`, `REQUEST_EXPIRED`, `NONCE_REPLAY`, `VALIDATION_ERROR`, `TENANT_NOT_FOUND`, `TENANT_ALREADY_EXISTS`, `PROVISION_FAILED`, `DB_CREATE_FAILED`, `TEMPLATE_APPLY_FAILED`.
