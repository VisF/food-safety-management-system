# Flujos y estados del trámite

## Índice

- Sección en español
- English section
## 1. Flujo general

```text
Registro e inicio de sesión
        ↓
Curso o acceso habilitado a examen
        ↓
Carga de documentación
        ↓
Revisión administrativa
        ↓
Asistencia y examen
        ↓
Resultado
        ↓
Carga del carnet emitido por DIPA
        ↓
Consulta y descarga del carnet
```

La documentación debe estar completa y aprobada antes de la instancia que
requiera habilitación. La emisión oficial ocurre en DIPA y luego se refleja en
esta aplicación.

## 2. Modalidades de acceso

### Curso presencial

1. El ciudadano se inscribe a un curso presencial.
2. Carga DNI y foto carnet.
3. Administración controla la documentación y registra asistencia.
4. Si cumple la asistencia mínima, queda habilitado para el examen.
5. Administración registra el resultado.

### Curso virtual

1. El ciudadano completa el curso en Moodle.
2. Se inscribe a una fecha de examen presencial.
3. Carga DNI, foto carnet y certificado de finalización de Moodle.
4. Administración valida los documentos y registra el examen.

### Recursante

1. La persona tiene un examen desaprobado.
2. Se inscribe a otra mesa sin repetir el curso, si aún está dentro del plazo.
3. Administración registra el nuevo resultado.

El plazo se configura en días desde **Gestión de Exámenes**. Si vence, la
aplicación puede impedir la reinscripción como recursante.

### Renovación

Una persona con carnet vencido puede inscribirse a una instancia de examen
sin repetir el curso, según las reglas vigentes. Luego de aprobar, el personal
verifica el antecedente en DIPA. Si no existe un carnet previo, puede ser
necesario completar el curso.

## 3. Estados implementados

| Estado interno | Lectura operativa |
|---|---|
| `pendiente` | El trámite fue creado y todavía no completó la siguiente condición |
| `documentacion_pendiente` | Falta cargar o corregir documentación |
| `documentacion_aprobada` | Los documentos obligatorios fueron aprobados |
| `inscripto_examen` | La persona quedó anotada a una mesa |
| `aprobado` | El resultado del examen es aprobado |
| `desaprobado` | El resultado del examen no alcanza la aprobación |
| `rechazado` | El trámite o una validación fue rechazado y requiere atención |
| `cancelado` | La inscripción o trámite fue cancelado |
| `carnet_emitido` | El carnet oficial fue cargado en la aplicación |

El texto visible puede variar según la pantalla, pero estos son los valores
de referencia del sistema.

## 4. Qué debe hacer cada rol

| Momento | Ciudadano | Administración |
|---|---|---|
| Registro | Crear cuenta y mantener datos correctos | Resolver cuentas o datos inconsistentes |
| Documentación | Cargar archivos y corregir observaciones | Revisar, aprobar o rechazar |
| Curso/examen | Elegir una fecha disponible | Crear fechas, cupos y estados |
| Asistencia | Asistir | Registrar asistencia |
| Resultado | Consultar el resultado | Registrar nota y aprobación |
| Carnet | Descargar cuando esté disponible | Cargar el documento de DIPA |
| Verificación | Presentar el carnet | Atender inconsistencias |

## 5. Reglas de transición

- Un archivo cargado queda pendiente hasta que administración lo revise.
- Una documentación rechazada debe corregirse antes de avanzar.
- El resultado aprobado no genera por sí solo el archivo del carnet.
- El carnet solo aparece cuando administración carga la emisión externa.
- La vigencia depende de la fecha de vencimiento registrada.
- La consulta pública no modifica estados.
---

# English: Workflows and Process States

## Table of Contents

1. [End-to-end workflow](#end-to-end-workflow)
2. [Access modes](#access-modes)
3. [Internal states](#internal-states)
4. [Responsibilities and transition rules](#responsibilities-and-transition-rules)

## End-to-end workflow

The complete process begins with registration and sign-in. Depending on the person's situation, the next step is an in-person course, a virtual course followed by an examination registration, a retake, or a renewal. The person uploads the required documents, administration reviews them, attendance and examination results are recorded, and the approved case continues to the official DIPA issuance process.

DIPA remains the official issuing system. This application records the municipal workflow before issuance and stores the certificate information and file after authorized staff receive and load the external document. Public consultation can verify the loaded certificate but cannot change a process state.

## Access modes

For an in-person course, the citizen registers for a course, uploads identity documents, attends the required classes, and waits for administration to record attendance. When the attendance condition is met, the examination can become available according to the configured rules.

For a virtual course, the citizen completes Moodle, obtains the completion certificate, registers for an in-person examination, and uploads the identity and Moodle documents. Administration validates the material before the examination.

A retake is available to a person with a previous failed examination when the new registration falls within the configured period. A renewal may allow a person with an expired certificate to take an examination without repeating the course, subject to the current administrative rule and verification in DIPA.

## Internal states

| Internal state | Operational meaning |
|---|---|
| `pending` | The process exists but the next condition has not been completed. |
| `documentation_pending` | A required document is missing, pending review, or must be corrected. |
| `documentation_approved` | The required documents have been reviewed and approved. |
| `exam_registered` | The person is registered for an examination session. |
| `approved` | The examination result meets the approval condition. |
| `failed` | The examination result does not meet the approval condition. |
| `rejected` | A process or validation was rejected and requires attention. |
| `cancelled` | The registration or process was cancelled. |
| `certificate_issued` | The official certificate was loaded into the application. |

The label shown to users may be translated or formatted differently by the view, but the internal state is the reference for services, reports, and administrative decisions.

## Responsibilities and transition rules

An uploaded file remains pending until administration reviews it. A rejected document must be corrected before the process can advance. An approved examination does not create a certificate automatically; the external DIPA issuance and the administrative upload are separate steps. The validity status depends on the expiration date stored with the certificate.

Citizens submit information and attend the required activities. Administration creates dates, validates evidence, records attendance and results, loads certificates, and resolves inconsistencies. Inspectors verify the certificate without changing workflow data.
