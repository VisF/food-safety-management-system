# Documentación del sistema

## Sistema Integral de Gestión de Carnets de Manipuladores de Alimentos

Esta carpeta reúne la documentación técnica y funcional del proyecto. El sistema centraliza la inscripción a cursos y exámenes, la recepción y validación de documentación, el seguimiento del trámite y la carga de carnets emitidos externamente por DIPA.

## Índice

| Documento | Contenido |
|---|---|
| [01-base-de-datos.md](./01-base-de-datos.md) | Modelo relacional, tablas, relaciones, estados y scripts SQL |
| [02-backend.md](./02-backend.md) | Arquitectura, flujo de una petición, capas, seguridad y módulos |
| [03-frontend.md](./03-frontend.md) | Vistas, estilos, JavaScript y criterios de interfaz |
| [04-rutas.md](./04-rutas.md) | Catálogo de rutas HTTP y permisos |
| [05-flujos-funcionales.md](./05-flujos-funcionales.md) | Flujos de ciudadanos, administradores e inspectores |
| [06-instalacion-y-operacion.md](./06-instalacion-y-operacion.md) | Requisitos, instalación, configuración, despliegue y mantenimiento |
| [07-manual-de-usuario.md](./07-manual-de-usuario.md) | Manual breve para los perfiles del sistema |

## Alcance

El proyecto es una aplicación web PHP para administrar el circuito municipal previo y posterior a la emisión oficial del carnet. No reemplaza a DIPA: el personal administrativo continúa realizando en DIPA la emisión oficial y luego carga el carnet resultante en este sistema.

## Arquitectura resumida

```text
Navegador
   |
   v
index.php -> Routes/web.php -> Middleware
                         |
                         v
                    Controllers
                         |
                         v
                     Servicios
                         |
                         v
                   Repositories
                         |
                         v
                       MySQL
```

Las respuestas HTML se construyen con vistas PHP ubicadas en `Views/`. Los recursos estáticos se organizan en `css/` y `js/`.

## Estado de referencia

La documentación describe el código presente en el repositorio y sus scripts actuales. Antes de una instalación limpia se debe comprobar que el `seed.sql` utilizado corresponda a la versión de `schema.sql`; el seed actual contiene referencias a tablas auxiliares que no aparecen en el esquema reducido vigente. Esta verificación evita que la carga de datos falle en un entorno nuevo.



---

# English: System Documentation

## Table of Contents

1. [Purpose and scope](#purpose-and-scope)
2. [Documentation map](#documentation-map)
3. [System architecture](#system-architecture)
4. [Reference status](#reference-status)

## Purpose and scope

This directory contains the technical and functional documentation for the Food Handler Certificate Management System. The application centralizes course and examination registration, document submission and review, process tracking, and the registration of certificates issued externally by DIPA.

The system supports the municipal workflow before and after official issuance. It does not replace DIPA: administrative staff continue to perform the official issuance in DIPA and then load the resulting certificate, dates, and file into this application.

## Documentation map

- **Database:** schema, entities, relationships, states, integrity, and initialization.
- **Backend:** layers, request lifecycle, authentication, authorization, security, uploads, logs, and scheduled tasks.
- **Frontend:** views, styles, JavaScript, interface conventions, privacy, and accessibility.
- **Routes:** HTTP methods, paths, access levels, parameters, and administrative operations.
- **Functional workflows:** citizen, administrator, inspector, course, examination, retake, renewal, and certificate flows.
- **Installation and operations:** requirements, environment, database setup, XAMPP, deployment, backups, and maintenance.
- **User manuals:** practical instructions for citizens, administrators, and public verification.

## System architecture

A request enters through `index.php`, is matched by the route definition, passes the applicable middleware, and reaches a controller. The controller delegates application rules to services. Services use repositories for PDO database access and return data to PHP views. CSS and JavaScript enhance the rendered interface without replacing server-side validation.

The system uses sessions and role-based access. POST actions require CSRF protection, output is escaped before HTML rendering, and downloads must verify ownership or an authorized administrative or public-consultation rule.

## Reference status

The documentation describes the current repository and its scripts. Before a clean installation, verify that `db/seed.sql` matches `db/schema.sql`; the current seed references auxiliary tables that are not present in the reduced schema. Treat this check as a deployment prerequisite rather than assuming that a successful file import proves that the application is ready.
