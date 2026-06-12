<?php
/**
 * Vista: panel_inspector.php
 * Propósito: Interfaz para inspectores: búsqueda y verificación de carnets.
 * Entradas: datos por defecto en `getDefaultData()`; resultados se obtienen desde el controlador.
 * Nota: no exponer datos sensibles; validar entradas de búsqueda en backend.
 */
class PanelInspectorVista
{
  private string $baseURL = '/ManipulacionDeAlimentos/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'Panel Inspector - App Ciudadana',
      'nombre' => 'Juan Perez',
      'dni' => '35.849.201',
      'estado' => 'HABILITADO',
      'estado_label' => 'Vigente',
      'vencimiento' => '15/10/2025',
      'categoria' => 'Clase A',
      'alert_title' => 'Alertas de irregularidades',
      'alert_message' => 'El perfil del ciudadano se encuentra libre de actas de inspección pendientes.',
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

  public function mostrar(array $inicioData): void
  {
    $data = array_replace_recursive($this->getDefaultData(), $inicioData);

    $this->getHeader($data);
    ?>

      <!-- TopAppBar Shell -->
      <main class="max-w-4xl mx-auto px-margin-mobile mt-8 space-y-8">
       <main class="contenido-principal contenido-principal--medio mt-8 space-y-8">
      <!-- Search Section -->
      <section class="space-y-4">
      <div class="bg-surface-container-lowest p-card-padding rounded-xl shadow-[0_4px_20px_rgba(27,96,162,0.08)] app-vista-card">
        <h2 class="font-headline-md text-headline-md text-primary mb-4">
         Verificación de Carnet
        </h2>
        <div class="flex flex-col sm:flex-row gap-stack-gap">
         <div class="relative flex-grow">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">
         search
        </span>
        <input class="w-full pl-12 pr-4 py-4 bg-surface-container border-none focus:ring-2 focus:ring-primary rounded-xl font-body-lg text-body-lg placeholder:text-outline-variant" placeholder="Ingrese DNI del ciudadano" type="text"/>
         </div>
         <button class="bg-primary-container text-on-primary font-bold px-8 py-4 rounded-xl hover:opacity-90 active:scale-98 transition-all shadow-md app-vista-button app-vista-button--primary">
        Buscar
         </button>
        </div>
       </div>
      </section>
      <!-- Results Section (Bento Inspired Layout) -->
      <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
       <!-- Profile Card -->
      <div class="md:col-span-2 bg-surface-container-lowest p-card-padding rounded-[24px] shadow-[0_4px_20px_rgba(27,96,162,0.08)] flex flex-col sm:flex-row gap-6 app-vista-card">
        <div class="w-32 h-32 rounded-xl overflow-hidden bg-surface-container flex-shrink-0">
         <img alt="Juan Perez" class="w-full h-full object-cover" data-alt="A professional studio headshot of a middle-aged male municipal employee named Juan Perez. He has a friendly, trustworthy expression, wearing a clean white shirt against a neutral, high-key background. The lighting is soft and even, characteristic of a formal government identification photograph, maintaining a clean and institutional light-mode aesthetic." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCTOU3eKQ_8bzP3ERhudPokImSLpVdZGTB7kx0t_B81v2KdxZMwmVL_yOIBJCRjn_3rhNV9EMKYCTa9ctFKir--89nLwQ2afMZVcuRSE_ho-Wlu6ECQBIkcEnU3bSCl8gx0_sjy8I9MJeZnQ_i5aME4xXweXQfTPrBeENwtzRI2ugHtZQwyRUKTFeSFETK_1tG5a-x9q7YLZgXF18H9IQrzpxOTf1UdKHsQ5JDhzjiQO1oTsplTGmy93wzyH2T4xRdmxJGFPlDP7UBh"/>
        </div>
        <div class="flex-grow space-y-3">
         <div class="flex flex-wrap justify-between items-start gap-2">
        <div>
         <h3 class="font-headline-lg text-headline-lg text-on-background">
          <?php echo $this->e($inicioData['usuario']['nombre']); ?>
         </h3>
         <p class="font-body-md text-body-md text-outline">
          DNI:
          <span class="numero-dni">
           <?php echo $this->e($inicioData['usuario']['dni']); ?>
          </span>
         </p>
        </div>
        <div class="flex flex-col items-end gap-1">
         <span class="bg-[#e8f5e9] text-[#2e7d32] px-4 py-1 rounded-full font-status-label text-status-label flex items-center gap-1">
          <span class="material-symbols-outlined icono-relleno text-[18px]">
           check_circle
          </span>
          <?php echo $this->e($inicioData['tramite']['estado']); ?>
         </span>
         <span class="text-[#2e7d32] font-label-md text-label-md">
          <?php echo $this->e($inicioData['estado_label']); ?>
         </span>
        </div>
         </div>
         <div class="pt-2 grid grid-cols-2 gap-4">
        <div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
         <p class="font-label-md text-label-md text-outline uppercase">
          Vencimiento
         </p>
         <p class="font-headline-md text-headline-md text-primary">
          <?php echo $this->e($inicioData['vencimiento']); ?>
         </p>
        </div>
        <div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
         <p class="font-label-md text-label-md text-outline uppercase">
          Categoría
         </p>
         <p class="font-headline-md text-headline-md text-primary">
          <?php echo $this->e($inicioData['categoria']); ?>
         </p>
        </div>
         </div>
        </div>
       </div>
       <!-- Card Preview Action -->
      <div class="bg-primary p-card-padding rounded-[24px] shadow-lg flex flex-col justify-between text-on-primary relative overflow-hidden app-vista-card app-vista-card--gradient">
        <div class="relative z-10">
         <p class="font-label-md text-label-md opacity-80 uppercase mb-2">
        Vista Previa Digital
         </p>
         <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
        <div class="flex items-center gap-3 mb-4">
         <div class="w-8 h-8 rounded-full bg-white/20">
         </div>
         <div class="h-2 w-20 bg-white/30 rounded-full">
         </div>
        </div>
        <div class="space-y-2">
         <div class="h-2 w-full bg-white/20 rounded-full">
         </div>
         <div class="h-2 w-2/3 bg-white/20 rounded-full">
         </div>
        </div>
         </div>
        </div>
        <div class="relative z-10 mt-6 space-y-2">
         <button class="w-full bg-white text-primary font-bold py-3 rounded-xl flex items-center justify-center gap-2 active:scale-95 transition-transform app-vista-button app-vista-button--primary">
        <span class="pie-principal__icono material-symbols-outlined">
         download
        </span>
        Descargar carnet
         </button>
         <button class="w-full text-white/80 font-label-md text-label-md py-2 hover:text-white transition-colors app-vista-button app-vista-button--secondary">
        Ver historial de trámites
         </button>
        </div>
        <!-- Abstract Background Ornament -->
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/5 rounded-full blur-2xl">
        </div>
       </div>
      </section>
      <!-- Alerts Section -->
      <section class="space-y-4">
       <h4 class="font-headline-md text-headline-md text-on-background flex items-center gap-2">
        <span class="encabezado-principal__icono material-symbols-outlined">
         warning
        </span>
        <?php echo $this->e($inicioData['alert_title']); ?>
       </h4>
      <div class="bg-surface-container-low border-2 border-dashed border-outline-variant rounded-[24px] p-12 flex flex-col items-center justify-center text-center app-vista-card app-vista-card--surface">
        <div class="w-16 h-16 bg-surface-container-highest rounded-full flex items-center justify-center mb-4">
         <span class="material-symbols-outlined text-outline text-3xl">
        verified_user
         </span>
        </div>
        <p class="font-headline-md text-headline-md text-on-surface-variant mb-1">
         Sin infracciones registradas
        </p>
        <p class="font-body-md text-body-md text-outline">
         <?php echo $this->e($inicioData['alert_message']); ?>
        </p>
       </div>
      </section>
       </main>
       <!-- BottomNavBar Shell -->
      </main>

    <?php
    $this->getFooter();
  }
}

(new PanelInspectorVista())->mostrar();
