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

