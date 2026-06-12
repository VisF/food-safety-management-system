<?php declare(strict_types = 1);
/**
 * Vista: actividad_reciente.php
 * Propósito: Listado de actividad reciente en el sistema (audit trail resumido).
 * Entradas: acepta GET 'data' con 'activities' para datos de ejemplo; el backend debe proveer datos reales.
 * Nota: mantener datos sensibles fuera de este listado público.
 */
class ActividadRecienteVista
{
  private string $baseURL = '/ManipulacionDeAlimentos/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'Actividad Reciente - App Ciudadana',
      'activities' => [
        [
          'nombre' => 'Juan Perez',
          'dni' => '35.849.201',
          'estado' => 'PENDIENTE',
          'estado_class' => 'pendiente',
        ],
        [
          'nombre' => 'Maria Garcia',
          'dni' => '27.482.910',
          'estado' => 'PAGADO',
          'estado_class' => 'pagado',
        ],
        [
          'nombre' => 'Carlos Rodriguez',
          'dni' => '31.902.115',
          'estado' => 'RECHAZADO',
          'estado_class' => 'rechazado',
        ],
      ],
    ];
  }

  private function getHeader(array $activityData): void
  {
    $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (preg_match('#/vistas$#', $assetBase) === 1) {
      $assetBase = (string) preg_replace('#/vistas$#', '', $assetBase);
    }
    if ($assetBase === '') {
      $assetBase = '';
    }
    ?>
    <!DOCTYPE html>
    <html class="light" lang="es">
     <head>
      <meta charset="utf-8"/>
      <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
      <title><?php echo $this->e($activityData['page_title']); ?></title>
      <script src="<?php echo $assetBase; ?>/js/tailwind-config.js">
      </script>
      <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries">
      </script>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
      <link href="<?php echo $assetBase; ?>/css/base.css" rel="stylesheet"/>
      <link href="<?php echo $assetBase; ?>/css/components.css" rel="stylesheet"/>
      <link href="<?php echo $assetBase; ?>/css/ui.css" rel="stylesheet"/>
     </head>
     <body class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano">
    <?php $page_title = 'Actividad Reciente'; include __DIR__ . '/header.php'; ?>
    <?php
  }

  private function getFooter(): void
  {
    include __DIR__ . '/footer.php';
    ?>
     </body>
    </html>
    <?php
  }

  private function getIncomingData(): array
  {
    if (!isset($_GET['data'])) {
      return [];
    }

    $decodedData = json_decode((string) $_GET['data'], true);
    return is_array($decodedData) ? $decodedData : [];
  }

  private function e(mixed $value): string
  {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  }

  public function mostrar(): void
  {
    $activityData = array_replace_recursive($this->getDefaultData(), $this->getIncomingData());

    $this->getHeader($activityData);
    ?>

      <main class="contenido-principal contenido-principal--ancho">
       <div class="space-y-6 max-w-[430px] mx-auto md:max-w-none">
        <section class="space-y-1 px-1">
         <h2 class="font-headline-lg text-headline-lg text-primary">
          Actividad Reciente
         </h2>
         <p class="font-body-md text-body-md text-on-surface-variant">
          Listado completo de actividades en el sistema
         </p>
        </section>

        <section class="panel-admin-actividad">
         <div class="divide-y divide-surface-container-high">
    <?php // Itera 'activities' y renderiza cada fila; el controlador debe paginar/limitar la colección para evitar cargas grandes.
    foreach ((array) $activityData['activities'] as $activity): ?>
          <article class="panel-admin-fila">
           <div class="panel-admin-fila__izquierda">
            <div class="panel-admin-fila__avatar">
             <span class="material-symbols-outlined panel-admin-fila__icono" data-icon="person">
              person
             </span>
            </div>
            <div>
             <p class="panel-admin-fila__nombre">
              <?php echo $this->e($activity['nombre']); ?>
             </p>
             <p class="panel-admin-fila__dni">
              DNI <?php echo $this->e($activity['dni']); ?>
             </p>
            </div>
           </div>
           <div class="panel-admin-fila__derecha">
            <div class="panel-admin-fila__estado-box">
             <span class="panel-admin-fila__estado-label">
              Estado de trámite
             </span>
             <span class="panel-admin-fila__estado panel-admin-fila__estado--<?php echo $this->e($activity['estado_class']); ?>">
              <?php echo $this->e($activity['estado']); ?>
             </span>
            </div>
            <button aria-label="Ver detalle" class="panel-admin-fila__boton" type="button">
             <span class="material-symbols-outlined panel-admin-fila__chevron" data-icon="chevron_right">
              chevron_right
             </span>
            </button>
           </div>
          </article>
    <?php endforeach; ?>
         </div>
        </section>
       </div>
      </main>

    <?php
    $this->getFooter();
  }
}


