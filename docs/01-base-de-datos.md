# Base de datos

## Índice

- Sección en español
- English section
## 1. Tecnología y creación

La aplicación utiliza MySQL o MariaDB con codificación `utf8mb4` y cotejamiento `utf8mb4_unicode_ci`. El script principal es [`db/schema.sql`](../db/schema.sql). Crea la base `manipulacion_alimentos` y define claves primarias, índices únicos y claves foráneas.

La conexión se centraliza en [`db/Connection.php`](../db/Connection.php), que usa PDO, consultas preparadas nativas, excepciones como modo de error y resultados asociativos.

## 2. Tablas del esquema vigente

### Tablas de configuración y seguridad

- **roles**: catálogo de roles (`usuario`, `admin`, `inspector`).
- **usuario_roles**: relación entre usuarios y roles. Un usuario puede tener más de un rol.
- **estados_tramite**: catálogo de estados del proceso.
- **tipo_inscripcion**: diferencia inscripción a curso e inscripción a examen.
- **configuracion_sistema**: valores configurables, como el plazo para recursantes.

### Identidad de usuarios

- **usuarios**: datos personales, DNI, correo, contraseña cifrada, contacto, estado de actividad y fecha de alta.
  - `dni` es único.
  - `email` es único.
  - `activo` permite desactivar sin borrar el historial.

### Formación y evaluación

- **cursos**: nombre, modalidad presencial/virtual, fecha, horario, ubicación, cupos y estado.
- **examenes**: fecha, horario, ubicación, aula, cupos y estado.
- **inscripciones**: vincula un usuario con un curso o examen y conserva el tipo, estado del trámite, observaciones y fechas.
- **resultado_examen**: nota, aprobación, observaciones y fecha del resultado.

### Documentación y certificación

- **documentos**: archivos subidos por el usuario, tipo, ruta, estado de revisión y observaciones.
  - Tipos definidos: `dni`, `foto_carnet`, `asistencia` y `moodle`.
  - Estados definidos: `pendiente`, `aprobado` y `rechazado`.
- **carnets**: número, fechas de emisión y vencimiento, archivo PDF, vigencia y estado activo.

## 3. Relaciones principales

```text
usuarios 1 --- N usuario_roles N --- 1 roles
usuarios 1 --- N inscripciones
usuarios 1 --- N documentos
cursos   1 --- N inscripciones
examenes 1 --- N inscripciones
inscripciones 1 --- N resultado_examen
inscripciones 1 --- 1 carnets
```

Las relaciones con usuarios eliminan en cascada sus datos dependientes. Las referencias opcionales de curso, examen, tipo y estado usan `SET NULL`, preservando la inscripción aunque se quite el elemento asociado. El carnet tiene una relación única con la inscripción y el número de carnet también es único.

## 4. Estados de trámite utilizados por el seed

El archivo [`db/seed.sql`](../db/seed.sql) define, entre otros, los siguientes estados:

| Estado | Significado |
|---|---|
| `PENDIENTE` | Trámite iniciado |
| `DOCUMENTACION_PENDIENTE` | Falta documentación |
| `DOCUMENTACION_APROBADA` | Documentación validada |
| `INSCRIPTO_EXAMEN` | Inscripción a examen confirmada |
| `APROBADO` | Examen aprobado |
| `DESAPROBADO` | Examen desaprobado |
| `RECHAZADO` | Trámite rechazado |
| `CANCELADO` | Trámite cancelado |
| `CARNET_EMITIDO` | Carnet cargado en el sistema |

Los nombres son datos de catálogo; las reglas de transición se aplican desde servicios y controladores.

## 5. Integridad y acceso a datos

- Las consultas de los repositorios deben recibir parámetros y utilizar PDO preparado.
- Los repositorios encapsulan SQL y no deben ser invocados directamente desde una vista.
- Los servicios coordinan operaciones que afectan varias tablas.
- Las operaciones que modifican el trámite pueden utilizar transacciones mediante `TramiteRepository`.
- Los archivos se almacenan en `uploads/`; la base conserva la ruta y los metadatos, no el binario.

## 6. Carga inicial

Orden recomendado:

1. Ejecutar [`db/schema.sql`](../db/schema.sql).
2. Revisar que el seed corresponda exactamente a ese esquema.
3. Ejecutar [`db/seed.sql`](../db/seed.sql) en un entorno de desarrollo.
4. Cambiar todas las contraseñas de ejemplo antes de utilizar el sistema fuera de desarrollo.

**Observación de entrega:** el seed vigente contiene `DELETE` e inserciones para tablas auxiliares como `asistencias`, `comprobantes_tramite`, `historial_tramite`, `fecha_cursos`, `habilitaciones_examen`, `notificaciones`, `recovery_tokens` y `auditoria_acciones`, mientras que el `schema.sql` reducido actual no las define. Para un despliegue limpio se debe sincronizar uno de los dos archivos antes de ejecutar la carga inicial.
---

# English: Database

## Table of Contents

1. [Technology and connection](#technology-and-connection)
2. [Main entities](#main-entities)
3. [Relationships and process states](#relationships-and-process-states)
4. [Integrity and data access](#integrity-and-data-access)
5. [Initial loading](#initial-loading)

## Technology and connection

The application uses MySQL or MariaDB with `utf8mb4` encoding and the `utf8mb4_unicode_ci` collation. The current schema is defined in [`db/schema.sql`](../db/schema.sql). It creates the application database and defines primary keys, unique indexes, foreign keys, and the relationships used by the process.

[`db/Connection.php`](../db/Connection.php) centralizes the PDO connection. Repositories receive this connection and should use native prepared statements, exception mode, and associative results. Database credentials must come from the environment and must not be copied into source files or committed to the repository.

## Main entities

The security catalog contains `roles`, `usuario_roles`, process states, registration types, and system configuration. `usuarios` stores identity, contact, password hash, active state, and creation date. A user's identity and email are unique, while the active flag allows the account to be disabled without deleting its history.

The training and evaluation entities are `cursos`, `examenes`, `inscripciones`, and `resultado_examen`. A course describes modality, date, time, location, capacity, and active state. An examination describes the session and capacity. A registration links a person to the selected activity and stores type, process state, observations, and dates. The result stores score, approval, observations, and result date.

The certification entities are `documentos` and `carnets`. Documents store type, path, review state, and observations. The certificate stores number, issue and expiration dates, PDF path, validity information, and active state.

## Relationships and process states

A user can have multiple roles, registrations, and documents. Courses and examinations can have many registrations. A registration can have examination results and one associated certificate. The certificate number and the registration relationship are unique where the schema requires it.

The seed catalog includes pending, documentation pending, documentation approved, examination registered, approved, failed, rejected, cancelled, and certificate issued states. These are catalog values; the transition rules are enforced by application services and controllers and should not be changed directly from a view.

## Integrity and data access

Repositories encapsulate SQL and must receive parameters instead of concatenating user input. Services coordinate operations that affect several tables and may use transactions through the process repository. Foreign keys preserve relationships and configured cascade or `SET NULL` actions determine what happens when an associated record is removed.

Files are stored in `uploads/`; the database stores their paths and metadata rather than the binary contents. Download authorization must be checked before serving a stored path. Historical users, registrations, documents, and audit information should not be deleted without an approved retention policy.

## Initial loading

Run [`db/schema.sql`](../db/schema.sql) first, verify that [`db/seed.sql`](../db/seed.sql) matches the same schema, and then run the seed only in a controlled development or deployment procedure. The current seed references auxiliary tables that are not present in the reduced schema, so the two scripts must be synchronized before a clean installation. Replace demonstration passwords and review default roles before exposing the application.
