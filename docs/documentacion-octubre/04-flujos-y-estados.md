# Flujos y estados del trámite

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
