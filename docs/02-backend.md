# Backend

## Índice

- Sección en español
- English section
## 1. Responsabilidades

El backend implementa autenticación, autorización, reglas del trámite, persistencia, manejo de archivos, generación de respuestas HTML y registro de actividad. Está desarrollado en PHP orientado a objetos y sigue una arquitectura MVC por capas.

## 2. Estructura de capas

### Punto de entrada

[`index.php`](../index.php) inicia la sesión, carga la configuración, crea el router, registra las rutas y despacha la coincidencia. Si no hay coincidencia devuelve HTTP 404.

### Rutas

[`Routes/web.php`](../Routes/web.php) declara método, patrón, middleware y callback de cada endpoint. También normaliza URLs quitando la barra final mediante una redirección 308.

### Controladores

Los controladores reciben la petición y coordinan un caso de uso. Entre los principales se encuentran:

- `AuthControlador`: login, registro y cierre de sesión.
- `HomeControlador`: inicio y resumen del usuario.
- `UsuarioControlador`: perfil y operaciones del ciudadano.
- `DocumentoControlador`: visualización y carga de documentación.
- `InscripcionControlador`: inscripción a cursos y exámenes.
- `ExamenControlador`: detalle y operaciones vinculadas a exámenes.
- `AdminCursoControlador`: ABM de cursos e inscriptos.
- `AdminExamenControlador`: ABM de exámenes, cupos y configuración.
- `AdminDocumentoControlador`: búsqueda y revisión documental.
- `AdminCarnetControlador`: emisión, descarga y anulación lógica de carnets.
- `AdminUsuarioControlador`: administración de usuarios y roles.
- `AdminControlador`: panel y actividad administrativa.

### Servicios

La carpeta [`Servicios/`](../Servicios) concentra la lógica de aplicación. Los servicios coordinan validaciones, repositorios, cambios de estado, notificaciones, reportes, uploads y operaciones de carnet. Algunos módulos relevantes son `UsuarioService`, `CursoService`, `ExamenService`, `InscripcionService`, `DocumentoService`, `TramiteService`, `CarnetService`, `ReporteService` y `UploadService`.

### Repositorios

La carpeta [`Repository/`](../Repository) encapsula el acceso a datos. Cada repositorio agrupa consultas de un área: usuarios, cursos, exámenes, documentos, inscripciones, trámites, resultados, carnets, configuración y auditoría.

### Vistas

Las vistas PHP de [`Views/`](../Views) renderizan HTML. [`Views/BaseVista.php`](../Views/BaseVista.php) centraliza escape HTML, generación de rutas, token CSRF y elementos comunes.

## 3. Flujo de una petición

1. Apache dirige la URL a `index.php`.
2. Se inicia o recupera la sesión.
3. Se carga la configuración y se registra `Routes/web.php`.
4. El router busca método y patrón.
5. Para rutas protegidas se valida sesión y rol.
6. Para formularios POST se valida CSRF.
7. El controlador ejecuta el caso de uso a través de servicios.
8. Los servicios consultan repositorios y actualizan el estado.
9. El controlador entrega datos a una vista o redirige con un mensaje.

## 4. Autenticación y autorización

- `AuthMiddleware` exige `$_SESSION['usuario_id']`.
- `RoleMiddleware` compara `$_SESSION['usuario_roles']` contra los roles permitidos.
- Las rutas administrativas requieren autenticación y rol `admin`.
- El seed contempla los roles `usuario`, `admin` e `inspector`; la cobertura efectiva de cada rol debe mantenerse alineada con las rutas.
- Las contraseñas deben almacenarse con `password_hash` y verificarse con `password_verify`.

## 5. Protección de formularios y salidas

- Los formularios POST generan un token mediante `CsrfMiddleware`.
- Las rutas POST validan el token antes de modificar datos.
- `BaseVista::e()` aplica `htmlspecialchars` con UTF-8 para evitar inyección HTML.
- Los repositorios usan consultas preparadas y PDO con `ATTR_EMULATE_PREPARES=false`.
- Las descargas deben comprobar que el recurso pertenece al usuario autenticado o que la ruta corresponde a una operación administrativa autorizada.

## 6. Archivos y logs

`UploadService` gestiona validación y almacenamiento de archivos. Las cargas de ciudadanos se guardan en `uploads/` y los carnets en `uploads/carnets/`. Los logs por módulo se encuentran en [`logs/`](../logs) y sirven para diagnosticar autenticación, documentos, exámenes, carnets, trámites y uploads.

## 7. Tareas auxiliares

[`Tools/cron/`](../Tools/cron) contiene procesos para alertas de vencimiento y validación de recursantes. En producción deben programarse mediante el mecanismo de tareas del servidor, con permisos limitados y registro de errores.
---

# English: Backend

## Table of Contents

1. [Responsibilities and layers](#responsibilities-and-layers)
2. [Request lifecycle](#request-lifecycle)
3. [Authentication and authorization](#authentication-and-authorization)
4. [Input, output, and files](#input-output-and-files)
5. [Jobs and logging](#jobs-and-logging)

## Responsibilities and layers

The backend is an object-oriented PHP application organized as a layered MVC architecture. It handles authentication, authorization, process rules, persistence, file management, HTML responses, notifications, and activity logging. Each layer should keep its responsibility: routes dispatch, controllers coordinate use cases, services apply application rules, repositories access data, and views render output.

[`index.php`](../index.php) starts the session, loads configuration, creates the router, registers routes, and dispatches the matched request. [`Routes/web.php`](../Routes/web.php) declares method, pattern, middleware, and callback. The `Servicios/` layer coordinates validation, repositories, state changes, reports, uploads, notifications, and certificates. The `Repository/` layer encapsulates SQL and the `Views/` layer renders escaped HTML.

## Request lifecycle

1. Apache forwards the request to `index.php`.
2. The application starts or restores the session and loads environment configuration.
3. Routes are registered and the router matches method and pattern.
4. Middleware checks authentication, role, maintenance mode, and CSRF when applicable.
5. The controller validates the use-case input and calls a service.
6. The service applies business rules and coordinates one or more repositories.
7. The controller renders a view or redirects with a controlled status message.

Errors should be visible in logs and communicated with an appropriate user-facing message. A successful response must not hide a failed database operation or invalid file upload.

## Authentication and authorization

`AuthMiddleware` requires the authenticated user identifier in the session. `RoleMiddleware` compares the session roles with the roles permitted by the route. Administrative endpoints require an authenticated `admin` role, while inspector and citizen access must remain limited to their intended operations.

Passwords must be created with `password_hash` and checked with `password_verify`. Sessions must not expose password material. Role assignment and account activation are administrative actions and should be audited.

## Input, output, and files

POST forms use CSRF tokens generated and checked by the middleware. `BaseVista::e()` escapes values with UTF-8 before rendering them in HTML. Repositories use native prepared PDO statements with emulated prepares disabled. Controllers and services validate identifiers, states, dates, roles, and uploaded files before persistence.

`UploadService` validates and stores files under `uploads/`; certificates use the certificates subdirectory. A download must confirm ownership or an authorized administrative/public-consultation rule before opening the path. Logs under `logs/` are operational records and must not become a public data source.

## Jobs and logging

Scheduled tools handle certificate expiration notices and retake validation. Production scheduling must use restricted permissions, an explicit environment, and error logging. Jobs should be idempotent where possible so that a retry does not duplicate notifications or change a process unexpectedly.
