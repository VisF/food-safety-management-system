# Mapeo Vistas ↔ Controladores

Documento que muestra cómo cada vista existente se conecta con los controladores creados.

---

## 📍 Vistas de Autenticación y Dashboard

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **header.php** | - (componente) | AuthControlador | `validarSesion()`, `obtenerUsuarioActual()` |
| **footer.php** | - (componente) | HomeControlador | - |
| **index.php** | /inicio | HomeControlador | `mostrarIndex()`, `mostrarDashboard()` |
| **panel_admin.php** | /novedades | AdminControlador | `obtenerEstadisticas()`, `listarInscripciones()` |
| **panel_inspector.php** | /mensajes | InspectorControlador | `buscarPorDNI()`, `obtenerInspeccionesRecientes()` |

---

## 📝 Vistas de Inscripciones

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **inscripcion_examen.php** | /servicios | InscripcionControlador | `obtenerExamenesDisponibles()`, `obtenerCursosDisponibles()` |
| **confirmar_inscripcion_examen.php** | - | InscripcionControlador | `confirmarInscripcionExamen()`, `obtenerDetalleInscripcion()` |
| **inscripcion_exitosa.php** | - | InscripcionControlador | `obtenerDetalleInscripcion()` |
| **detalle_examen.php** | - | ExamenControlador | `obtenerDetalleExamen()`, `obtenerExamenesDisponibles()` |

---

## 📄 Vistas de Documentación

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **subida_documentacion.php** | /contactos | DocumentoControlador | `obtenerDocumentosRequeridos()`, `obtenerDocumentos()` |
| **subir_archivo.php** | - | UploadControlador | `procesarCarga()` |
| **documento_subido.php** | - | DocumentoControlador | `obtenerDocumento()` |
| **preview_documento.php** | - | DocumentoControlador | `obtenerDocumento()`, `descargarDocumento()` |
| **correccion_documentacion.php** | - | DocumentoControlador | `obtenerDocumentosPorTipo()`, `subirDocumento()` |
| **solicitar_revision.php** | - | DocumentoControlador | `obtenerDocumento()` |

---

## 🎓 Vistas de Exámenes y Resultados

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **detalle_examen.php** | - | ExamenControlador | `obtenerDetalleExamen()` |
| **usuario_aprobado.php** | - | ExamenControlador | `obtenerResultado()` |
| **usuario_rechazado.php** | - | ExamenControlador | `obtenerResultado()` |
| **motivo_rechazo.php** | - | ValidacionControlador | `obtenerMotivosRechazo()` |

---

## 🎫 Vistas de Trámites y Carnets

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **detalle_tramite.php** | - | TramiteControlador | `obtenerDetalleTramite()` |
| **historial_tramite.php** | - | TramiteControlador | `obtenerHistorialTramite()` |
| **comprobante_tramite.php** | - | TramiteControlador | `obtenerComprobanteDescargable()` |
| **carnet_emitido.php** | /carnet-emitido | TramiteControlador, DipaControlador | `obtenerCarnet()`, `verificarVigenciaCarnet()` |

---

## 🔍 Vistas de Validación y Administración

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **detalle_validacion.php** | - | ValidacionControlador | `obtenerDetalleValidacion()` |
| **crear_respuesta_admin.php** | - | AdminControlador | `responderSolicitud()` |

---

## 📊 Vistas de Actividad y Reportes

| Vista | Ruta | Controlador | Métodos |
|---|---|---|---|
| **actividad_reciente.php** | /actividad-reciente | ReporteControlador | `obtenerActividadReciente()` |
| **detalle_actividad.php** | - | ReporteControlador | `obtenerDetalleActividad()` |

---

## 🔄 Flujo de Integración Propuesto

### 1. En Router.php, agregar rutas:

```php
// Autenticación
'login' => AuthControlador::class . '::procesarLogin()',
'logout' => AuthControlador::class . '::procesarLogout()',

// Inscripciones
'inscripcion-examen' => InscripcionControlador::class . '::obtenerExamenesDisponibles()',
'confirmar-inscripcion' => InscripcionControlador::class . '::confirmarInscripcionExamen()',

// Documentos
'subir-doc' => UploadControlador::class . '::procesarCarga()',
'validar-doc' => AdminControlador::class . '::validarDocumentacion()',

// Exámenes
'registrar-resultado' => ExamenControlador::class . '::registrarResultado()',

// Inspección
'buscar-dni' => InspectorControlador::class . '::buscarPorDNI()',

// Reportes
'actividad' => ReporteControlador::class . '::obtenerActividadReciente()',
```

### 2. En cada vista PHP, al inicio:

```php
<?php
// Obtener datos del controlador
$datos = $controlador->metodo();

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ?r=login');
    exit;
}
?>
```

### 3. Ejemplo con inscripcion_examen.php:

```php
<?php
$inscripcionCtrl = new InscripcionControlador();
$examenes = $inscripcionCtrl->obtenerExamenesDisponibles();
$cursos = $inscripcionCtrl->obtenerCursosDisponibles();
?>
<div class="form-examen">
    <?php foreach ($examenes as $examen): ?>
        <option value="<?= $examen['id'] ?>">
            <?= $examen['fecha'] ?> - Cupos: <?= $examen['cupos_disponibles'] ?>
        </option>
    <?php endforeach; ?>
</div>
```

---

## 📋 Checklist de Integración

### Fase 5A: Conectar Vistas Principales
- [ ] index.php ← HomeControlador
- [ ] header.php ← AuthControlador (sesión)
- [ ] inscripcion_examen.php ← InscripcionControlador
- [ ] subida_documentacion.php ← DocumentoControlador
- [ ] panel_admin.php ← AdminControlador
- [ ] panel_inspector.php ← InspectorControlador

### Fase 5B: Conectar Vistas Secundarias
- [ ] detalle_tramite.php ← TramiteControlador
- [ ] carnet_emitido.php ← TramiteControlador + DipaControlador
- [ ] actividad_reciente.php ← ReporteControlador
- [ ] usuario_aprobado.php ← ExamenControlador
- [ ] motivo_rechazo.php ← ValidacionControlador

### Fase 5C: Crear Nuevas Vistas (No existen)
- [ ] Landing page pública (contexto.txt lo requiere)
- [ ] Página de términos y condiciones
- [ ] Página de ayuda/FAQ

---

## 🎨 Mejoras UI/UX para las Vistas Existentes

Todas las vistas necesitan:
1. **Remover Tailwind CSS** - Usar simple CSS en `css/base.css` y `css/components.css`
2. **Mejorar formularios** - Validación en cliente y servidor
3. **Mejorar tablas** - Paginación, filtros, exportar
4. **Responsive Design** - Mobile-first approach
5. **Accesibilidad** - ARIA labels, contraste, navegación
6. **Mensajes** - Notificaciones, alertas, confirmaciones
7. **Carga** - Spinners, progress bars, estados

---

## 🔐 Validaciones de Seguridad

Todos los formularios deben validar:
- [ ] CSRF token en cada POST
- [ ] Rol del usuario (AuthControlador::validarRol)
- [ ] Permisos de acceso a datos
- [ ] Input sanitization
- [ ] SQL injection prevention
- [ ] XSS prevention

---

## 📞 Comunicación Entre Vistas y Controladores

### Métodos de Paso de Datos:

**1. GET / URL Parameters:**
```php
// Vista llama
$resultado = $controlador->obtenerPorId($_GET['id']);

// Controlador retorna array
['id' => 1, 'nombre' => 'Juan', ...]
```

**2. POST / Form Data:**
```php
// Vista envía
if ($_POST['action'] === 'crear') {
    $resultado = $controlador->crear($_POST);
}
```

**3. Sessions:**
```php
// AuthControlador guarda usuario actual
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['rol'] = $usuario['rol'];
```

**4. Archivos:**
```php
// UploadControlador procesa $_FILES
$resultado = $uploadCtrl->procesarCarga($_FILES['documento']);
```

---

## 📝 Notas Importantes

- Todos los controladores retornan arrays (NO HTML)
- Las vistas son responsables de mostrar (HTML/CSS)
- Los modelos manejan la lógica de BD
- Los controladores orquestan (modelo + validación + flujo)
- Logging centralizado en cada método

