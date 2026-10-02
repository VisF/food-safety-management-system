<?php

declare(strict_types=1);

/**
 * Vista: admin_curso_inscriptos.php
 *
 * Propósito:
 * Mostrar los usuarios inscriptos en un curso.
 *
 * Responsabilidades:
 * - Mostrar información básica del curso.
 * - Mostrar cantidad de inscriptos.
 * - Mostrar listado de usuarios.
 * - Mostrar información adaptada a móvil.
 */

require_once __DIR__ . '/BaseVista.php';

class CursoInscriptosAdminVista extends BaseVista
{
    /**
     * Genera el encabezado de la página.
     */
    private function getHeader(array $data): void
    {
        $assetBase = rtrim(
            dirname($_SERVER['SCRIPT_NAME'] ?? ''),
            '/\\'
        );

        if (
            preg_match(
                '#/vistas$#',
                $assetBase
            ) === 1
        ) {
            $assetBase = (string) preg_replace(
                '#/vistas$#',
                '',
                $assetBase
            );
        }

        if ($assetBase === '') {
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
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php
        echo $this->e(
            $data['page_title']
            ?? 'Inscriptos del curso'
        );
        ?>
    </title>

    <script
        src="<?php echo $assetBase; ?>/js/tailwind-config.js"
    ></script>

    <script
        src="https://cdn.tailwindcss.com?plugins=forms,container-queries"
    ></script>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    >

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap"
    >

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    >

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/base.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/components.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/ui.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/app.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/Views/admin-curso-inscriptos.css"
    >

</head>

<body
    class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano"
>

<?php

$page_title =
    $data['page_title']
    ?? 'Inscriptos del curso';

include __DIR__ . '/header.php';

}

public function mostrar(array $data = []): void
{
    if (empty($data)) {

        $data = [
            'page_title' => 'Inscriptos del curso',

            'curso' => [
                'id' => 0,
                'nombre' => '',
                'descripcion' => '',
                'modalidad' => '',
                'fecha_inicio' => '',
                'hora_inicio' => '',
                'ubicacion' => '',
                'cupos' => 0
            ],

            'inscriptos' => []
        ];
    }

    /*
     * Extraemos los datos recibidos por el controlador.
     *
     * El controlador envía:
     * - curso
     * - inscriptos
     */
    $curso =
        $data['curso']
        ?? [];

    $inscriptos =
        $data['inscriptos']
        ?? [];

    $this->getHeader($data);

?>

<main
    class="contenido-principal contenido-principal--ancho"
>

    <div class="curso-inscriptos">

        <!-- =====================================================
             CABECERA
             ===================================================== -->

        <section class="curso-inscriptos__encabezado">

            <div>

                <h2
                    class="font-headline-lg text-headline-lg text-primary"
                >
                    Inscriptos del curso
                </h2>

                <p
                    class="font-body-md text-body-md text-on-surface-variant"
                >

                    <?php

                    echo $this->e(
                        $curso['nombre']
                        ?? 'Curso'
                    );

                    ?>

                </p>

            </div>

            <div class="curso-inscriptos__acciones">

                <a
                    href="<?php
                        echo $this->getRoute(
                            'editar_curso',
                            (int)($curso['id'] ?? 0)
                        );
                    ?>"
                    class="app-vista-button app-vista-button--secondary"
                >
                    Editar curso
                </a>

                <a
                    href="<?php
                        echo $this->getRoute(
                            'admin_cursos'
                        );
                    ?>"
                    class="app-vista-button app-vista-button--secondary"
                >
                    Volver
                </a>

            </div>

        </section>


        <!-- =====================================================
             INFORMACIÓN DEL CURSO
             ===================================================== -->

        <section class="app-vista-card">

            <div class="curso-inscriptos__encabezado">

                <div>

                    <h3
                        class="font-headline-md text-headline-md text-on-surface"
                    >
                        Información del curso
                    </h3>

                </div>

            </div>

            <div class="grid gap-4 md:grid-cols-4">

                <div>

                    <span
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >
                        Modalidad
                    </span>

                    <p
                        class="font-body-md text-body-md text-on-surface"
                    >
                        <?php

                        echo $this->e(
                            ucfirst(
                                (string)(
                                    $curso['modalidad']
                                    ?? ''
                                )
                            )
                        );

                        ?>
                    </p>

                </div>

                <div>

                    <span
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >
                        Fecha de inicio
                    </span>

                    <p
                        class="font-body-md text-body-md text-on-surface"
                    >
                        <?php

                        echo $this->e(
                            (string)(
                                $curso['fecha_inicio']
                                ?? ''
                            )
                        );

                        ?>
                    </p>

                </div>

                <div>

                    <span
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >
                        Hora
                    </span>

                    <p
                        class="font-body-md text-body-md text-on-surface"
                    >
                        <?php

                        $hora =
                            (string)(
                                $curso['hora_inicio']
                                ?? ''
                            );

                        echo $this->e(
                            $hora !== ''
                                ? substr($hora, 0, 5)
                                : ''
                        );

                        ?>
                    </p>

                </div>

                <div>

                    <span
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >
                        Ubicación
                    </span>

                    <p
                        class="font-body-md text-body-md text-on-surface"
                    >
                        <?php

                        echo $this->e(
                            (string)(
                                $curso['ubicacion']
                                ?? ''
                            )
                        );

                        ?>
                    </p>

                </div>

            </div>

        </section>


        <!-- =====================================================
             LISTADO DE INSCRIPTOS
             ===================================================== -->

        <section class="app-vista-card">

            <div class="curso-inscriptos__encabezado">

                <div>

                    <h3
                        class="font-headline-md text-headline-md text-on-surface"
                    >
                        Usuarios inscriptos
                    </h3>

                    <p
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >

                        <?php

                        $cantidadInscriptos =
                            count($inscriptos);

                        $cupos =
                            (int)(
                                $curso['cupos']
                                ?? 0
                            );

                        echo $cantidadInscriptos;

                        echo $cantidadInscriptos === 1
                            ? ' inscripto'
                            : ' inscriptos';

                        echo ' de ';

                        echo $cupos;

                        echo ' cupos.';

                        ?>

                    </p>

                </div>

            </div>


            <!-- =================================================
                 TABLA DESKTOP
                 ================================================= -->

            <div class="curso-inscriptos__tabla">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Nombre
                            </th>

                            <th>
                                Apellido
                            </th>

                            <th>
                                DNI
                            </th>

                            <th>
                                Fecha de inscripción
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($inscriptos)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="curso-inscriptos__vacio"
                            >
                                No hay usuarios inscriptos
                                en este curso.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($inscriptos as $inscripto): ?>

                            <tr>

                                <td>

                                    <?php

                                    echo $this->e(
                                        (string)(
                                            $inscripto['nombre']
                                            ?? ''
                                        )
                                    );

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    echo $this->e(
                                        (string)(
                                            $inscripto['apellido']
                                            ?? ''
                                        )
                                    );

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    echo $this->e(
                                        (string)(
                                            $inscripto['dni']
                                            ?? ''
                                        )
                                    );

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    $fecha =
                                        (string)(
                                            $inscripto[
                                                'fecha_inscripcion'
                                            ]
                                            ?? ''
                                        );

                                    echo $this->e(
                                        $fecha !== ''
                                            ? date(
                                                'd/m/Y H:i',
                                                strtotime($fecha)
                                            )
                                            : ''
                                    );

                                    ?>

                                </td>

                                <td>
                                    <span
                                        class="curso-inscripto__estado"
                                    >
                                        <?php
                                        echo $this->e(
                                            (string)(
                                                $inscripto['estado']
                                                ?? 'Sin estado'
                                            )
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex flex-wrap gap-2">

                                        <form
                                            method="POST"
                                            action="<?php
                                                echo $this->getRoute(
                                                    'aprobar_inscripcion_curso',
                                                    (int)($curso['id'] ?? 0),
                                                    (int)($inscripto['id'] ?? 0)
                                                );
                                            ?>"
                                        >
                                            <?php echo $this->getCsrfInput(); ?>

                                            <button
                                                type="submit"
                                                class="app-vista-button app-vista-button--primary"
                                            >
                                                Aprobar
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="<?php
                                                echo $this->getRoute(
                                                    'desaprobar_inscripcion_curso',
                                                    (int)($curso['id'] ?? 0),
                                                    (int)($inscripto['id'] ?? 0)
                                                );
                                            ?>"
                                        >
                                            <?php echo $this->getCsrfInput(); ?>

                                            <button
                                                type="submit"
                                                class="app-vista-button app-vista-button--danger"
                                            >
                                                Desaprobar
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 TARJETAS MOBILE
                 ================================================= -->

            <div class="curso-inscriptos__mobile">

                <?php if (empty($inscriptos)): ?>

                    <div class="curso-inscriptos__vacio">

                        No hay usuarios inscriptos
                        en este curso.

                    </div>

                <?php else: ?>

                    <?php foreach ($inscriptos as $inscripto): ?>

                        <article
                            class="curso-inscripto-card"
                        >

                            <div
                                class="curso-inscripto-card__nombre"
                            >

                                <?php

                                echo $this->e(
                                    trim(
                                        (string)(
                                            $inscripto['nombre']
                                            ?? ''
                                        )
                                        . ' ' .
                                        (string)(
                                            $inscripto['apellido']
                                            ?? ''
                                        )
                                    )
                                );

                                ?>

                            </div>

                            <div
                                class="curso-inscripto-card__dato"
                            >

                                <span>
                                    DNI
                                </span>

                                <strong>

                                    <?php

                                    echo $this->e(
                                        (string)(
                                            $inscripto['dni']
                                            ?? ''
                                        )
                                    );

                                    ?>

                                </strong>

                            </div>

                            <div
                                class="curso-inscripto-card__dato"
                            >

                                <span>
                                    Fecha
                                </span>

                                <strong>

                                    <?php

                                    $fecha =
                                        (string)(
                                            $inscripto[
                                                'fecha_inscripcion'
                                            ]
                                            ?? ''
                                        );

                                    echo $this->e(
                                        $fecha !== ''
                                            ? date(
                                                'd/m/Y H:i',
                                                strtotime($fecha)
                                            )
                                            : ''
                                    );

                                    ?>

                                </strong>

                            </div>

                            <div
                                class="curso-inscripto-card__dato"
                            >

                                <span>
                                    Estado
                                </span>

                                <strong>

                                    <span
                                        class="curso-inscripto__estado"
                                    >

                                        <?php

                                        echo $this->e(
                                            (string)(
                                                $inscripto['estado']
                                                ?? 'Sin estado'
                                            )
                                        );

                                        ?>

                                    </span>

                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </div>

</main>

<?php

$this->getFooter();

?>

</body>

</html>

<?php

}

}