
# Registro de trabajo — Recuperación de contraseñas y tokens

**Proyecto:** Manipulación de Alimentos  
**Fecha de registro:** 3 de noviembre de 2026  
**Estado:** Trabajo pausado por decisión de alcance. Se confirmó que la funcionalidad no es necesaria en esta etapa del proyecto.

---

## Índice

- Sección en español
- English section
## 1. Motivo de la pausa

Se interrumpió el trabajo sobre recuperación de contraseñas y tokens porque se confirmó que esta funcionalidad no es necesaria para la etapa actual del proyecto.

La pausa no significa que todo el flujo haya sido probado o aprobado funcionalmente. Se documenta qué se revisó, qué cambios se realizaron y qué verificaciones quedaron pendientes para poder retomarlo en el futuro sin repetir el diagnóstico.

## 2. Archivos revisados

| Archivo | Revisión realizada |
|---|---|
| `config/env.php` | Carga de variables `.env` y configuración de zona horaria PHP. |
| `db/Connection.php` | Instancia PDO compartida, opciones de conexión y zona horaria de sesión MariaDB. |
| `Servicios/RecoveryTokenService.php` | Generación, validación y consumo de tokens; actualización de contraseña y transacciones. |
| `Repository/RecoveryTokenRepository.php` | Alta, búsqueda, consumo, invalidación y limpieza de tokens. |
| `UsuarioRepository.php` | Uso de la conexión compartida y disponibilidad de `cambiarPassword()`. |
| Tabla `recovery_tokens` | Columnas, índices, motor de almacenamiento y relación con usuarios. |

## 3. Cambios realizados

### 3.1. Zona horaria de PHP

Se agregó al final de `config/env.php`, después de cargar el archivo `.env`:

```php
date_default_timezone_set('UTC');
```

Esto establece UTC como zona horaria predeterminada de PHP cuando se carga esa configuración.

### 3.2. Zona horaria de la conexión PDO

En `db/Connection.php`, inmediatamente después de crear la conexión PDO, se agregó:

```php
self::$pdo->exec("SET time_zone = '+00:00'");
```

La instrucción configura la zona horaria de la sesión MariaDB correspondiente a esa conexión PDO. No modifica por sí sola la zona horaria global de MariaDB ni las sesiones independientes de phpMyAdmin.

### 3.3. Validación de tokens

Se modificó `obtenerValidoPorHash()` para incluir `fecha_creacion` en el resultado:

```sql
SELECT
    id,
    usuario_id,
    expiracion,
    fecha_creacion
FROM recovery_tokens
WHERE token = :token
  AND usado = 0
  AND expiracion > NOW()
LIMIT 1
```

La intención fue evitar una segunda consulta para recuperar la fecha de creación.

En `RecoveryTokenService::validarToken()` se incorporó la lectura de `fecha_creacion` desde el registro devuelto y la construcción explícita de fechas con `DateTimeZone('UTC')`.

**Atención:** en el código pegado durante la conversación, la expresión regular apareció visualmente deformada por el formato del mensaje. La expresión que debe existir literalmente en el archivo es:

```php
'/\A[a-f0-9]{64}\z/'
```

No se debe asumir que el archivo final contiene esa expresión hasta volver a inspeccionarlo.

### 3.4. Orden de operaciones al restablecer la contraseña

Se cambió el orden del método `restablecerPassword()` para que, dentro de una transacción:

1. Se consuma condicionalmente el token mediante `marcarComoUsado()`.
2. Se actualice la contraseña mediante `UsuarioRepository::cambiarPassword()`.
3. Se invaliden los demás tokens pendientes del usuario.
4. Se confirme la transacción.

Si falla una operación, se ejecuta `rollBack()`.

También se agregó una validación para limitar la contraseña a 72 bytes, debido al límite de bcrypt.

### 3.5. Método de consulta redundante

Se comprobó con una búsqueda recursiva que `consultarFechaDeCreacion()` solo aparecía en su propia definición dentro de `Repository/RecoveryTokenRepository.php`.

Se recomendó eliminarlo porque `obtenerValidoPorHash()` ya devuelve `fecha_creacion`.

**No quedó confirmado en la conversación que el método se haya eliminado efectivamente.** Antes de retomarlo, comprobar el estado actual del archivo.

## 4. Verificaciones realizadas

### 4.1. Conexión PDO compartida

Se revisaron los constructores de:

- `RecoveryTokenService`
- `UsuarioRepository`
- `RecoveryTokenRepository`

Los tres utilizan `Connection::getPDO()`. `Connection` mantiene una propiedad estática PDO, por lo que las llamadas realizadas durante una misma ejecución PHP reciben la misma instancia.

Esto permite que las operaciones de los repositorios participen en una transacción iniciada por el servicio, siempre que no abran conexiones independientes ni administren transacciones incompatibles.

### 4.2. Zona horaria

Antes del cambio se detectó una diferencia entre la zona horaria de PHP (`Europe/Berlin`) y la de MariaDB (`SYSTEM`, con horario local de Argentina).

Después de configurar UTC, se creó temporalmente `test_timezone.php` para comprobar la conexión real de la aplicación.

Resultado observado:

```text
Zona PHP: UTC
Hora PHP: 2026-11-03 18:17:40

Array
(
    [hora_mariadb] => 2026-11-03 18:17:40
    [hora_utc] => 2026-11-03 18:17:40
    [zona_sesion] => +00:00
)
```

La comprobación confirmó que PHP y la sesión PDO utilizada por la aplicación trabajaban con la misma referencia horaria UTC.

Se indicó eliminar `test_timezone.php` después de la prueba. **No quedó confirmado que se haya eliminado.**

### 4.3. Tabla `recovery_tokens`

Se revisó la estructura mediante phpMyAdmin y consultas alternativas, porque `SHOW CREATE TABLE` y algunas consultas a `information_schema` no funcionaron en ese entorno.

Columnas observadas:

| Columna | Tipo/configuración observada |
|---|---|
| `id` | `BIGINT(20) UNSIGNED`, `AUTO_INCREMENT` |
| `usuario_id` | `INT(10) UNSIGNED` |
| `token` | `VARCHAR(255)`, `NOT NULL` |
| `expiracion` | `DATETIME`, `NOT NULL` |
| `usado` | `TINYINT(1)`, valor predeterminado `0` |
| `fecha_creacion` | `DATETIME`, valor predeterminado `CURRENT_TIMESTAMP` |

Motor observado: **InnoDB**.  
Collation observada: `utf8mb4_unicode_ci`.

Índices observados:

- `PRIMARY` sobre `id`.
- Índice único `rt_token_unique` sobre `token`.
- Índice `rt_usuario_idx` sobre `usuario_id`.

Relación observada en la vista de relaciones:

- Restricción: `rt_usuario_fk`.
- `recovery_tokens.usuario_id` referencia `usuarios.id`.
- `ON DELETE CASCADE`.
- `ON UPDATE CASCADE`.

La tabla tenía cero filas durante la consulta de estado. Esto no se considera un error por sí mismo.

### 4.4. Sintaxis PHP

Se ejecutaron estos comandos:

```powershell
php -l .\Servicios\RecoveryTokenService.php
php -l .\Repository\RecoveryTokenRepository.php
php -l .\db\Connection.php
php -l .\config\env.php
```

Los cuatro devolvieron:

```text
No syntax errors detected
```

Esto confirma únicamente la sintaxis de esos archivos en ese momento; no demuestra que el flujo completo funcione.

### 4.5. Referencias a `consultarFechaDeCreacion()`

Se ejecutó una búsqueda recursiva excluyendo `vendor`, `.git` y `.venv`.

El único resultado fue la definición del método en `Repository/RecoveryTokenRepository.php`. No se encontraron otros usos PHP en el alcance de esa búsqueda.

## 5. Aspectos revisados en el código

### `RecoveryTokenService`

Se revisaron:

- Generación del token con `random_bytes(32)`.
- Conversión a hexadecimal mediante `bin2hex()`, que produce 64 caracteres.
- Almacenamiento del hash SHA-256, no del token original.
- Vencimiento configurado en 60 minutos.
- Invalidación de tokens anteriores al generar uno nuevo.
- Validación del formato, existencia, estado y vencimiento.
- Actualización de contraseña con `password_hash(..., PASSWORD_BCRYPT)`.
- Consumo condicional del token.
- Invalidación de otros tokens pendientes.
- Uso de `commit()` y `rollBack()`.

### `RecoveryTokenRepository`

Se revisaron:

- `crear()`
- `obtenerValidoPorHash()`
- `marcarComoUsado()`
- `invalidarPorUsuario()`
- `eliminarAntiguos()`
- `consultarFechaDeCreacion()` (candidato a eliminación por redundancia).

`marcarComoUsado()` realiza un `UPDATE` condicionado por `id`, `usado = 0` y `expiracion > NOW()`, y devuelve `true` solo si se afectó exactamente una fila.

## 6. Trabajo que NO se realizó o quedó pendiente

No se completó una prueba funcional de extremo a extremo. En particular, no quedó documentado que se hayan probado:

- Solicitud real de recuperación desde la vista.
- Envío y recepción del correo.
- Apertura del enlace recibido.
- Validación de un token recién generado.
- Rechazo de un token inexistente.
- Rechazo de un token vencido.
- Rechazo de un token ya utilizado.
- Cambio real de contraseña y autenticación posterior con la nueva contraseña.
- Reutilización del mismo enlace después de un restablecimiento exitoso.
- Rollback provocado por un fallo durante la actualización de contraseña.
- Dos solicitudes simultáneas de restablecimiento con el mismo token.
- Dos solicitudes simultáneas de generación de tokens para el mismo usuario.
- Ejecución y comportamiento de `eliminarAntiguos()`.
- Pruebas de regresión sobre el inicio de sesión y los flujos existentes.

Tampoco se confirmó la eliminación del archivo temporal `test_timezone.php` ni la eliminación efectiva de `consultarFechaDeCreacion()`.

## 7. Observaciones técnicas para una futura reanudación

1. **Verificar el código real antes de continuar.** Los fragmentos compartidos por chat pueden haber sufrido alteraciones de formato, especialmente las expresiones regulares.
2. **Revisar la ruta real del servicio.** En los comandos de consola se utilizó `Servicios/RecoveryTokenService.php`, mientras que en algunos comentarios anteriores se había mencionado `Services/`. Confirmar la estructura efectiva del proyecto.
3. **Revisar el método `UsuarioRepository::cambiarPassword()`.** Confirmar que usa la conexión compartida y que devuelve un resultado coherente cuando actualiza la contraseña.
4. **Revisar el manejo de errores de `invalidarPorUsuario()`.** El método devuelve el resultado de `execute()`, pero el servicio actualmente no comprueba explícitamente ese booleano.
5. **Analizar concurrencia en la generación de tokens.** Invalidar tokens anteriores y crear uno nuevo dentro de una transacción no garantiza por sí solo que dos solicitudes simultáneas no dejen más de un token activo. Si esta funcionalidad vuelve a ser necesaria, evaluar bloqueo por usuario o una estrategia equivalente.
6. **Confirmar el límite de contraseña.** La validación de 72 bytes evita el truncamiento de bcrypt, pero debe ser coherente con las reglas y mensajes de la interfaz.
7. **No considerar la funcionalidad terminada.** La revisión estática y la comprobación de sintaxis no reemplazan las pruebas de integración y seguridad.

## 8. Estado final al pausar

| Área | Estado al momento de pausar |
|---|---|
| Revisión de `Connection.php` | Realizada |
| Conexión PDO compartida | Comprobada por inspección de constructores |
| Configuración UTC de PHP | Aplicada |
| Configuración UTC de sesión PDO | Aplicada y comprobada |
| Estructura de `recovery_tokens` | Revisada visualmente |
| Motor InnoDB | Confirmado |
| Índices | Revisados |
| Clave foránea | Confirmada visualmente |
| Cambios en validación y transacción | Realizados según lo compartido; pendientes de verificación final del archivo |
| Sintaxis de cuatro archivos | Comprobada sin errores |
| Pruebas funcionales completas | No realizadas |
| Pruebas de concurrencia | No realizadas |
| Envío de correo y flujo real | No probado en esta etapa |

**Decisión:** pausar el desarrollo y las pruebas de recuperación de contraseñas porque se confirmó que no es un requisito de la etapa actual del proyecto.

**Criterio para retomarlo:** partir de este registro, inspeccionar el estado actual de los archivos y continuar únicamente si la funcionalidad vuelve a formar parte del alcance.
---

# English: Password Recovery and Token Work Log

## Table of Contents

1. [Scope and pause decision](#scope-and-pause-decision)
2. [Reviewed components](#reviewed-components)
3. [Changes and technical observations](#changes-and-technical-observations)
4. [Verification performed](#verification-performed)
5. [Pending tests and restart criteria](#pending-tests-and-restart-criteria)

## Scope and pause decision

This work log records a review of password recovery and recovery-token handling that was paused because the feature was confirmed to be outside the current project stage. The record is not an approval of the complete flow. It preserves the findings and the checks that must be repeated before the feature is enabled or considered complete.

## Reviewed components

The review covered environment loading and PHP time zone configuration, the shared PDO connection, `RecoveryTokenService`, `RecoveryTokenRepository`, `UsuarioRepository::cambiarPassword()`, and the `recovery_tokens` table. It also considered token generation, SHA-256 storage, expiration, conditional consumption, password hashing, transaction boundaries, invalidation of previous tokens, and cleanup of old tokens.

## Changes and technical observations

The reviewed changes align PHP and the PDO session with UTC, include `fecha_creacion` in the valid-token query, construct dates explicitly with a UTC time zone, consume a token conditionally before changing the password, invalidate other pending tokens, and roll back when a transactional operation fails. A 72-byte password limit was documented because bcrypt has a length limitation.

The token format must be verified literally in the real source file as `/\A[a-f0-9]{64}\z/`. A redundant creation-date query method was identified as a candidate for removal, but its final deletion was not confirmed. Temporary verification files must also be confirmed as removed before deployment.

## Verification performed

The constructors were inspected to confirm that the service and repositories use the shared PDO connection. A time-zone check showed that PHP and the MariaDB session used UTC. The `recovery_tokens` table was reviewed as InnoDB with unique token and user indexes and a cascading user relationship. PHP syntax checks for the four reviewed files returned no syntax errors at that time.

These checks prove syntax and selected configuration only. They do not prove that email delivery, token validation, password replacement, login regression, or concurrent requests work correctly in the real application.

## Pending tests and restart criteria

Before resuming this feature, test request creation, email delivery, valid, expired, used, malformed, and unknown tokens, password replacement, login with the new password, link reuse, rollback, concurrent reset attempts, concurrent token generation, cleanup, and regression of existing login flows.

First inspect the current files rather than relying on copied chat fragments. Confirm the actual service path, the password-update return value, error handling for token invalidation, the 72-byte rule and its UI message, and the removal of temporary files and redundant methods. Only then decide whether integration and security testing should continue.
