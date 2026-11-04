# Manual de usuario

## Índice

- Sección en español
- English section
## Ciudadano

### Registrarse e iniciar sesión

1. Entrar en **Registro**.
2. Completar los datos solicitados.
3. Confirmar el alta.
4. Ingresar con correo y contraseña.

### Inscribirse

1. Abrir **Inscripciones**.
2. Elegir curso o examen disponible.
3. Revisar fecha, modalidad, ubicación y cupos.
4. Confirmar la inscripción.

### Cargar documentación

1. Abrir **Documentación**.
2. Seleccionar el tipo de documento.
3. Elegir el archivo.
4. Enviar el formulario.
5. Consultar si quedó pendiente, aprobado o rechazado.
6. Si fue rechazado, leer la observación y volver a cargarlo.

### Consultar el trámite

El inicio y el perfil muestran el avance de la inscripción, la documentación, el examen y el carnet. Cuando el carnet fue cargado, puede descargarse desde la opción correspondiente.

## Administrador

1. Ingresar con una cuenta con rol `admin`.
2. Crear y activar cursos o exámenes.
3. Revisar inscriptos y cupos.
4. Validar o rechazar documentos con observaciones.
5. Registrar asistencia, resultados y estados.
6. Cargar el carnet recibido desde DIPA.
7. Consultar actividad, usuarios y documentación cuando sea necesario.

Las acciones de cambio se realizan con formularios POST y requieren token CSRF.

## Inspector y consulta pública

1. Abrir **Consulta pública**.
2. Ingresar el DNI o identificador solicitado.
3. Revisar el estado y la vigencia del carnet.
4. Descargar o visualizar únicamente los recursos que el sistema habilite.

No se deben compartir credenciales ni utilizar la consulta pública para acceder a datos distintos de la verificación sanitaria necesaria.
---

# English: User Manual

## Table of Contents

1. [Citizen account and registration](#citizen-account-and-registration)
2. [Documents and process tracking](#documents-and-process-tracking)
3. [Administrator operations](#administrator-operations)
4. [Inspector and public consultation](#inspector-and-public-consultation)

## Citizen account and registration

A citizen creates an account, signs in, opens **Registrations**, and chooses an available course or examination. Before confirming, review the date, modality, location, and capacity. The citizen should keep profile data correct and use the registered email for any notification or recovery flow.

## Documents and process tracking

Open **Documents**, select the required type, choose the file, submit it, and wait for confirmation. Review whether the document is pending, approved, or rejected. A rejected document must be corrected according to the observation and uploaded again. The dashboard and profile show the progress of registration, documentation, examination, and certificate.

Attendance and examination results are recorded by administration. When the result is approved, the official certificate is still issued externally by DIPA. After administration loads it, the citizen can open **My certificate** and download the authorized copy.

## Administrator operations

An administrator creates and activates courses and examinations, reviews registrations and capacity, approves or rejects documents with observations, records attendance and results, loads the DIPA certificate, and checks users, activity, and documentation. State-changing forms use POST and require CSRF. Do not grant the `admin` role to solve a problem that can be handled through the citizen workflow.

## Inspector and public consultation

Open **Public Consultation**, enter the requested CUIL/CUIT, compare the displayed identity with the person being inspected, and review status and validity. Download or display only the resources enabled by the system and authorized by the procedure. Do not share credentials or use public consultation to access information beyond the permitted verification.
