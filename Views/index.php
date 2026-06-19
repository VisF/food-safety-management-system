<?php
/**
 * Vista: Inicio
 * Propósito: Página principal del usuario que muestra estado del trámite, documentos y exámenes.
 * Entradas/estructura de datos (ejemplo):
 *  - page_title: string
 *  - welcome_text: string
 *  - user_name: string
 *  - documents: array[{label:string, icon:string, route:string, state:int}]
 *  - exams: array[{month:string, day:string, title:string, time:string, place:string, available:int}]
 * Fuente de datos: `getDefaultData()` y opcional `GET['data']` (JSON — se decodifica con `json_decode` y mergea).
 * Notas técnicas:
 *  - Use `e()` o `htmlspecialchars` para escapar toda salida; `getRoute()` genera enlaces a `Router.php` con `rawurlencode`.
 *  - No confiar en estados enviados por cliente; validar la disponibilidad de exámenes y permisos en backend.
 */


class InicioVista
{
    private string $baseURL = '/ManipulacionDeAlimentos/';
    


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


    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function getDocumentCardClass(string $state): string
    {
        return match ($state) {

            'aprobado' =>
                'app-vista-card documento-home documento-home--aprobado',

            'rechazado' =>
                'app-vista-card documento-home documento-home--rechazado',

            default =>
                'app-vista-card documento-home documento-home--pendiente'
        };
    }

    private function getDocumentButtonClass(string $state): string
    {
        return match ($state) {

            'aprobado' =>
                'documento-home__texto documento-home__texto--aprobado',

            'rechazado' =>
                'documento-home__texto documento-home__texto--rechazado',

            default =>
                'documento-home__texto documento-home__texto--pendiente'
        };
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

        return $basePath . '/' . rawurlencode($route);
    }

    public function mostrar(array $inicioData): void
    {
        $this->getHeader($inicioData);
        ?>
        <?php if (($_GET['toast'] ?? '') === 'inscripcion_exitosa'): ?>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                mostrarToast(
                    'Inscripción realizada correctamente',
                    'success'
                );
            });
            console.log("Toast de inscripción exitosa mostrado");
            </script>
        <?php endif; ?>
        <?php if (!empty($_GET['toast_error'])): ?>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                mostrarToast(
                    <?= json_encode($_GET['toast_error']) ?>,
                    'error'
                );
            });
            </script>
         <?php endif; ?>

                    <main class="contenido-principal">
                       <section style="margin-bottom: 22px; padding-inline: 2px;">
                        <p style="margin: 0; color: #5b6b80; font-size: 0.84rem; font-weight: 600;">
                         <?php echo $this->e($inicioData['welcome_text']); ?>
                        </p>
                        <h2 style="margin: 4px 0 0; color: #0a4e93; font-size: clamp(1.5rem, 7vw, 1.85rem); font-weight: 800; line-height: 1.1;">
                                <?= $this->e(
                                    $inicioData['usuario']['nombre'] ?? 'Invitado'
                                ) ?>
                        </h2>
                     </section>

                     <article class="app-vista-card" style="padding: 22px; margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px;">
                         <div>
                            <p style="margin: 0; color: #5b6b80; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;">
                             <?php echo $this->e($inicioData['tramite']['label']); ?>
                            </p>
                            <h3 style="margin: 6px 0 0; color: #0a4e93; font-size: 1.12rem; font-weight: 800; line-height: 1.25;">
                             <?php echo $this->e($inicioData['tramite']['titulo']); ?>
                            </h3>
                         </div>
                         <span class="app-vista-chip app-vista-chip--vigente" style="font-size: 0.72rem;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #2d6e43; box-shadow: 0 0 0 4px rgba(45,110,67,0.12);"></span>
                            <?php echo $this->e($inicioData['tramite']['estado']); ?>
                         </span>
                        </div>

                        <p style="display: flex; align-items: center; gap: 8px; margin: 0 0 14px; color: #5b6b80; font-size: 0.9rem; font-weight: 500;">
                         <span class="material-symbols-outlined" data-icon="calendar_today" style="font-size: 17px;">calendar_today</span>
                         <?php echo $this->e($inicioData['tramite']['fecha_vencimiento']); ?>
                        </p>

                        <div style="height: 10px; border-radius: 999px; background: #e3ebf5; overflow: hidden;">
                            <div
                                style="
                                    width: <?= (int)($inicioData['tramite']['porcentaje'] ?? 0) ?>%;
                                    height: 100%;
                                    border-radius: 999px;
                                    background: linear-gradient(90deg, #1462b5, #0a4e93);
                                "
                            ></div>
                        </div>
                        <p style="margin: 10px 0 16px; color: #5b6b80; font-size: 0.84rem; font-weight: 600;">
                         <?php echo $this->e($inicioData['tramite']['progreso']); ?>
                        </p>
                        <?php
                        $documentacionCompleta =
                            ($inicioData['tramite']['porcentaje'] ?? 0) == 100;

                        $href = $documentacionCompleta
                            ? '#proximos-examenes'
                            : $this->getRoute('subida_documentacion');
                            ?>
                        <a
                            class="app-vista-button app-vista-button--primary"
                            href="<?= $href ?>"
                            role="button"
                        >
                            <span class="material-symbols-outlined">
                                task_alt
                            </span>

                            <?= $inicioData['tramite']['accion_principal']['texto'] ?>
                        </a>
                     </article>

                     <section style="margin-bottom: 18px;">
                        <h4 style="margin: 0 0 10px; color: #1f2f46; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                         Documentación Requerida
                        </h4>
                        <div class="grid grid-cols-2" style="gap: 12px;">
                            <?php if (!empty($inicioData['documentos_faltantes'])): ?>
                                <div
                                    style="
                                        margin-top:12px;
                                        padding:12px;
                                        border-radius:12px;
                                        background:#fff4e5;
                                        border:1px solid #ffd59a;
                                    "
                                >
                                    <strong>Documentos pendientes:</strong>
                                    <ul style="margin-top:8px; padding-left:20px;">
                                        <?php foreach (
                                            $inicioData['documentos_faltantes']
                                            as $faltante
                                        ): ?>
                                            <li>
                                                <?= $this->e($faltante) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                    <?php // Itera tarjetas de documentos; el array debe ser limitado y sanitizado por el backend.
                    foreach ($inicioData['documentos'] as $document): ?>
                        <a
                            class="<?php echo $this->getDocumentCardClass($document['state']); ?>"
                            href="<?php echo $this->getRoute((string) $document['route']); ?>"
                            role="button"
                        >
                            <div class="documento-home__icono">
                             <span class="material-symbols-outlined" data-icon="<?php echo $this->e($document['icon']); ?>"><?php echo $this->e($document['icon']); ?></span>
                            </div>
                            <div class="inicio-documento__contenido">

                                <span class="inicio-documento__titulo">
                                    <?php echo $this->e($document['label']); ?>
                                </span>

                                <span class="inicio-documento__descripcion">
                                    <?php echo $this->e($document['descripcion']); ?>
                                </span>

                            </div>
                        </a>
                    <?php endforeach; ?>
                        </div>
                     </section>

                    <section class="home-cursos">

                        <h4 class="home-cursos__titulo">
                            Cursos Disponibles
                        </h4>

                        <div class="home-cursos__grid">

                            <?php foreach ($inicioData['cursos'] as $curso): ?>

                            <article class="app-vista-card app-vista-card--surface home-curso-card">

                                <div class="home-curso-card__contenido">

                                    <h5 class="home-curso-card__nombre">
                                        <?= $this->e($curso['nombre']) ?>
                                    </h5>

                                    <p class="home-curso-card__descripcion">
                                        <?= $this->e($curso['descripcion']) ?>
                                    </p>
                                    <div class="home-curso-card__datos">

                                        <?php if (!empty($curso['fecha_inicio'])): ?>
                                        <p class="home-curso-card__dato">
                                            <?= date(
                                                'd/m/Y',
                                                strtotime($curso['fecha_inicio'])
                                            ) ?>
                                        </p>
                                        <?php endif; ?>

                                        <?php if (!empty($curso['hora_inicio'])): ?>
                                        <p class="home-curso-card__dato">
                                             <?= $this->e(
                                                $curso['hora_inicio']
                                            ) ?> hs
                                        </p>
                                        <?php endif; ?>

                                        <?php if (!empty($curso['ubicacion'])): ?>
                                        <p class="home-curso-card__dato">
                                            <?= $this->e(
                                                $curso['ubicacion']
                                            ) ?>
                                        </p>
                                        <?php endif; ?>

                                        <p class="home-curso-card__dato">

                                            👥

                                            <?= (int)$curso['cupos_disponibles'] ?>

                                            cupos disponibles

                                            de

                                            <?= (int)$curso['cupos_totales'] ?>

                                        </p>

                                    </div>
                                    <span class="app-vista-chip">

                                        <?php if (
                                            strtolower($curso['modalidad']) === 'presencial'
                                        ): ?>

                                            📍 Presencial

                                        <?php else: ?>

                                            💻 Virtual

                                        <?php endif; ?>

                                    </span>

                                </div>

                               <?php if (!$curso['inscripto']): ?>

                                    <form
                                        method="POST"
                                        action="<?= BASE_URL ?>/curso/inscribirse"
                                    >

                                        <input
                                            type="hidden"
                                            name="curso_id"
                                            value="<?= (int)$curso['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="app-vista-button app-vista-button--primary"
                                        >
                                            Inscribirme
                                        </button>

                                    </form>

                                    <?php else: ?>

                                    <button
                                        type="button"
                                        class="app-vista-button app-vista-button--secondary"
                                        disabled
                                    >
                                        Ya inscripto
                                    </button>

                                    <?php endif; ?>

                            </article>

                            <?php endforeach; ?>

                        </div>

                    </section>

                     <section id="proximos-examenes" style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #1f2f46; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                         Próximos Exámenes
                        </h4>
                        <div style="display: grid; gap: 12px;">
                    <?php // Itera exámenes próximos; validar cupos en servidor antes de mostrar acciones habilitadas.
                    foreach ($inicioData['examenes'] as $exam): ?>
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
                                    <a
                                        class="app-vista-button app-vista-button--primary"
                                        href="/manipulacionDeAlimentos/detalle_examen?id=<?php echo (int)$exam['id']; ?>"
                                        role="button"
                                        style="margin-top: 10px;"
                                    >
                                        Inscribirse
                                    </a>
                             </div>
                            </div>
                         </article>
                    <?php endforeach; ?>
                        </div>
                     </section>

                     <section style="padding-bottom: 6px;">
                        <a class="app-vista-button app-vista-button--secondary" href="<?php echo $this->getRoute((string) $inicioData['carnet']['ruta_descarga']); ?>" role="button">
                         <span class="material-symbols-outlined" data-icon="download">download</span>
                         <?php echo $this->e($inicioData['carnet']['etiqueta_descarga']); ?>
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


