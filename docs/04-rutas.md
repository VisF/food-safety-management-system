# Rutas HTTP

La base de la aplicación es `/manipulacionDeAlimentos`. Las rutas se declaran en [`Routes/web.php`](../Routes/web.php). Las rutas con `POST` requieren CSRF; las marcadas como administrativas requieren sesión y rol `admin`.

## 1. Acceso y ciudadanía

| Método | Ruta | Acceso | Función |
|---|---|---|---|
| GET | `/` | Público / sesión | Inicio |
| GET | `/login` | Público | Formulario de login |
| POST | `/login` | Público | Autenticar usuario |
| GET | `/registro` | Público | Formulario de registro |
| POST | `/registro` | Público | Crear usuario |
| GET | `/logout` | Sesión | Cerrar sesión |
| GET | `/inscripciones` | Sesión | Listar opciones de inscripción |
| POST | `/curso/inscribirse` | Sesión | Inscribirse a un curso |
| GET | `/detalle_examen/{id}` | Sesión | Ver detalle de examen |
| POST | `/inscripcion/confirmar` | Sesión | Confirmar inscripción a examen |
| GET | `/documentacion` | Sesión | Ver documentación propia |
| POST | `/documentos/subir` | Sesión | Subir un documento |
| GET | `/documentos/{id}/descargar` | Sesión | Descargar documento autorizado |
| GET | `/perfil` | Sesión | Ver perfil |
| POST | `/perfil/actualizar` | Sesión | Actualizar perfil |
| GET | `/carnet/descargar` | Sesión | Descargar carnet propio |

## 2. Consulta pública

| Método | Ruta | Acceso | Función |
|---|---|---|---|
| GET | `/consulta-publica` | Público | Consultar carnet |
| GET | `/consulta-publica/carnet/{id}/descargar` | Público controlado | Descargar carnet consultado |
| GET | `/consulta-publica/carnet/{id}/foto` | Público controlado | Ver foto asociada |

La consulta pública debe limitar los datos mostrados a la verificación necesaria del carnet y aplicar las reglas de privacidad definidas por el área.

## 3. Administración general

| Método | Ruta | Función |
|---|---|---|
| GET | `/admin` | Panel |
| GET | `/admin/actividad` | Actividad reciente |
| GET | `/admin/usuarios` | Listar usuarios |
| POST | `/admin/usuarios` | Crear/guardar usuario |
| POST | `/admin/usuarios/{id}` | Actualizar usuario |
| POST | `/admin/usuarios/{id}/activar` | Activar usuario |
| POST | `/admin/usuarios/{id}/desactivar` | Desactivar usuario |

## 4. Cursos

| Método | Ruta | Función |
|---|---|---|
| GET | `/admin/cursos` | Listar cursos |
| GET | `/admin/cursos/nuevo` | Formulario de alta |
| POST | `/admin/cursos` | Crear curso |
| GET | `/admin/cursos/{id}/editar` | Formulario de edición |
| POST | `/admin/cursos/{id}` | Actualizar curso |
| POST | `/admin/cursos/{id}/activar` | Activar curso |
| POST | `/admin/cursos/{id}/desactivar` | Desactivar curso |
| GET | `/admin/cursos/{id}/inscriptos` | Ver inscriptos |
| POST | `/admin/cursos/{cursoId}/inscriptos/{inscripcionId}/aprobar` | Aprobar inscripción/asistencia |
| POST | `/admin/cursos/{cursoId}/inscriptos/{inscripcionId}/desaprobar` | Desaprobar inscripción/asistencia |

## 5. Exámenes e inscripciones

| Método | Ruta | Función |
|---|---|---|
| GET | `/admin/examenes` | Listar exámenes |
| GET | `/admin/examenes/nuevo` | Formulario de alta |
| POST | `/admin/examenes` | Crear examen |
| GET | `/admin/examenes/{id}` | Ver detalle |
| GET | `/admin/examenes/{id}/editar` | Formulario de edición |
| POST | `/admin/examenes/{id}` | Actualizar examen/resultados |
| POST | `/admin/examenes/{id}/activar` | Activar examen |
| POST | `/admin/examenes/{id}/desactivar` | Desactivar examen |
| GET | `/admin/inscripciones/{id}` | Ver trámite |
| POST | `/admin/inscripciones/{id}` | Actualizar estado/observaciones |
| POST | `/admin/examenes/configuracion/recursante` | Configurar plazo de recursante |

## 6. Carnets y documentos administrativos

| Método | Ruta | Función |
|---|---|---|
| GET | `/admin/carnets` | Listar carnets |
| GET | `/admin/carnets/{id}/cargar` | Formulario de carga |
| POST | `/admin/carnets/{id}/emitir` | Cargar carnet emitido externamente |
| GET | `/admin/carnets/{id}/descargar` | Descargar carnet |
| POST | `/admin/carnets/{id}/anular` | Anular carnet |
| GET | `/admin/documentos` | Listar documentos |
| GET | `/admin/documentos/buscar` | Buscar documentos |
| POST | `/admin/documentos/{id}/aprobar` | Aprobar documento |
| POST | `/admin/documentos/{id}/rechazar` | Rechazar documento |
| POST | `/admin/documentos/{id}/rechazar-asistencia` | Rechazar constancia de asistencia |
| GET | `/admin/documentos/{id}/descargar` | Descargar documento |

Los segmentos `{id}`, `{cursoId}` e `{inscripcionId}` se reciben como enteros según las restricciones de AltoRouter.

