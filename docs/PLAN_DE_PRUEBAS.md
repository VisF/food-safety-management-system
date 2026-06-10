# Plan de Pruebas - Sistema de Manipulación de Alimentos

## Objetivo

Verificar que el flujo completo del sistema funcione correctamente desde la inscripción hasta la emisión del carnet.

---

# Prueba 1 - Inscripción a Curso

## Precondiciones

* Existe un usuario.
* Existe al menos un curso activo.

## Pasos

1. Iniciar sesión.
2. Inscribirse al curso presencial.
3. Verificar en la tabla `inscripciones`.

## Resultado esperado

Se crea una inscripción con:

* usuario_id correcto
* curso_id correcto
* estado_tramite_id = CURSANDO

---

# Prueba 2 - Carga de Documentación

## Precondiciones

* Existe una inscripción en estado CURSANDO.

## Pasos

1. Cargar documento DNI.
2. Cargar Foto Carnet.
3. Validar ambos documentos.

## Resultado esperado

En la tabla `documentos`:

* Existen ambos registros.
* validado = 1.

---

# Prueba 3 - Registro de Asistencia

## Precondiciones

* Existe una inscripción presencial.

## Pasos

1. Registrar asistencia.
2. Registrar presencia.

## Resultado esperado

En la tabla `asistencias`:

* Existe al menos un registro.
* presente = 1.

---

# Prueba 4 - Validación del Curso

## Precondiciones

* Documentación validada.
* Asistencia suficiente.

## Pasos

1. Ejecutar validación.
2. Revisar resultado.

## Resultado esperado

procesarValidacion() devuelve:

* resultado_general = true
* pueden_rendir = true

La inscripción pasa a:

* estado_tramite_id = HABILITADO_EXAMEN

---

# Prueba 5 - Inscripción a Examen

## Precondiciones

* Estado HABILITADO_EXAMEN.
* Existe examen activo.

## Pasos

1. Inscribirse a examen.

## Resultado esperado

Se crea una nueva inscripción:

* examen_id correcto
* estado_tramite_id = INSCRIPTO_EXAMEN

Se descuenta un cupo del examen.

---

# Prueba 6 - Registrar Resultado Aprobado

## Precondiciones

* Existe inscripción a examen.

## Pasos

1. Registrar nota 85.

## Resultado esperado

En `resultado_examen`:

* aprobado = 1

La inscripción cambia a:

* estado_tramite_id = EXAMEN_APROBADO

---

# Prueba 7 - Emisión de Carnet

## Precondiciones

* Examen aprobado.

## Pasos

1. Ejecutar emitirCarnet().

## Resultado esperado

En `carnets`:

* Se genera registro.
* Se asigna número de carnet.
* Se guarda fecha de emisión.
* Se guarda fecha de vencimiento.

La inscripción cambia a:

* estado_tramite_id = CARNET_EMITIDO

---

# Prueba 8 - Intentar Emitir Carnet Duplicado

## Precondiciones

* Ya existe carnet para la inscripción.

## Pasos

1. Ejecutar emitirCarnet() nuevamente.

## Resultado esperado

* success = false
* Mensaje indicando que ya existe un carnet.

---

# Prueba 9 - Examen Reprobado

## Precondiciones

* Existe inscripción a examen.

## Pasos

1. Registrar nota 20.

## Resultado esperado

En `resultado_examen`:

* aprobado = 0

La inscripción cambia a:

* estado_tramite_id = RECHAZADO

---

# Prueba 10 - Reportes

## Pasos

1. Ejecutar obtenerReportePorFecha().
2. Ejecutar obtenerEstadisticasTramites().

## Resultado esperado

Los valores coinciden con los datos existentes en la base.

---

# Prueba 11 - Obtener Trámites de Usuario

## Pasos

1. Ejecutar obtenerTramitesUsuario(id_usuario).

## Resultado esperado

Se listan todas las inscripciones del usuario con:

* curso
* examen
* fecha
* estado

---

# Prueba 12 - Trámites Pendientes

## Pasos

1. Ejecutar obtenerTramitesPendientes().

## Resultado esperado

Sólo aparecen estados:

* PENDIENTE
* CURSANDO
* HABILITADO_EXAMEN
* INSCRIPTO_EXAMEN

No deben aparecer:

* RECHAZADO
* EXAMEN_APROBADO
* CARNET_EMITIDO

---

# Prueba 13 - Estadísticas

## Pasos

1. Ejecutar obtenerEstadisticasTramites().

## Resultado esperado

Verificar:

* total_tramites
* aprobados
* rechazados
* en_tramite
* tasa_aprobacion
* tasa_rechazo
* dias_promedio_tramite

---

# Prueba 14 - Exportación DIPA

## Pasos

1. Ejecutar generarDocumentacionParaDIPA().

## Resultado esperado

Se genera archivo CSV con:

* DNI
* Nombre
* Apellido
* Email
* Fecha de inscripción

Solo para usuarios aprobados sin carnet emitido.

---

# Criterio de Aceptación Final

El flujo completo debe ser:

CURSANDO
→ HABILITADO_EXAMEN
→ INSCRIPTO_EXAMEN
→ EXAMEN_APROBADO
→ CARNET_EMITIDO

sin errores de PHP, PDO ni restricciones de base de datos.
