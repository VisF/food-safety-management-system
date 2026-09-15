<?php

declare(strict_types=1);

/**
 * Vista: Inicio ciudadano.
 *
 * Propósito:
 * Mostrar el dashboard del ciudadano:
 * - Bienvenida.
 * - Estado del trámite.
 * - Carnet vigente.
 * - Documentación requerida.
 * - Cursos disponibles.
 * - Examen asignado.
 * - Próximos exámenes.
 *
 * Los datos son preparados por HomeControlador.
 */

require_once __DIR__ . '/BaseVista.php';

class InicioVista extends BaseVista
{
    /**
     * Devuelve la clase visual correspondiente al estado
     * de un documento.
     */
    private function getDocumentCardClass(string $state): string
    {
        return match (strtolower($state)) {
            'aprobado' =>
                'app-vista-card documento-home documento-home--aprobado',

            'rechazado' =>
                'app-vista-card documento-home documento-home--rechazado',

            default =>
                'app-vista-card documento-home documento-home--pendiente',
        };
    }

    /**
     * Devuelve la clase visual correspondiente al estado
     * mostrado dentro de una tarjeta de documento.
     */
    private function getDocumentButtonClass(string $state): string
    {
        return match (strtolower($state)) {
            'aprobado' =>
                'documento-home__texto documento-home__texto--aprobado',

            'rechazado' =>
                'documento-home__texto documento-home__texto--rechazado',

            default =>
                'documento-home__texto documento-home__texto--pendiente',
        };
    }

    /**
     * Devuelve la clase visual del estado de disponibilidad
     * de un examen.
     */
    private function getExamBadgeClass(int $available): string
    {
        return $available === 1
            ? 'app-vista-chip app-vista-chip--vigente'
            : 'app-vista-chip';
    }

    /**
     * Devuelve el texto del estado de disponibilidad
     * de un examen.
     */
    private function getExamBadgeText(int $available): string
    {
        return $available === 1
            ? 'CUPOS DISPONIBLES'
            : 'SIN CUPOS';
    }

    /**
     * Renderiza la vista.
     */
    public function mostrar(array $inicioData = []): void
    {
        $pageTitle =
            (string)($inicioData['page_title'] ?? 'App Ciudadana - Inicio');

        $welcomeText =
            (string)($inicioData['welcome_text'] ?? 'Bienvenido de nuevo,');

        $usuario =
            $inicioData['usuario'] ?? [];

        $tramite =
            $inicioData['tramite'] ?? [];

        $documentos =
            $inicioData['documentos'] ?? [];

        $documentosFaltantes =
            $inicioData['documentos_faltantes'] ?? [];

        $cursos =
            $inicioData['cursos'] ?? [];

        $examenes =
            $inicioData['examenes'] ?? [];

        $proximoExamen =
            $inicioData['proximo_examen'] ?? null;

        $carnetVigente =
            $inicioData['carnet_vigente'] ?? null;

        $mostrarExamenes =
            $inicioData['mostrar_examenes'] ?? true;

        $porcentajeTramite =
            (int)($tramite['porcentaje'] ?? 0);

        $porcentajeTramite =
            max(0, min(100, $porcentajeTramite));

        $documentacionCompleta =
            $porcentajeTramite >= 100;

        $nombreUsuario =
            (string)($usuario['nombre'] ?? 'Invitado');

        $accionPrincipal =
            $tramite['accion_principal'] ?? [];

        $textoAccion =
            (string)($accionPrincipal['texto'] ?? '');

        ?>

<!DOCTYPE html>

<html
    class="light"
    lang="es"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $this->e($pageTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= $this->e(
            $this->baseURL . 'css/app.css'
        ) ?>"
    >

</head>

<body
    class="bg-background min-h-screen text-on-surface pb-24 tema-ciudadano"
>

<?php

$page_title = $pageTitle;

include __DIR__ . '/header.php';

?>


<main class="contenido-principal">

    <!-- =====================================================
         BIENVENIDA
         ===================================================== -->

    <section class="home-bienvenida">

        <p class="home-bienvenida__texto">
            <?= $this->e($welcomeText) ?>
        </p>

        <h2 class="home-bienvenida__nombre">
            <?= $this->e($nombreUsuario) ?>
        </h2>

    </section>


    <!-- =====================================================
         CARNET VIGENTE
         ===================================================== -->

    <?php if (!empty($carnetVigente)): ?>

        <section id="carnet-vigente" class="home-carnet-vigente">

            <article class="app-vista-card">

                <div class="home-carnet-vigente__header">

                    <h3>
                        Carnet vigente
                    </h3>

                    <span
                        class="app-vista-chip app-vista-chip--vigente"
                    >
                        Vigente
                    </span>

                </div>

                <p>
                    <strong>N°</strong>

                    <?= $this->e(
                        $carnetVigente['numero_carnet'] ?? ''
                    ) ?>
                </p>

                <?php if (!empty($carnetVigente['fecha_emision'])): ?>

                    <p>

                        <strong>Emitido:</strong>

                        <?= $this->e(
                            date(
                                'd/m/Y',
                                strtotime(
                                    $carnetVigente['fecha_emision']
                                )
                            )
                        ) ?>

                    </p>

                <?php endif; ?>

                <?php if (!empty($carnetVigente['fecha_vencimiento'])): ?>

                    <p>

                        <strong>Vence:</strong>

                        <?= $this->e(
                            date(
                                'd/m/Y',
                                strtotime(
                                    $carnetVigente['fecha_vencimiento']
                                )
                            )
                        ) ?>

                    </p>

                <?php endif; ?>

                <?php if (!empty($carnetVigente['mensaje'])): ?>

                    <p>

                        <?= $this->e(
                            $carnetVigente['mensaje']
                        ) ?>

                    </p>

                <?php endif; ?>

                <?php
                $estadoCarnet =
                    (string)($carnetVigente['estado'] ?? '');

                $diasRestantes =
                    (int)($carnetVigente['dias_restantes'] ?? 0);
                ?>

                <?php if ($estadoCarnet === 'vigente'): ?>

                    <p>

                        <strong>
                            Días restantes:
                        </strong>

                        <?= $diasRestantes ?>

                    </p>

                <?php elseif ($estadoCarnet === 'proximo_vencimiento'): ?>

                    <p>

                        <strong>
                            Vence en:
                        </strong>

                        <?= $diasRestantes ?>

                        días

                    </p>

                <?php else: ?>

                    <p>

                        <strong>
                            Venció hace:
                        </strong>

                        <?= abs($diasRestantes) ?>

                        días

                    </p>

                <?php endif; ?>

            </article>

        </section>

    <?php endif; ?>


    <!-- =====================================================
         ESTADO DEL TRÁMITE
         ===================================================== -->

    <article class="app-vista-card home-tramite">

        <div class="home-tramite__header">

            <div>

                <p class="home-tramite__label">
                    <?= $this->e(
                        $tramite['label'] ?? 'Estado del Trámite'
                    ) ?>
                </p>

                <h3 class="home-tramite__titulo">
                    <?= $this->e(
                        $tramite['titulo'] ?? ''
                    ) ?>
                </h3>

            </div>

            <span
                class="app-vista-chip app-vista-chip--vigente home-tramite__estado"
            >

                <span
                    class="home-tramite__estado-indicador"
                ></span>

                <?= $this->e(
                    $tramite['estado'] ?? 'Sin inscripción'
                ) ?>

            </span>

        </div>


        <?php if (!empty($tramite['fecha_vencimiento'])): ?>

            <p class="home-tramite__fecha">

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    calendar_today
                </span>

                <?= $this->e(
                    $tramite['fecha_vencimiento']
                ) ?>

            </p>

        <?php endif; ?>


        <div
            class="home-tramite__barra"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="<?= $porcentajeTramite ?>"
            aria-label="Progreso del trámite"
        >

            <progress
                class="home-tramite__progreso"
                max="100"
                value="<?= $porcentajeTramite ?>"
            >
                <?= $porcentajeTramite ?>%
            </progress>

        </div>


        <p class="home-tramite__porcentaje">
            <?= $this->e(
                $tramite['progreso'] ?? ($porcentajeTramite . '%')
            ) ?>
        </p>


        <?php if (!empty($carnetVigente)): ?>

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

                <?= $this->e($textoAccion) ?>

            </p>

        <?php endif; ?>


        <?php if (!empty($carnetVigente)): ?>

            <a
                class="app-vista-button app-vista-button--primary home-tramite__boton"
                href="<?= $this->e(
                    $this->getRoute(
                        'descargar_carnet_ciudadano'
                    )
                ) ?>"
            >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    badge
                </span>

                Descargar carnet

            </a>

        <?php else: ?>

            <?php
            $hrefAccion =
                $documentacionCompleta
                    ? '#proximos-examenes'
                    : $this->getRoute('documentacion');
            ?>

            <a
                class="app-vista-button app-vista-button--primary home-tramite__boton"
                href="<?= $this->e($hrefAccion) ?>"
            >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    task_alt
                </span>

                <?= $this->e($textoAccion) ?>

            </a>

        <?php endif; ?>

    </article>


    <!-- =====================================================
         DOCUMENTACIÓN
         ===================================================== -->

    <section class="home-documentos">

        <h4 class="home-documentos__titulo">
            Documentación Requerida
        </h4>


        <?php if (!empty($documentosFaltantes)): ?>

            <div class="home-documentos__alerta">

                <strong>
                    Documentos pendientes:
                </strong>

                <ul class="home-documentos__lista">

                    <?php foreach ($documentosFaltantes as $faltante): ?>

                        <li>
                            <?= $this->e($faltante) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <div class="home-documentos__grid">

            <?php foreach ($documentos as $documento): ?>

                <?php
                $estadoDocumento =
                    (string)($documento['state'] ?? 'pendiente');

                $rutaDocumento =
                    (string)($documento['route'] ?? 'documentacion');
                ?>

                <a
                    class="<?= $this->e(
                        $this->getDocumentCardClass(
                            $estadoDocumento
                        )
                    ) ?>"
                    href="<?= $this->e(
                        $this->getRoute(
                            $rutaDocumento
                        )
                    ) ?>"
                >

                    <div class="documento-home__icono">

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            <?= $this->e(
                                $documento['icon'] ?? 'description'
                            ) ?>
                        </span>

                    </div>


                    <div class="home-documento__contenido">

                        <span class="home-documento__titulo">

                            <?= $this->e(
                                $documento['label'] ?? ''
                            ) ?>

                        </span>


                        <span class="home-documento__descripcion">

                            <?= $this->e(
                                $documento['descripcion'] ?? ''
                            ) ?>

                        </span>


                        <span
                            class="<?= $this->e(
                                $this->getDocumentButtonClass(
                                    $estadoDocumento
                                )
                            ) ?>"
                        >

                            <?= $this->e(
                                ucfirst($estadoDocumento)
                            ) ?>

                        </span>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- =====================================================
         CURSOS DISPONIBLES
         ===================================================== -->

    <section id="cursos-disponibles" class="home-cursos">

        <div class="home-cursos__header">

            <h4 class="home-cursos__titulo">
                Cursos Disponibles
            </h4>

        </div>


        <div class="home-cursos__grid">

            <?php foreach ($cursos as $curso): ?>

                <article class="app-vista-card home-curso-card">

                    <div class="home-curso-card__contenido">

                        <h5 class="home-curso-card__nombre">

                            <?= $this->e(
                                $curso['nombre'] ?? ''
                            ) ?>

                        </h5>


                        <p class="home-curso-card__descripcion">

                            <?= $this->e(
                                $curso['descripcion'] ?? ''
                            ) ?>

                        </p>


                        <div class="home-curso-card__datos">

                            <?php if (!empty($curso['fecha_inicio'])): ?>

                                <p class="home-curso-card__dato">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >
                                        calendar_today
                                    </span>

                                    <?= $this->e(
                                        date(
                                            'd/m/Y',
                                            strtotime(
                                                $curso['fecha_inicio']
                                            )
                                        )
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <?php if (!empty($curso['hora_inicio'])): ?>

                                <p class="home-curso-card__dato">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >
                                        schedule
                                    </span>

                                    <?= $this->e(
                                        $curso['hora_inicio']
                                    ) ?>

                                    hs

                                </p>

                            <?php endif; ?>


                            <?php if (!empty($curso['ubicacion'])): ?>

                                <p class="home-curso-card__dato">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >
                                        location_on
                                    </span>

                                    <?= $this->e(
                                        $curso['ubicacion']
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <p class="home-curso-card__dato">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    groups
                                </span>

                                <?= (int)(
                                    $curso['cupos_disponibles'] ?? 0
                                ) ?>

                                /

                                <?= (int)(
                                    $curso['cupos_totales'] ?? 0
                                ) ?>

                                cupos

                            </p>

                        </div>


                        <div class="home-curso-card__footer">

                            <span class="app-chip app-chip--info">

                                <?php if (
                                    strtolower(
                                        (string)(
                                            $curso['modalidad'] ?? ''
                                        )
                                    ) === 'presencial'
                                ): ?>

                                    📍 Presencial

                                <?php else: ?>

                                    💻 Virtual

                                <?php endif; ?>

                            </span>


                            <?php if (
                                empty($curso['inscripto'])
                                && !empty($curso['puede_inscribirse'])
                            ): ?>

                                <form
                                    method="POST"
                                    action="<?= $this->e(
                                        $this->getRoute(
                                            'guardar_inscripcion_curso'
                                        )
                                    ) ?>"
                                    class="home-curso-card__form"
                                >
                                    
                                    <?= $this->getCsrfInput() ?>

                                    <input
                                        type="hidden"
                                        name="curso_id"
                                        value="<?= (int)(
                                            $curso['id'] ?? 0
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="app-vista-button app-vista-button--primary"
                                    >
                                        Inscribirme
                                    </button>

                                </form>

                            <?php elseif (!empty($curso['inscripto'])): ?>

                                <button
                                    type="button"
                                    class="app-vista-button app-vista-button--secondary"
                                    disabled
                                >
                                    Ya inscripto
                                </button>

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


    <!-- =====================================================
         PRÓXIMO EXAMEN DEL USUARIO
         ===================================================== -->

    <?php if ($proximoExamen !== null): ?>

        <section class="home-proximo-examen">

            <h4 class="home-examenes__titulo">
                Mi examen
            </h4>


            <article class="app-vista-card home-examen-card">

                <div class="home-examen-card__contenido">

                    <h5 class="home-examen-card__titulo">
                        Examen de Manipulación de Alimentos
                    </h5>


                    <?php if (!empty($proximoExamen['fecha'])): ?>

                        <p class="home-examen-card__detalle">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                calendar_today
                            </span>

                            <?= $this->e(
                                date(
                                    'd/m/Y',
                                    strtotime(
                                        $proximoExamen['fecha']
                                    )
                                )
                            ) ?>

                        </p>

                    <?php endif; ?>


                    <?php if (!empty($proximoExamen['hora'])): ?>

                        <p class="home-examen-card__detalle">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                schedule
                            </span>

                            <?= $this->e(
                                substr(
                                    (string)$proximoExamen['hora'],
                                    0,
                                    5
                                )
                            ) ?>

                        </p>

                    <?php endif; ?>


                    <?php if (!empty($proximoExamen['ubicacion'])): ?>

                        <p class="home-examen-card__detalle">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                location_on
                            </span>

                            <?= $this->e(
                                $proximoExamen['ubicacion']
                            ) ?>

                            <?php if (!empty($proximoExamen['aula'])): ?>

                                -

                                <?= $this->e(
                                    $proximoExamen['aula']
                                ) ?>

                            <?php endif; ?>

                        </p>

                    <?php endif; ?>

                </div>

            </article>

        </section>

    <?php endif; ?>


    <!-- =====================================================
         PRÓXIMOS EXÁMENES
         ===================================================== -->

    <?php if (
        $proximoExamen === null
        && $mostrarExamenes
    ): ?>

        <section
            id="proximos-examenes"
            class="home-examenes"
        >

            <h4 class="home-examenes__titulo">
                Próximos Exámenes
            </h4>


            <div class="home-examenes__grid">

                <?php foreach ($examenes as $exam): ?>

                    <?php
                    $examId =
                        (int)($exam['id'] ?? 0);

                    $examDisponible =
                        (int)($exam['available'] ?? 0);
                    ?>

                    <article class="app-vista-card home-examen-card">

                        <div class="home-examen-card__fecha">

                            <span class="home-examen-card__mes">

                                <?= $this->e(
                                    $exam['month'] ?? ''
                                ) ?>

                            </span>


                            <span class="home-examen-card__dia">

                                <?= $this->e(
                                    $exam['day'] ?? ''
                                ) ?>

                            </span>

                        </div>


                        <div class="home-examen-card__contenido">

                            <h5 class="home-examen-card__titulo">

                                <?= $this->e(
                                    $exam['title'] ?? ''
                                ) ?>

                            </h5>


                            <p class="home-examen-card__detalle">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    schedule
                                </span>

                                <?= $this->e(
                                    $exam['time'] ?? ''
                                ) ?>

                            </p>


                            <p class="home-examen-card__detalle">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    location_on
                                </span>

                                <?= $this->e(
                                    $exam['place'] ?? ''
                                ) ?>

                            </p>


                            <div class="home-examen-card__footer">

                                <span
                                    class="<?= $this->e(
                                        $this->getExamBadgeClass(
                                            $examDisponible
                                        )
                                    ) ?>"
                                >

                                    <?php if ($examDisponible === 1): ?>

                                        <span
                                            class="home-examen-card__indicador"
                                            aria-hidden="true"
                                        ></span>

                                    <?php endif; ?>

                                    <?= $this->e(
                                        $this->getExamBadgeText(
                                            $examDisponible
                                        )
                                    ) ?>

                                </span>


                                <?php if (
                                        !empty(
                                            $exam['puede_inscribirse']
                                        )
                                        && $examId > 0
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="<?= $this->e(
                                                $this->getRoute(
                                                    'confirmar_inscripcion'
                                                )
                                            ) ?>"
                                            class="home-examen-card__form"
                                        >
                                            <?= $this->getCsrfInput() ?>
                                            <?= $this->getCsrfInput() ?>

                                            <input
                                                type="hidden"
                                                name="id_examen"
                                                value="<?= $examId ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="app-vista-button app-vista-button--primary"
                                            >
                                                Inscribirse
                                            </button>

                                        </form>

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

?>

</body>

</html>

<?php
}
}