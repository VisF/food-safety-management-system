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
  private string $baseURL = '/ManipulacionDeAlimentos/';

  private function getDefaultData(): array
  {
    return [
      'page_title' => 'Subida de documentación - App Ciudadana',
      'hero_title' => 'Subida de documentación',
      'hero_text' => 'Complete los campos requeridos para avanzar con su trámite municipal.',
      'info_title' => 'Formatos aceptados',
      'info_text' => 'Solo se permiten archivos en formato JPG, PNG y PDF. Peso máximo: 5MB.',
      'documents_title' => 'Documentación requerida',
      'documents' => [],
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

  public function mostrar(array $documentos = []): void
{
    $data = $this->getDefaultData();

    $tiposRequeridos = [
        'dni' => [
            'icon' => 'badge',
            'title' => 'DNI frente y dorso',
            'description' => 'Ambos lados en una misma imagen o PDF.'
        ],

        'foto_carnet' => [
            'icon' => 'account_circle',
            'title' => 'Foto carnet',
            'description' => 'Fondo blanco, frente despejado.'
        ],

        'moodle' => [
            'icon' => 'school',
            'title' => 'Certificado Moodle',
            'description' => 'Constancia de aprobación del curso.'
        ]
    ];

    $data['documents'] = [];

    foreach ($tiposRequeridos as $tipo => $config) {

        $documentoEncontrado = null;

        foreach ($documentos as $doc) {

            if (
                strtolower(
                    $doc->getTipoDocumento()
                ) === $tipo
            ) {
                $documentoEncontrado = $doc;
                break;
            }
        }

        $estado = $documentoEncontrado
            ? $documentoEncontrado->getEstado()
            : 'pendiente';

        $data['documents'][] = [
            'tipo' => $tipo,
            'icon' => $config['icon'],
            'title' => $config['title'],
            'description' => $config['description'],

            'status' => match ($estado) {
                'aprobado' => 'Aprobado',
                'rechazado' => 'Rechazado',
                default => 'Pendiente'
            },

            'status_icon' => match ($estado) {
                'aprobado' => 'check_circle',
                'rechazado' => 'cancel',
                default => 'pending'
            },

            'status_class' => match ($estado) {
                'aprobado' => 'bg-green-100 text-green-700',
                'rechazado' => 'bg-red-100 text-red-700',
                default => 'bg-amber-100 text-amber-700'
            },

            'estado' => $estado,
            'documento' => $documentoEncontrado
        ];
    }

    $this->getHeader($data);
    ?>


      <main class="contenido-principal contenido-principal--estrecho subida-documentacion">
       <section class="subida-documentacion__hero">
        <h2 class="app-vista-section-title">
         <?php echo $this->e($data['hero_title']); ?>
        </h2>
        <p class="app-vista-section-subtitle subida-documentacion__hero-texto">
         <?php echo $this->e($data['hero_text']); ?>
        </p>
       </section>

       <article class="app-vista-card app-vista-card--surface subida-documentacion__info">

            <span
                class="material-symbols-outlined subida-documentacion__info-icono"
                data-icon="info"
            >
                info
            </span>

            <div class="subida-documentacion__info-contenido">

                <p class="subida-documentacion__info-titulo">
                    <?php echo $this->e($data['info_title']); ?>
                </p>

                <p class="subida-documentacion__info-texto">
                    <?php echo $this->e($data['info_text']); ?>
                </p>

            </div>

        </article>

       <section class="subida-documentacion__documentos">
        <h3 class="subida-documentacion__documentos-titulo">
         <?php echo $this->e($data['documents_title']); ?>
        </h3>

        <?php // Itera documentos requeridos; evitar pasar objetos grandes a la vista y escapar todas las propiedades.
        foreach ($data['documents'] as $document): ?>
        <article class="app-vista-card documento-card">
         <div class="documento-card__header">
          <div class="documento-card__icono">
           <span class="material-symbols-outlined" data-icon="<?php echo $this->e($document['icon']); ?>" style="font-size: 22px;">
        <?php echo $this->e($document['icon']); ?>
           </span>
          </div>
          <div class="documento-card__contenido">
            <h4 class="documento-card__titulo">
        <?php echo $this->e($document['title']); ?>
           </h4>
           <p class="documento-card__descripcion">
        <?php echo $this->e($document['description']); ?>
           </p>
           <?php if ($document['documento']): ?>
            <p class="documento-card__archivo">
                Archivo:
                <?php echo $this->e(
                    $document['documento']->getNombreOriginal()
                ); ?>
            </p>
            <?php endif; ?>
            <?php
            $observaciones =
                $document['documento']
                    ? $document['documento']->getObservaciones()
                    : null;
            ?>

            <?php if (!empty($observaciones)): ?>

            <p class="documento-card__observacion" >
                <strong>Observación:</strong>
                <?= $this->e($observaciones) ?>
            </p>

            <?php endif; ?>
          </div>
         </div>

         <div class="documento-card__footer">

          <span class="app-vista-chip 
          <?php echo ((string) $document['status_icon'] === 'check_circle') ? 'app-vista-chip--vigente' : ''; ?>"
           style="font-size: 0.84rem;">

           <span class="material-symbols-outlined 
           <?php echo ((string) $document['status_icon'] === 'pending') ? '' : 'icono-relleno'; ?>"
            data-icon="
            <?php echo $this->e($document['status_icon']); ?>" 
            style="font-size: 18px;">

        <?php echo $this->e($document['status_icon']); ?>
           </span>
           <?php echo $this->e($document['status']); ?>
          </span>
            <?php if ($document['estado'] !== 'aprobado'): ?>

          <form
              method="POST"
              action="<?= BASE_URL ?>/documentos/subir"
              enctype="multipart/form-data"
          >

              <input
                  type="hidden"
                  name="tipo_documento"
                  value="<?= $this->e($document['tipo']) ?>"
              >

              <input
                  type="file"
                  name="archivo"
                  id="archivo_<?= $this->e($document['tipo']) ?>"
                  style="display:none;"
                  accept=".pdf,.jpg,.jpeg,.png"
              >

              <button type="button" class="app-vista-button app-vista-button--primary documento-card__boton"
                      onclick="
                      document
                          .getElementById(
                              'archivo_<?= $this->e($document['tipo']) ?>'
                          )
                          .click();
                  "
              >
                  Subir archivo
              </button>

          </form>

          <?php else: ?>

          <button
              type="button"
              class="app-vista-button app-vista-button--secondary documento-card__boton"
              disabled
          >
              Documento aprobado
          </button>

          <?php endif; ?>
         </div>
        </article>
        <?php endforeach; ?>
       </section>

       <section class="subida-documentacion__footer">
        <button class="app-vista-button app-vista-button--primary">
         <?php echo $this->e($data['footer_button']); ?>
        </button>
        <p class="subida-documentacion__footer-nota">
         <?php echo $this->e($data['footer_note']); ?>
        </p>
       </section>
      </main>
      <!-- BottomNavBar -->
<script>
document.querySelectorAll('input[type="file"]').forEach(input => {

    input.addEventListener('change', function () {

        if (this.files.length > 0) {
            this.form.submit();
        }

    });

});
</script>
    <?php
    $this->getFooter();
  }
}


