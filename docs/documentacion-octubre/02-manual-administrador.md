# Manual administrativo

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
