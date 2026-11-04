# Manual administrativo

## Índice

- Sección en español
- English section
## 1. Acceso y responsabilidad

El administrador ingresa con una cuenta que tenga el rol `admin`. Desde el
**Panel Administrativo** puede ver estadísticas, actividad reciente y accesos
a las áreas operativas.

El administrador debe verificar los datos antes de aprobar documentación,
registrar resultados o cargar un carnet. Estas acciones afectan el historial
del trámite y pueden dejar registro de auditoría.

## 2. Orden recomendado de trabajo

1. Crear o actualizar cursos y fechas de examen.
2. Revisar inscripciones y cupos.
3. Revisar y resolver la documentación.
4. Registrar asistencia.
5. Registrar resultados.
6. Descargar la documentación necesaria para DIPA.
7. Cargar el carnet emitido por DIPA.
8. Verificar el estado final y la consulta pública.

No cargue un carnet antes de tener una inscripción aprobada y los datos del
documento oficial disponibles.

## 3. Gestión de cursos

En **Administración > Cursos**:

### Crear un curso

1. Seleccione **Nuevo curso**.
2. Complete nombre, modalidad, fecha, hora, ubicación y cupos.
3. Revise los datos y seleccione **Guardar curso**.
4. Compruebe que el curso figure activo y que el cupo sea correcto.

### Editar o desactivar

Use **Editar** para corregir datos de una fecha existente. Desactive un curso
cuando ya no deba ofrecerse; prefiera desactivar antes que borrar información
histórica. Revise los inscriptos antes de cambiar fecha, ubicación o cupos.

La tabla permite ver la cantidad de inscriptos respecto del cupo configurado.
No anuncie una fecha hasta verificar esa relación.

## 4. Gestión de exámenes

En **Administración > Exámenes**:

### Crear o editar una mesa

1. Seleccione **Nuevo examen** o **Editar**.
2. Complete fecha, hora, ubicación y cupos según el formulario.
3. Guarde y confirme que la mesa esté activa.

El listado puede ordenarse por fecha más cercana o más lejana. Desactive
mesas que ya no estén disponibles para evitar nuevas inscripciones.

### Configurar recursantes

En **Configuración de recursantes**, indique el plazo en días y seleccione
**Guardar configuración**. El plazo comienza a contar desde la fecha del
examen desaprobado. El valor contemplado por el proyecto es 90 días, pero el
administrador puede modificarlo según la regla vigente.

## 5. Revisar documentación

En **Administración > Documentación**:

1. Use el buscador por CUIL/CUIT para localizar una persona o revise el
   listado de pendientes.
2. Abra cada documento y compruebe legibilidad, identidad y correspondencia
   con el requisito.
3. Descargue o visualice el archivo cuando sea necesario.
4. Seleccione **Aprobar** si cumple.
5. Seleccione **Rechazar** y escriba una observación concreta si no cumple.

Una observación útil indica qué debe corregirse, por ejemplo: “La foto está
recortada; cargar una imagen donde se vea el rostro completo”. No apruebe un
documento solo porque existe un archivo cargado.

La documentación puede incluir DNI, foto carnet y certificado Moodle. Para el
curso presencial, el certificado Moodle no necesariamente forma parte de los
requisitos; siga lo que indique el trámite y la modalidad.

## 6. Inscripciones, asistencia y resultados

En la gestión de inscripciones a examen, seleccione la mesa y revise las
personas anotadas. La vista permite consultar el estado y registrar:

- asistencia o inasistencia;
- nota;
- aprobado o desaprobado;
- observaciones;
- cancelación cuando corresponda.

Antes de marcar como habilitada a una persona, valide que la documentación
requerida esté aprobada. Para un curso presencial, controle la asistencia
mínima. Para una persona virtual, compruebe el certificado Moodle. Para un
recursante, confirme que esté dentro del plazo.

Registre resultados una sola vez y corrija mediante la acción prevista por la
aplicación, no creando una inscripción duplicada.

## 7. Carga del carnet emitido por DIPA

En **Administración > Carnets**:

1. Localice la inscripción aprobada o busque por CUIL/CUIT.
2. Abra **Cargar carnet oficial**.
3. Ingrese número de carnet, fecha de emisión y fecha de vencimiento.
4. Adjunte el archivo oficial emitido por DIPA y la foto si el formulario la
   solicita.
5. Verifique que el titular, CUIL/CUIT y fechas coincidan con el documento.
6. Guarde y confirme que aparezca como carnet emitido.

DIPA continúa siendo el sistema de emisión oficial. Esta pantalla registra el
resultado de ese proceso externo y lo pone a disposición del ciudadano y de
la consulta pública.

## 8. Gestión de usuarios

En **Administración > Usuarios**, busque y revise cuentas. Las acciones de
alta, edición, activación/desactivación y actualización de roles deben
realizarse con criterio de mínimo privilegio:

- `usuario`: acceso al trámite propio;
- `inspector`: consulta de carnets;
- `admin`: gestión completa.

No asigne `admin` para resolver un inconveniente ciudadano. Compruebe el
correo y el CUIL/CUIT antes de modificar una cuenta.

## 9. Consulta pública como control

Después de emitir un carnet, compruebe desde **Consulta pública** que el
CUIL/CUIT devuelva el titular correcto, el número y las fechas esperadas.
También verifique que la descarga del carnet funcione.

## 10. Buenas prácticas administrativas

- Mantener una única inscripción por instancia de examen.
- Escribir observaciones claras y accionables.
- No aprobar documentos ilegibles o de otra persona.
- No cargar datos de DIPA sin contrastar el documento oficial.
- No compartir archivos descargados fuera de los canales autorizados.
- Revisar actividad reciente ante una modificación inesperada.
- Hacer respaldos de base de datos y archivos según la política operativa.
---

# English: Administrator Manual

## Table of Contents

1. [Access and operating principles](#access-and-operating-principles)
2. [Recommended workflow](#recommended-workflow)
3. [Courses and examinations](#courses-and-examinations)
4. [Documents, attendance, and results](#documents-attendance-and-results)
5. [Certificates and users](#certificates-and-users)
6. [Verification and good practices](#verification-and-good-practices)

## Access and operating principles

Use an account with the `admin` role to open the administrative panel. Administrative actions change the process history, so confirm the person's identity, registration, and supporting documentation before approving, rejecting, or changing a state. Use the existing actions instead of creating duplicate registrations or records.

Apply least privilege when assigning roles. A citizen should not receive administrative access merely to solve a problem with their own process, and an inspector should receive only the access required for verification.

## Recommended workflow

1. Create or update course and examination dates and configure capacity.
2. Review registrations and identify pending requirements.
3. Validate documents and write a specific observation when rejecting one.
4. Record attendance for in-person courses.
5. Record examination results, scores, and observations.
6. Prepare the required information for the official DIPA process.
7. Load the certificate issued by DIPA and verify its data.
8. Check the final citizen view and public consultation result.

Do not load a certificate before the registration and official document have been reviewed. Deactivate unavailable dates instead of deleting historical data when the application provides that option.

## Courses and examinations

When creating a course or examination, verify the modality, date, time, location, room, capacity, and active state. Before activating a date, check that its capacity and schedule are correct. Review the registration count before modifying a date or reducing its available places.

The retake period is configured in the corresponding administration area. Confirm that the applicant has a failed result and remains within the configured number of days. For virtual courses, check the Moodle completion certificate; for renewals, verify the previous certificate in DIPA according to the current rule.

## Documents, attendance, and results

For each document, verify that the file is legible, complete, belongs to the correct person, and meets the requirement for the selected modality. Select **Approve** only after these checks. Select **Reject** with a clear, actionable observation, such as explaining which part of an identity image is missing or unreadable.

Record attendance and examination results once, using the available administrative action. Confirm the examination, date, registration, score, approval status, and observation before saving. Correct an existing result through the supported correction process rather than creating a second registration.

## Certificates and users

When loading a DIPA certificate, compare the holder, CUIL/CUIT, certificate number, issue date, expiration date, PDF, and photograph with the official document. Save only after all fields match. Then verify that the citizen can see the certificate and that public consultation shows the intended limited information.

In user management, review email, identity data, active state, and roles before saving. Use `usuario` for the citizen workflow, `inspector` for certificate verification, and `admin` only for full operational management.

## Verification and good practices

Use the recent activity view to investigate unexpected changes. Do not share downloaded personal documents through unauthorized channels. Keep observations clear and respectful, protect credentials, and follow the organization's backup, retention, privacy, and audit procedures. If a result is inconsistent, preserve the relevant record and report it through the technical or administrative support channel.
