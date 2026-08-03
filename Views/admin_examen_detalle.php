<?php

declare(strict_types=1);

/**
 * Vista: admin_examen_detalle.php
 *
 * Propósito:
 * Mostrar el detalle completo de un examen y
 * el listado de alumnos inscriptos.
 *
 * Responsabilidades:
 * - Mostrar información del examen.
 * - Mostrar estadísticas.
 * - Mostrar alumnos inscriptos.
 * - Servir como pantalla principal de administración
 *   del examen.
 */
class ExamenDetalleVista
{
    private string $baseURL = '/ManipulacionDeAlimentos/';

    private function getHeader(array $data): void
    {
        $assetBase = rtrim(
            dirname($_SERVER['SCRIPT_NAME'] ?? ''),
            '/\\'
        );

        if (preg_match('#/vistas$#', $assetBase) === 1) {

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
    lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>

        <?php echo $this->e($data['page_title']); ?>

    </title>

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

    private function getRoute(string $route,int $id = 0): string
    {
        return match ($route) {

            'listar' =>
                '/manipulacionDeAlimentos/admin/examenes',

            'editar' =>
                '/manipulacionDeAlimentos/admin/examenes/' . $id . '/editar',

            'administrar_inscripto' =>
                '/manipulacionDeAlimentos/admin/inscripciones/' . $id,

            default =>
                '#',
        };
    }

    public function mostrar(array $data = []): void
{
    if (empty($data)) {

        $data = [

            'page_title' => 'Detalle del Examen',

            'examen' => [

                'id' => 0,
                'fecha' => '',
                'hora' => '',
                'lugar' => '',
                'aula' => '',
                'estado' => 'ACTIVO',
                'cupos_totales' => 0,
                'cupos_disponibles' => 0

            ],

            'inscriptos' => []

        ];
    }

    $this->getHeader($data);

    $examen = $data['examen'];

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8">

        <section class="space-y-2">

            <h2 class="font-headline-lg text-headline-lg text-primary">

                Detalle del examen

            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">

                Información general y alumnos inscriptos.

            </p>

        </section>

        <section class="app-vista-card">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                <div>

                    <span class="app-form-label">

                        Fecha

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['fecha']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Hora

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['hora']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Lugar

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['lugar']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Aula

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['aula']); ?>

                    </p>

                </div>

            </div>

            <hr class="my-6">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">

                <div>

                    <span class="app-form-label">

                        Estado

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['estado']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Cupos Totales

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['cupos_totales']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Cupos Disponibles

                    </span>

                    <p class="font-semibold">

                        <?php echo $this->e($examen['cupos_disponibles']); ?>

                    </p>

                </div>

                <div>

                    <span class="app-form-label">

                        Inscriptos

                    </span>

                    <p class="font-semibold">

                        <?php
                        echo
                            (int)$examen['cupos_totales']
                            -
                            (int)$examen['cupos_disponibles'];
                        ?>

                    </p>

                </div>

            </div>

        </section>

        <section class="app-vista-card">

            <div class="flex justify-between items-center mb-6">

                <h3 class="font-title-lg text-title-lg">

                    Alumnos inscriptos

                </h3>

                <a
                    href="<?php echo $this->getRoute('editar', (int)$examen['id']); ?>"
                    class="app-vista-button app-vista-button--primary">

                    Editar examen

                </a>

            </div>

            <div class="overflow-x-auto">

                <table class="app-tabla">

                    <thead>

                        <tr>

                            <th>DNI</th>

                            <th>Apellido</th>

                            <th>Nombre</th>

                            <th>Fecha inscripción</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

<?php if (empty($data['inscriptos'])): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-10">

                                No hay alumnos inscriptos.

                            </td>

                        </tr>

<?php else: ?>

<?php foreach ($data['inscriptos'] as $inscripto): ?>

                        <tr>

                            <td>

                                <?php echo $this->e($inscripto['dni']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($inscripto['apellido']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($inscripto['nombre']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($inscripto['fecha_inscripcion']); ?>

                            </td>

                            <td>

                                <?php echo $this->e($inscripto['estado']); ?>

                            </td>

                            <td>

                            <a
                                href="<?php echo $this->getRoute(
                                    'administrar_inscripto',
                                    (int) $inscripto['inscripcion_id']
                                ); ?>"
                                class="app-vista-button app-vista-button--secondary">

                                Administrar

                            </a>

                            </td>

                        </tr>

<?php endforeach; ?>

<?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <div class="flex justify-end">

            <a
                href="<?php echo $this->getRoute('listar'); ?>"
                class="app-vista-button app-vista-button--secondary">

                Volver al listado

            </a>

        </div>

    </div>

</main>

<?php

    $this->getFooter();
}

}