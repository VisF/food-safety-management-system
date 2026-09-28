<?php

declare(strict_types=1);

/**
 * Vista: Panel administrativo.
 *
 * Los datos son preparados por AdminDashboardControlador
 * y AdminService.
 */

require_once __DIR__ . '/BaseVista.php';

class PanelAdminVista extends BaseVista
{
    /**
     * Devuelve la clase visual correspondiente
     * al estilo de una tarjeta estadística.
     */
    private function getStatCardClass(string $style): string
    {
        return match ($style) {
            'success' =>
                'panel-admin-stat panel-admin-stat--success',

            'danger' =>
                'panel-admin-stat panel-admin-stat--danger',

            'secondary' =>
                'panel-admin-stat panel-admin-stat--secondary',

            default =>
                'panel-admin-stat panel-admin-stat--primary',
        };
    }

    /**
     * Renderiza el dashboard administrativo.
     */
    public function mostrar(array $panelAdminData = []): void
    {
        $pageTitle =
            (string)(
                $panelAdminData['page_title']
                ?? 'Panel Administrativo'
            );

        $stats =
            (array)(
                $panelAdminData['stats']
                ?? []
            );

        $activities =
            array_slice(
                (array)(
                    $panelAdminData['activities']
                    ?? []
                ),
                0,
                5
            );

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

    <link
        rel="stylesheet"
        href="<?= $this->e(
            $this->baseURL . 'css/Views/panel_admin.css'
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


<main class="contenido-principal contenido-principal--ancho">

    <div class="panel-admin">


        <!-- =====================================================
             CABECERA
             ===================================================== -->

        <section class="panel-admin__cabecera">

            <p class="panel-admin__etiqueta">
                Administración
            </p>

            <h1 class="panel-admin__titulo">
                Panel Administrativo
            </h1>

            <p class="panel-admin__descripcion">
                Control de emisión de carnets para manipulación
                de alimentos.
            </p>

        </section>


        <!-- =====================================================
             ESTADÍSTICAS
             ===================================================== -->

        <?php if (!empty($stats)): ?>

            <section
                class="panel-admin__estadisticas"
                aria-label="Estadísticas"
            >

                <?php foreach ($stats as $stat): ?>

                    <?php
                    $style =
                        (string)(
                            $stat['style']
                            ?? 'primary'
                        );

                    $label =
                        (string)(
                            $stat['label']
                            ?? ''
                        );

                    $value =
                        (string)(
                            $stat['value']
                            ?? '0'
                        );

                    $icon =
                        (string)(
                            $stat['icon']
                            ?? 'analytics'
                        );
                    ?>

                    <article
                        class="<?= $this->e(
                            $this->getStatCardClass($style)
                        ) ?>"
                    >

                        <p class="panel-admin-stat__etiqueta">
                            <?= $this->e($label) ?>
                        </p>

                        <div class="panel-admin-stat__fila">

                            <span
                                class="panel-admin-stat__numero"
                            >
                                <?= $this->e($value) ?>
                            </span>

                            <span
                                class="material-symbols-outlined panel-admin-stat__icono"
                                aria-hidden="true"
                            >
                                <?= $this->e($icon) ?>
                            </span>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>


        <!-- =====================================================
             ACCIONES RÁPIDAS
             ===================================================== -->

        <section class="panel-admin__seccion">

            <div class="panel-admin__seccion-cabecera">

                <div>

                    <h2 class="panel-admin__seccion-titulo">
                        Acciones rápidas
                    </h2>

                    <p class="panel-admin__seccion-descripcion">
                        Accesos directos a las principales funciones
                        administrativas.
                    </p>

                </div>

            </div>


            <div class="panel-admin__acciones">

                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('crear_examen')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        calendar_today
                    </span>

                    <span>
                        Crear fecha de examen
                    </span>

                </a>


                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('admin_examenes')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        event_note
                    </span>

                    <span>
                        Gestionar exámenes
                    </span>

                </a>


                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('admin_documentos')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        fact_check
                    </span>

                    <span>
                        Gestionar documentación
                    </span>

                </a>


                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('admin_usuarios')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        group
                    </span>

                    <span>
                        Administrar usuarios
                    </span>

                </a>


                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('admin_carnets')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        badge
                    </span>

                    <span>
                        Gestionar carnets
                    </span>

                </a>


                <a
                    class="panel-admin__accion app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute('admin_actividad')
                    ) ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        manage_history
                    </span>

                    <span>
                        Actividad del sistema
                    </span>

                </a>

            </div>

        </section>


        <!-- =====================================================
             ACTIVIDAD RECIENTE
             ===================================================== -->

        <section class="panel-admin__actividad">

            <div class="panel-admin__actividad-cabecera">

                <div>

                    <h2 class="panel-admin__seccion-titulo">
                        Actividad reciente
                    </h2>

                    <p class="panel-admin__seccion-descripcion">
                        Últimas inscripciones registradas.
                    </p>

                </div>

                <a
                    class="panel-admin__ver-todas"
                    href="<?= $this->e(
                        $this->getRoute('admin_actividad')
                    ) ?>"
                >
                    Ver todos
                </a>

            </div>


            <?php if (!empty($activities)): ?>

                <div class="panel-admin__lista">

                    <?php foreach ($activities as $activity): ?>

                        <?php
                        $nombre =
                            (string)(
                                $activity['nombre']
                                ?? ''
                            );

                        $dni =
                            (string)(
                                $activity['dni']
                                ?? ''
                            );

                        $estado =
                            (string)(
                                $activity['estado']
                                ?? 'SIN ESTADO'
                            );

                        $estadoClass =
                            strtolower(
                                trim(
                                    (string)(
                                        $activity['estado_class']
                                        ?? ''
                                    )
                                )
                            );

                        $estadoClass =
                            preg_replace(
                                '/[^a-z0-9_-]+/',
                                '-',
                                $estadoClass
                            );
                        ?>

                        <article class="panel-admin__actividad-fila">

                            <div class="panel-admin__actividad-persona">

                                <div class="panel-admin__avatar">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >
                                        person
                                    </span>

                                </div>


                                <div>

                                    <p class="panel-admin__nombre">
                                        <?= $this->e($nombre) ?>
                                    </p>

                                    <p class="panel-admin__dni">
                                        DNI <?= $this->e($dni) ?>
                                    </p>

                                </div>

                            </div>


                            <div class="panel-admin__estado">

                                <span class="panel-admin__estado-label">
                                    Estado de trámite
                                </span>

                                <span
                                    class="<?= $this->e(
                                        'panel-admin__estado-chip panel-admin__estado-chip--'
                                        . $estadoClass
                                    ) ?>"
                                >
                                    <?= $this->e($estado) ?>
                                </span>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="panel-admin__vacio">

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        history
                    </span>

                    <p>
                        No hay actividad reciente.
                    </p>

                </div>

            <?php endif; ?>

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