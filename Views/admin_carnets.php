<?php

declare(strict_types=1);

/**
 * Vista: admin_carnets.php
 *
 * Propósito:
 * Gestión administrativa de carnets.
 *
 * Responsabilidades:
 * - Buscar ciudadanos por DNI.
 * - Mostrar inscripciones aprobadas pendientes de carnet.
 * - Mostrar formulario para cargar un carnet.
 * - Mostrar carnets emitidos.
 */
class AdminCarnetsVista
{
    private string $baseURL =
        '/manipulacionDeAlimentos/';

    /**
     * Genera el encabezado de la página.
     */
    private function getHeader(
        array $data
    ): void
    {
        $assetBase =
            rtrim(
                dirname(
                    $_SERVER['SCRIPT_NAME']
                    ?? ''
                ),
                '/\\'
            );

        if (
            preg_match(
                '#/vistas$#',
                $assetBase
            ) === 1
        ) {

            $assetBase =
                (string) preg_replace(
                    '#/vistas$#',
                    '',
                    $assetBase
                );
        }

        if (
            $assetBase === ''
        ) {

            $assetBase = '';
        }

?>
<!DOCTYPE html>

<html
    class="light"
    lang="es"
>

<head>

    <meta charset="utf-8">

    <meta
        content="width=device-width, initial-scale=1.0"
        name="viewport"
    >

    <title>

        <?php
        echo $this->e(
            $data['page_title']
            ?? 'Gestión de Carnets'
        );
        ?>

    </title>


    <script
        src="<?php
            echo $assetBase;
        ?>/js/tailwind-config.js"
    ></script>


    <script
        src="https://cdn.tailwindcss.com?plugins=forms,container-queries"
    ></script>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet"
    >


    <link
        href="<?php
            echo $assetBase;
        ?>/css/base.css"
        rel="stylesheet"
    >


    <link
        href="<?php
            echo $assetBase;
        ?>/css/components.css"
        rel="stylesheet"
    >


    <link
        href="<?php
            echo $assetBase;
        ?>/css/ui.css"
        rel="stylesheet"
    >


    <link
        href="<?php
            echo $assetBase;
        ?>/css/app.css"
        rel="stylesheet"
    >


    <link
        href="<?php
            echo $assetBase;
        ?>/css/Views/admin-carnets.css"
        rel="stylesheet"
    >
    <script
        src="<?php
            echo $assetBase;
        ?>/js/admin-carnets.js"
    ></script>

</head>


<body
    class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano"
>

<?php

$page_title =
    $data['page_title']
    ?? 'Gestión de Carnets';

include __DIR__ .
    '/header.php';

    }


    /**
     * Genera el pie de página.
     */
    private function getFooter(): void
    {
        include __DIR__ .
            '/footer.php';

?>

</body>

</html>

<?php

    }


    /**
     * Escapa valores para HTML.
     */
    private function e(
        mixed $valor
    ): string
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }


    /**
     * Obtiene las rutas utilizadas
     * por la vista.
     */
    private function getRoute(
        string $route,
        ?int $id = null
    ): string
    {
        return match ($route) {

            'admin' =>
                $this->baseURL
                . 'admin',

            'carnets' =>
                $this->baseURL
                . 'admin/carnets',

            'cargar' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/cargar',

            'emitir' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/emitir',

            default =>
                '#',
        };
    }
    /**
     * Muestra el formulario para cargar el carnet
     * de una inscripción aprobada.
     */
    private function mostrarFormularioCarga(array $inscripcion,array $formulario,array $errores = []
    ): void
    {
        $idInscripcion =
            (int)(
                $inscripcion['inscripcion_id']
                ?? $inscripcion['id']
                ?? 0
            );

        $nombre =
            trim(
                (string)(
                    $inscripcion['nombre']
                    ?? ''
                )
            );

        $apellido =
            trim(
                (string)(
                    $inscripcion['apellido']
                    ?? ''
                )
            );

        $dni =
            (string)(
                $inscripcion['dni']
                ?? ''
            );
    ?>

        <section
            class="app-vista-card admin-carnets__carga"
        >

            <div
                class="admin-carnets__seccion-header"
            >

                <div>

                    <p class="admin-carnets__eyebrow">

                        Emisión de carnet

                    </p>

                    <h2 class="admin-carnets__seccion-titulo">

                        Cargar carnet oficial

                    </h2>

                    <p class="admin-carnets__seccion-descripcion">

                        Ingrese los datos del carnet emitido
                        por DIPA y adjunte el documento oficial.

                    </p>

                </div>

            </div>


            <!-- =============================================
                DATOS DEL ALUMNO
                ============================================= -->

            <div class="admin-carnets__alumno">

                <div class="admin-carnets__alumno-icono">

                    <span class="material-symbols-outlined">

                        person

                    </span>

                </div>

                <div class="admin-carnets__alumno-datos">

                    <h3>

                        <?= $this->e(
                            trim(
                                $apellido
                                . ', '
                                . $nombre
                            )
                        ); ?>

                    </h3>

                    <p>

                        <strong>
                            DNI:
                        </strong>

                        <?= $this->e($dni); ?>

                    </p>

                </div>

                <span
                    class="app-chip app-chip--success"
                >

                    <span class="material-symbols-outlined">

                        check_circle

                    </span>

                    Examen aprobado

                </span>

            </div>


            <!-- =============================================
                FORMULARIO
                ============================================= -->

            <form
                method="POST"
                action="<?= $this->getRoute(
                    'emitir',
                    $idInscripcion
                ); ?>"
                enctype="multipart/form-data"
                class="admin-carnets__form"
            >

                <div class="admin-carnets__form-grid">

                    <div>

                        <label
                            for="numero_carnet"
                            class="app-form-label"
                        >

                            Número de carnet DIPA

                        </label>

                        <input
                            type="text"
                            id="numero_carnet"
                            name="numero_carnet"
                            value="<?= $this->e(
                                (string)(
                                    $formulario[
                                        'numero_carnet'
                                    ]
                                    ?? ''
                                )
                            ); ?>"
                            class="app-form-input"
                            maxlength="100"
                            required
                        >

                        <p class="admin-carnets__campo-ayuda">

                            Ingrese exactamente el número
                            que figura en el carnet oficial.

                        </p>

                    </div>


                    <div>

                        <label
                            for="fecha_emision"
                            class="app-form-label"
                        >

                            Fecha de emisión

                        </label>

                        <input
                            type="date"
                            id="fecha_emision"
                            name="fecha_emision"
                            value="<?= $this->e(
                                (string)(
                                    $formulario[
                                        'fecha_emision'
                                    ]
                                    ?? ''
                                )
                            ); ?>"
                            class="app-form-input"
                            required
                        >

                    </div>


                    <div>

                        <label
                            for="fecha_vencimiento"
                            class="app-form-label"
                        >

                            Fecha de vencimiento

                        </label>

                        <input
                            type="date"
                            id="fecha_vencimiento"
                            name="fecha_vencimiento"
                            value="<?= $this->e(
                                (string)(
                                    $formulario[
                                        'fecha_vencimiento'
                                    ]
                                    ?? ''
                                )
                            ); ?>"
                            class="app-form-input"
                            required
                        >

                    </div>

                </div>


                <!-- =============================================
                    PDF
                    ============================================= -->

                <div class="admin-carnets__archivo">

                    <label
                        for="carnet_pdf"
                        class="app-form-label"
                    >

                        PDF del carnet oficial

                    </label>

                    <div
                        class="admin-carnets__archivo-contenido"
                    >

                        <span class="material-symbols-outlined">

                            picture_as_pdf

                        </span>

                        <div>

                            <p
                                class="admin-carnets__archivo-titulo"
                            >

                                Seleccionar documento

                            </p>

                            <p class="admin-carnets__campo-ayuda">

                                Formato permitido: PDF.

                            </p>

                        </div>

                    </div>

                    <input
                        type="file"
                        id="carnet_pdf"
                        name="carnet_pdf"
                        accept="application/pdf,.pdf"
                        class="admin-carnets__archivo-input"
                        required
                    >

                </div>


                <!-- =============================================
                    ADVERTENCIA
                    ============================================= -->

                <div class="admin-carnets__advertencia">

                    <span class="material-symbols-outlined">

                        warning

                    </span>

                    <div>

                        <strong>

                            Verifique los datos antes de continuar.

                        </strong>

                        <p>

                            Una vez cargado el carnet,
                            la inscripción pasará de
                            <strong>APROBADO</strong>
                            a
                            <strong>CARNET EMITIDO</strong>.

                        </p>

                    </div>

                </div>


                <!-- =============================================
                    ERRORES DEL FORMULARIO
                    ============================================= -->

                <?php if (!empty($errores)): ?>

                    <div class="admin-carnets__mensajes">

                        <?php foreach (
                            $errores
                            as
                            $error
                        ): ?>

                            <div
                                class="admin-carnets__error"
                                role="alert"
                            >

                                <span
                                    class="material-symbols-outlined"
                                >

                                    error

                                </span>

                                <span>

                                    <?= $this->e(
                                        (string)$error
                                    ); ?>

                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                    ACCIONES
                    ============================================= -->

                <div class="admin-carnets__acciones">

                    <a
                        href="<?= $this->getRoute(
                            'carnets'
                        ); ?>"
                        class="app-vista-button
                            app-vista-button--secondary"
                    >

                        <span class="material-symbols-outlined">

                            close

                        </span>

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="app-vista-button
                            app-vista-button--primary"
                    >

                        <span class="material-symbols-outlined">

                            upload_file

                        </span>

                        Cargar carnet

                    </button>

                </div>

            </form>

        </section>

    <?php
    }


    /**
     * Muestra la vista.
     */
    public function mostrar(
        array $data = []
    ): void
    {

        if (
            empty($data)
        ) {

            $data = [

                'page_title' =>
                    'Gestión de Carnets',

                'pendientes' =>
                    [],

                'carnets' =>
                    [],

                'resultado' =>
                    null,

                'errores' =>
                    [],

                'modo_carga' =>
                    false,

                'inscripcion' =>
                    null,

                'formulario' => [

                    'numero_carnet' =>
                        '',

                    'fecha_emision' =>
                        '',

                    'fecha_vencimiento' =>
                        ''
                ],

                'busqueda' =>
                    ''
            ];
        }


        $this->getHeader(
            $data
        );

?>
        <!-- =====================================================
             RESULTADO DE BÚSQUEDA
             ===================================================== -->

        <?php

        $pendientes =
            $data['pendientes']
            ?? [];

        $carnets =
            $data['carnets']
            ?? [];

        $errores =
            $data['errores']
            ?? [];

        $resultadoBusqueda =
            $data['resultado']
            ?? null;

        $modoCarga =
            !empty(
                $data['modo_carga']
            );

        $inscripcion =
            $data['inscripcion']
            ?? null;

        $origenCarga =
            $data['origen_carga']
            ?? '';

        $formulario =
            $data['formulario']
            ?? [

                'numero_carnet' =>
                    '',

                'fecha_emision' =>
                    '',

                'fecha_vencimiento' =>
                    ''
            ];

        $busqueda =
            $data['busqueda']
            ?? '';

        ?>


        <main class="contenido-principal">

            <div class="admin-carnets">


                <!-- =================================================
                     ENCABEZADO
                     ================================================= -->

                <header
                    class="admin-carnets__header"
                >

                    <div>

                        <p
                            class="admin-carnets__eyebrow"
                        >

                            Administración

                        </p>

                        <h1
                            class="admin-carnets__titulo"
                        >

                            Gestión de carnets

                        </h1>

                        <p
                            class="admin-carnets__descripcion"
                        >

                            Consulte los ciudadanos con examen
                            aprobado, cargue los carnets oficiales
                            y consulte los carnets ya emitidos.

                        </p>

                    </div>

                </header>


                <!-- =================================================
                     ERRORES
                     ================================================= -->

                <?php if (!empty($errores)): ?>

                    <section
                        class="admin-carnets__mensajes"
                        aria-live="polite"
                    >

                        <?php foreach (
                            $errores
                            as
                            $error
                        ): ?>

                            <div
                                class="admin-carnets__error"
                                role="alert"
                            >

                                <span
                                    class="material-symbols-outlined"
                                >

                                    error

                                </span>

                                <span>

                                    <?= $this->e(
                                        (string)$error
                                    ); ?>

                                </span>

                            </div>

                        <?php endforeach; ?>

                    </section>

                <?php endif; ?>


                <!-- =================================================
                     BÚSQUEDA POR DNI
                     ================================================= -->

                <section
                    class="app-vista-card
                           admin-carnets__busqueda"
                >

                    <div
                        class="admin-carnets__seccion-header"
                    >

                        <div>

                            <h2
                                class="admin-carnets__seccion-titulo"
                            >

                                Buscar ciudadano

                            </h2>

                            <p
                                class="admin-carnets__seccion-descripcion"
                            >

                                Busque por DNI para consultar si el
                                ciudadano tiene un examen aprobado
                                pendiente de emisión.

                            </p>

                        </div>

                    </div>


                    <form
                        method="GET"
                        action="<?= $this->getRoute(
                            'carnets'
                        ); ?>"
                        class="admin-carnets__busqueda-form"
                    >

                        <div
                            class="admin-carnets__campo-busqueda"
                        >

                            <label
                                for="dni"
                                class="app-form-label"
                            >

                                DNI

                            </label>

                            <input
                                type="text"
                                id="dni"
                                name="dni"
                                value="<?= $this->e(
                                    (string)$busqueda
                                ); ?>"
                                class="app-form-input"
                                placeholder="Ingrese el DNI"
                                autocomplete="off"
                            >

                        </div>


                        <button
                            type="submit"
                            class="app-vista-button
                                   app-vista-button--primary"
                        >

                            <span
                                class="material-symbols-outlined"
                            >

                                search

                            </span>

                            Buscar

                        </button>

                    </form>

                </section>


                <!-- =================================================
                     RESULTADO DE BÚSQUEDA
                     ================================================= -->

                <?php if ($busqueda !== ''): ?>

                    <section
                        id="resultado-busqueda"
                        class="app-vista-card
                               admin-carnets__resultado-busqueda"
                    >

                        <div
                            class="admin-carnets__seccion-header"
                        >

                            <div>

                                <h2
                                    class="admin-carnets__seccion-titulo"
                                >

                                    Resultado de búsqueda

                                </h2>

                            </div>

                        </div>


                        <?php if (
                            $resultadoBusqueda !== null
                        ): ?>

                            <article
                                class="admin-carnets__resultado"
                            >

                                <div
                                    class="admin-carnets__resultado-icono"
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >

                                        person

                                    </span>

                                </div>


                                <div
                                    class="admin-carnets__resultado-datos"
                                >

                                    <h3>

                                        <?= $this->e(
                                            trim(
                                                (string)(
                                                    $resultadoBusqueda[
                                                        'apellido'
                                                    ]
                                                    ?? ''
                                                )
                                                . ', '
                                                .
                                                (string)(
                                                    $resultadoBusqueda[
                                                        'nombre'
                                                    ]
                                                    ?? ''
                                                )
                                            )
                                        ); ?>

                                    </h3>


                                    <p>

                                        <strong>
                                            DNI:
                                        </strong>

                                        <?= $this->e(
                                            (string)(
                                                $resultadoBusqueda[
                                                    'dni'
                                                ]
                                                ?? $busqueda
                                            )
                                        ); ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $resultadoBusqueda[
                                                'email'
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <strong>
                                                Correo:
                                            </strong>

                                            <?= $this->e(
                                                (string)(
                                                    $resultadoBusqueda[
                                                        'email'
                                                    ]
                                                )
                                            ); ?>

                                        </p>

                                    <?php endif; ?>


                                    <span
                                        class="app-chip
                                               app-chip--success"
                                    >

                                        <span
                                            class="material-symbols-outlined"
                                        >

                                            check_circle

                                        </span>

                                        Examen aprobado

                                    </span>

                                </div>


                                <div
                                    class="admin-carnets__resultado-accion"
                                >

                                    <?php

                                    $idInscripcionBusqueda =
                                        (int)(
                                            $resultadoBusqueda[
                                                'inscripcion_id'
                                            ]
                                            ??
                                            $resultadoBusqueda[
                                                'id'
                                            ]
                                            ??
                                            0
                                        );

                                    ?>


                                    <?php if (
                                        $idInscripcionBusqueda > 0
                                    ): ?>
                                        <a
                                            href="<?= $this->getRoute(
                                                'cargar',
                                                $idInscripcionBusqueda
                                            ); ?>?origen=busqueda&dni=<?= urlencode(
                                                (string)(
                                                    $resultadoBusqueda['dni']
                                                    ?? $busqueda
                                                )
                                            ); ?>#resultado-busqueda"
                                            class="app-vista-button
                                                app-vista-button--primary"
                                        >

                                            <span
                                                class="material-symbols-outlined"
                                            >

                                                upload_file

                                            </span>

                                            Cargar carnet

                                        </a>

                                    <?php endif; ?>

                                </div>

                            </article>

                            <?php if (
                                $modoCarga
                                && $origenCarga === 'busqueda'
                                && $inscripcion !== null
                            ): ?>

                                <?php

                                $this->mostrarFormularioCarga(
                                    $inscripcion,
                                    $formulario,
                                    $errores
                                );

                                ?>

                            <?php endif; ?>

                        <?php else: ?>

                            <div
                                class="admin-carnets__sin-resultado"
                            >

                                <span
                                    class="material-symbols-outlined"
                                >

                                    person_search

                                </span>

                                <div>

                                    <h3>

                                        No se encontró una emisión
                                        pendiente

                                    </h3>

                                    <p>

                                        No existe una inscripción con
                                        examen aprobado pendiente de
                                        carnet para el DNI ingresado.

                                    </p>

                                </div>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>
                                <!-- =================================================
                     PENDIENTES DE EMISIÓN
                     ================================================= -->

                <section
                    class="admin-carnets__pendientes"
                >

                    <div
                        class="admin-carnets__seccion-header"
                    >

                        <div>

                            <h2
                                class="admin-carnets__seccion-titulo"
                            >

                                Pendientes de emisión

                            </h2>

                            <p
                                class="admin-carnets__seccion-descripcion"
                            >

                                Ciudadanos que aprobaron el examen
                                y todavía no tienen un carnet cargado.

                            </p>

                        </div>


                        <span
                            class="app-chip app-chip--warning"
                        >

                            <?= count($pendientes); ?>

                            pendiente<?= count($pendientes) === 1
                                ? ''
                                : 's'; ?>

                        </span>

                    </div>


                    <?php if (!empty($pendientes)): ?>

                        <div
                            class="admin-carnets__lista"
                        >

                            <?php foreach (
                                $pendientes
                                as
                                $pendiente
                            ): ?>

                                <?php

                                $idInscripcion =
                                    (int)(
                                        $pendiente[
                                            'inscripcion_id'
                                        ]
                                        ??
                                        $pendiente['id']
                                        ??
                                        0
                                    );

                                $nombre =
                                    trim(
                                        (string)(
                                            $pendiente[
                                                'nombre'
                                            ]
                                            ?? ''
                                        )
                                    );

                                $apellido =
                                    trim(
                                        (string)(
                                            $pendiente[
                                                'apellido'
                                            ]
                                            ?? ''
                                        )
                                    );

                                $dni =
                                    (string)(
                                        $pendiente['dni']
                                        ?? ''
                                    );

                                $fechaExamen =
                                    $pendiente[
                                        'fecha_examen'
                                    ]
                                    ?? null;

                                ?>


                                <article id="carnet-<?= $idInscripcion ?>"
                                    class="app-vista-card
                                           admin-carnets__pendiente"
                                >

                                    <div
                                        class="admin-carnets__pendiente-icono"
                                    >

                                        <span
                                            class="material-symbols-outlined"
                                        >

                                            person

                                        </span>

                                    </div>


                                    <div
                                        class="admin-carnets__pendiente-datos"
                                    >

                                        <h3
                                            class="admin-carnets__pendiente-nombre"
                                        >

                                            <?= $this->e(
                                                trim(
                                                    $apellido
                                                    . ', '
                                                    . $nombre
                                                )
                                            ); ?>

                                        </h3>


                                        <div
                                            class="admin-carnets__pendiente-info"
                                        >

                                            <span>

                                                <strong>
                                                    DNI:
                                                </strong>

                                                <?= $this->e(
                                                    $dni
                                                ); ?>

                                            </span>


                                            <?php if (
                                                !empty(
                                                    $fechaExamen
                                                )
                                            ): ?>

                                                <span>

                                                    <strong>
                                                        Examen:
                                                    </strong>

                                                    <?= $this->e(
                                                        date(
                                                            'd/m/Y',
                                                            strtotime(
                                                                $fechaExamen
                                                            )
                                                        )
                                                    ); ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <span
                                            class="app-chip
                                                   app-chip--success"
                                        >

                                            <span
                                                class="material-symbols-outlined"
                                            >

                                                check_circle

                                            </span>

                                            Examen aprobado

                                        </span>

                                    </div>


                                    <div
                                        class="admin-carnets__pendiente-accion"
                                    >

                                        <?php if (
                                            $idInscripcion > 0
                                        ): ?>

                                            <a
                                                    href="<?= $this->getRoute(
                                                        'cargar',
                                                        $idInscripcion
                                                    ); ?>#carnet-<?= $idInscripcion ?>"
                                                    class="app-vista-button app-vista-button--primary"
                                                >

                                                <span
                                                    class="material-symbols-outlined"
                                                >

                                                    upload_file

                                                </span>

                                                Cargar carnet

                                            </a>

                                        <?php else: ?>

                                            <span
                                                class="admin-carnets__accion-error"
                                            >

                                                Inscripción no válida

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </article>
                                <?php if (
                                    $modoCarga
                                    && $origenCarga !== 'busqueda'
                                    && $inscripcion !== null
                                    && (int)(
                                        $inscripcion['inscripcion_id']
                                        ?? $inscripcion['id']
                                        ?? 0
                                    ) === $idInscripcion
                                ): ?>

                                    <?php

                                    $this->mostrarFormularioCarga(
                                        $inscripcion,
                                        $formulario,
                                        $errores
                                    );

                                    ?>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div
                            class="app-vista-card
                                   admin-carnets__vacio"
                        >

                            <span
                                class="material-symbols-outlined"
                            >

                                verified

                            </span>

                            <div>

                                <h3>

                                    No hay carnets pendientes
                                    de emisión

                                </h3>

                                <p>

                                    Todas las inscripciones aprobadas
                                    tienen su carnet cargado o todavía
                                    no hay exámenes aprobados pendientes.

                                </p>

                            </div>

                        </div>

                    <?php endif; ?>

                </section>
                              
                                <!-- =================================================
                     CARNETS EMITIDOS
                     ================================================= -->

                <section
                    class="admin-carnets__emitidos"
                >

                    <div
                        class="admin-carnets__seccion-header"
                    >

                        <div>

                            <h2
                                class="admin-carnets__seccion-titulo"
                            >

                                Carnets emitidos

                            </h2>

                            <p
                                class="admin-carnets__seccion-descripcion"
                            >

                                Listado de carnets oficiales
                                cargados en el sistema.

                            </p>

                        </div>


                        <span
                            class="app-chip
                                   app-chip--success"
                        >

                            <?= count($carnets); ?>

                            emitido<?= count($carnets) === 1
                                ? ''
                                : 's'; ?>

                        </span>

                    </div>


                    <?php if (!empty($carnets)): ?>

                        <div
                            class="admin-carnets__tabla-contenedor"
                        >

                            <div
                                class="admin-carnets__tabla-scroll"
                            >

                                <table
                                    class="admin-carnets__tabla"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                Ciudadano
                                            </th>

                                            <th>
                                                DNI
                                            </th>

                                            <th>
                                                Número de carnet
                                            </th>

                                            <th>
                                                Emisión
                                            </th>

                                            <th>
                                                Vencimiento
                                            </th>

                                            <th>
                                                Estado
                                            </th>

                                            <th>
                                                Acción
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            $carnets
                                            as
                                            $carnet
                                        ): ?>

                                            <?php

                                            $nombre =
                                                trim(
                                                    (string)(
                                                        $carnet[
                                                            'nombre'
                                                        ]
                                                        ?? ''
                                                    )
                                                );

                                            $apellido =
                                                trim(
                                                    (string)(
                                                        $carnet[
                                                            'apellido'
                                                        ]
                                                        ?? ''
                                                    )
                                                );

                                            $dni =
                                                (string)(
                                                    $carnet['dni']
                                                    ?? ''
                                                );

                                            $numeroCarnet =
                                                (string)(
                                                    $carnet[
                                                        'numero_carnet'
                                                    ]
                                                    ?? ''
                                                );

                                            $fechaEmision =
                                                $carnet[
                                                    'fecha_emision'
                                                ]
                                                ?? null;

                                            $fechaVencimiento =
                                                $carnet[
                                                    'fecha_vencimiento'
                                                ]
                                                ?? null;

                                            $rutaPdf =
                                                $carnet[
                                                    'ruta_pdf'
                                                ]
                                                ?? '';

                                            ?>


                                            <tr>

                                                <td
                                                    data-label="Ciudadano"
                                                >

                                                    <strong>

                                                        <?= $this->e(
                                                            trim(
                                                                $apellido
                                                                . ', '
                                                                . $nombre
                                                            )
                                                        ); ?>

                                                    </strong>

                                                </td>


                                                <td
                                                    data-label="DNI"
                                                >

                                                    <?= $this->e(
                                                        $dni
                                                    ); ?>

                                                </td>


                                                <td
                                                    data-label="Número de carnet"
                                                >

                                                    <span
                                                        class="admin-carnets__numero"
                                                    >

                                                        <?= $this->e(
                                                            $numeroCarnet
                                                        ); ?>

                                                    </span>

                                                </td>


                                                <td
                                                    data-label="Emisión"
                                                >

                                                    <?php if (
                                                        !empty(
                                                            $fechaEmision
                                                        )
                                                    ): ?>

                                                        <?= $this->e(
                                                            date(
                                                                'd/m/Y',
                                                                strtotime(
                                                                    $fechaEmision
                                                                )
                                                            )
                                                        ); ?>

                                                    <?php else: ?>

                                                        —

                                                    <?php endif; ?>

                                                </td>


                                                <td
                                                    data-label="Vencimiento"
                                                >

                                                    <?php if (
                                                        !empty(
                                                            $fechaVencimiento
                                                        )
                                                    ): ?>

                                                        <?= $this->e(
                                                            date(
                                                                'd/m/Y',
                                                                strtotime(
                                                                    $fechaVencimiento
                                                                )
                                                            )
                                                        ); ?>

                                                    <?php else: ?>

                                                        —

                                                    <?php endif; ?>

                                                </td>


                                                <td
                                                    data-label="Estado"
                                                >

                                                    <span
                                                        class="app-chip
                                                               app-chip--success"
                                                    >

                                                        <span
                                                            class="material-symbols-outlined"
                                                        >

                                                            verified

                                                        </span>

                                                        Emitido

                                                    </span>

                                                </td>


                                                <td
                                                    data-label="Acción"
                                                >

                                                    <?php if (
                                                        !empty(
                                                            $rutaPdf
                                                        )
                                                    ): ?>

                                                        <a
                                                            href="<?= $this->e(
                                                                $rutaPdf
                                                            ); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="app-vista-button
                                                                   app-vista-button--secondary"
                                                        >

                                                            <span
                                                                class="material-symbols-outlined"
                                                            >

                                                                picture_as_pdf

                                                            </span>

                                                            Ver carnet

                                                        </a>

                                                    <?php else: ?>

                                                        <span
                                                            class="admin-carnets__sin-pdf"
                                                        >

                                                            PDF no disponible

                                                        </span>

                                                    <?php endif; ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    <?php else: ?>

                        <div
                            class="app-vista-card
                                   admin-carnets__vacio"
                        >

                            <span
                                class="material-symbols-outlined"
                            >

                                badge

                            </span>

                            <div>

                                <h3>

                                    No hay carnets emitidos

                                </h3>

                                <p>

                                    Todavía no se ha cargado ningún
                                    carnet oficial en el sistema.

                                </p>

                            </div>

                        </div>

                    <?php endif; ?>

                </section>
                    <!-- =================================================
                     FIN DEL CONTENIDO
                     ================================================= -->

            </div>

        </main>

<?php

        $this->getFooter();

    }

}