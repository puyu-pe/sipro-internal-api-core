# Contrato operativo de las librerías 2.0

Esta es la referencia para los paquetes 2.0.0. El prefijo HTTP continúa siendo
`/internal/v1`; los consumidores deben actualizarse coordinadamente al contrato
canónico. No existe una segunda interpretación legacy del payload.

## Procesamiento

1. Una integración deshabilitada responde 404.
2. Validar el request target exacto: query string → `QUERY_NOT_ALLOWED`; path
   inválido, espacios, doble slash o slash final → `INVALID_PATH`.
3. Resolver operación; ruta desconocida → 404, método distinto de POST → 405.
4. Verificar headers, timestamp, credencial y firma; reservar nonce atómicamente.
5. Decodificar objeto JSON y validar DTO, identidad y políticas.
6. Resolver el adaptador de la capacidad y ejecutar una sola operación.
7. Serializar su DTO o el error compartido.

## Canonical y firma

```text
{METHOD}\n{PATH}\n{TIMESTAMP}\n{NONCE}\n{BODY_SHA256_HEX}
```

`METHOD` está en mayúsculas; `PATH` es el path exacto aceptado. El hash utiliza
los bytes originales del cuerpo, sin volver a serializar el JSON.
Único algoritmo/codificación: **HMAC-SHA256 / hexadecimal minúscula**.

Headers obligatorios: `X-Internal-KeyId`, `X-Internal-Timestamp`,
`X-Internal-Nonce`, `X-Internal-Signature`.
El margen temporal se configura en el constructor del verificador: 300 segundos
por defecto, rango admitido 0–86400. Los extremos de la ventana son inclusivos.

## Nonce

- Identidad de reserva: SHA-256 del JSON `[keyId, nonce]`, sin concatenación ambigua.
- TTL: `2 * allowedClockSkewSeconds + 1`; con el valor predeterminado son 601 segundos.
- La reserva se realiza después de verificar la firma y antes de ejecutar negocio.
- `consume` es atómico: una solicitud gana incluso bajo concurrencia.
- Una firma inválida no consume el nonce. Un fallo del store detiene la solicitud
  con 500. La expiración permite nuevas reservas.
- Laravel admite cache compartido file, database o Redis con `add` atómico.
  CI3 utiliza la tabla `internal_api_nonces` en una conexión master dedicada.
  Un cache local por proceso no cumple el contrato de replay entre workers.
- Deshabilitar la persistencia de nonce mantiene su header dentro de la firma.

## Estados y errores

| Estado | Códigos |
|---|---|
| 400 | `VALIDATION_ERROR`, `QUERY_NOT_ALLOWED`, `INVALID_PATH` |
| 401 | `INVALID_SIGNATURE`, `REQUEST_EXPIRED`, `NONCE_REPLAY` |
| 404 | `ROUTE_NOT_FOUND`, `TENANT_NOT_FOUND`, `USER_NOT_FOUND` |
| 405 | `METHOD_NOT_ALLOWED`, con `Allow: POST` |
| 409 | `TENANT_ALREADY_EXISTS` |
| 500 | `ADAPTER_NOT_CONFIGURED`, `INTERNAL_ERROR` y errores operativos del adaptador |

Formato: `{ "ok": false, "error": { "code": "...", "message": "...", "details": {} } }`.
Los detalles de validación usan `details.errors` y conservan el campo canónico.
El éxito utiliza `{ "ok": true, ...camposDTO }`, sin un envoltorio `data`.

## Vector de prueba independiente

| Campo | Valor |
|---|---|
| Secret (solo prueba) | `test-secret` |
| Método / path | `POST` / `/internal/v1/tenants/tenant:warn` |
| Timestamp / nonce | `1760467200` / `nonce-vector` |
| Cuerpo exacto | `{"resolveKey":"tenant","projectCode":"PROJ"}` |
| SHA-256 del cuerpo | `f9282946bd0df2aee678126fec27dee46edff0680480e4fb3a190cb0657eb0bf` |
| Firma | `1a777e132ac903c84c7e3720e576efe7586ca6e4366add9558908ab088705592` |

Los timeouts de provisioning deben coordinarse con el trabajo de la aplicación
(referencia: cliente 75 s, proxy/PHP 90 s). Las claves HMAC se administran por
`keyId`; la rotación de secretos y credenciales de tenants es un proceso separado.
