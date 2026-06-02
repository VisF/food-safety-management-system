## Plan: Revisar y Completar la Aplicación

TL;DR - Qué, por qué y cómo:
Armar un plan de trabajo para llevar la aplicación a un estado estable y seguro: completar TODOs críticos (notificaciones, inspector, migraciones), endurecer seguridad (CSRF, subida de archivos, sesiones), añadir tablas faltantes y crear una base de pruebas mínima. Empezar por bloqueadores (recovery_tokens, SMTP, CSRF global) y luego abordar funcionalidades incompletas y pruebas.

### Steps
1. Bloqueadores críticos (prioridad alta)
   1.1 Crear la tabla `recovery_tokens` en `db/schema.sql` y ejecutar migración (*depende de 1.2 para pruebas*).
   1.2 Completar la configuración SMTP en `controlador/NotificacionControlador.php` y leer credenciales desde `config/env.php` (no hardcodear). Probar envío con credenciales reales o entorno de pruebas.
   1.3 Revisar y aplicar CSRF tokens a todas las rutas POST/PUT/DELETE (expandir patrón de `AuthControlador.php`).
2. Seguridad y hardening (prioridad alta)
   2.1 Endurecer `controlador/UploadControlador.php`: MIME+ext check (ya implementado), escaneo antivirus (opcional) y validación de paths.
   2.2 Revisar manejo de sesiones: timeout, regeneración de ID en login, y headers de seguridad en `index.php`/`Router.php`.
   2.3 Revisar inyecciones SQL y asegurar uso de prepared statements.
3. Funcionalidad crítica a completar
   3.1 Completar TODOs en `controlador/NotificacionControlador.php` (plantillas, historial, reintentos, lectura SMTP config).
   3.2 Completar TODOs en `controlador/InspectorControlador.php` (búsquedas por DNI, vigencia de carnet, auditoría).
   3.3 Implementar recuperación de contraseña completa (token persistente, expiración, emails con link seguro).
   3.4 Implementar `controlador/MigracionControlador.php` para importaciones seguras.
4. Calidad y pruebas
   4.1 Añadir pruebas unitarias básicas (PHPUnit) para rutas críticas: login, recuperación, subida de archivos, notificaciones (mock SMTP).
   4.2 Añadir pruebas de integración ligeras para flujos completos.
   4.3 Añadir verificación automática de esquema (migrations) y script de seed para pruebas.
5. Operaciones y mantenimiento
   5.1 Implementar cron de limpieza para `uploads/temporal` y registrar su ejecución.
   5.2 Monitoreo mínimo: logs rotados, alertas por fallos críticos de notificación.
6. Documentación y despliegue
   6.1 Mantener este archivo `docs/paraHacer.md` actualizado con prioridades y estimaciones.
   6.2 Escribir README operativo para despliegue local y configuración SMTP/cron.

### Archivos relevantes
- `controlador/NotificacionControlador.php` — completar plantillas, SMTP, cola y reintentos
- `controlador/InspectorControlador.php` — completar búsquedas, validaciones y auditoría
- `controlador/UploadControlador.php` — ya actualizado; añadir AV scan y path hardening
- `controlador/MigracionControlador.php` — implementar import
- `controlador/AuthControlador.php` — patrón CSRF a aplicar globalmente
- `db/schema.sql` — agregar `recovery_tokens` y tablas faltantes
- `config/env.php` — añadir configuración segura para SMTP
- `index.php`, `Router.php` — añadir headers de seguridad y manejo de sesiones
- `vistas/` — revisar y escapar outputs (XSS), mostrar mensajes amigables

### Verificación
1. Ejecutar migración y comprobar que `recovery_tokens` existe en la DB.
2. Ejecutar un script de prueba: registro → login → solicitud recuperación → recibir email (log o fake SMTP) → reset.
3. Prueba de subida: subir JPG/PDF inválidos (MIME/ext mismatch) y confirmar rechazo; subir válido y confirmar guardado.
4. Ejecutar pruebas unitarias (PHPUnit) y verificar cobertura en rutas críticas > 60%.
5. Revisar logs y probar cron de limpieza contra archivos temporales simulados.

### Decisiones
- Usar `config/env.php` (o variables de entorno) para credenciales SMTP; preferir SMTP autenticado sobre `mail()`.
- Mantener comprobación dual: extensión + `finfo` MIME; añadir `getimagesize()` para imágenes.
- No bloquear desarrollo por escaneo AV; dejar integración opcional con adaptador (ClamAV) en producción.

### Consideraciones adicionales
1. Internacionalización para mensajes de UI.
2. Migrar a librería de correo (PHPMailer/SwiftMailer) para attachments y SMTP.
3. Plan de pruebas de seguridad: pentest ligero tras implementar hardening.

---

Archivo generado automáticamente por el asistente el 2026-06-01.
