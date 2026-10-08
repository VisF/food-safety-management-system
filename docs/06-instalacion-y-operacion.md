# Instalación y operación

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

