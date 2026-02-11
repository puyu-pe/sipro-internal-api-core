# SIPRO Internal API Core (puyu-pe/sipro-internal-api-core)

> Guía de implementación **de punta a punta** para integradores (Laravel o CI3).
> Este README es la guía operativa corta. La referencia completa está en [`docs/OPERATIONAL_SPEC_V1.md`](docs/OPERATIONAL_SPEC_V1.md).

## A) ¿Qué es este paquete y cuándo usarlo?

- Define el contrato común para la SIPRO Internal API `/internal/v1`.
- Estandariza firmado/verificación HMAC-SHA256 (hex lower).
- Estandariza errores (`ErrorCode`, `ErrorResponse`, `ErrorFactory`).
- Aporta DTOs/validación mínima para requests internas.
- Se usa junto a bridges de framework (Laravel o CI3) en el SaaS.

## B) Arquitectura en 3 paquetes

```text
SIPRO Control Plane (cliente firmante)
   -> HTTP + HMAC
SaaS API (Laravel o CI3 bridge)
   -> usa puyu-pe/sipro-internal-api-laravel  O  puyu-pe/sipro-internal-api-ci3
   -> ambos consumen puyu-pe/sipro-internal-api-core (fuente de verdad)
```

## C) Quick Start (10–15 minutos)

1) **Instalar paquetes en el SaaS**
- Laravel:
  ```bash
  composer require puyu-pe/sipro-internal-api-core puyu-pe/sipro-internal-api-laravel
  ```
- CI3:
  ```bash
  composer require puyu-pe/sipro-internal-api-core puyu-pe/sipro-internal-api-ci3
  ```

2) **Configurar KeyId y Secret**
- Define al menos 2 llaves activas (rotación):
  - `SIPRO_INTERNAL_KEY_ID_ACTIVE=sipro-2026-01`
  - `SIPRO_INTERNAL_KEY_SECRET_ACTIVE=TEST_ONLY__CHANGE_ME__2026`
  - `SIPRO_INTERNAL_KEY_ID_NEXT=sipro-2026-02`
  - `SIPRO_INTERNAL_KEY_SECRET_NEXT=TEST_ONLY__CHANGE_ME__2026_NEXT`

3) **Preparar nonce store en DB master (sin Redis)**
- Crear tabla con índice único `(key_id, nonce)` y expiración TTL 600s (ver sección F).

4) **Exponer endpoints `/internal/v1` desde el bridge**
- `POST /internal/v1/tenants`
- `POST /internal/v1/tenants/{tenant_uuid}:warn`
- `POST /internal/v1/tenants/{tenant_uuid}:suspend`
- `POST /internal/v1/tenants/{tenant_uuid}:activate`

5) **Implementar TenantAdapter en el SaaS**
- Responsabilidades:
  - crear tenant / usuario admin
  - advertir / suspender / activar
  - mapear errores de negocio a `ErrorCode`
  - devolver respuesta consistente (`ok`, `error`)

6) **Probar con request firmada (golden vector de este README)**
- Usa el ejemplo de sección G.1 y verifica que:
  - firma coincida
  - request sin querystring
  - path sin slash final

## D) Configuración

Parámetros mínimos recomendados:

- `SIPRO_INTERNAL_ALLOWED_CLOCK_SKEW=300` (segundos, ±300s)
- `SIPRO_INTERNAL_NONCE_TTL=600` (segundos)
- `SIPRO_INTERNAL_KEY_ID_ACTIVE`
- `SIPRO_INTERNAL_KEY_SECRET_ACTIVE`
- `SIPRO_INTERNAL_KEY_ID_NEXT`
- `SIPRO_INTERNAL_KEY_SECRET_NEXT`

Política de rotación:
- Mantener siempre **2 keys activas** (actual + próxima).
- El verificador debe resolver secret por `KeyId`.
- Retirar key antigua solo cuando no haya tráfico firmado con ella.

## E) HMAC: cómo firmar y verificar (resumen)

- Algoritmo: **HMAC-SHA256**
- Formato firma: **hex lower**
- Canonical string exacto:

```text
{METHOD}\n{PATH}\n{TIMESTAMP}\n{NONCE}\n{BODY_SHA256_HEX}
```

Reglas de `PATH`:
- Solo path (sin dominio).
- Querystring **prohibido**: si contiene `?` => `400 QUERY_NOT_ALLOWED`.
- Trailing slash **inválido** (`/internal/v1/tenants/`) => `400 INVALID_PATH`.
- No normalizar path en servidor.

`BODY raw`:
- El servidor debe hashear el **raw body exacto recibido**.
- No re-serializar JSON antes de calcular hash.

## F) Nonce anti-replay (sin Redis)

Estrategia: DB master, TTL 600s, inserción atómica por índice único.

DDL conceptual (MySQL):

```sql
CREATE TABLE internal_request_nonces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key_id VARCHAR(64) NOT NULL,
  nonce VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_key_nonce (key_id, nonce),
  KEY idx_expires_at (expires_at)
) ENGINE=InnoDB;
```

Flujo recomendado:
1. Si `NOW() > expires_at` al leer nonce, se considera vencido.
2. Insertar `(key_id, nonce)` con `expires_at = NOW() + 600s`.
3. Si falla por unique => `NONCE_REPLAY`.
4. Cron de limpieza (cada 1–5 min): `DELETE ... WHERE expires_at < NOW()`.

## G) Ejemplos completos

### 1) Request válido `POST /internal/v1/tenants`

Secret (solo pruebas):
- `KeyId`: `sipro-2026-01`
- `Secret`: `TEST_ONLY__CHANGE_ME__2026`
- `Timestamp`: `1760467200`
- `Nonce`: `00000000-0000-0000-0000-000000000001`

Body raw exacto:

```json
{"tenant_uuid":"11111111-1111-4111-8111-111111111111","tenant_name":"Demo SAC","ruc":"20123456789","plan_code":"PRO","billing_status":"active","admin_user":{"email":"admin@demo.pe","name":"Admin Demo","temp_password":"Temp1234"},"locale_config":{"timezone":"America/Lima","currency":"PEN","igv_rate":0.18,"tax_mode":"included"},"series_config":{"enabled":true},"limits":{"max_users":20,"max_branches":5,"max_docs_month":5000},"features":{"inventory":true,"billing":true},"notes":"Tenant inicial"}
```

`BODY_SHA256_HEX` real:

```text
074ff7e98c90bbc45ae4a44402377fe0f3a08c6193defb60cc952a777570ad10
```

Canonical string literal real:

```text
POST
/internal/v1/tenants
1760467200
00000000-0000-0000-0000-000000000001
074ff7e98c90bbc45ae4a44402377fe0f3a08c6193defb60cc952a777570ad10
```

Signature `hex_lower` real:

```text
1fca0ccbe71a2a79bf9460fcb40fec697500673511110cc5fcfa55c0b4061a50
```

Headers completos:

```http
X-Internal-KeyId: sipro-2026-01
X-Internal-Timestamp: 1760467200
X-Internal-Nonce: 00000000-0000-0000-0000-000000000001
X-Internal-Signature: 1fca0ccbe71a2a79bf9460fcb40fec697500673511110cc5fcfa55c0b4061a50
Content-Type: application/json
```

Response 200 ejemplo:

```json
{
  "ok": true,
  "data": {
    "tenant_uuid": "11111111-1111-4111-8111-111111111111",
    "status": "active"
  }
}
```

### 2) Ejemplo `400 QUERY_NOT_ALLOWED`

Request inválido:
- `POST /internal/v1/tenants?source=sipro`

Response ejemplo:

```json
{
  "ok": false,
  "error": {
    "code": "QUERY_NOT_ALLOWED",
    "message": "Querystring is not allowed for internal signed endpoints."
  }
}
```

### 3) Ejemplo `401 NONCE_REPLAY`

Caso:
- Se repite mismo `KeyId + Nonce` dentro de TTL 600s.

Response ejemplo:

```json
{
  "ok": false,
  "error": {
    "code": "NONCE_REPLAY",
    "message": "Replay request detected."
  }
}
```

## H) Test vectors v1

Golden vector (`TEST ONLY`) de esta guía:
- `method`: `POST`
- `path`: `/internal/v1/tenants`
- `timestamp`: `1760467200`
- `nonce`: `00000000-0000-0000-0000-000000000001`
- `body_sha256_hex`: `074ff7e98c90bbc45ae4a44402377fe0f3a08c6193defb60cc952a777570ad10`
- `signature_hex`: `1fca0ccbe71a2a79bf9460fcb40fec697500673511110cc5fcfa55c0b4061a50`

Edge vectors esperados:
- `POST /internal/v1/tenants?x=1` => `400 QUERY_NOT_ALLOWED`
- `POST /internal/v1/tenants/` => `400 INVALID_PATH`

> Para detalles ampliados y reglas normativas completas: ver [`docs/OPERATIONAL_SPEC_V1.md`](docs/OPERATIONAL_SPEC_V1.md).

## I) Checklist “Listo para integrar”

- [ ] Core + bridge instalados en el SaaS.
- [ ] Existen 2 keys activas (actual + próxima).
- [ ] Resolución `secret` por `KeyId` implementada.
- [ ] Verificación de querystring prohibido implementada.
- [ ] Verificación de trailing slash inválido implementada.
- [ ] Clock skew ±300s aplicado.
- [ ] Nonce store en DB master con `UNIQUE(key_id, nonce)`.
- [ ] TTL nonce 600s + limpieza por cron activa.
- [ ] Se calcula hash del **raw body exacto** (sin re-serializar).
- [ ] Golden vector de sección H validado extremo a extremo.

## J) Troubleshooting

1. **INVALID_SIGNATURE**
   - Causa: body re-serializado, orden/carácteres cambiados.
   - Solución: firmar/verificar con raw body exacto.

2. **INVALID_SIGNATURE**
   - Causa: firma enviada en base64 en vez de hex lower.
   - Solución: usar siempre `hex_lower`.

3. **INVALID_SIGNATURE**
   - Causa: path distinto al firmado (proxy reescribe ruta).
   - Solución: firmar/verificar mismo path exacto.

4. **REQUEST_EXPIRED**
   - Causa: reloj desincronizado (NTP).
   - Solución: sincronizar NTP en SIPRO, proxy y SaaS.

5. **NONCE_REPLAY**
   - Causa: retry reutiliza mismo nonce dentro de TTL.
   - Solución: generar nonce nuevo por intento; mantener TTL 600s.

6. **QUERY_NOT_ALLOWED / INVALID_PATH**
   - Causa: cliente agrega query params o slash final.
   - Solución: enviar exactamente `/internal/v1/...` sin `?` y sin slash final.

7. **Timeouts en create tenant**
   - Causa: límites bajos en cliente/proxy/PHP.
   - Solución: usar referencia operativa: SIPRO 75s, proxy 90s, PHP 90s.
