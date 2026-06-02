<?php
/**
 * Vista: usuario_rechazado.php
 * Propósito: Indicar motivos de rechazo y pasos a seguir para el usuario.
 * Entradas: datos por defecto en `getDefaultData()` o vía GET 'data'.
 * Nota: ofrecer acciones claras (corregir, solicitar revisión) y no exponer información sensible.
 */
class UsuarioRechazadoVista
{
  private string $baseURL = '/ManipulacionDeAlimentosAPI/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'App Ciudadana - Estado de Trámite',
      'nombre' => 'Juan Perez',
      'estado' => 'Rechazado',
      'mensaje' => 'No cumple con las condiciones para rendir el examen',
      'motivos_title' => 'Motivos del rechazo',
      'motivos' => [
        [
          'icon' => 'description',
          'texto' => 'Documentación Incompleta',
        ],
        [
          'icon' => 'verified_user',
          'texto' => 'Certificado Inválido',
        ],
        [
          'icon' => 'event_busy',
          'texto' => 'Plazo vencido',
        ],
      ],
      'footer_note' => 'Si tiene dudas sobre esta decisión, puede acercarse a la oficina municipal más cercana.',
      'footer_button_1' => 'Corregir documentación',
      'footer_button_2' => 'Solicitar revisión',
    ];
  }

  private function getHeader(array $data): void
  {
    ?>
    <!DOCTYPE html>
    <html class="light" lang="es">
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
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
      <link href="/css/base.css" rel="stylesheet"/>
      <link href="/css/components.css" rel="stylesheet"/>
     </head>
     <body class="bg-background text-on-background min-h-screen pb-24 tema-ciudadano">
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
      <main class="contenido-principal mt-8 space-y-stack-gap">
       <!-- User Profile & Status Header -->
      <section class="bg-surface-container-lowest rounded-[24px] p-card-padding shadow-[0_4px_20px_rgba(0,0,0,0.08)] flex flex-col items-center text-center app-vista-card">
      <div class="w-20 h-20 rounded-full mb-4 overflow-hidden border-2 border-primary-fixed">
       <img alt="Juan Perez profile" class="w-full h-full object-cover" data-alt="A professional headshot of a friendly Argentine man named Juan Perez in his 30s. He has short dark hair and a kind expression, wearing a neutral casual shirt. The background is a soft, out-of-focus city park in daylight, maintaining a clean and trustworthy institutional aesthetic consistent with a modern municipal application." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDCJWVRrv-_m1gVoyFrMnLK87-u4lW39TbzZgJJwfvy6wMu12dt0R6FBTg2_W6RdNRJkW4iUM7NaGNSxOXaLaqpc6VRIlHxyDqCMrB20kT2TA2k75uLMNOtFnWmDWWZ1ElBkUP6FBXg5NNmaIMkHlN93VdYhnTTynexxoAzdO-_xvU1SX2HYkXlcdmS1TMBrabZARHeNnNvwsh0LMJkcWV4hwyE3a7v3Ylknint-PaCTSGbwYk4E0hVJQOdtC88z_Vd30cHcsN8zlLq"/>
      </div>
      <h2 class="font-headline-md text-headline-md text-on-surface mb-1">
       <?php echo $this->e($data['nombre']); ?>
      </h2>
      <!-- Rechazado Status Chip -->
      <div class="bg-error-container text-on-error-container px-4 py-1.5 rounded-full font-status-label text-status-label flex items-center gap-2 mt-2">
       <span class="material-symbols-outlined icono-relleno text-[18px]">
        cancel
       </span>
       <?php echo $this->e($data['estado']); ?>
      </div>
       </section>
       <!-- Rejection Message Card -->
      <section class="bg-surface-container-lowest rounded-[24px] p-card-padding shadow-[0_4px_20px_rgba(0,0,0,0.08)] app-vista-card">
      <div class="flex flex-col items-center text-center space-y-4">
       <div class="bg-error-container/30 p-4 rounded-full">
        <span class="material-symbols-outlined icono-relleno text-error text-[48px]" data-icon="warning">
         warning
        </span>
       </div>
       <p class="font-body-lg text-body-lg text-on-surface-variant font-semibold">
        <?php echo $this->e($data['mensaje']); ?>
       </p>
      </div>
      <!-- Reasons List -->
      <div class="mt-8 space-y-4">
       <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">
        <?php echo $this->e($data['motivos_title']); ?>
       </h3>
       <div class="space-y-3">
        <?php // Itera motivos de rechazo. Cada motivo debe ser un array simple con 'icon' y 'texto'.
        foreach ($data['motivos'] as $motivo): ?>
        <div class="flex items-start gap-3 p-3 bg-surface-container-low rounded-xl">
         <span class="material-symbols-outlined icono-relleno text-error text-[20px]" data-icon="<?php echo $this->e($motivo['icon']); ?>">
        <?php echo $this->e($motivo['icon']); ?>
         </span>
         <span class="font-body-md text-body-md text-on-surface">
        <?php echo $this->e($motivo['texto']); ?>
         </span>
        </div>
        <?php endforeach; ?>
       </div>
      </div>
       </section>
       <!-- Action Buttons -->
       <section class="pt-4 flex flex-col gap-4">
      <button class="boton-principal-gradiente text-on-primary font-body-lg text-body-lg font-bold py-4 rounded-xl shadow-md active:scale-[0.98] transition-all app-vista-button app-vista-button--primary">
       <?php echo $this->e($data['footer_button_1']); ?>
      </button>
      <button class="bg-surface-container-lowest border border-outline-variant text-primary font-body-lg text-body-lg font-bold py-4 rounded-xl active:scale-[0.98] transition-all app-vista-button app-vista-button--secondary">
       <?php echo $this->e($data['footer_button_2']); ?>
      </button>
       </section>
       <!-- Informational Footer -->
       <p class="text-center font-label-md text-label-md text-on-surface-variant px-6 pb-8">
      <?php echo $this->e($data['footer_note']); ?>
       </p>
      </main>
      <!-- BottomNavBar Component -->

    <?php
    $this->getFooter();
  }
}

(new UsuarioRechazadoVista())->mostrar();
