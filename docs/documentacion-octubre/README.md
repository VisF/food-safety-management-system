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
---

# English: User Documentation

## Table of Contents

1. [Purpose and audience](#purpose-and-audience)
2. [Document map](#document-map)
3. [Roles and responsibilities](#roles-and-responsibilities)
4. [Navigation and privacy](#navigation-and-privacy)

## Purpose and audience

This directory contains the functional documentation for the application. It is written for citizens who register for a course or examination, administrative staff who validate and manage the process, inspectors who verify certificates, and technical staff who support the installation and operation of the system.

The documentation describes the process from registration through document review, attendance, examination, result recording, official issuance in DIPA, certificate loading, and public verification. The system complements DIPA and does not issue the official certificate by itself.

## Document map

- **Citizen manual:** account creation, sign-in, document uploads, registrations, examination information, results, profile, and certificate download.
- **Administrator manual:** dates, capacity, registrations, document review, attendance, results, certificates, users, roles, and operational controls.
- **Public consultation:** CUIL/CUIT search, identity comparison, validity, download restrictions, and privacy.
- **Workflows and states:** access modes, retakes, renewals, internal states, and transition rules.
- **Operations and troubleshooting:** requirements, common failures, secure support reports, maintenance, backups, and system limits.

## Roles and responsibilities

Citizens provide accurate information, upload readable documents, attend the required activities, and correct rejected files. Administrators create dates, review evidence, record attendance and results, coordinate the DIPA step, and load the external certificate. Inspectors verify the certificate using the information authorized for their role. Public users receive only the minimum information required for a legitimate verification.

## Navigation and privacy

Use the canonical routes without a trailing slash and wait for confirmation after state-changing actions or uploads. Do not share credentials, recovery links, identity documents, photographs, certificate files, or internal observations through unauthorized channels. When a result is inconsistent, verify the CUIL/CUIT and identity first, then refer the case to administration instead of creating duplicate records.
