<?php

declare(strict_types=1);

/**
 * Vista: admin_documentos.php
 *
 * Propósito:
 * Gestión administrativa de documentación.
 *
 * Responsabilidades:
 * - Buscar ciudadanos por DNI.
 * - Mostrar ciudadanos con documentación pendiente.
 * - Mostrar el estado de cada documento.
 * - Acceder a la descarga de documentos.
 * - Aprobar documentos.
 * - Rechazar documentos con observación.
 *
 * La vista concentra todas las operaciones
 * administrativas de documentación.
 */
class AdminDocumentosVista
{
    private string $baseURL =
        '/ManipulacionDeAlimentos/';


    /**
     * Construye el encabezado común de la vista.
     */
    private function getHeader(
        array $data
    ): void {

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
            ?? 'Gestión de Documentación'
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
        ?>/css/Views/admin-documentos.css"
        rel="stylesheet"
    >
    <script
        src="<?php echo $assetBase; ?>/js/admin-documentos.js"
        defer
    ></script>

</head>


<body
    class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano"
>

<?php

$page_title =
    $data['page_title']
    ?? 'Gestión de Documentación';

include __DIR__ .
    '/header.php';

    }


    /**
     * Construye el pie de página común.
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
     * Escapa valores para salida HTML.
     */
    private function e(
        mixed $valor
    ): string {

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
    ): string {

        return match ($route) {

            'admin' =>
                $this->baseURL .
                'admin',

            'documentos' =>
                $this->baseURL .
                'admin/documentos',

            'buscar' =>
                $this->baseURL .
                'admin/documentos/buscar',

            'aprobar' =>
                $this->baseURL .
                'admin/documentos/' .
                $id .
                '/aprobar',

            'rechazar' =>
                $this->baseURL .
                'admin/documentos/' .
                $id .
                '/rechazar',

            'descargar' =>
                $this->baseURL .
                'admin/documentos/' .
                $id .
                '/descargar',

            default =>
                '#',
        };
    }


    /**
     * Muestra la vista.
     */
    public function mostrar(
        array $data = []
    ): void {

        if (
            empty($data)
        ) {

            $data = [

                'page_title' =>
                    'Gestión de Documentación',

                'usuarios' =>
                    [],

                'resultado' =>
                    null,

                'busqueda' =>
                    '',

                'errores' =>
                    []
            ];
        }


        /*
         * Ciudadanos mostrados en el listado
         * administrativo.
         */
        $usuarios =
            $data['usuarios']
            ?? [];


        /*
         * Resultado específico de una
         * búsqueda por DNI.
         */
        $resultado =
            $data['resultado']
            ?? null;


        /*
         * DNI introducido en el buscador.
         */
        $busqueda =
            $data['busqueda']
            ?? '';


        /*
         * Errores enviados por el controlador.
         */
        $errores =
            $data['errores']
            ?? [];
        
        /*
        * ==============================================
        * DATOS DE PAGINACIÓN
        * ==============================================
        */

        $pagina =
            (int)(
                $data['pagina']
                ?? 1
            );

        $limite =
            (int)(
                $data['limite']
                ?? 10
            );

        $total =
            (int)(
                $data['total']
                ?? 0
            );

        $totalPaginas =
            (int)(
                $data['total_paginas']
                ?? 0
            );

        $tieneAnterior =
            !empty(
                $data['tiene_anterior']
            );

        $tieneSiguiente =
            !empty(
                $data['tiene_siguiente']
            );

        $this->getHeader(
            $data
        );

?>

<main
    class="admin-documentos-page"
>

    <!--
        El contenido principal de la vista
        continúa en la Parte 2.
    -->


        <section
            class="admin-documentos-container"
        >

            <!-- ==========================================
                 ENCABEZADO
            =========================================== -->

            <div
                class="admin-documentos-header"
            >

                <div>

                    <span
                        class="admin-documentos-eyebrow"
                    >
                        ADMINISTRACIÓN
                    </span>

                    <h1
                        class="admin-documentos-title"
                    >
                        Gestión de Documentación
                    </h1>

                    <p
                        class="admin-documentos-subtitle"
                    >
                        Revisá, validá y gestioná la documentación
                        presentada por los ciudadanos.
                    </p>

                </div>

            </div>


                    <!-- ==========================================
             BUSCADOR POR DNI
        =========================================== -->

        <section
            class="admin-documentos-search-card"
        >

            <div
                class="admin-documentos-search-header"
            >

                <div
                    class="admin-documentos-search-icon"
                >

                    <span
                        class="material-symbols-outlined"
                    >
                        search
                    </span>

                </div>

                <div>

                    <h2>
                        Buscar ciudadano
                    </h2>

                    <p>
                        Ingresá el DNI para consultar
                        su documentación.
                    </p>

                </div>

            </div>


            <form
                action="<?php
                    echo $this->e(
                        $this->getRoute(
                            'buscar'
                        )
                    );
                ?>"
                method="GET"
                class="admin-documentos-search-form"
            >

                <div
                    class="admin-documentos-search-field"
                >

                    <label
                        for="dni"
                    >
                        DNI
                    </label>

                    <input
                        type="text"
                        id="dni"
                        name="dni"
                        value="<?php
                            echo $this->e(
                                $busqueda
                            );
                        ?>"
                        placeholder="Ingresá el DNI"
                        autocomplete="off"
                        inputmode="numeric"
                    >

                </div>


                <button
                    type="submit"
                    class="
                        admin-documentos-btn
                        admin-documentos-btn-primary
                    "
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


        <!-- ==========================================
             MENSAJES DE ERROR
        =========================================== -->

<?php if (!empty($errores)): ?>

        <section
            class="
                admin-documentos-alert
                admin-documentos-alert-error
            "
        >

            <span
                class="material-symbols-outlined"
            >
                error
            </span>

            <div>

<?php foreach ($errores as $error): ?>

                <p>
                    <?php
                    echo $this->e(
                        $error
                    );
                    ?>
                </p>

<?php endforeach; ?>

            </div>

        </section>

<?php endif; ?>


        <!-- ==========================================
             RESULTADO DE BÚSQUEDA
        =========================================== -->

<?php if ($busqueda !== ''): ?>

        <section
            class="admin-documentos-section"
        >

            <div
                class="admin-documentos-section-header"
            >

                <div>

                    <span
                        class="admin-documentos-eyebrow"
                    >
                        RESULTADO
                    </span>

                    <h2>
                        Consulta por DNI
                    </h2>

                </div>

            </div>


<?php if ($resultado !== null): ?>

            <!-- ======================================
                 DATOS DEL CIUDADANO
            ======================================= -->

            <div
                class="admin-documentos-user-card"
            >

                <div
                    class="admin-documentos-user-header"
                >

                    <div
                        class="admin-documentos-user-avatar"
                    >

                        <span
                            class="material-symbols-outlined"
                        >
                            person
                        </span>

                    </div>


                    <div
                        class="admin-documentos-user-info"
                    >

                        <h3>
                            <?php
                            echo $this->e(
                                $resultado['nombre']
                                . ' '
                                . $resultado['apellido']
                            );
                            ?>
                        </h3>

                        <p>
                            DNI:

                            <strong>
                                <?php
                                echo $this->e(
                                    $resultado['dni']
                                );
                                ?>
                            </strong>
                        </p>

                    </div>

                </div>


                <div
                    class="admin-documentos-user-data"
                >

                    <div>

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php
                            echo $this->e(
                                $resultado['email']
                                ?? 'No informado'
                            );
                            ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Teléfono
                        </span>

                        <strong>
                            <?php
                            echo $this->e(
                                $resultado['telefono']
                                ?? 'No informado'
                            );
                            ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Domicilio
                        </span>

                        <strong>
                            <?php
                            echo $this->e(
                                $resultado['domicilio']
                                ?? 'No informado'
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- ======================================
                 DOCUMENTOS DEL CIUDADANO
            ======================================= -->

            <div
                class="admin-documentos-list"
            >

<?php foreach (
    $resultado['documentos']
    ?? []
    as $documento
): ?>

<?php

    $estado =
        strtolower(
            trim(
                (string)(
                    $documento['estado']
                    ?? ''
                )
            )
        );

    $tipo =
        strtolower(
            trim(
                (string)(
                    $documento['tipo_documento']
                    ?? ''
                )
            )
        );


    $tipoNombre = match ($tipo) {

        'dni' =>
            'DNI',

        'foto',
        'foto_carnet' =>
            'Foto carnet',

        'asistencia' =>
            'Asistencia',

        'moodle',
        'certificado_moodle' =>
            'Certificado Moodle',

        default =>
            ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $tipo
                )
            )
    };


    $estadoNombre = match ($estado) {

        'aprobado' =>
            'Aprobado',

        'rechazado' =>
            'Rechazado',

        'pendiente' =>
            'Pendiente',

        default =>
            ucfirst(
                $estado
            )
    };


    $estadoIcono = match ($estado) {

        'aprobado' =>
            'check_circle',

        'rechazado' =>
            'cancel',

        'pendiente' =>
            'schedule',

        default =>
            'help'
    };

?>

                <article
                    class="
                        admin-documento-card
                        admin-documento-estado-<?php
                            echo $this->e(
                                $estado
                            );
                        ?>
                    "
                >

                    <div
                        class="admin-documento-main"
                    >

                        <div
                            class="admin-documento-icon"
                        >

                            <span
                                class="material-symbols-outlined"
                            >
                                description
                            </span>

                        </div>


                        <div
                            class="admin-documento-info"
                        >

                            <div
                                class="admin-documento-title-row"
                            >

                                <h3>
                                    <?php
                                    echo $this->e(
                                        $tipoNombre
                                    );
                                    ?>
                                </h3>


                                <span
                                    class="
                                        admin-documento-status
                                        admin-documento-status-<?php
                                            echo $this->e(
                                                $estado
                                            );
                                        ?>
                                "
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >
                                        <?php
                                        echo $this->e(
                                            $estadoIcono
                                        );
                                        ?>
                                    </span>

                                    <?php
                                    echo $this->e(
                                        $estadoNombre
                                    );
                                    ?>

                                </span>

                            </div>


                            <p
                                class="admin-documento-filename"
                            >
                                <?php
                                echo $this->e(
                                    $documento[
                                        'nombre_original'
                                    ]
                                    ?? 'Archivo sin nombre'
                                );
                                ?>
                            </p>


<?php if (
    !empty(
        $documento['observaciones']
    )
): ?>

                            <div
                                class="admin-documento-observacion"
                            >

                                <span
                                    class="material-symbols-outlined"
                                >
                                    notes
                                </span>

                                <span>

                                    <strong>
                                        Observación:
                                    </strong>

                                    <?php
                                    echo $this->e(
                                        $documento[
                                            'observaciones'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>

<?php endif; ?>

                        </div>

                    </div>


                    <!-- ==================================
                         ACCIONES DEL DOCUMENTO
                    =================================== -->

                    <div
                        class="admin-documento-actions"
                    >

                        <a
                            href="<?php
                                echo $this->e(
                                    $this->getRoute(
                                        'descargar',
                                        (int)$documento['id']
                                    )
                                );
                            ?>"
                            class="
                                admin-documentos-btn
                                admin-documentos-btn-secondary
                            "
                            target="_blank"
                            rel="noopener noreferrer"
                        >

                            <span
                                class="material-symbols-outlined"
                            >
                                download
                            </span>

                            Ver / descargar

                        </a>


<?php if (
    $estado === 'pendiente'
): ?>

                        <form
                            action="<?php
                                echo $this->e(
                                    $this->getRoute(
                                        'aprobar',
                                        (int)$documento['id']
                                    )
                                );
                            ?>"
                            method="POST"
                        >

                            <button
                                type="submit"
                                class="
                                    admin-documentos-btn
                                    admin-documentos-btn-success
                                "
                            >

                                <span
                                    class="material-symbols-outlined"
                                >
                                    check
                                </span>

                                Aprobar

                            </button>

                        </form>


                        <button
                            type="button"
                            class="
                                admin-documentos-btn
                                admin-documentos-btn-danger
                                js-rechazar-documento
                            "
                            data-documento-id="<?php
                                echo (int)$documento['id'];
                            ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                            >
                                close
                            </span>

                            Rechazar

                        </button>

<?php endif; ?>

                    </div>


                    <!-- ==================================
                         FORMULARIO DE RECHAZO
                    =================================== -->

                    <div
                        id="rechazo-<?php
                            echo (int)$documento['id'];
                        ?>"
                        class="
                            admin-documento-rechazo
                            js-formulario-rechazo
                        "
                        hidden
                    >

                        <form
                            action="<?php
                                echo $this->e(
                                    $this->getRoute(
                                        'rechazar',
                                        (int)$documento['id']
                                    )
                                );
                            ?>"
                            method="POST"
                        >

                            <label
                                for="observaciones-<?php
                                    echo (int)$documento['id'];
                                ?>"
                            >
                                Motivo del rechazo
                            </label>


                            <textarea
                                id="observaciones-<?php
                                    echo (int)$documento['id'];
                                ?>"
                                name="observaciones"
                                rows="3"
                                required
                                placeholder="Indicá qué debe corregir el ciudadano..."
                            ></textarea>


                            <div
                                class="admin-documento-rechazo-actions"
                            >

                                <button
                                    type="button"
                                    class="
                                        admin-documentos-btn
                                        admin-documentos-btn-secondary
                                        js-cancelar-rechazo
                                    "
                                    data-documento-id="<?php
                                        echo (int)$documento['id'];
                                    ?>"
                                >
                                    Cancelar
                                </button>


                                <button
                                    type="submit"
                                    class="
                                        admin-documentos-btn
                                        admin-documentos-btn-danger
                                    "
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >
                                        close
                                    </span>

                                    Confirmar rechazo

                                </button>

                            </div>

                        </form>

                    </div>

                </article>

<?php endforeach; ?>

            </div>

<?php else: ?>

            <div
                class="
                    admin-documentos-empty
                    admin-documentos-empty-search
                "
            >

                <span
                    class="material-symbols-outlined"
                >
                    person_search
                </span>

                <h3>
                    Ciudadano no encontrado
                </h3>

                <p>
                    No encontramos documentación asociada
                    al DNI ingresado.
                </p>

            </div>

<?php endif; ?>

        </section>

<?php endif; ?>
        <!-- ==========================================
             LISTADO GENERAL DE DOCUMENTACIÓN PENDIENTE
        =========================================== -->

        <section
            class="admin-documentos-section"
        >

            <div
                class="admin-documentos-section-header"
            >

                <div>

                    <span
                        class="admin-documentos-eyebrow"
                    >
                        REVISIÓN
                    </span>

                    <h2>
                        Documentación pendiente
                    </h2>

                    <p>
                        Ciudadanos que tienen al menos un
                        documento pendiente de revisión.
                    </p>

                </div>


                <div
                    class="admin-documentos-counter"
                >

                    <span
                        class="material-symbols-outlined"
                    >
                        pending_actions
                    </span>

                    <strong>
                        <?php
                        echo $total;
                        ?>
                    </strong>

                    <span>
                        ciudadanos
                    </span>

                </div>

            </div>


<?php if (!empty($usuarios)): ?>

            <div
                class="admin-documentos-users"
            >

<?php foreach ($usuarios as $usuario): ?>

<?php

    $documentosUsuario =
        $usuario['documentos']
        ?? [];

    $pendientesUsuario = 0;
    $aprobadosUsuario = 0;
    $rechazadosUsuario = 0;


    foreach (
        $documentosUsuario
        as $documento
    ) {

        $estadoDocumento =
            strtolower(
                trim(
                    (string)(
                        $documento['estado']
                        ?? ''
                    )
                )
            );


        if (
            $estadoDocumento === 'pendiente'
        ) {

            $pendientesUsuario++;

        } elseif (
            $estadoDocumento === 'aprobado'
        ) {

            $aprobadosUsuario++;

        } elseif (
            $estadoDocumento === 'rechazado'
        ) {

            $rechazadosUsuario++;
        }
    }

?>

                <article
                    class="admin-documentos-user-panel"
                >

                    <!-- ==================================
                         CABECERA DEL CIUDADANO
                    =================================== -->

                    <div
                        class="admin-documentos-user-panel-header"
                    >

                        <div
                            class="admin-documentos-user-identity"
                        >

                            <div
                                class="admin-documentos-user-avatar"
                            >

                                <span
                                    class="material-symbols-outlined"
                                >
                                    person
                                </span>

                            </div>


                            <div>

                                <h3>
                                    <?php
                                    echo $this->e(
                                        ($usuario['nombre']
                                        ?? '')
                                        . ' '
                                        .
                                        ($usuario['apellido']
                                        ?? '')
                                    );
                                    ?>
                                </h3>

                                <p>
                                    DNI:

                                    <strong>
                                        <?php
                                        echo $this->e(
                                            $usuario['dni']
                                            ?? ''
                                        );
                                        ?>
                                    </strong>
                                </p>

                            </div>

                        </div>


                        <div
                            class="admin-documentos-user-summary"
                        >

                            <span
                                class="
                                    admin-documentos-summary-item
                                    admin-documentos-summary-pending
                                "
                            >

                                <strong>
                                    <?php
                                    echo $pendientesUsuario;
                                    ?>
                                </strong>

                                pendientes

                            </span>


                            <span
                                class="
                                    admin-documentos-summary-item
                                    admin-documentos-summary-approved
                                "
                            >

                                <strong>
                                    <?php
                                    echo $aprobadosUsuario;
                                    ?>
                                </strong>

                                aprobados

                            </span>


<?php if ($rechazadosUsuario > 0): ?>

                            <span
                                class="
                                    admin-documentos-summary-item
                                    admin-documentos-summary-rejected
                                "
                            >

                                <strong>
                                    <?php
                                    echo $rechazadosUsuario;
                                    ?>
                                </strong>

                                rechazados

                            </span>

<?php endif; ?>

                        </div>

                    </div>


                    <!-- ==================================
                         DATOS DEL CIUDADANO
                    =================================== -->

                    <div
                        class="admin-documentos-user-contact"
                    >

<?php if (
    !empty(
        $usuario['email']
    )
): ?>

                        <div>

                            <span
                                class="material-symbols-outlined"
                            >
                                mail
                            </span>

                            <span>
                                <?php
                                echo $this->e(
                                    $usuario['email']
                                );
                                ?>
                            </span>

                        </div>

<?php endif; ?>


<?php if (
    !empty(
        $usuario['telefono']
    )
): ?>

                        <div>

                            <span
                                class="material-symbols-outlined"
                            >
                                phone
                            </span>

                            <span>
                                <?php
                                echo $this->e(
                                    $usuario['telefono']
                                );
                                ?>
                            </span>

                        </div>

<?php endif; ?>


<?php if (
    !empty(
        $usuario['domicilio']
    )
): ?>

                        <div>

                            <span
                                class="material-symbols-outlined"
                            >
                                home
                            </span>

                            <span>
                                <?php
                                echo $this->e(
                                    $usuario['domicilio']
                                );
                                ?>
                            </span>

                        </div>

<?php endif; ?>

                    </div>


                    <!-- ==================================
                         DOCUMENTOS DEL CIUDADANO
                    =================================== -->

                    <div
                        class="admin-documentos-user-documents"
                    >

<?php foreach (
    $documentosUsuario
    as $documento
): ?>

<?php

    $estado =
        strtolower(
            trim(
                (string)(
                    $documento['estado']
                    ?? ''
                )
            )
        );


    $tipo =
        strtolower(
            trim(
                (string)(
                    $documento['tipo_documento']
                    ?? ''
                )
            )
        );


    $tipoNombre = match ($tipo) {

        'dni' =>
            'DNI',

        'foto',
        'foto_carnet' =>
            'Foto carnet',

        'asistencia' =>
            'Asistencia',

        'moodle',
        'certificado_moodle' =>
            'Certificado Moodle',

        default =>
            ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $tipo
                )
            )
    };


    $estadoNombre = match ($estado) {

        'aprobado' =>
            'Aprobado',

        'rechazado' =>
            'Rechazado',

        'pendiente' =>
            'Pendiente',

        default =>
            ucfirst(
                $estado
            )
    };


    $estadoIcono = match ($estado) {

        'aprobado' =>
            'check_circle',

        'rechazado' =>
            'cancel',

        'pendiente' =>
            'schedule',

        default =>
            'help'
    };

?>

                        <div
                            class="
                                admin-documentos-document-row
                                admin-documento-estado-<?php
                                    echo $this->e(
                                        $estado
                                    );
                                ?>
                            "
                        >

                            <div
                                class="admin-documentos-document-info"
                            >

                                <div
                                    class="admin-documentos-document-icon"
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >
                                        description
                                    </span>

                                </div>


                                <div>

                                    <h4>
                                        <?php
                                        echo $this->e(
                                            $tipoNombre
                                        );
                                        ?>
                                    </h4>

                                    <p>
                                        <?php
                                        echo $this->e(
                                            $documento[
                                                'nombre_original'
                                            ]
                                            ?? 'Archivo sin nombre'
                                        );
                                        ?>
                                    </p>

                                </div>

                            </div>


                            <div
                                class="
                                    admin-documentos-document-status
                                    admin-documento-status-<?php
                                        echo $this->e(
                                            $estado
                                        );
                                    ?>
                                "
                            >

                                <span
                                    class="material-symbols-outlined"
                                >
                                    <?php
                                    echo $this->e(
                                        $estadoIcono
                                    );
                                    ?>
                                </span>

                                <?php
                                echo $this->e(
                                    $estadoNombre
                                );
                                ?>

                            </div>


                            <div
                                class="admin-documentos-document-actions"
                            >

                                <a
                                    href="<?php
                                        echo $this->e(
                                            $this->getRoute(
                                                'descargar',
                                                (int)$documento['id']
                                            )
                                        );
                                    ?>"
                                    class="
                                        admin-documentos-btn
                                        admin-documentos-btn-secondary
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >
                                        visibility
                                    </span>

                                    Ver

                                </a>


<?php if (
    $estado === 'pendiente'
): ?>

                                <form
                                    action="<?php
                                        echo $this->e(
                                            $this->getRoute(
                                                'aprobar',
                                                (int)$documento['id']
                                            )
                                        );
                                    ?>"
                                    method="POST"
                                >

                                    <button
                                        type="submit"
                                        class="
                                            admin-documentos-btn
                                            admin-documentos-btn-success
                                        "
                                    >

                                        <span
                                            class="material-symbols-outlined"
                                        >
                                            check
                                        </span>

                                        Aprobar

                                    </button>

                                </form>


                                <button
                                    type="button"
                                    class="
                                        admin-documentos-btn
                                        admin-documentos-btn-danger
                                        js-rechazar-documento
                                    "
                                    data-documento-id="<?php
                                        echo (int)$documento['id'];
                                    ?>"
                                    data-contexto="listado"
                                >

                                    <span
                                        class="material-symbols-outlined"
                                    >
                                        close
                                    </span>

                                    Rechazar

                                </button>

<?php endif; ?>

                            </div>


                            <!-- ==================================
                                 FORMULARIO DE RECHAZO
                            =================================== -->

                            <div
                                id="rechazo-listado-<?php
                                    echo (int)$documento['id'];
                                ?>"
                                class="
                                    admin-documento-rechazo
                                    js-formulario-rechazo
                                "
                                hidden
                            >

                                <form
                                    action="<?php
                                        echo $this->e(
                                            $this->getRoute(
                                                'rechazar',
                                                (int)$documento['id']
                                            )
                                        );
                                    ?>"
                                    method="POST"
                                >

                                    <label
                                        for="observaciones-listado-<?php
                                            echo (int)$documento['id'];
                                        ?>"
                                    >
                                        Motivo del rechazo
                                    </label>


                                    <textarea
                                        id="observaciones-listado-<?php
                                            echo (int)$documento['id'];
                                        ?>"
                                        name="observaciones"
                                        rows="3"
                                        required
                                        placeholder="Indicá qué debe corregir el ciudadano..."
                                    ></textarea>


                                    <div
                                        class="admin-documento-rechazo-actions"
                                    >

                                        <button
                                            type="button"
                                            class="
                                                admin-documentos-btn
                                                admin-documentos-btn-secondary
                                                js-cancelar-rechazo
                                            "
                                            data-documento-id="<?php
                                                echo (int)$documento['id'];
                                            ?>"
                                            data-contexto="listado"
                                        >
                                            Cancelar
                                        </button>


                                        <button
                                            type="submit"
                                            class="
                                                admin-documentos-btn
                                                admin-documentos-btn-danger
                                            "
                                        >

                                            <span
                                                class="material-symbols-outlined"
                                            >
                                                close
                                            </span>

                                            Confirmar rechazo

                                        </button>

                                    </div>

                                </form>

                            </div>

                        </div>

<?php endforeach; ?>

                    </div>

                </article>

<?php endforeach; ?>

            </div>
<?php if ($totalPaginas > 1): ?>

            <!-- ==========================================
                 PAGINACIÓN
            =========================================== -->

            <nav
                class="admin-documentos-pagination"
                aria-label="Paginación de documentación"
            >

                <?php if ($tieneAnterior): ?>

                    <a
                        href="<?php
                            echo $this->e(
                                $this->getRoute(
                                    'documentos'
                                )
                                . '?pagina='
                                . ($pagina - 1)
                                . '&limite='
                                . $limite
                            );
                        ?>"
                        class="
                            app-vista-button
                            app-vista-button--secondary
                            admin-documentos-pagination-button
                        "
                    >

                        <span
                            class="material-symbols-outlined"
                        >
                            chevron_left
                        </span>

                        Anterior

                    </a>

                <?php endif; ?>


                <div
                    class="admin-documentos-pagination-controls"
                >

                    <?php

                    /*
                     * Mostramos todas las páginas.
                     *
                     * Más adelante, si el número de páginas
                     * crece demasiado, podemos limitar el
                     * rango mostrado.
                     */
                    for (
                        $i = 1;
                        $i <= $totalPaginas;
                        $i++
                    ):

                    ?>

                        <a
                            href="<?php
                                echo $this->e(
                                    $this->getRoute(
                                        'documentos'
                                    )
                                    . '?pagina='
                                    . $i
                                    . '&limite='
                                    . $limite
                                );
                            ?>"
                            class="
                                admin-documentos-pagination-page
                                <?php
                                if (
                                    $i === $pagina
                                ) {
                                    echo
                                        'admin-documentos-pagination-page--active';
                                }
                                ?>
                            "
                        >

                            <?php
                            echo $i;
                            ?>

                        </a>

                    <?php endfor; ?>

                </div>


                <?php if ($tieneSiguiente): ?>

                    <a
                        href="<?php
                            echo $this->e(
                                $this->getRoute(
                                    'documentos'
                                )
                                . '?pagina='
                                . ($pagina + 1)
                                . '&limite='
                                . $limite
                            );
                        ?>"
                        class="
                            app-vista-button
                            app-vista-button--secondary
                            admin-documentos-pagination-button
                        "
                    >

                        Siguiente

                        <span
                            class="material-symbols-outlined"
                        >
                            chevron_right
                        </span>

                    </a>

                <?php endif; ?>

            </nav>


            <div
                class="admin-documentos-pagination-info"
            >

                Mostrando

                <strong>
                    <?php

                    $desde =
                        (
                            ($pagina - 1)
                            * $limite
                        ) + 1;

                    $hasta =
                        min(
                            $pagina * $limite,
                            $total
                        );

                    echo $desde;
                    ?>
                    –
                    <?php
                    echo $hasta;
                    ?>
                </strong>

                de

                <strong>
                    <?php
                    echo $total;
                    ?>
                </strong>

                ciudadanos

            </div>

<?php endif; ?>

<?php if (
    empty($usuarios)
    && $busqueda === ''
): ?>

            <div
                class="admin-documentos-empty"
            >

                <span
                    class="material-symbols-outlined"
                >
                    task_alt
                </span>

                <h3>
                    No hay documentación pendiente
                </h3>

                <p>
                    Actualmente no hay ciudadanos con documentos
                    pendientes de revisión.
                </p>

            </div>

<?php endif; ?>

<?php endif; ?>

        </section>


        <!-- ==========================================
             INFORMACIÓN DEL PANEL
        =========================================== -->

        <section
            class="admin-documentos-info-card"
        >

            <div
                class="admin-documentos-info-icon"
            >

                <span
                    class="material-symbols-outlined"
                >
                    info
                </span>

            </div>


            <div>

                <h3>
                    Gestión de documentación
                </h3>

                <p>
                    Los documentos pendientes requieren una
                    revisión administrativa. Al aprobar o
                    rechazar un documento, el estado y la
                    observación quedan registrados en el sistema.
                </p>

            </div>

        </section>
                </section>

    </main>

<?php

        $this->getFooter();

    }
}