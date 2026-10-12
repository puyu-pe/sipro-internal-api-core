# Sipro Internal API Core 2.0

Contratos PHP >=8.1 y política compartida para las integraciones Sipro en
`/internal/v1`. La versión **2.0.0** elimina la compatibilidad con la identidad
legacy. Laravel y CI3 delegan en `Http\InternalApi` la validación, el despacho,
HMAC y la traducción de errores.

## Contrato de identidad

| Superficie | Nombre canónico |
|---|---|
| Propiedades PHP y argumentos nombrados | `resolveKey` |
| Solicitud de provisioning | `project.resolveKey` |
| Otras solicitudes de tenant | `resolveKey` |
| Respuesta de provisioning | `resolve_key` (esquema snake_case) |
| Otras respuestas de tenant | `resolveKey` |

Se rechazan campos de identidad `appKey`, `app_key` y el uso de `resolve_key` en
solicitudes que requieren `resolveKey`, incluso si también incluyen el campo
correcto. La identidad de la ruta y la del cuerpo deben coincidir.
Los adaptadores verifican la pertenencia del proyecto y de la base de datos.

## Operaciones

Todas utilizan POST y responden con HTTP 200 y `{ "ok": true, ...camposDTO }`:

- `/internal/v1/tenants`: provisioning.
- `/internal/v1/tenants/{resolveKey}:warn`, `:suspend`, `:activate`, `:close`, `:reopen`.
- `/internal/v1/tenants/{resolveKey}:export`, `:import`.
- `/internal/v1/tenants/{resolveKey}:impersonation-users/search`.
- `/internal/v1/tenants/{resolveKey}:impersonate`.

Cada capacidad tiene su interfaz en `Contracts\Adapter`. `TenantAdapterInterface`
agrupa provisioning y lifecycle; clone e impersonation tienen interfaces propias.

```json
{"resolveKey":"tenant-001","projectCode":"PROJ","reason":"MAINTENANCE"}
```

Los DTOs de provisioning mantienen `project`, `client`, `services`, módulos y
credenciales anidados. Los campos suministrados se validan antes de convertirlos;
un valor de tipo incorrecto no se transforma silenciosamente. La búsqueda tiene
`page=1`, `perPage=20`, máximo 50. La impersonación aplica duración predeterminada
5 minutos y límites 5–60, configurables por el bridge.

## Errores

`InternalApiError` es un valor. Para interrumpir una operación se utiliza la
excepción compartida:

```php
use PuyuPe\SiproInternalApiCore\Errors\ErrorFactory;
use PuyuPe\SiproInternalApiCore\Errors\InternalApiException;

throw new InternalApiException(ErrorFactory::tenantNotFound($resolveKey));
```

`Http\Response\ApiResponse` define los estados HTTP. Todos los errores tienen
la forma `{ "ok": false, "error": { "code": "...", "message": "...", "details": {} } }`.
`details` se omite cuando está vacío. Una excepción inesperada devuelve
`500 INTERNAL_ERROR` sin serializar su mensaje interno.

## Firma y replay

```php
use PuyuPe\SiproInternalApiCore\Security\Hmac\HmacSigner;

$headers = (new HmacSigner())->buildSignedHeaders(
    method: 'POST',
    path: '/internal/v1/tenants/tenant-001:warn',
    rawBody: $rawBody,
    keyId: 'sipro-current',
    secret: $_ENV['SIPRO_INTERNAL_SECRET'],
);
```

La firma es HMAC-SHA256 hexadecimal minúscula sobre el cuerpo exacto. No se
aceptan query strings, slash final ni otra codificación de firma. El nonce es
obligatorio incluso cuando se deshabilita su persistencia.

`Security\Hmac\NonceStoreInterface::consume(string $nonceKey, int $ttlSeconds): bool`
debe reservar atómicamente el nonce: `true` para la primera reserva, `false` para
una reserva vigente, excepción para fallos de almacenamiento. La reserva ocurre
después de comprobar la firma. Consulte [la especificación 2.0](docs/OPERATIONAL_SPEC_V2.md).

## Migración desde 1.x

Actualice payloads, argumentos nombrados y overrides a los nombres canónicos.
Reemplace la excepción específica de Laravel por `InternalApiException`, implemente
`consume` en los nonce stores y utilice las firmas simplificadas de `HmacSigner`.
Los helpers solo necesitan `databasePrefix()` cuando se utiliza la generación
predeterminada de nombres de DB. También se puede sobrescribir `buildDatabaseName()`;
los adaptadores que usan otros helpers no necesitan definir un prefijo.
La transición no rota credenciales ni modifica Laravel `APP_KEY` o `setting.api_key`.

## Pruebas

Ejecute `composer install` y `composer test`. Los bridges consumen los mismos
casos de `resources/contracts/v2/`, con respuestas completas y controles de
invocación del adaptador. El workspace coordinador incluye entornos Docker para
las versiones mínimas de PHP y las pruebas de integración entre paquetes.

## Instalación y plataformas

```bash
composer require puyu-pe/sipro-internal-api-core:^2.0
```

Core admite PHP >=8.1 y se verifica en PHP 8.1, 8.2, 8.3 y 8.4. No requiere Laravel,
CodeIgniter, una base de datos ni herramientas de clonado para utilizar DTOs o firmar
solicitudes. PHPUnit es una dependencia de desarrollo, no del consumidor.

| Aplicación objetivo | Paquetes necesarios según su función |
|---|---|
| Sipro, cliente de la API (PHP 8.4) | core |
| Yures y Yubus (PHP 8.3 / Laravel 12) | core + bridge Laravel; clone-db si se usa clonado |
| Yubiz (PHP 8.2 / Pocketarc CI3 3.3) | core + bridge CI3; clone-db si se usa clonado |

La compatibilidad de plataforma no sustituye la migración del contrato 1.x:
actualice las restricciones Composer, excepciones y overrides de cada consumidor.
