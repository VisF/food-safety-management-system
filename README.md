# 🏛️ Sistema TUDAI - Gestión de Carnets de Manipuladores de Alimentos

**Estado Actual: ✅ Arquitectura Base Completa (Mayo 2026)**

Bienvenido al repositorio del Sistema Integral de Gestión de Carnets de Manipuladores de Alimentos para la Carrera **TUDAI** (Tecnicatura Universitaria en Desarrollo de Aplicaciones Informáticas).

---

## 📋 ¿Qué es este proyecto?

Un sistema web centralizado que gestiona todo el proceso de obtención y renovación de carnets de manipuladores de alimentos, complementando el sistema provincial DIPA. 

**Usuarios del sistema:**
- 👤 **Ciudadanos** - Inscribirse a cursos, cargar documentos, consultar estado
- 👨‍💼 **Administradores** - Gestionar cursos, exámenes, usuarios, validaciones
- 🔍 **Inspectores** - Buscar carnets por DNI, verificar vigencia
- 🔐 **Público** - Consulta pública limitada de estado de carnet

---

## 🎯 Principales Características

✅ **Inscripción a cursos** (presencial y virtual)  
✅ **Carga y validación de documentación**  
✅ **Gestión de exámenes y resultados**  
✅ **Emisión de certificados/carnets**  
✅ **Búsqueda por DNI** para inspectores  
✅ **Integración con sistema DIPA provincial**  
✅ **Auditoría completa** de acciones  
✅ **Notificaciones por email** del estado  

---

## 🗂️ Estructura del Proyecto

```
manipulaciondealimentos/
├── .github/agents/
│   └── tudai-developer.agent.md       🤖 Custom Agent para desarrollo
│
├── Modelo/                             📦 15 Modelos OOP
│   ├── UsuarioModelo.php
│   ├── InscripcionModelo.php
│   ├── ExamenModelo.php
│   ├── DocumentoModelo.php
│   ├── CarnetModelo.php
│   └── ... (10 más)
│
├── Controller/                        🎮 16 Controladores
│   ├── AuthControlador.php
│   ├── InscripcionControlador.php
│   ├── ExamenControlador.php
│   ├── AdminControlador.php
│   ├── InspectorControlador.php
│   └── ... (11 más)
│
├── Views/                             👁️ 26 Vistas HTML
│   ├── index.php (dashboard)
│   ├── inscripcion_examen.php
│   ├── panel_admin.php
│   └── ... (23 más)
│
├── css/                                🎨 Estilos
│   ├── base.css
│   └── components.css
│
├── ARQUITECTURA_COMPLETADA.md          📚 Documentación detallada
├── MAPEO_VISTAS_CONTROLADORES.md       🔗 Integración vistas
├── VERIFICACION_FINAL.md               ✅ Verificación de componentes
└── RESUMEN_PROYECTO.md                 📖 Resumen ejecutivo
```

---

## 📊 Estadísticas

| Métrica | Cantidad | Estado |
|---|---|---|
| **Modelos** | 15 | ✅ Completo |
| **Controladores** | 16 | ✅ Completo |
| **Vistas** | 26 | ⏳ Integración |
| **Métodos** | 262 | ✅ Implementados |
| **Líneas de Código** | ~6,000+ | ✅ Base |
| **TODO (BD)** | ~300+ | ⏳ Próximo |

---

## 🚀 Quick Start

### 1. Entender la Arquitectura
```bash
# Comienza aquí para entender el proyecto completo:
📖 Lee: RESUMEN_PROYECTO.md
# Documentación del proyecto

Toda la documentación del proyecto está centralizada en la carpeta `docs/`.

Archivos principales:

- `docs/ARQUITECTURA_COMPLETADA.md` — detalle técnico de la arquitectura
- `docs/MAPEO_VISTAS_CONTROLADORES.md` — cómo integrar vistas con controladores
- `docs/RESUMEN_PROYECTO.md` — resumen ejecutivo y estado
- `docs/VERIFICACION_FINAL.md` — verificación final de componentes
- `docs/README_PROJECT.md` — README completo del proyecto (versión larga)

Para comenzar, abre `docs/RESUMEN_PROYECTO.md`.
# (Disponible en VS Code al escribir @)

```
