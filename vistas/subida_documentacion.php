<?php
/**
 * Vista: Subida de documentación
 * Propósito: Mostrar lista de documentos requeridos y botones para subir/corregir archivos.
 * Estructura esperada (`getDefaultData()`):
 *  - documents: array[{icon, title, description, status, status_icon, status_class}]
 * Implementación técnica:
 *  - El loop `foreach ($data['documents'] as $document)` renderiza cada tarjeta usando `e()` para escapar.
 *  - Los botones de acción deben apuntar a rutas gestionadas por `Router.php` donde el controlador valida y almacena archivos.
 * Seguridad:
 *  - Mantener la lógica de validación/almacenamiento fuera de la vista; evitar mostrar rutas de filesystem o nombres reales.
 */
class SubidaDocumentacionVista
{
  private string $baseURL = '/ManipulacionDeAlimentosAPI/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'Subida de documentación - App Ciudadana',
      'hero_title' => 'Subida de documentación',
      'hero_text' => 'Complete los campos requeridos para avanzar con su trámite municipal.',
      'info_title' => 'Formatos aceptados',
      'info_text' => 'Solo se permiten archivos en formato JPG, PNG y PDF. Peso máximo: 5MB.',
      'documents_title' => 'Documentación requerida',
      'documents' => [
        [
          'icon' => 'badge',
          'title' => 'DNI frente y dorso',
          'description' => 'Ambos lados en una misma imagen o PDF.',
          'status' => 'Cargado',
          'status_icon' => 'check_circle',
          'status_class' => 'bg-green-100 text-green-700',
        ],
        [
          'icon' => 'account_circle',
          'title' => 'Foto carnet',
          'description' => 'Fondo blanco, frente despejado.',
          'status' => 'Pendiente',
          'status_icon' => 'pending',
          'status_class' => 'bg-amber-100 text-amber-700',
        ],
        [
          'icon' => 'school',
          'title' => 'Certificado Moodle',
          'description' => 'Constancia de aprobación del curso.',
          'status' => 'Rechazado',
          'status_icon' => 'cancel',
          'status_class' => 'bg-red-100 text-red-700',
        ],
      ],
      'footer_button' => 'Enviar documentación',
      'footer_note' => 'Usted será notificado una vez que los documentos sean validados.',
    ];
  }

  private function getHeader(array $data): void
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
       <?php echo $this->e($data['page_title']); ?>
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

      <main class="contenido-principal contenido-principal--estrecho" style="display: grid; gap: 18px;">
       <section style="display: grid; gap: 8px; margin-top: 4px;">
        <h2 class="app-vista-section-title">
         <?php echo $this->e($data['hero_title']); ?>
        </h2>
        <p class="app-vista-section-subtitle" style="margin: 0; max-width: 34ch;">
         <?php echo $this->e($data['hero_text']); ?>
        </p>
       </section>

       <article class="app-vista-card app-vista-card--surface" style="padding: 16px; display: flex; gap: 12px; align-items: flex-start;">
        <span class="material-symbols-outlined" data-icon="info" style="font-size: 21px; color: #0a4e93;">info</span>
        <div style="display: grid; gap: 4px;">
         <p style="margin: 0; color: #0a4e93; font-size: 1rem; font-weight: 700;">
          <?php echo $this->e($data['info_title']); ?>
         </p>
         <p style="margin: 0; color: #4f5f75; font-size: 0.96rem; line-height: 1.45;">
          <?php echo $this->e($data['info_text']); ?>
         </p>
        </div>
       </article>

       <section style="display: grid; gap: 12px;">
        <h3 style="margin: 4px 0 0; color: #1f2f46; font-size: 1.06rem; font-weight: 700;">
         <?php echo $this->e($data['documents_title']); ?>
        </h3>

        <?php // Itera documentos requeridos; evitar pasar objetos grandes a la vista y escapar todas las propiedades.
        foreach ($data['documents'] as $document): ?>
        <article class="app-vista-card" style="padding: 16px; display: grid; gap: 14px;">
         <div style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="width: 42px; height: 42px; border-radius: 12px; background: #e9f2fb; color: #0a4e93; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;">
           <span class="material-symbols-outlined" data-icon="<?php echo $this->e($document['icon']); ?>" style="font-size: 22px;">
        <?php echo $this->e($document['icon']); ?>
           </span>
          </div>
          <div style="display: grid; gap: 4px; min-width: 0;">
           <h4 style="margin: 0; color: #1f2f46; font-size: 1.08rem; font-weight: 700; line-height: 1.25;">
        <?php echo $this->e($document['title']); ?>
           </h4>
           <p style="margin: 0; color: #5b6b80; font-size: 0.95rem; line-height: 1.45;">
        <?php echo $this->e($document['description']); ?>
           </p>
          </div>
         </div>

         <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
          <span class="app-vista-chip <?php echo ((string) $document['status_icon'] === 'check_circle') ? 'app-vista-chip--vigente' : ''; ?>" style="font-size: 0.84rem;">
           <span class="material-symbols-outlined <?php echo ((string) $document['status_icon'] === 'pending') ? '' : 'icono-relleno'; ?>" data-icon="<?php echo $this->e($document['status_icon']); ?>" style="font-size: 18px;">
        <?php echo $this->e($document['status_icon']); ?>
           </span>
           <?php echo $this->e($document['status']); ?>
          </span>
          <button class="app-vista-button app-vista-button--primary" style="width: auto; min-height: 44px; padding: 10px 16px; border-radius: 14px; font-size: 0.86rem;">
           Subir archivo
          </button>
         </div>
        </article>
        <?php endforeach; ?>
       </section>

       <section style="padding-bottom: 10px; display: grid; gap: 12px;">
        <button class="app-vista-button app-vista-button--primary">
         <?php echo $this->e($data['footer_button']); ?>
        </button>
        <p style="margin: 0; text-align: center; color: #6a778a; font-size: 0.84rem; line-height: 1.45;">
         <?php echo $this->e($data['footer_note']); ?>
        </p>
       </section>
      </main>
      <!-- BottomNavBar -->

    <?php
    $this->getFooter();
  }
}

(new SubidaDocumentacionVista())->mostrar();
