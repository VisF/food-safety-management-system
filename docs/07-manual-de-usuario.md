# Manual de usuario

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

