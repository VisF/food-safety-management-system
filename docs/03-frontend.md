# Frontend

## 1. Tecnología y enfoque

La interfaz se renderiza del lado del servidor con HTML/PHP y se mejora con CSS y JavaScript nativo. No se utiliza un framework SPA; cada vista recibe datos del controlador y genera una página completa.

## 2. Vistas principales

### Ciudadanía

- `index.php`: inicio y resumen del estado.
- `login.php`: acceso.
- `registro.php`: alta de usuario.
- `perfil.php`: edición de datos personales.
- `documentacion.php`: carga y seguimiento de documentos.
- `detalle_examen.php`: información de una mesa.
- `confirmar_inscripcion_examen.php`: confirmación de inscripción.
- `consulta_publica.php`: consulta pública de carnet.

### Administración

- `panel_admin.php`: tablero general.
- `admin_cursos.php`, `admin_curso_form.php`, `admin_curso_inscriptos.php`: cursos y asistentes.
- `admin_examenes.php`, `admin_examen_form.php`, `admin_examen_detalle.php`: mesas y resultados.
- `admin_documentos.php`: revisión documental.
- `admin_carnets.php`: carga y administración de carnets.
- `admin_usuarios.php`: usuarios y roles.
- `actividad_reciente.php`: actividad administrativa.

## 3. Organización de estilos

- `css/base/`: reset, variables, layout, formularios, botones, tarjetas, header y footer.
- `css/components/`: tablas, chips, tarjetas de entidad y toast.
- `css/Views/`: estilos específicos de cada pantalla.
- `css/app.css`, `css/components.css` y `css/ui.css`: composición global y componentes compartidos.

La apariencia utiliza variables CSS y componentes reutilizables para mantener consistencia entre el portal ciudadano y el panel administrativo.

## 4. JavaScript

- `subida-documentacion.js`: envía automáticamente el formulario cuando se selecciona un archivo.
- `crear-examen.js`: convierte fechas visibles `dd/mm/aaaa` al formato `aaaa-mm-dd` esperado por el backend y valida el campo.
- `admin-carnets.js`, `admin-documentos.js` y `admin-usuarios.js`: interacciones de las pantallas administrativas.
- `menu-navegacion.js`: comportamiento del menú.
- `notificaciones.js`: presentación de avisos.

Los scripts son progresivos: si el elemento esperado no existe, finalizan sin romper otras vistas.

## 5. Convenciones de interfaz

- Los formularios de modificación se envían por POST.
- Las vistas usan rutas centralizadas en `BaseVista`.
- Los valores impresos desde la base deben escaparse.
- Los mensajes de resultado se comunican mediante toast o parámetros de redirección.
- La consulta pública debe mostrar solamente la información necesaria para verificar el carnet; no debe exponer domicilio, correo ni archivos internos sin autorización.

## 6. Criterios de accesibilidad y entrega

Antes de entregar una pantalla se debe comprobar:

1. Navegación por teclado y foco visible.
2. Labels asociados a inputs.
3. Mensajes de error comprensibles.
4. Contraste suficiente.
5. Funcionamiento en resoluciones de escritorio y móvil.
6. Confirmación visual después de operaciones POST.

