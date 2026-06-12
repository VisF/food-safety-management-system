<?php
/**
 * Vista: footer.php
 * Propósito: Barra de navegación inferior reutilizable (BottomNav).
 * Implementación técnica:
 *  - Determina `currentRoute` a partir de `basename($_SERVER['SCRIPT_NAME'])` y un `match` para mapear vistas a secciones.
 *  - Calcula `$basePath` similar a header.php para resolver rutas cuando las vistas están en `/vistas`.
 *  - `routeUrl($route)` devuelve `$basePath . '/manipulacionDeAlimentos/' . rawurlencode($route)` (usar `rawurlencode` para seguridad en URLs).
 * Notas de despliegue:
 *  - Si se cambia la estrategia de routing (front controller distinto), actualizar la resolución de `$basePath`.
 */
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$currentRoute = match ($currentFile) {
   'panel_inspector.php' => 'mensajes',
   'panel_admin.php', 'actividad_reciente.php' => 'actividad_reciente',
   'inscripcion_examen.php',
   'carnet_emitido.php',
   'usuario_aprobado.php',
   'usuario_rechazado.php' => 'servicios',
   'subida_documentacion.php' => 'contactos',
   default => 'inicio',
};

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if (preg_match('#/vistas$#', $basePath) === 1) {
   $basePath = (string) preg_replace('#/vistas$#', '', $basePath);
}

$routeUrl = static function (string $route) use ($basePath): string {
   return $basePath . '/' . rawurlencode($route);
};

$linkClass = static function (string $route) use ($currentRoute): string {
   return 'pie-principal__enlace' . ($currentRoute === $route ? ' pie-principal__enlace--activo' : '');
};
?>
<nav aria-label="Navegación principal" class="pie-principal app-shell-footer">
   <a class="<?php echo $linkClass('mensajes'); ?>" href="<?php echo $routeUrl('mensajes'); ?>">
   <span class="pie-principal__icono material-symbols-outlined" data-icon="chat">
    chat
   </span>
   <span class="pie-principal__texto font-label-md text-label-md">
    Mensajes
   </span>
   </a>
   <a class="<?php echo $linkClass('actividad_reciente'); ?>" href="<?php echo $routeUrl('actividad_reciente'); ?>">
   <span class="pie-principal__icono material-symbols-outlined" data-icon="newspaper">
    newspaper
   </span>
   <span class="pie-principal__texto font-label-md text-label-md">
    Novedades
   </span>
   </a>
   <a<?php echo $currentRoute === 'inicio' ? ' aria-current="page"' : ''; ?> class="<?php echo $linkClass('inicio'); ?>" href="<?php echo $routeUrl('index.php'); ?>">
   <span class="material-symbols-outlined icono-relleno" data-icon="home">
    home
   </span>
   <span class="pie-principal__texto font-label-md text-label-md">
    Inicio
   </span>
   </a>
   <a class="<?php echo $linkClass('servicios'); ?>" href="<?php echo $routeUrl('servicios'); ?>">
   <span class="pie-principal__icono material-symbols-outlined" data-icon="apps">
    apps
   </span>
   <span class="pie-principal__texto font-label-md text-label-md">
    Servicios
   </span>
   </a>
   <a class="<?php echo $linkClass('contactos'); ?>" href="<?php echo $routeUrl('contactos'); ?>">
   <span class="pie-principal__icono material-symbols-outlined" data-icon="call">
    call
   </span>
   <span class="pie-principal__texto font-label-md text-label-md">
    Contactos
   </span>
   </a>
  </nav>
