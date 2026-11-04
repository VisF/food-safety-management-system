# Operación y resolución de problemas

## Índice

- Sección en español
- English section
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
---

# English: Operations and Troubleshooting

## Table of Contents

1. [User and installation requirements](#user-and-installation-requirements)
2. [Common problems](#common-problems)
3. [Secure support reports](#secure-support-reports)
4. [Maintenance and system limits](#maintenance-and-system-limits)

## User and installation requirements

End users need an updated browser with JavaScript enabled, a stable connection, readable files in the formats accepted by the form, and access to the email address associated with the account. During an upload or download, wait for the confirmation or error message before closing the page.

A local installation requires Apache, PHP with PDO and PDO MySQL, MySQL or MariaDB, a configured base URL, protected environment variables, and write permissions for the upload and log directories. Do not expose `.env`, configuration files, logs, database backups, or unrestricted upload directories to the public Internet. Use HTTPS in any accessible environment.

## Common problems

| Problem | Recommended action |
|---|---|
| The page does not load | Check the base URL, Apache, PHP, and the database connection. |
| The application redirects to login | Sign in again and check whether the session expired. |
| A form cannot be submitted | Complete required fields, reload the page, and retry once. |
| A file upload fails | Check size, extension, readability, permissions, and connection. |
| A document remains pending | Wait for administrative review; do not create duplicate uploads. |
| A course or examination is missing | Confirm that the date is active and has available capacity. |
| The person cannot take the examination | Review documents, attendance, modality, and retake period. |
| The certificate is not visible | Confirm official DIPA issuance and administrative loading. |
| Public consultation returns not found | Verify the eleven-digit CUIL/CUIT without punctuation. |
| Recovery email does not arrive | Check spam and request a new link if the feature is enabled. |

## Secure support reports

When reporting an issue, record the date and time, account role, affected CUIL/CUIT through a secure channel, screen, action, exact message, and a screenshot with passwords, tokens, and unnecessary personal data removed. Do not send recovery links, credentials, identity documents, or certificate files through public email.

Technical staff should preserve the error context, avoid changing historical records to hide an issue, and escalate inconsistencies through the established support process.

## Maintenance and system limits

Back up the database and `uploads/` before changes, review available disk space and permissions, rotate logs, review administrative activity, and test certificate downloads after updates. Run scheduled expiration and retake jobs according to the agreed frequency. Update PHP, Apache, MySQL/MariaDB, and application dependencies in a test environment first.

The application organizes and records the process but does not replace DIPA for official issuance. Moodle may provide evidence for virtual-course completion, while the visible registration and validation decisions remain subject to the configured administrative workflow.
