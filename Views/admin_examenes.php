<?php

declare(strict_types=1);

/**
 * Vista: examen_admin.php
 *
 * Propósito:
 * Gestión administrativa de exámenes.
 *
 * Responsabilidades:
 * - Mostrar listado de exámenes.
 * - Acceder a creación de un nuevo examen.
 * - Acceder al detalle.
 * - Acceder a edición.
 * - Activar / desactivar exámenes.
 */
class ExamenAdminVista
{
    private string $baseURL = '/ManipulacionDeAlimentos/';

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
        content="width=device-width, initial-scale=1.0"
        name="viewport">

    <title>
        <?php echo $this->e($data['page_title']); ?>
    </title>

    <script src="<?php echo $assetBase; ?>/js/tailwind-config.js"></script>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">

    <link
        href="<?php echo $assetBase; ?>/css/base.css"
        rel="stylesheet">

    <link
        href="<?php echo $assetBase; ?>/css/components.css"
        rel="stylesheet">

    <link
        href="<?php echo $assetBase; ?>/css/ui.css"
        rel="stylesheet">

    <link
        href="<?php echo $assetBase; ?>/css/app.css"
        rel="stylesheet">
    <link
        href="<?php echo $assetBase; ?>/css/Views/admin-examenes.css"
        rel="stylesheet">


</head>

<body class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano">

<?php

$page_title = 'Gestión de Exámenes';

include __DIR__ . '/header.php';

    }

    private function getFooter(): void
    {
        include __DIR__ . '/footer.php';

?>
</body>

</html>
<?php
    }

    private function e(mixed $valor): string
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function getRoute(string $route, ?int $id = null): string
    {
        return match ($route) {

            'admin' =>
                '/manipulacionDeAlimentos/admin',

            'nuevo' =>
                '/manipulacionDeAlimentos/admin/examenes/nuevo',

            'detalle' =>
                '/manipulacionDeAlimentos/admin/examenes/' . $id,

            'editar' =>
                '/manipulacionDeAlimentos/admin/examenes/' . $id . '/editar',

            'activar' =>
                '/manipulacionDeAlimentos/admin/examenes/' . $id . '/activar',

            'desactivar' =>
                '/manipulacionDeAlimentos/admin/examenes/' . $id . '/desactivar',

            default =>
                '#',
        };
    }
    public function mostrar(array $data = []): void
    {
        
        if (empty($data)) {

            $data = [
                'page_title' => 'Gestión de Exámenes',
                'examenes' => []
            ];

        }

        $this->getHeader($data);

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8 max-w-[430px] mx-auto md:max-w-none">

        <section class="space-y-1 px-1">

            <h2 class="font-headline-lg text-headline-lg text-primary">
                Gestión de Exámenes
            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">
                Administración de fechas de examen, cupos y estado de cada evaluación.
            </p>

        </section>

        <section class="app-vista-card">

            <div class="examen-admin__encabezado">

                <div>

                    <h3 class="font-headline-md text-headline-md text-on-surface">
                        Listado de Exámenes
                    </h3>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        Visualice, edite o desactive las fechas de examen disponibles.
                    </p>

                </div>

                <a
                    href="<?php echo $this->getRoute('nuevo'); ?>"
                    class="app-vista-button app-vista-button--primary">

                    <span class="material-symbols-outlined">
                        add
                    </span>

                    <span>
                        Nuevo examen
                    </span>

                </a>

            </div>

            <div class="examen-admin__tabla">
                <table class="app-vista-table">

                    <thead>

                        <tr>

                            <th>Fecha</th>

                            <th>Hora</th>

                            <th>Lugar</th>

                            <th>Aula</th>

                            <th>Cupos</th>

                            <th>Disponibles</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

<?php if (empty($data['examenes'])): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="app-vista-table__empty">

                                No hay exámenes registrados.

                            </td>

                        </tr>

<?php else: ?>

<?php foreach ($data['examenes'] as $examen): ?>

                        <tr>

                            <td>

                                <?php echo $this->e($examen['fecha']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($examen['hora']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($examen['lugar']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($examen['aula']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($examen['cupos_totales']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($examen['cupos_disponibles']); ?>

                            </td>

                            <td>

                                <span class="estado <?php echo $examen['estado'] === 'ACTIVO'
                                    ? 'estado--activo'
                                    : 'estado--inactivo'; ?>">
                                    <?php echo $this->e($examen['estado']); ?>
                                </span>

                            </td>

                            <td>

                                <div class="examen-admin__acciones">

                                    <a
                                        class="app-vista-button app-vista-button--secondary"
                                        href="<?php echo $this->getRoute('detalle', (int)$examen['id']); ?>">

                                        Ver

                                    </a>

                                    <a
                                        class="app-vista-button app-vista-button--primary"
                                        href="<?php echo $this->getRoute('editar', (int)$examen['id']); ?>">

                                        Editar

                                    </a>

<?php if (($examen['estado'] ?? '') === 'ACTIVO'): ?>

                                    <form
                                        action="<?php echo $this->getRoute('desactivar', (int)$examen['id']); ?>"
                                        method="post">

                                        <button
                                            class="app-vista-button app-vista-button--danger"
                                            type="submit">

                                            Desactivar

                                        </button>

                                    </form>

<?php else: ?>

                                    <form
                                        action="<?php echo $this->getRoute('activar', (int)$examen['id']); ?>"
                                        method="post">

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

        </section>
                </div>

    </main>

<?php

        $this->getFooter();
    }
}