# SIPRO Internal API - Anexo Operativo v1 (Fuente de verdad)

Documento normativo de operación para integraciones SIPRO ↔ SaaS en `/internal/v1`.

---

## 1) Alcance

Este anexo define:
- Contrato de firmado/verificación HMAC.
- Reglas de transporte y validación de rutas.
- Anti-replay por nonce en DB master.
- Códigos de error operativos mínimos.
- Parámetros de tiempo (clock skew, TTL, timeouts recomendados).

El `README.md` es guía de implementación rápida.
Este documento es la **referencia completa** para auditoría y troubleshooting.

---

## 2) Decisiones cerradas v1

1. Algoritmo: `HMAC-SHA256`
2. Firma: `hex_lower`
3. Canonical string:
   ```text
   {METHOD}\n{PATH}\n{TIMESTAMP}\n{NONCE}\n{BODY_SHA256_HEX}
   ```
4. `PATH`: solo path (sin dominio), sin querystring
5. Querystring: **PROHIBIDO** (`?` => `400 QUERY_NOT_ALLOWED`)
6. `PATH` con slash final: **inválido** (`400 INVALID_PATH`)
7. Clock skew permitido: `±300s`
8. Anti-replay: nonce store en DB master, TTL `600s`, atomicidad por `UNIQUE(key_id, nonce)`
9. `KeyId` obligatorio, mínimo 2 llaves activas
10. Timeouts recomendados para create tenant (~1 min): SIPRO `75s`, proxy `90s`, PHP `90s`

---

## 3) Headers requeridos

- `X-Internal-KeyId`
- `X-Internal-Timestamp` (epoch seconds)
- `X-Internal-Nonce`
- `X-Internal-Signature`

Faltante de cualquiera => rechazo (`VALIDATION_ERROR` o equivalente de validación de headers).

---

## 4) Canonical y firma

## 4.1 Regla de canonical

```text
{METHOD}\n{PATH}\n{TIMESTAMP}\n{NONCE}\n{BODY_SHA256_HEX}
```

Dónde:
- `METHOD`: verbo HTTP en mayúsculas (ej. `POST`).
- `PATH`: exacto, sin dominio, sin querystring, sin normalizaciones.
- `TIMESTAMP`: string del epoch (segundos).
- `NONCE`: string único por request.
- `BODY_SHA256_HEX`: `sha256(rawBody)` en hex minúsculas.

## 4.2 Reglas de body

- Firmar/verificar el **raw body exacto** recibido.
- No re-serializar JSON para calcular hash.
- Cambios de espacios, orden o encoding modifican la firma.

---

## 5) Validaciones del servidor

Orden recomendado:
1. Validar path (`?` prohibido, slash final inválido).
2. Validar presencia de headers obligatorios.
3. Validar timestamp dentro de ±300s.
4. Resolver secret por `KeyId`.
5. Validar nonce anti-replay (DB master, TTL 600s).
6. Recalcular canonical + firma y comparar en tiempo constante.

---

## 6) Anti-replay nonce en DB master (sin Redis)

## 6.1 Estructura conceptual

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

## 6.2 Reglas

- TTL operativo: `600s`.
- Insert atómico por `(key_id, nonce)`.
- Si inserción choca por unique => `NONCE_REPLAY`.
- Limpieza periódica:
  ```sql
  DELETE FROM internal_request_nonces WHERE expires_at < NOW();
  ```

---

## 7) Gestión de llaves

- Requerido: mínimo 2 llaves activas (actual/próxima).
- Resolver secret por `KeyId`.
- Rotación recomendada:
  1. Publicar key nueva como activa secundaria.
  2. Migrar emisor SIPRO a la nueva.
  3. Esperar ventana segura.
  4. Retirar key anterior.

Nunca loguear secretos en texto plano.

---

## 8) Errores operativos (mínimos)

- `INVALID_SIGNATURE`
- `REQUEST_EXPIRED`
- `NONCE_REPLAY`
- `VALIDATION_ERROR`
- `QUERY_NOT_ALLOWED`
- `INVALID_PATH`

Formato recomendado:

```json
{
  "ok": false,
  "error": {
    "code": "INVALID_SIGNATURE",
    "message": "Invalid request signature."
  }
}
```

---

## 9) Golden vector v1 (TEST ONLY)

- `secret`: `TEST_ONLY__CHANGE_ME__2026`
- `method`: `POST`
- `path`: `/internal/v1/tenants`
- `timestamp`: `1760467200`
- `nonce`: `00000000-0000-0000-0000-000000000001`
- `body_sha256_hex`: `074ff7e98c90bbc45ae4a44402377fe0f3a08c6193defb60cc952a777570ad10`
- `signature_hex`: `1fca0ccbe71a2a79bf9460fcb40fec697500673511110cc5fcfa55c0b4061a50`

Canonical literal:

```text
POST
/internal/v1/tenants
1760467200
00000000-0000-0000-0000-000000000001
074ff7e98c90bbc45ae4a44402377fe0f3a08c6193defb60cc952a777570ad10
```

---

## 10) Timeouts recomendados (create tenant)

- SIPRO client timeout: `75s`
- Proxy/API Gateway timeout: `90s`
- PHP max execution time: `90s`

Objetivo: permitir provisión ~1 min sin falsos timeouts.

---

## 11) Checklist operativo rápido

- Path sin query y sin slash final.
- Headers HMAC presentes.
- Timestamp dentro de ±300s.
- Nonce insertado en DB master con unique.
- Firma comparada en tiempo constante.
- Secrets no expuestos en logs/errores.

