# puyupe/sipro-internal-api-core

Paquete Composer framework-agnostic para integraciones entre **SIPRO Control Plane** y los **SaaS** en `/internal/v1`.

## ¿Qué resuelve este paquete?

1. **Contracts (DTOs)** para provisioning, ciclo de vida y clonado de tenants.
2. **HMAC** para firmado y verificación de requests entre servicios.
3. **Errores estándar** para respuestas JSON consistentes (`ErrorResponse`).

> Objetivo: reducir código repetido y evitar diferencias de implementación entre servicios.

## Payload de ejemplo: ProvisionPayloadDTO (createTenant)

```json
{
  "project": {
    "name": "Acme Suite",
    "code": "ACME",
    "description": "Suite principal",
    "billingCycle": "monthly",
    "priceAgreed": 199.9,
    "startDate": "2026-03-25",
    "renewalDate": "2027-03-25",
    "execStatus": "active",
    "isActive": true,
    "accessUrlCustom": "https://acme.sipro.app",
    "accessUrls": {
      "app": "https://acme.sipro.app",
      "api": "https://api.acme.sipro.app"
    },
    "appKey": "acme-app-001",
    "logo": null,
    "address": "Av. Demo 123",
    "phone": "+51 1 5555555",
    "email": "admin@acme.pe",
    "ubigeo": "150101",
    "latitud": -12.0464,
    "longitud": -77.0428,
    "color": "#004c97",
    "notes": "Cliente migrado desde legacy"
  },
  "client": {
    "ruc": "20123456789",
    "businessName": "Acme SAC",
    "tradeName": "Acme"
  },
  "services": [
    {
      "key": "billing",
      "externalId": "srv-01",
      "code": "BILL",
      "name": "Facturacion",
      "description": "Modulo de facturacion",
      "priceList": 99.0,
      "defaultBillingCycle": "monthly",
      "type": "core",
      "accessUrl": "https://acme.sipro.app/billing",
      "logo": null,
      "credentials": [
        {
          "name": "Admin Acme",
          "username": "admin",
          "email": "admin@acme.pe",
          "role": "owner",
          "initialPassword": "Temporal123!",
          "mustChangePassword": true
        }
      ],
      "modules": [
        {
          "id": 10,
          "externalId": "mod-01",
          "name": "Ventas",
          "description": "Ventas y cotizaciones",
          "price": 20.0,
          "isUnlimited": false,
          "customPrice": null,
          "quantity": 5
        }
      ]
    }
  ],
  "metadata": {
    "source": "control-plane",
    "priority": "high"
  }
}
```

## Ciclo de vida del tenant (warn / suspend / activate / close / reopen)

`TenantLifecycleRequestDTO` (payload estándar para todas las acciones de ciclo de vida):

| Campo | Tipo | Descripción |
|---|---|---|
| `appKey` | `string` | Identificador del tenant |
| `projectCode` | `string` | Código del proyecto |
| `reason` | `?string` | Motivo de la acción (opcional) |
| `requestedAt` | `?string` | ISO 8601 — cuándo se solicitó (opcional) |
| `requestedBy` | `?string` | Identificador del operador que solicita la acción (opcional) |

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "reason": "PAYMENT_OVERDUE",
  "requestedAt": "2026-03-25T10:00:00Z",
  "requestedBy": "user-42"
}
```

`TenantLifecycleResponseDTO`:

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "status": "ok",
  "systemStatus": "suspended"
}
```

## Clonado de tenant (export / import)

`TenantExportRequestDTO`:

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "reason": "MIGRATION"
}
```

`TenantExportResponseDTO`:

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "dumpPath": "/mnt/backups/acme-20260325.sql.gz",
  "checksum": "sha256:3f4b8c...",
  "createdAt": "2026-03-25T10:35:00Z"
}
```

`TenantImportRequestDTO`:

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "dumpPath": "/mnt/backups/acme-20260325.sql.gz",
  "checksum": "sha256:3f4b8c..."
}
```

`TenantImportResponseDTO`:

```json
{
  "appKey": "acme-app-001",
  "projectCode": "ACME",
  "database": "acme_20260325",
  "restored": true
}
```

## Interfaces de adapters

Provisioning:
- `TenantProvisioningAdapterInterface::createTenant(ProvisionPayloadDTO $dto): ProvisionResponseDTO`

Ciclo de vida:
- `TenantLifecycleAdapterInterface::warnTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO`
- `TenantLifecycleAdapterInterface::suspendTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO`
- `TenantLifecycleAdapterInterface::activateTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO`
- `TenantLifecycleAdapterInterface::closeTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO`
- `TenantLifecycleAdapterInterface::reopenTenant(string $appKey, TenantLifecycleRequestDTO $dto): TenantLifecycleResponseDTO`

Clonado:
- `TenantCloneAdapterInterface::exportTenant(string $appKey, TenantExportRequestDTO $dto): TenantExportResponseDTO`
- `TenantCloneAdapterInterface::importTenant(string $appKey, TenantImportRequestDTO $dto): TenantImportResponseDTO`

Nota: `TenantAdapterInterface` agrupa provisioning + ciclo de vida. El clonado se mantiene separado en `TenantCloneAdapterInterface`.

## Firma HMAC (Control Plane) — pasos

Headers requeridos:
- `X-Internal-KeyId`
- `X-Internal-Timestamp`
- `X-Internal-Nonce`
- `X-Internal-Signature`

Canonical string **v1 exacto**:

```text
{METHOD}\n{PATH}\n{TIMESTAMP}\n{NONCE}\n{BODY_SHA256_HEX}
```

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\CanonicalRequest;
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

$method = 'POST';
$path = '/internal/v1/tenants';
$rawBody = json_encode(['tenant_uuid' => '6fd22e43-c8a7-4f02-9f8f-31157a4f1b74'], JSON_THROW_ON_ERROR);
$timestamp = (string) time();
$nonce = bin2hex(random_bytes(16));
$keyId = 'cp-key-01';
$secret = 'super-shared-secret';

// 1) BODY_SHA256_HEX
$bodyHash = CanonicalRequest::bodySha256Hex($rawBody);

// 2) Canonical string
$canonical = CanonicalRequest::build($method, $path, $timestamp, $nonce, $rawBody);

// 3) Firma con secret (hex por defecto)
$signer = new HmacSigner();
$signature = $signer->sign($canonical, $secret, 'sha256', 'hex');

// 4) Armar headers
$headers = [
  'X-Internal-KeyId' => $keyId,
  'X-Internal-Timestamp' => $timestamp,
  'X-Internal-Nonce' => $nonce,
  'X-Internal-Signature' => $signature,
];
```

## Verificación HMAC en SaaS con `HmacVerifier`

```php
<?php

use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacVerifier;
use PuyuPe\SiproInternalApiCore\Security\Hmac\NonceStoreInterface;

final class RedisNonceStore implements NonceStoreInterface {
    public function has(string $nonce): bool {
        // GET nonce
        return false;
    }

    public function put(string $nonce, int $ttlSeconds): void {
        // SETEX nonce ttlSeconds 1
    }
}

$verifier = new HmacVerifier(allowedClockSkewSeconds: 300);

$result = $verifier->verify(
    method: 'POST',
    path: '/internal/v1/tenants',
    rawBody: $rawBody,
    headers: $headers,
    // resolveSecretByKeyId: lookup seguro por KeyId
    resolveSecretByKeyId: function (string $keyId): ?string {
        return $keyId === 'cp-key-01' ? 'super-shared-secret' : null;
    },
    nonceStore: new RedisNonceStore(), // opcional, recomendado en producción
);

if (! $result->ok) {
    // errorCode: VALIDATION_ERROR | REQUEST_EXPIRED | NONCE_REPLAY | INVALID_SIGNATURE
}
```

Notas prácticas:
- `HmacVerifier` valida timestamp dentro de ±300s (configurable).
- `NonceStoreInterface` evita replay. Implementación típica:
  - **Redis** (`SETEX` por nonce), o
  - **tabla en DB master** con TTL/fecha de expiración.
- Si no envías `nonceStore`, se verifica firma/timestamp pero sin protección anti-replay.

## Ejemplos de `ErrorResponse`

### 1) `VALIDATION_ERROR` con errores por campo

```json
{
  "ok": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed.",
    "details": {
      "errors": [
        {"field": "tenant_uuid", "code": "invalid_uuid_v4", "message": "tenant_uuid must be a valid UUID v4."},
        {"field": "admin_user.email", "code": "invalid_email", "message": "admin_user.email must be a valid email address."}
      ]
    }
  }
}
```

### 2) `INVALID_SIGNATURE`

```json
{
  "ok": false,
  "error": {
    "code": "INVALID_SIGNATURE",
    "message": "Invalid request signature."
  }
}
```

## Notas de seguridad

- **No loguear secrets** (ni secretos HMAC ni credenciales de DB).
- **No exponer connection strings** ni tokens sensibles en `error.details`.
- Usa `keyId` para resolver secretos de forma rotativa y segura.

## Versionado y compatibilidad

- Este paquete está orientado al contrato `/internal/v1`.
- Cambios incompatibles deben versionarse como nueva versión mayor del paquete y/o nueva ruta (`/internal/v2`).
- Mantén signer/verifier con el mismo formato canonical v1 para asegurar interoperabilidad.
