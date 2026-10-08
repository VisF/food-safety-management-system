# Base de datos

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

