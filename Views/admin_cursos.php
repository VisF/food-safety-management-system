<?php

declare(strict_types=1);

/**
 * Vista: admin_cursos.php
 *
 * Propósito:
 * Gestión administrativa de cursos.
 *
 * Responsabilidades:
 * - Mostrar listado de cursos.
 * - Acceder a creación de un nuevo curso.
 * - Acceder a edición.
 * - Activar / desactivar cursos.
 */

require_once __DIR__ . '/BaseVista.php';

class CursosAdminVista extends BaseVista
{
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

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?php echo $this->e($data['page_title']); ?></title>

    <script src="<?php echo $assetBase; ?>/js/tailwind-config.js"></script>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/base.css">

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/components.css">

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/ui.css">

    <link
        rel="stylesheet"
        href="<?php echo $assetBase; ?>/css/app.css">

</head>

<body class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano">

<?php

$page_title = $data['page_title'];

include __DIR__ . '/header.php';

}

public function mostrar(array $data = []): void
{
    if (empty($data)) {

        $data = [
            'page_title' => 'Gestión de Cursos',
            'cursos' => []
        ];

    }

    $this->getHeader($data);

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8 max-w-[430px] mx-auto md:max-w-none">

        <section class="space-y-1 px-1">

            <h2 class="font-headline-lg text-headline-lg text-primary">

                Gestión de Cursos

            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">

                Administración de cursos disponibles para inscripción.

            </p>

        </section>

        <section class="app-vista-card">

            <div class="examen-admin__encabezado">

                <div>

                    <h3 class="font-headline-md text-headline-md text-on-surface">

                        Listado de Cursos

                    </h3>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">

                        Visualice, edite, active o desactive los cursos registrados.

                    </p>

                </div>

                <div class="examen-admin__herramientas">

                    <a
                        href="<?php echo $this->getRoute('crear_curso'); ?>"
                        class="app-vista-button app-vista-button--primary">

                        <span class="material-symbols-outlined">
                            add
                        </span>

                        <span>
                            Nuevo curso
                        </span>

                    </a>

                </div>

            </div>

            <div class="examen-admin__tabla">

                <table class="app-vista-table">

                    <thead>

                        <tr>

                            <th>Curso</th>
                            <th>Modalidad</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Ubicación</th>
                            <th>Cupos</th>
                            <th>Estado</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

<?php if (empty($data['cursos'])): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="app-vista-table__empty">

                                No hay cursos registrados.

                            </td>

                        </tr>

<?php else: ?>

<?php foreach ($data['cursos'] as $curso): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php echo $this->e($curso['nombre']); ?>
                                </strong>

                            </td>

                            <td>

                                <?php echo $this->e($curso['modalidad']); ?>

                            </td>

                            <td>

                                <?php
                                echo !empty($curso['fecha_inicio'])
                                    ? date('d/m/Y', strtotime($curso['fecha_inicio']))
                                    : '-';
                                ?>

                            </td>

                            <td>

                                <?php
                                echo !empty($curso['hora_inicio'])
                                    ? substr($this->e($curso['hora_inicio']), 0, 5)
                                    : '-';
                                ?>

                            </td>

                            <td>

                                <?php echo $this->e($curso['ubicacion']); ?>

                            </td>

                            <td>
                                <?php echo $this->e(
                                    (string)($curso['inscriptos'] ?? 0)
                                ); ?>
                                /
                                <?php echo $this->e(
                                    (string)($curso['cupos'] ?? 0)
                                ); ?>
                            </td>

                            <td>

                                <span
                                    class="estado <?php echo (int)$curso['activo'] === 1
                                        ? 'estado--activo'
                                        : 'estado--inactivo'; ?>">

                                    <?php echo (int)$curso['activo'] === 1
                                        ? 'ACTIVO'
                                        : 'INACTIVO'; ?>

                                </span>

                            </td>

                            <td>

                                <div class="examen-admin__acciones">

                                    <a
                                        href="<?php echo $this->getRoute(
                                            'editar_curso',
                                            (int)$curso['id']
                                        ); ?>"
                                        class="app-vista-button app-vista-button--primary">

                                        Editar

                                    </a>
                                    <a
                                        href="<?= $this->getRoute(
                                            'inscriptos_curso',
                                            (int)$curso['id']
                                        ); ?>"
                                        class="app-vista-button app-vista-button--secondary">

                                        Inscriptos

                                    </a>

<?php if ((int)$curso['activo'] === 1): ?>

                                    <form
                                        action="<?php echo $this->getRoute(
                                            'desactivar_curso',
                                            (int)$curso['id']
                                        ); ?>"
                                        method="post">

                                        <?= $this->getCsrfInput() ?>

                                        <button
                                            class="app-vista-button app-vista-button--danger"
                                            type="submit">

                                            Desactivar

                                        </button>

                                    </form>

<?php else: ?>

                                    <form
                                        action="<?php echo $this->getRoute(
                                            'activar_curso',
                                            (int)$curso['id']
                                        ); ?>"
                                        method="post">

                                        <?= $this->getCsrfInput() ?>

                                        <button
                                            class="app-vista-button app-vista-button--success"
                                            type="submit">

                                            Activar

                                        </button>

                                    </form>

<?php endif; ?>

                                </div>

                            </td>

                        </tr>

<?php endforeach; ?>

<?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="examen-admin__mobile">

<?php if (empty($data['cursos'])): ?>

                <p class="app-vista-table__empty">

                    No hay cursos registrados.

                </p>

<?php else: ?>

<?php foreach ($data['cursos'] as $curso): ?>

                <article class="examen-mobile-card">

                    <div class="examen-mobile-card__header">

                        <div>

                            <strong>
                                <?php echo $this->e($curso['nombre']); ?>
                            </strong>

                        </div>

                        <span
                            class="estado <?php echo (int)$curso['activo'] === 1
                                ? 'estado--activo'
                                : 'estado--inactivo'; ?>">

                            <?php echo (int)$curso['activo'] === 1
                                ? 'ACTIVO'
                                : 'INACTIVO'; ?>

                        </span>

                    </div>

                    <div class="examen-mobile-card__datos">

                        <p>

                            <strong>📚 Modalidad:</strong>

                            <?php echo $this->e($curso['modalidad']); ?>

                        </p>

                        <p>

                            <strong>📅 Fecha:</strong>

                            <?php
                            echo !empty($curso['fecha_inicio'])
                                ? date('d/m/Y', strtotime($curso['fecha_inicio']))
                                : '-';
                            ?>

                        </p>

                        <p>

                            <strong>🕒 Hora:</strong>

                            <?php
                            echo !empty($curso['hora_inicio'])
                                ? substr($this->e($curso['hora_inicio']), 0, 5)
                                : '-';
                            ?>

                        </p>

                        <p>

                            <strong>📍 Ubicación:</strong>

                            <?php echo $this->e($curso['ubicacion']); ?>

                        </p>

                        <p>

                            <strong>👥 Cupos:</strong>

                            <?php echo $this->e((string)$curso['cupos']); ?>

                        </p>

<?php if (!empty($curso['descripcion'])): ?>

                        <p>

                            <strong>📝 Descripción:</strong>

                            <?php echo $this->e($curso['descripcion']); ?>

                        </p>

<?php endif; ?>

                    </div>

                    <div class="examen-mobile-card__acciones">

                        <a
                            class="app-vista-button app-vista-button--primary"
                            href="<?php echo $this->getRoute(
                                'editar_curso',
                                (int)$curso['id']
                            ); ?>">

                            Editar

                        </a>

<?php if ((int)$curso['activo'] === 1): ?>

                        <form
                            method="post"
                            action="<?php echo $this->getRoute(
                                'desactivar_curso',
                                (int)$curso['id']
                            ); ?>">

                            <?= $this->getCsrfInput() ?>

                            <button
                                class="app-vista-button app-vista-button--danger"
                                type="submit">

                                Desactivar

                            </button>

                        </form>

<?php else: ?>

                        <form
                            method="post"
                            action="<?php echo $this->getRoute(
                                'activar_curso',
                                (int)$curso['id']
                            ); ?>">

                            <?= $this->getCsrfInput() ?>

                            <button
                                class="app-vista-button app-vista-button--success"
                                type="submit">

                                Activar

                            </button>

                        </form>

<?php endif; ?>

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