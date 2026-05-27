<header class="encabezado-principal app-shell-header encabezado-principal--alto encabezado-principal--fijo encabezado-principal--realzado encabezado-principal--primario text-on-primary topbar">
   <div class="encabezado-principal__grupo encabezado-principal__grupo--espaciado">
    <button aria-label="Abrir menú" class="encabezado-principal__boton" type="button">
     <span class="encabezado-principal__icono material-symbols-outlined" data-icon="menu">
      menu
     </span>
    </button>
    <h1 class="encabezado-principal__titulo">
     <?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'App Ciudadana'; ?>
    </h1>
   </div>
   <button aria-label="Cambiar tema" class="encabezado-principal__boton" type="button">
    <span class="encabezado-principal__icono material-symbols-outlined" data-icon="light_mode">
     light_mode
    </span>
   </button>
  </header>
<?php
// Determine a reliable base path for assets.
// If current script is under /vistas, strip that segment.
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if (preg_match('#/vistas$#', $assetBase) === 1) {
  $assetBase = (string) preg_replace('#/vistas$#', '', $assetBase);
}
if ($assetBase === '') { $assetBase = ''; }
?>
<script src="<?php echo $assetBase; ?>/js/sample-data.js"></script>
<script>
(function(){
  const base = '<?php echo $assetBase; ?>';
  const add = (tag, attrs) => {
    const e = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
    document.head.appendChild(e);
  };
  const has = (selector) => Boolean(document.querySelector(selector));

  if (!has('script[src$="tailwind-config.js"]')) {
    add('script', { src: base + '/js/tailwind-config.js' });
  }
  if (!has('script[src*="cdn.tailwindcss.com"]')) {
    // NOTE: Tailwind CDN is fine for prototyping only; avoid in production.
    add('script', { src: 'https://cdn.tailwindcss.com?plugins=forms,container-queries' });
  }
  if (!has('link[href*="family=Inter"]')) {
    add('link', { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap' });
  }
  if (!has('link[href*="family=Poppins"]')) {
    add('link', { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;display=swap' });
  }
  if (!has('link[href*="Material+Symbols+Outlined"]')) {
    add('link', { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap' });
  }
  if (!has('link[href$="/css/base.css"]')) {
    add('link', { rel: 'stylesheet', href: base + '/css/base.css' });
  }
  if (!has('link[href$="/css/components.css"]')) {
    add('link', { rel: 'stylesheet', href: base + '/css/components.css' });
  }
  if (!has('link[href$="/css/ui.css"]')) {
    add('link', { rel: 'stylesheet', href: base + '/css/ui.css' });
  }
})();
</script>
