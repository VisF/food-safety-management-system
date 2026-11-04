# Flujos funcionales

## Índice

- Sección en español
- English section
## 1. Alta y acceso

1. La persona completa nombre, apellido, DNI, correo y contraseña.
2. El sistema valida campos y unicidad de DNI/correo.
3. Se crea el usuario con contraseña cifrada y rol ciudadano.
4. La persona inicia sesión.
5. Se crea la sesión con su identificador y roles.

## 2. Curso presencial

1. El ciudadano consulta cursos activos y cupos.
2. Se inscribe al curso.
3. Carga DNI y foto carnet.
4. Administración revisa la documentación.
5. Se registra la asistencia o aprobación del curso.
6. El ciudadano queda habilitado para el examen según las reglas del trámite.
7. Se registra el resultado del examen.
8. Si aprueba, administración continúa la gestión en DIPA y carga el carnet emitido.

## 3. Curso virtual

1. El ciudadano completa el curso en Moodle.
2. Se inscribe a una mesa de examen.
3. Carga DNI, foto y certificado Moodle.
4. Administración valida los documentos.
5. Rinde el examen presencial.
6. Se registra el resultado y, si corresponde, se carga el carnet.

## 4. Recursante

1. Se identifica un resultado de examen desaprobado.
2. Se verifica que la nueva inscripción se encuentre dentro del plazo configurado.
3. El ciudadano elige una nueva mesa.
4. Se registra la nueva inscripción sin repetir el curso.
5. Se registra el resultado del reexamen.

El valor inicial de referencia se encuentra en `configuracion_sistema` como `plazo_recursante_dias`; debe confirmarse con la regla vigente del área antes de producción.

## 5. Renovación

1. El ciudadano se inscribe a una instancia de examen.
2. Administración verifica antecedentes y documentación.
3. Se registra el resultado.
4. Si aprueba, se consulta la situación anterior en DIPA.
5. Se carga el nuevo carnet con fechas de emisión y vencimiento.

## 6. Revisión documental

Cada archivo comienza en estado `pendiente`. Administración puede:

- aprobarlo y registrar fecha de revisión;
- rechazarlo con observaciones;
- descargarlo para verificarlo o utilizarlo en el proceso externo.

El ciudadano visualiza el estado y debe volver a cargar el archivo cuando sea necesario.

## 7. Emisión y consulta de carnet

1. El administrador abre el trámite aprobado.
2. Carga el número de carnet, fechas y PDF recibido desde DIPA.
3. El sistema registra el carnet vinculado a la inscripción.
4. El ciudadano descarga su carnet desde su sesión.
5. Un inspector o una persona autorizada puede consultar el estado por DNI desde la consulta pública.

## 8. Roles

| Rol | Responsabilidades |
|---|---|
| Usuario | Registro, perfil, inscripciones, documentación y descarga de su carnet |
| Administrador | Gestión integral de cursos, exámenes, usuarios, documentos, trámites y carnets |
| Inspector | Verificación de carnets según los permisos habilitados |
| Público | Consulta limitada de vigencia/estado |
---

# English: Functional Workflows

## Table of Contents

1. [Registration and access](#registration-and-access)
2. [In-person and virtual courses](#in-person-and-virtual-courses)
3. [Retakes and renewals](#retakes-and-renewals)
4. [Documents, results, and certificates](#documents-results-and-certificates)
5. [Roles](#roles)

## Registration and access

A person submits name, surname, identity identifier, email, and password. The application validates required fields and uniqueness, creates the account with a password hash and citizen role, and establishes a session after successful authentication. The session contains the user identifier and authorized roles, not the password.

## In-person and virtual courses

For an in-person course, the citizen chooses an active date, registers, uploads identity and photograph documents, and attends the required classes. Administration reviews the files and records attendance. When the attendance rule is met, the person becomes eligible for the examination according to the current process rules.

For a virtual course, the citizen completes Moodle, obtains a completion certificate, registers for an in-person examination, and uploads the identity, photograph, and Moodle documents. Administration validates the documents before recording the examination result.

## Retakes and renewals

A retake starts from a failed examination. The application checks that the new registration is within the configured period, allows the person to choose a new examination session, and records the new result without requiring the course again when the rule permits it.

A renewal starts from an expired certificate. The person registers for an examination, administration checks the documentation and previous DIPA record, and a new certificate is loaded only after the result and official process are complete. If no previous record exists, the person may need to complete the full course.

## Documents, results, and certificates

Every uploaded file begins as `pending`. Administration can approve it and record a review date, or reject it with a concrete observation. The citizen sees the state and must upload a corrected file when necessary. Attendance, score, approval, failure, observations, and cancellation are recorded by authorized staff.

An approved result does not create an official certificate automatically. Administration continues the issuance process in DIPA and then loads the number, dates, and official file into this application. The citizen can download it after loading, and authorized users can verify it through public consultation.

## Roles

Citizens manage their own registration and documents. Administrators manage dates, capacity, validation, results, users, process states, and certificates. Inspectors verify certificates within their permissions. Public users receive only the limited information necessary to confirm validity.
