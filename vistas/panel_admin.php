<?php
/**
 * Vista: panel_admin.php
 * Propósito: Panel administrativo con métricas y actividad reciente.
 * Entradas: puede recibir datos vía GET 'data' para sobreescribir valores por defecto.
 * Nota: Usar métodos `e()` para escapar salida al renderizar nombres/valores.
 */
class PanelAdminVista
{
    private string $baseURL = '/ManipulacionDeAlimentosAPI/';

    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Panel Administrativo - App Ciudadana',
            'stats' => [
                [
                    'label' => 'TOTAL INSCRIPTOS',
                    'value' => '1,240',
                    'icon' => 'groups',
                    'style' => 'primary',
                ],
                [
                    'label' => 'APROBADOS',
                    'value' => '850',
                    'icon' => 'check_circle',
                    'style' => 'success',
                ],
                [
                    'label' => 'RECHAZADOS',
                    'value' => '120',
                    'icon' => 'cancel',
                    'style' => 'danger',
                ],
                [
                    'label' => 'CARNETS EMITIDOS',
                    'value' => '730',
                    'icon' => 'badge',
                    'style' => 'secondary',
                ],
            ],
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

    private function getHeader(array $panelAdminData): void
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
          <title>
           <?php echo $this->e($panelAdminData['page_title']); ?>
          </title>
          <script src="<?php echo $assetBase; ?>/js/tailwind-config.js">
          </script>
          <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries">
          </script>
          <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
          <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;display=swap" rel="stylesheet"/>
          <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
          <link href="<?php echo $assetBase; ?>/css/base.css" rel="stylesheet"/>
          <link href="<?php echo $assetBase; ?>/css/components.css" rel="stylesheet"/>
          <link href="<?php echo $assetBase; ?>/css/ui.css" rel="stylesheet"/>
         </head>
         <body class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano">
        <?php $page_title = 'Panel Administrativo'; include __DIR__ . '/header.php'; ?>
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

    private function cardStyleClass(string $style): string
    {
        return match ($style) {
            'success' => 'panel-admin-card--success',
            'danger' => 'panel-admin-card--danger',
            'secondary' => 'panel-admin-card--secondary',
            default => 'panel-admin-card--primary',
        };
    }

    private function activityLink(): string
    {
        return $this->getRoute('actividad_reciente');
    }

    private function getRoute(string $route): string
    {
        echo $this->getRoute('login');
        die();
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if (preg_match('#/vistas$#', $basePath) === 1) {
            $basePath = (string) preg_replace('#/vistas$#', '', $basePath);
        }

        return $basePath . '/' . rawurlencode($route);
    }

    public function mostrar(): void
    {
        $panelAdminData = array_replace_recursive($this->getDefaultData(), $this->getIncomingData());
        $activityLimitedRows = array_slice((array) $panelAdminData['activities'], 0, 5);

        $this->getHeader($panelAdminData);
        ?>

          <!-- TopAppBar Shell -->
          <main class="contenido-principal contenido-principal--ancho">
           <div class="space-y-8 max-w-[430px] mx-auto md:max-w-none">
            <section class="space-y-1 px-1">
             <h2 class="font-headline-lg text-headline-lg text-primary">
              Panel Administrativo
             </h2>
             <p class="font-body-md text-body-md text-on-surface-variant">
              Control de emisión de carnets para manipulación de alimentos.
             </p>
            </section>
            <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <?php // Itera métricas 'stats' para mostrar cards; asegurar estructura esperada en el controlador.
        foreach ($panelAdminData['stats'] as $stat): ?>
             <article class="panel-admin-card <?php echo $this->cardStyleClass((string) $stat['style']); ?> app-vista-card">
              <p class="panel-admin-card__etiqueta">
               <?php echo $this->e($stat['label']); ?>
              </p>
              <div class="panel-admin-card__fila">
               <span class="panel-admin-card__numero panel-admin-card__numero--<?php echo $this->e($stat['style']); ?>">
                <?php echo $this->e($stat['value']); ?>
               </span>
               <span class="material-symbols-outlined panel-admin-card__icono" data-icon="<?php echo $this->e($stat['icon']); ?>">
                <?php echo $this->e($stat['icon']); ?>
               </span>
              </div>
             </article>
        <?php endforeach; ?>
            </section>
            <section class="space-y-4">
             <h3 class="font-headline-md text-headline-md text-on-surface">
              Acciones Rápidas
             </h3>
             <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <a class="panel-admin-action app-vista-button app-vista-button--primary" href="<?php echo $this->getRoute('crear_examen'); ?>" role="button" style="text-decoration: none;">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="calendar_today">
                calendar_today
               </span>
               <span class="panel-admin-action__texto">
                Crear fecha de examen
               </span>
              </a>
              <button class="panel-admin-action app-vista-button app-vista-button--primary">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="event_seat">
                event_seat
               </span>
               <span class="panel-admin-action__texto">
                Gestionar cupos
               </span>
              </button>
              <button class="panel-admin-action app-vista-button app-vista-button--primary">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="fact_check">
                fact_check
               </span>
               <span class="panel-admin-action__texto">
                Validar documentación
               </span>
              </button>
              <button class="panel-admin-action app-vista-button app-vista-button--primary">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="assignment_turned_in">
                assignment_turned_in
               </span>
               <span class="panel-admin-action__texto">
                Registrar resultados
               </span>
              </button>
              <button class="panel-admin-action app-vista-button app-vista-button--primary">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="description">
                description
               </span>
               <span class="panel-admin-action__texto">
                Descargar documentación
               </span>
              </button>
              <button class="panel-admin-action app-vista-button app-vista-button--primary">
               <span class="material-symbols-outlined panel-admin-action__icono" data-icon="add_card">
                add_card
               </span>
               <span class="panel-admin-action__texto">
                Cargar Carnet
               </span>
              </button>
             </div>
            </section>
            <section class="panel-admin-actividad app-vista-card">
             <div class="panel-admin-actividad__encabezado">
              <h3 class="font-headline-md text-headline-md text-on-surface">
               Actividad Reciente
              </h3>
              <a class="panel-admin-actividad__enlace" href="<?php echo $this->activityLink(); ?>" role="button">
               Ver todos
              </a>
             </div>
             <div class="divide-y divide-surface-container-high">
        <?php // Itera actividades recientes (limitadas). No iterar colecciones sin límites desde la vista.
        foreach ($activityLimitedRows as $activity): ?>
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
          <!-- Bottom Navigation Bar -->

        <?php
        $this->getFooter();
    }
}

(new PanelAdminVista())->mostrar();
