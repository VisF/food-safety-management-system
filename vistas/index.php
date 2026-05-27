<?php

class InicioVista
{
    private string $baseURL = '/bromatologiaAPI/';

    private function getDefaultData(): array
    {
        return [
            'page_title' => 'App Ciudadana - Inicio',
            'welcome_text' => 'Bienvenido de nuevo,',
            'user_name' => 'Juan Perez',
            'tramite_label' => 'Estado del Trámite',
            'tramite_title' => 'Carnet de Manipulador',
            'tramite_status' => 'PENDIENTE',
            'tramite_deadline' => 'Próximo vencimiento: 15/12/2025',
            'tramite_progress' => 'Paso 2 de 3: Evaluación Técnica',
            'documents' => [
                [
                    'label' => 'Subir DNI',
                    'icon' => 'badge',
                    'route' => 'subida_documentacion',
                    'state' => 1,
                ],
                [
                    'label' => 'Foto Carnet',
                    'icon' => 'add_a_photo',
                    'route' => 'subida_documentacion',
                    'state' => 0,
                ],
            ],
            'exams' => [
                [
                    'month' => 'OCT',
                    'day' => '24',
                    'title' => 'CRESTA',
                    'time' => '09:00 AM',
                    'place' => 'Aula 3',
                    'available' => 1,
                    'route' => 'inscripcion_examen',
                ],
                [
                    'month' => 'NOV',
                    'day' => '08',
                    'title' => 'Polideportivo Municipal',
                    'time' => '02:00 PM',
                    'place' => 'Salón B',
                    'available' => 0,
                    'route' => 'inscripcion_examen',
                ],
            ],
            'download_label' => 'Descargar Carnet',
            'download_route' => 'carnet_emitido',
            'download_enabled' => 1,
        ];
    }

    private function getHeader(array $inicioData): void
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
           <?php echo htmlspecialchars($inicioData['page_title'], ENT_QUOTES, 'UTF-8'); ?>
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
         <body class="bg-background min-h-screen text-on-surface pb-24 tema-ciudadano">
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

    private function getDocumentCardClass(int $state): string
    {
        return $state === 1
            ? 'app-vista-card app-vista-card--surface flex flex-col items-center justify-center p-6 border border-[#b8e2c4] bg-[#ebf8ef] transition-all'
            : 'app-vista-card app-vista-card--surface flex flex-col items-center justify-center p-6 transition-all';
    }

    private function getDocumentButtonClass(int $state): string
    {
        return $state === 1
            ? 'text-sm font-semibold text-[#2d6e43]'
            : 'text-sm font-semibold text-[#1f2f46]';
    }

    private function getExamBadgeClass(int $available): string
    {
        return $available === 1
            ? 'app-vista-chip app-vista-chip--vigente'
            : 'app-vista-chip';
    }

    private function getExamBadgeText(int $available): string
    {
        return $available === 1 ? 'CUPOS DISPONIBLES' : 'SIN CUPOS';
    }

    private function getRoute(string $route): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if (preg_match('#/vistas$#', $basePath) === 1) {
            $basePath = (string) preg_replace('#/vistas$#', '', $basePath);
        }

        return $basePath . '/Router.php?r=' . rawurlencode($route);
    }

    public function mostrar(): void
    {
        $inicioData = array_replace_recursive($this->getDefaultData(), $this->getIncomingData());

        $this->getHeader($inicioData);
        ?>

                    <main class="contenido-principal">
                       <section style="margin-bottom: 22px; padding-inline: 2px;">
                        <p style="margin: 0; color: #5b6b80; font-size: 0.84rem; font-weight: 600;">
                         <?php echo $this->e($inicioData['welcome_text']); ?>
                        </p>
                        <h2 style="margin: 4px 0 0; color: #0a4e93; font-size: clamp(1.5rem, 7vw, 1.85rem); font-weight: 800; line-height: 1.1;">
                         Hola, <?php echo $this->e($inicioData['user_name']); ?>
                        </h2>
                     </section>

                     <article class="app-vista-card" style="padding: 22px; margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px;">
                         <div>
                            <p style="margin: 0; color: #5b6b80; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;">
                             <?php echo $this->e($inicioData['tramite_label']); ?>
                            </p>
                            <h3 style="margin: 6px 0 0; color: #0a4e93; font-size: 1.12rem; font-weight: 800; line-height: 1.25;">
                             <?php echo $this->e($inicioData['tramite_title']); ?>
                            </h3>
                         </div>
                         <span class="app-vista-chip app-vista-chip--vigente" style="font-size: 0.72rem;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #2d6e43; box-shadow: 0 0 0 4px rgba(45,110,67,0.12);"></span>
                            <?php echo $this->e($inicioData['tramite_status']); ?>
                         </span>
                        </div>

                        <p style="display: flex; align-items: center; gap: 8px; margin: 0 0 14px; color: #5b6b80; font-size: 0.9rem; font-weight: 500;">
                         <span class="material-symbols-outlined" data-icon="calendar_today" style="font-size: 17px;">calendar_today</span>
                         <?php echo $this->e($inicioData['tramite_deadline']); ?>
                        </p>

                        <div style="height: 10px; border-radius: 999px; background: #e3ebf5; overflow: hidden;">
                         <div style="width: 67%; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #1462b5, #0a4e93);"></div>
                        </div>
                        <p style="margin: 10px 0 16px; color: #5b6b80; font-size: 0.84rem; font-weight: 600;">
                         <?php echo $this->e($inicioData['tramite_progress']); ?>
                        </p>

                        <a class="app-vista-button app-vista-button--primary" href="<?php echo $this->getRoute('inscripcion_examen'); ?>" role="button">
                         <span class="material-symbols-outlined" data-icon="task_alt">task_alt</span>
                         Inscribirse a examen
                        </a>
                     </article>

                     <section style="margin-bottom: 18px;">
                        <h4 style="margin: 0 0 10px; color: #1f2f46; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                         Documentación Requerida
                        </h4>
                        <div class="grid grid-cols-2" style="gap: 12px;">
                    <?php foreach ($inicioData['documents'] as $document): ?>
                        <a class="<?php echo $this->getDocumentCardClass((int) $document['state']); ?>" href="<?php echo $this->getRoute((string) $document['route']); ?>" role="button" style="text-decoration: none;">
                            <div style="width: 48px; height: 48px; border-radius: 16px; background: #e9f2fb; color: #0a4e93; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                             <span class="material-symbols-outlined" data-icon="<?php echo $this->e($document['icon']); ?>"><?php echo $this->e($document['icon']); ?></span>
                            </div>
                            <span class="<?php echo $this->getDocumentButtonClass((int) $document['state']); ?>">
                             <?php echo $this->e($document['label']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                        </div>
                     </section>

                     <section style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #1f2f46; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                         Próximos Exámenes
                        </h4>
                        <div style="display: grid; gap: 12px;">
                    <?php foreach ($inicioData['exams'] as $exam): ?>
                         <article class="app-vista-card" style="padding: 0;">
                            <div style="display: grid; grid-template-columns: 82px 1fr; align-items: stretch;">
                             <div style="background: linear-gradient(160deg, #1462b5, #0a4e93); color: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                <span style="font-size: 0.7rem; font-weight: 700; opacity: 0.9;">
                                 <?php echo $this->e($exam['month']); ?>
                                </span>
                                <span style="font-size: 1.55rem; font-weight: 800; line-height: 1;">
                                 <?php echo $this->e($exam['day']); ?>
                                </span>
                             </div>
                             <div style="padding: 14px;">
                                <h5 style="margin: 0 0 4px; color: #1f2f46; font-size: 0.98rem; font-weight: 700; line-height: 1.25;">
                                 <?php echo $this->e($exam['title']); ?>
                                </h5>
                                <p style="margin: 0 0 10px; color: #5b6b80; font-size: 0.84rem; font-weight: 500;">
                                 <?php echo $this->e($exam['time']); ?> • <?php echo $this->e($exam['place']); ?>
                                </p>
                                <span class="<?php echo $this->getExamBadgeClass((int) $exam['available']); ?>" style="margin-bottom: 10px;">
                                 <?php if ((int) $exam['available'] === 1): ?>
                                 <span style="width: 7px; height: 7px; border-radius: 50%; background: #2d6e43;"></span>
                                 <?php endif; ?>
                                 <?php echo $this->e($this->getExamBadgeText((int) $exam['available'])); ?>
                                </span>
                                <a class="app-vista-button app-vista-button--primary" href="<?php echo $this->getRoute((string) $exam['route']); ?>" role="button" style="margin-top: 10px;">
                                 Inscribirse
                                </a>
                             </div>
                            </div>
                         </article>
                    <?php endforeach; ?>
                        </div>
                     </section>

                     <section style="padding-bottom: 6px;">
                        <a class="app-vista-button app-vista-button--secondary" href="<?php echo $this->getRoute((string) $inicioData['download_route']); ?>" role="button">
                         <span class="material-symbols-outlined" data-icon="download">download</span>
                         <?php echo $this->e($inicioData['download_label']); ?>
                        </a>
                        <p style="margin: 10px 0 0; text-align: center; color: #7a8798; font-size: 0.82rem; font-style: italic;">
                         Disponible una vez aprobado el examen.
                        </p>
                     </section>
                    </main>
          <!-- Bottom Navigation -->

        <?php
        $this->getFooter();
    }
}

(new InicioVista())->mostrar();
