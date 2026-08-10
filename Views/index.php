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
          <link href="<?= $assetBase ?>/css/app.css" rel="stylesheet"/>
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

                    <main class="contenido-principal">

    <section class="home-bienvenida">

        <p class="home-bienvenida__texto">
            <?= $this->e($inicioData['welcome_text']); ?>
        </p>

        <h2 class="home-bienvenida__nombre">
            <?= $this->e(
                $inicioData['usuario']['nombre'] ?? 'Invitado'
            ) ?>
        </h2>

    </section>
    <?php if (!empty($inicioData['carnet_vigente'])): ?>

    <section class="home-carnet-vigente">

        <article class="app-vista-card">

            <div class="home-carnet-vigente__header">

                <h3>

                    Carnet vigente

                </h3>

                <span class="app-vista-chip app-vista-chip--vigente">

                    Vigente

                </span>

            </div>

            <p>

                <strong>N°</strong>

                <?= $this->e(
                    $inicioData['carnet_vigente']['numero_carnet']
                ); ?>

            </p>

            <p>

                <strong>Emitido:</strong>

                <?= date(
                    'd/m/Y',
                    strtotime(
                        $inicioData['carnet_vigente']['fecha_emision']
                    )
                ); ?>

            </p>

            <p>

                <strong>Vence:</strong>

                <?= date(
                    'd/m/Y',
                    strtotime(
                        $inicioData['carnet_vigente']['fecha_vencimiento']
                    )
                ); ?>

            </p>

            <p>

                <?= $this->e(
                    $inicioData['carnet_vigente']['mensaje']
                ); ?>

            </p>
            <?php if (
                $inicioData['carnet_vigente']['estado']
                === 'vigente'
            ): ?>

                <p>

                    <strong>

                        Días restantes:

                    </strong>

                    <?= (int)$inicioData['carnet_vigente']['dias_restantes']; ?>

                </p>

            <?php elseif (
                $inicioData['carnet_vigente']['estado']
                === 'proximo_vencimiento'
            ): ?>

                <p>

                    <strong>

                        Vence en:

                    </strong>

                    <?= (int)$inicioData['carnet_vigente']['dias_restantes']; ?>

                    días

                </p>

            <?php else: ?>

                <p>

                    <strong>

                        Venció hace:

                    </strong>

                    <?= abs(
                        (int)$inicioData['carnet_vigente']['dias_restantes']
                    ); ?>

                    días

                </p>

            <?php endif; ?>

        </article>

    </section>

    <?php endif; ?>
    <article class="app-vista-card home-tramite">

        <div class="home-tramite__header">

            <div>

                <p class="home-tramite__label">
                    <?= $this->e($inicioData['tramite']['label']); ?>
                </p>

                <h3 class="home-tramite__titulo">
                    <?= $this->e($inicioData['tramite']['titulo']); ?>
                </h3>

            </div>

            <span class="app-vista-chip app-vista-chip--vigente home-tramite__estado">

                <span class="home-tramite__estado-indicador"></span>

                <?= $this->e($inicioData['tramite']['estado']); ?>

            </span>

        </div>

        <p class="home-tramite__fecha">

            <span class="material-symbols-outlined">
                calendar_today
            </span>

            <?= $this->e(
                $inicioData['tramite']['fecha_vencimiento']
            ); ?>

        </p>

        <div class="home-tramite__barra">

            <div
                class="home-tramite__progreso"
                style="width: <?= (int)($inicioData['tramite']['porcentaje'] ?? 0) ?>%;"
            ></div>

        </div>

        <p class="home-tramite__porcentaje">

            <?= $this->e(
                $inicioData['tramite']['progreso']
            ); ?>

        </p>

        <?php if (
                $inicioData['tramite']['estado']
                === 'CARNET_EMITIDO'
            ): ?>

                <p class="home-tramite__accion">

                    <strong>

                        Estado:

                    </strong>

                    Trámite finalizado.

                </p>

            <?php else: ?>

                <p class="home-tramite__accion">

                    <strong>

                        Siguiente paso:

                    </strong>

                    <?= $this->e(
                        $inicioData['tramite']['accion_principal']['texto']
                    ); ?>

                </p>

            <?php endif; ?>

        <?php

            $documentacionCompleta =
                ($inicioData['tramite']['porcentaje'] ?? 0) == 100;

            $href = $documentacionCompleta
                ? '#proximos-examenes'
                : $this->getRoute('subida_documentacion');

            ?>

            <?php if (
                    $inicioData['tramite']['estado']
                    === 'CARNET_EMITIDO'
                ): ?>

                    <a
                        class="app-vista-button app-vista-button--primary home-tramite__boton"
                        href="/manipulacionDeAlimentos/carnet"
                        role="button"
                    >

                        <span class="material-symbols-outlined">

                            badge

                        </span>

                        Descargar carnet

                    </a>

                <?php else: ?>

                    <a
                        class="app-vista-button app-vista-button--primary home-tramite__boton"
                        href="<?= $href ?>"
                        role="button"
                    >

                        <span class="material-symbols-outlined">

                            task_alt

                        </span>

                        <?= $inicioData['tramite']['accion_principal']['texto'] ?>

                    </a>

                <?php endif; ?>

        </article>
                        <section class="home-documentos">

        <h4 class="home-documentos__titulo">

            Documentación Requerida

        </h4>

        <div class="home-documentos__grid">

            <?php if (!empty($inicioData['documentos_faltantes'])): ?>

                <div class="home-documentos__alerta">

                    <strong>

                        Documentos pendientes:

                    </strong>

                    <ul class="home-documentos__lista">

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

            <?php foreach ($inicioData['documentos'] as $document): ?>

                <a
                    class="<?= $this->getDocumentCardClass($document['state']); ?>"
                    href="<?= $this->getRoute((string)$document['route']); ?>"
                    role="button"
                >

                    <div class="documento-home__icono">

                        <span
                            class="material-symbols-outlined"
                            data-icon="<?= $this->e($document['icon']); ?>"
                        >
                            <?= $this->e($document['icon']); ?>
                        </span>

                    </div>

                    <div class="home-documento__contenido">

                        <span class="home-documento__titulo">

                            <?= $this->e($document['label']); ?>

                        </span>

                        <span class="home-documento__descripcion">

                            <?= $this->e($document['descripcion']); ?>

                        </span>

                        <span
                            class="<?= $this->getDocumentButtonClass(
                                $document['state']
                            ); ?>"
                        >

                            <?= ucfirst(
                                $this->e($document['state'])
                            ); ?>

                        </span>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    </section>
                    <section class="home-cursos">

    <div class="home-cursos__header">

        <h4 class="home-cursos__titulo">

            Cursos Disponibles

        </h4>

    </div>

    <div class="home-cursos__grid">

            <?php foreach ($inicioData['cursos'] as $curso): ?>

                <article class="app-vista-card home-curso-card">

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

                                    <span class="material-symbols-outlined">

                                        calendar_today

                                    </span>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($curso['fecha_inicio'])
                                    ) ?>

                                </p>

                            <?php endif; ?>

                            <?php if (!empty($curso['hora_inicio'])): ?>

                                <p class="home-curso-card__dato">

                                    <span class="material-symbols-outlined">

                                        schedule

                                    </span>

                                    <?= $this->e($curso['hora_inicio']) ?> hs

                                </p>

                            <?php endif; ?>

                            <?php if (!empty($curso['ubicacion'])): ?>

                                <p class="home-curso-card__dato">

                                    <span class="material-symbols-outlined">

                                        location_on

                                    </span>

                                    <?= $this->e($curso['ubicacion']) ?>

                                </p>

                            <?php endif; ?>

                            <p class="home-curso-card__dato">

                                <span class="material-symbols-outlined">

                                    groups

                                </span>

                                <?= (int)$curso['cupos_disponibles'] ?>

                                /

                                <?= (int)$curso['cupos_totales'] ?>

                                cupos

                            </p>

                        </div>

                        <div class="home-curso-card__footer">

                            <span class="app-chip app-chip--info">

                                <?php if (
                                    strtolower($curso['modalidad']) === 'presencial'
                                ): ?>

                                    📍 Presencial

                                <?php else: ?>

                                    💻 Virtual

                                <?php endif; ?>

                            </span>

                            <?php if (
                                !$curso['inscripto']
                                && $curso['puede_inscribirse']
                            ): ?>

                                <form
                                    method="POST"
                                    action="<?= BASE_URL ?>/curso/inscribirse"
                                    class="home-curso-card__form"
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

                            <?php elseif ($curso['inscripto']): ?>

                                <button
                                    class="app-vista-button app-vista-button--secondary"
                                    disabled
                                >

                                    Ya inscripto

                                </button>

                            <?php else: ?>

                                <button
                                    class="app-vista-button app-vista-button--secondary"
                                    disabled
                                >

                                    Ya posee un carnet vigente

                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

<?php if ($inicioData['proximo_examen'] !== null): ?>

    <section class="home-proximo-examen">

        <h4 class="home-examenes__titulo">

            Mi examen

        </h4>

        <article class="app-vista-card home-examen-card">

            <div class="home-examen-card__contenido">

                <h5 class="home-examen-card__titulo">

                    Examen de Manipulación de Alimentos

                </h5>

                <p class="home-examen-card__detalle">

                    <span class="material-symbols-outlined">
                        calendar_today
                    </span>

                    <?= date(
                        'd/m/Y',
                        strtotime($inicioData['proximo_examen']['fecha'])
                    ); ?>

                </p>

                <p class="home-examen-card__detalle">

                    <span class="material-symbols-outlined">
                        schedule
                    </span>

                    <?= substr(
                        $inicioData['proximo_examen']['hora'],
                        0,
                        5
                    ); ?>

                </p>

                <p class="home-examen-card__detalle">

                    <span class="material-symbols-outlined">
                        location_on
                    </span>

                    <?= $this->e(
                        $inicioData['proximo_examen']['ubicacion']
                    ); ?>

                    <?php if (!empty($inicioData['proximo_examen']['aula'])): ?>

                        - <?= $this->e(
                            $inicioData['proximo_examen']['aula']
                        ); ?>

                    <?php endif; ?>

                </p>

            </div>

        </article>

    </section>

    <?php endif; ?>

<?php if (
    $inicioData['proximo_examen'] === null
    && ($inicioData['mostrar_examenes'] ?? true)
): ?>

    <section id="proximos-examenes" class="home-examenes">

    <h4 class="home-examenes__titulo">

        Próximos Exámenes

    </h4>

    <div class="home-examenes__grid">

            <?php foreach ($inicioData['examenes'] as $exam): ?>

                <article class="app-vista-card home-examen-card">

                    <div class="home-examen-card__fecha">

                        <span class="home-examen-card__mes">

                            <?= $this->e($exam['month']); ?>

                        </span>

                        <span class="home-examen-card__dia">

                            <?= $this->e($exam['day']); ?>

                        </span>

                    </div>

                    <div class="home-examen-card__contenido">

                        <h5 class="home-examen-card__titulo">

                            <?= $this->e($exam['title']); ?>

                        </h5>

                        <p class="home-examen-card__detalle">

                            <span class="material-symbols-outlined">

                                schedule

                            </span>

                            <?= $this->e($exam['time']); ?>

                        </p>

                        <p class="home-examen-card__detalle">

                            <span class="material-symbols-outlined">

                                location_on

                            </span>

                            <?= $this->e($exam['place']); ?>

                        </p>

                        <div class="home-examen-card__footer">

                            <span
                                class="<?= $this->getExamBadgeClass(
                                    (int)$exam['available']
                                ); ?>"
                            >

                                <?php if ((int)$exam['available'] === 1): ?>

                                    <span
                                        class="home-examen-card__indicador"
                                    ></span>

                                <?php endif; ?>

                                <?= $this->e(
                                    $this->getExamBadgeText(
                                        (int)$exam['available']
                                    )
                                ); ?>

                            </span>

                            <?php if ($exam['puede_inscribirse']): ?>

                                <a
                                    href="/manipulacionDeAlimentos/detalle_examen?id=<?= (int)$exam['id']; ?>"
                                    class="app-vista-button app-vista-button--primary"
                                >

                                    Inscribirse

                                </a>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="app-vista-button app-vista-button--secondary"
                                    disabled
                                >

                                    Ya posee un carnet vigente

                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>
    <?php endif; ?>


    </main>

    <?php

        $this->getFooter();

    }

}