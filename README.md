# puyupe/sipro-internal-api-core

Paquete Composer framework-agnostic para integraciones entre **SIPRO Control Plane** y los **SaaS** en `/internal/v1`.

## ¿Qué resuelve este paquete?

1. **Contracts (DTOs)** para requests internos (ej. creación/estado de tenant).
2. **HMAC** para firmado y verificación de requests entre servicios.
3. **Errores estándar** para respuestas JSON consistentes (`ErrorResponse`).

> Objetivo: reducir código repetido y evitar diferencias de implementación entre servicios.

## Payload de ejemplo: CreateTenantRequest

```json
{
  "tenant_uuid": "6fd22e43-c8a7-4f02-9f8f-31157a4f1b74",
  "tenant_name": "Acme SAC",
  "ruc": "20123456789",
  "plan_code": "pro",
  "billing_status": "active",
  "admin_user": {
    "email": "admin@acme.pe",
    "name": "Admin Acme",
    "temp_password": "Temporal123!"
  },
  "locale_config": {
    "timezone": "America/Lima",
    "currency": "PEN",
    "igv_rate": 0.18,
    "tax_mode": "included"
  },
  "series_config": {
    "enabled": true
  },
  "limits": {
    "max_users": 20,
    "max_branches": 5,
    "max_docs_month": 5000
  },
  "features": {
    "inventory": true,
    "billing": true
  },
  "notes": "Cliente migrado desde legacy"
}
```

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
