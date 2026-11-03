# Documentación de uso

## Sistema de gestión de carnets de manipuladores de alimentos

Esta carpeta contiene la documentación funcional de la aplicación. Está
dirigida a ciudadanos, personal administrativo e inspectores.

## Índice

1. [Manual del ciudadano](./01-manual-ciudadano.md)
2. [Manual administrativo](./02-manual-administrador.md)
3. [Consulta pública e inspección](./03-consulta-publica.md)
4. [Flujos y estados del trámite](./04-flujos-y-estados.md)
5. [Operación y resolución de problemas](./05-operacion-y-problemas.md)

## Qué resuelve la aplicación

La aplicación centraliza la inscripción, la carga y revisión de documentación,
la organización de cursos y exámenes, el registro de resultados y la gestión
del carnet. El carnet oficial sigue siendo emitido externamente por DIPA: el
personal administrativo carga en esta aplicación la información y el archivo
del carnet que ya fue emitido.

## Roles

| Rol | Acceso principal |
|---|---|
| Ciudadano (`usuario`) | Perfil, inscripciones, documentación, exámenes y carnet propio |
| Administrador (`admin`) | Gestión completa del circuito y de la configuración |
| Inspector (`inspector`) | Consulta pública de carnets |
| Consulta pública | Verificación limitada por CUIL/CUIT, sin iniciar sesión |

## Convenciones de navegación

- Las direcciones se muestran sin barra final. Por ejemplo, use `/login`, no
  `/login/`.
- Las acciones que modifican información solicitan una confirmación o token
  de seguridad. No cierre la página mientras se procesa una carga.
- Los mensajes emergentes y los estados de las tarjetas indican si una acción
  fue aceptada, quedó pendiente o requiere corrección.

## Datos de contacto

La aplicación no reemplaza los canales administrativos definidos por el Área
de Manipulación de Alimentos. Si un trámite queda pendiente por una validación,
consulte al personal responsable indicando su CUIL/CUIT y el estado que muestra
la pantalla.
