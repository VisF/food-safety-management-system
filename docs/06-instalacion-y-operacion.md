# Instalación y operación

## Índice

- Sección en español
- English section
## 1. Requisitos

- Apache con PHP habilitado.
- PHP con extensiones PDO y PDO MySQL.
- MySQL o MariaDB.
- Navegador moderno.
- Permisos de escritura para `uploads/` y `logs/`.
- Git para obtener y actualizar el código.

## 2. Configuración local

1. Copiar `.env.example` como `.env`.
2. Completar `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS`.
3. Ajustar `BASE_URL` en `config/config.php` si el proyecto se instala en otra carpeta.
4. Mantener `APP_DEBUG=true` solo durante desarrollo.
5. En producción, ocultar `.env`, desactivar debug y proteger los logs.

Ejemplo:

```dotenv
DB_HOST=127.0.0.1
DB_NAME=manipulacion_alimentos
DB_USER=root
DB_PASS=
APP_ENV=development
APP_DEBUG=true
```

## 3. Inicialización de base

```bash
mysql -u root -p < db/schema.sql
mysql -u root -p manipulacion_alimentos < db/seed.sql
```

La segunda orden es válida únicamente después de sincronizar el seed con el esquema. En XAMPP también puede ejecutarse el SQL desde phpMyAdmin.

## 4. Puesta en marcha con XAMPP

1. Colocar el proyecto dentro de `htdocs`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Crear la base y ejecutar los scripts.
4. Abrir `/manipulacionDeAlimentos/`.
5. Probar login, registro, carga de documentos y panel administrativo.

## 5. Datos de demostración

El seed incluye cuentas de demostración con casos de documentación, aprobación, desaprobación, recursante y carnet. Sus contraseñas son datos de desarrollo; no deben conservarse en un entorno real.

## 6. Checklist previo a entrega

- [ ] `.env` no está versionado ni expone credenciales reales.
- [ ] `APP_DEBUG` está desactivado en producción.
- [ ] El esquema y el seed fueron probados juntos.
- [ ] Las carpetas de uploads no permiten ejecutar scripts subidos.
- [ ] Se verificaron permisos de lectura/escritura.
- [ ] Se probó el flujo ciudadano completo.
- [ ] Se probó el flujo administrativo completo.
- [ ] Se probó la consulta pública sin exponer datos sensibles.
- [ ] Se configuraron copias de seguridad de la base y archivos.
- [ ] Se revisaron logs y errores del servidor.

## 7. Mantenimiento

- Respaldar base y `uploads/` antes de cambios.
- Rotar o archivar logs periódicamente.
- Revisar carnets próximos a vencer.
- Ejecutar los cron de alertas y recursantes según la frecuencia acordada.
- Actualizar dependencias de forma controlada y probar en desarrollo.
- No eliminar usuarios ni documentos históricos sin una política de retención aprobada.
---

# English: Installation and Operations

## Table of Contents

1. [Requirements](#requirements)
2. [Environment configuration](#environment-configuration)
3. [Database initialization](#database-initialization)
4. [XAMPP deployment](#xampp-deployment)
5. [Delivery and maintenance](#delivery-and-maintenance)

## Requirements

Use Apache with PHP enabled, PHP PDO and PDO MySQL extensions, MySQL or MariaDB, a modern browser, Git, and write permissions for `uploads/` and `logs/`. The selected PHP version and database server must be compatible with the code and the schema scripts.

## Environment configuration

Copy `.env.example` to `.env` and set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`. Adjust `BASE_URL` when the project is installed under a different directory. Keep `APP_DEBUG=true` only while developing. In production, protect `.env`, disable debug output, use HTTPS, and restrict access to logs and uploaded files.

## Database initialization

Run `db/schema.sql` first and review the seed before running `db/seed.sql`. The current seed references auxiliary tables that are not present in the reduced schema, so both files must be synchronized before a clean installation. Demonstration accounts and passwords are for development only and must be replaced or disabled before production use.

## XAMPP deployment

Place the project under `htdocs`, start Apache and MySQL from XAMPP, create the database, and open the configured base URL. Test registration, login, document upload, administrative review, examination workflows, certificate download, and public consultation. Confirm that uploads cannot execute scripts and that configuration and logs are not publicly browsable.

## Delivery and maintenance

Before delivery, verify environment secrets, debug settings, schema and seed compatibility, permissions, complete citizen and administrative flows, public privacy, backups, and server logs. Back up the database and `uploads/` before changes, rotate logs, review expiring certificates, run expiration and retake jobs according to the agreed schedule, and test dependency updates in development first.
