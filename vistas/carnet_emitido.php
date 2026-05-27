<?php

class CarnetEmitidoVista
{
    private string $baseURL = '/bromatologiaAPI/';

    private function getHeader(): void
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
        <body class="bg-surface text-on-surface min-h-screen pb-24 tema-ciudadano">
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

    private function obtenerDatosCarnet(): array
    {
        return [
            'numero' => $_GET['numero'] ?? '20-35849201-8',
            'titular' => $_GET['titular'] ?? 'Juan Perez',
            'fecha_emision' => $_GET['fecha_emision'] ?? '15/10/2023',
            'fecha_vencimiento' => $_GET['fecha_vencimiento'] ?? '15/10/2025',
        ];
    }

    public function mostrar(): void
    {
        $carnetEmitido = $this->obtenerDatosCarnet();

        $this->getHeader();
        ?>

        <main class="contenido-principal flex flex-col gap-8">
          <div class="flex flex-col gap-1">
            <h2 class="font-headline-lg text-headline-lg text-primary">
              Carnet Emitido
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
              Tu credencial oficial ya se encuentra disponible para su uso.
            </p>
          </div>

          <section class="panel-admin-actividad overflow-visible">
            <div class="panel-admin-actividad__encabezado app-vista-card--gradient" style="padding: 22px; border-top-left-radius: 24px; border-top-right-radius: 24px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin: -1px -1px 0;">
              <div class="flex flex-col gap-1">
                <span class="panel-admin-card__etiqueta" style="color: rgba(255,255,255,0.82);">
                  Credencial Municipal
                </span>
                <h3 class="panel-admin-card__numero" style="font-size: 30px; color: #ffffff; line-height: 1.1; max-width: 15ch;">
                  Carnet de Manipulador de Alimentos
                </h3>
              </div>
              <div class="flex items-start">
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 14px; margin-left: 16px;">
                  <span class="material-symbols-outlined" style="color: #ffffff; font-size: 28px;">
                    verified_user
                  </span>
                </div>
              </div>
            </div>

            <div class="bg-surface-container-lowest rounded-b-[20px] p-6 shadow-[0_8px_24px_rgba(2,36,57,0.06)]">
              <div class="flex justify-between items-center mb-4">
                <div class="inline-flex items-center gap-3">
                  <span class="w-3 h-3 rounded-full bg-green-600 inline-block">
                  </span>
                  <span class="font-label-md text-label-md text-green-800">
                    Vigente
                  </span>
                </div>
                <span class="font-label-md text-label-md text-outline">
                  N° <?php echo htmlspecialchars($carnetEmitido['numero'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </div>

              <div class="mb-4">
                <p class="font-label-md text-label-md text-outline">
                  Titular
                </p>
                <p class="font-headline-md text-headline-md text-on-surface font-bold">
                  <?php echo htmlspecialchars($carnetEmitido['titular'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
              </div>

              <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                  <p class="font-label-md text-label-md text-outline">
                    Fecha Emisión
                  </p>
                  <p class="font-body-lg text-body-lg text-on-surface">
                    <?php echo htmlspecialchars($carnetEmitido['fecha_emision'], ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                </div>
                <div>
                  <p class="font-label-md text-label-md text-outline">
                    Fecha Vencimiento
                  </p>
                  <p class="font-body-lg text-body-lg text-on-surface">
                    <?php echo htmlspecialchars($carnetEmitido['fecha_vencimiento'], ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                </div>
              </div>

              <div class="rounded-xl bg-surface-container-low p-6 flex justify-center items-center">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                  <div class="w-40 h-40 bg-primary-800 rounded-md flex items-center justify-center overflow-hidden">
                    <img alt="QR" class="w-32 h-32 object-contain rounded-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCh0E9vAaBK_cW1Kogpd3F_c1KipVMDAYZRBbQhE8bHEZ9O80oa5yEhCLVKxHHGw91gK15nTNhL8h5VhYEe_DJR5-yAM0v_phxl7U6J_MlbY2jTw9QJ9bq2KXZ0-0N5VNzGa9LnxYNGTRh6KlxBCB9lET4QFVi2G0WOF7YvFIzxaKox4SEv_R6OO6UKJA8OVkkHIO7VJ_hTKdWr7Vjmu3qD0ef8XKE6BoYq1aei32_IRITiP-uDqSH5IVzIWy0PJTT0OtNuji57w1o3"/>
                  </div>
                </div>
              </div>

              <p class="text-center text-on-surface-variant mt-4">
                Escanee el código para validar la autenticidad del carnet
              </p>

              <div class="mt-6 space-y-4">
                <button class="w-full boton-principal-gradiente py-4 rounded-lg flex items-center justify-center gap-3">
                  <span class="material-symbols-outlined">
                    download
                  </span>
                  Descargar carnet
                </button>
                <button class="w-full bg-surface-container-lowest border border-outline-variant py-4 rounded-lg flex items-center justify-center gap-3">
                  <span class="material-symbols-outlined">
                    visibility
                  </span>
                  Mostrar carnet
                </button>
              </div>
            </div>
          </section>
        </main>

        <?php
        $this->getFooter();
    }
}

(new CarnetEmitidoVista())->mostrar();
