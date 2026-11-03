# Operación y resolución de problemas

## 1. Requisitos de uso

### Para usuarios finales

- Navegador actualizado con JavaScript habilitado.
- Conexión estable durante cargas y descargas.
- Archivos legibles en los formatos aceptados por el formulario.
- Acceso al correo utilizado para la cuenta.

### Para la instalación local

La aplicación está diseñada para PHP sobre Apache, con MySQL/MariaDB y
servidor web configurado. El punto de entrada es `index.php`; la configuración
de entorno se encuentra en `config/` y los archivos subidos se almacenan en
`uploads/`. El responsable técnico debe definir la URL base, la conexión a la
base de datos, el correo y las rutas de almacenamiento antes de habilitar el
sistema.

No exponga públicamente `uploads/`, `logs/`, archivos de configuración ni la
base de datos. Use HTTPS en cualquier entorno accesible desde Internet.

## 2. Problemas habituales

| Problema | Resolución |
|---|---|
| La pantalla no carga | Verificar URL base, Apache, PHP y conexión a la base de datos |
| Se redirige al login | Iniciar sesión nuevamente; revisar que la sesión no haya expirado |
| No se puede enviar un formulario | Completar campos obligatorios y volver a cargar la página |
| La carga de archivo falla | Revisar tamaño, extensión, legibilidad y conexión |
| El documento queda pendiente | La revisión es administrativa; no volver a cargar duplicados |
| No aparece un curso/examen | Verificar que esté activo y tenga cupo |
| No se puede rendir | Revisar documentación, asistencia, modalidad y plazo de recursante |
| No aparece el carnet | Confirmar que DIPA lo haya emitido y que administración lo haya cargado |
| Consulta “no encontrado” | Confirmar CUIL/CUIT de 11 dígitos, sin guiones |
| No llega recuperación de contraseña | Revisar correo no deseado y solicitar un nuevo enlace |

## 3. Procedimiento de soporte

Para reportar un problema, registrar:

1. fecha y hora;
2. rol de la cuenta;
3. CUIL/CUIT involucrado, por canal seguro;
4. pantalla y acción realizada;
5. mensaje exacto mostrado;
6. captura sin exponer contraseñas ni tokens.

No enviar por correo público documentos personales, contraseñas, enlaces de
recuperación ni archivos de carnet sin autorización.

## 4. Mantenimiento recomendado

- Realizar copias de seguridad de base de datos y `uploads/`.
- Verificar periódicamente permisos y espacio disponible.
- Revisar logs y actividad administrativa.
- Probar una descarga de carnet luego de cada actualización.
- Comprobar el flujo de recuperación de contraseña.
- Mantener PHP, Apache, MySQL/MariaDB y dependencias actualizados.
- Probar primero en un entorno de prueba antes de modificar producción.

## 5. Alcance y límites

La aplicación organiza y registra el trámite, pero no sustituye a DIPA para
la emisión oficial. Moodle puede intervenir como fuente del certificado de
curso virtual; la inscripción y validación visible en esta aplicación siguen
dependiendo del flujo administrativo configurado.
