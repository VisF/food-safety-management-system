<?php
/**
 * Vista: usuario_aprobado.php
 * Propósito: Mostrar estado exitoso del usuario y pasos siguientes (comprobante/carnet).
 * Entradas: valores por defecto en `getDefaultData()`; el controlador puede pasar datos reales.
 * Nota: ofrecer rutas claras para descargar comprobantes o ver estado del trámite.
 */
class UsuarioAprobadoVista
{
  private string $baseURL = '/ManipulacionDeAlimentos/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'App Ciudadana - Estado de Trámite',
      'nombre' => 'Juan Perez',
      'estado_texto' => 'Aprobado – Pendiente de carnet',
      'examen_realizado' => 'Manipulación de Alimentos',
      'fecha_aprobacion' => '24 de Mayo, 2024',
      'vencimiento_provisorio' => '24 de Junio, 2024',
      'steps' => [
        [
          'icon' => 'file_download',
          'title' => 'Descarga tu comprobante',
          'description' => 'Utiliza el código QR para circular hasta recibir el plástico.',
          'bg_class' => 'bg-secondary-container',
          'text_class' => 'text-on-secondary-container',
        ],
        [
          'icon' => 'notifications_active',
          'title' => 'Espera la notificación',
          'description' => 'Te avisaremos cuando tu carnet físico esté listo para retirar.',
          'bg_class' => 'bg-primary-fixed',
          'text_class' => 'text-on-primary-fixed',
        ],
      ],
      'footer_button_1' => 'Descargar comprobante',
      'footer_button_2' => 'Ver estado',
    ];
  }

  private function getHeader(array $data): void
  {
    ?>
    <!DOCTYPE html>
    <html lang="es">
     <head>
      <meta charset="utf-8"/>
      <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
      <title>
       <?php echo $this->e($data['page_title']); ?>
      </title>
      <script src="/js/tailwind-config.js">
      </script>
      <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries">
      </script>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
      <link href="/css/base.css" rel="stylesheet"/>
      <link href="/css/components.css" rel="stylesheet"/>
     </head>
     <body class="bg-background text-on-background min-h-screen flex flex-col tema-ciudadano">
    <?php include __DIR__ . '/header.php'; ?>
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

  private function e(mixed $value): string
  {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  }

  public function mostrar(): void
  {
    $data = array_replace_recursive($this->getDefaultData(), []);

    $this->getHeader($data);
    ?>

      <!-- TopAppBar Component -->
      <!-- Main Content Canvas -->
      <main class="flex-grow pt-32 pb-24 contenido-principal contenido-principal--estrecho w-full">
       <!-- Success Badge & Status -->
       <div class="flex flex-col items-center mb-8 text-center">
      <div class="w-24 h-24 bg-[#e8f5e9] text-[#2e7d32] rounded-full flex items-center justify-center mb-4 shadow-sm border-4 border-white">
       <span class="material-symbols-outlined icono-relleno !text-5xl">
        check_circle
       </span>
      </div>
      <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">
       ¡Felicitaciones!
      </h2>
      <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-[#fff8e1] text-[#f57f17] font-status-label text-status-label">
       <span class="material-symbols-outlined mr-2 text-[18px]">
        verified
       </span>
       <?php echo $this->e($data['estado_texto']); ?>
      </div>
       </div>
       <!-- User Details Card -->
      <div class="bg-surface-container-lowest rounded-[24px] p-card-padding shadow-[0_4px_20px_rgba(0,85,150,0.08)] mb-stack-gap border border-surface-container app-vista-card">
      <div class="flex justify-between items-start mb-4">
       <div>
        <p class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider">
         Titular del Trámite
        </p>
        <h3 class="font-headline-md text-headline-md text-primary">
         <?php echo $this->e($data['nombre']); ?>
        </h3>
       </div>
       <div class="w-12 h-12 bg-primary-fixed rounded-xl flex items-center justify-center text-on-primary-fixed">
        <span class="pie-principal__icono material-symbols-outlined">
         person
        </span>
       </div>
      </div>
      <div class="space-y-3">
       <div class="flex justify-between border-t border-surface-container-high pt-3">
        <span class="font-body-md text-body-md text-on-surface-variant">
         Examen realizado:
        </span>
        <span class="font-body-md text-body-md font-semibold text-on-surface">
         <?php echo $this->e($data['examen_realizado']); ?>
        </span>
       </div>
       <div class="flex justify-between">
        <span class="font-body-md text-body-md text-on-surface-variant">
         Fecha de aprobación:
        </span>
        <span class="font-body-md text-body-md font-semibold text-on-surface">
         <?php echo $this->e($data['fecha_aprobacion']); ?>
        </span>
       </div>
       <div class="flex justify-between">
        <span class="font-body-md text-body-md text-on-surface-variant">
         Vencimiento provisorio:
        </span>
        <span class="font-body-md text-body-md font-semibold text-on-surface">
         <?php echo $this->e($data['vencimiento_provisorio']); ?>
        </span>
       </div>
      </div>
       </div>
       <!-- Next Steps Bento-ish Layout -->
       <section class="mb-8">
      <h4 class="font-label-md text-label-md text-on-surface-variant mb-4 px-1 uppercase tracking-widest">
       Próximos pasos
      </h4>
      <div class="grid grid-cols-1 gap-3">
      <?php // Itera pasos a mostrar al usuario (UI). 'steps' debe ser un array estático y pequeño.
      foreach ($data['steps'] as $step): ?>
      <div class="bg-white p-4 rounded-xl border border-surface-container flex gap-4 items-start app-vista-card app-vista-card--surface">
        <div class="<?php echo $this->e($step['bg_class']); ?> p-2 rounded-lg <?php echo $this->e($step['text_class']); ?>">
         <span class="pie-principal__icono material-symbols-outlined">
        <?php echo $this->e($step['icon']); ?>
         </span>
        </div>
        <div>
         <h5 class="font-body-lg text-body-lg font-bold text-on-surface">
        <?php echo $this->e($step['title']); ?>
         </h5>
         <p class="font-body-md text-body-md text-on-surface-variant">
        <?php echo $this->e($step['description']); ?>
         </p>
        </div>
       </div>
       <?php endforeach; ?>
      </div>
       </section>
       <!-- Action Buttons -->
       <div class="space-y-3">
      <button class="w-full bg-gradient-to-r from-primary to-primary-container text-on-primary py-4 px-6 rounded-lg font-bold shadow-md active:scale-[0.98] transition-all flex items-center justify-center gap-2 app-vista-button app-vista-button--primary">
       <span class="pie-principal__icono material-symbols-outlined">
        download
       </span>
       <?php echo $this->e($data['footer_button_1']); ?>
      </button>
      <button class="w-full bg-white border border-outline-variant text-primary py-4 px-6 rounded-lg font-bold active:scale-[0.98] transition-all flex items-center justify-center gap-2 app-vista-button app-vista-button--secondary">
       <span class="pie-principal__icono material-symbols-outlined">
        visibility
       </span>
       <?php echo $this->e($data['footer_button_2']); ?>
      </button>
       </div>
      </main>
      <!-- BottomNavBar Component -->
      <!-- Background Decoration for institutional feel -->
      <div class="fixed top-0 right-0 w-1/2 h-1/2 -z-10 opacity-5 pointer-events-none">
       <img class="w-full h-full object-cover grayscale" data-alt="A subtle, highly desaturated and transparent Argentine flag texture overlaying the background. The visual style is minimalist and clean, reflecting an institutional municipal authority. Soft blue and white gradients blend into the minimalist interface, creating a professional and patriotic yet modern digital environment." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCbAmrUOiWLonnKJLPLSJ_p1lVavbDeZxuPt3Vfp9aHBaOAA1gRMp2p_51SBhH09MRzI-9jReXn73LzPMmNGeoBO598-ELCFZVyhCYMicV3seEaWdsTSqng3MsPCyRv630Y4jXNNjmpPDI7CDH2Tjzxpi4I-mQhy_K8GYoFBtceOW8Ia8uFaSpDMgV96N8KIZcpJxxVVPTBhzYaD2ZiUKLUiYUC0NYsJSK5Z2ILrYeUyRlhr4Fblnv9Dp0tyjHYMT8ixmVXcceE-875"/>
      </div>

    <?php
    $this->getFooter();
  }
}

(new UsuarioAprobadoVista())->mostrar();
