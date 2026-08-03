<?php

declare(strict_types=1);

/**
 * Vista: admin_examen_form.php
 *
 * Propósito:
 * Formulario de alta y edición de exámenes.
 *
 * Responsabilidades:
 * - Mostrar formulario.
 * - Precargar datos cuando corresponda.
 * - Mostrar errores de validación.
 */
class ExamenFormVista
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

    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/base.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/components.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/ui.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">

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
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function getRoute(string $route): string
    {
        return match ($route) {

            'listar' =>
                '/manipulacionDeAlimentos/admin/examenes',

            'guardar' =>
                '/manipulacionDeAlimentos/admin/examenes',

            default =>
                '#',
        };
    }
        public function mostrar(array $data = []): void
    {
        if (empty($data)) {

            $data = [

                'page_title' => 'Nuevo Examen',

                'modo' => 'crear',

                'examen' => [

                    'id' => 0,
                    'fecha' => '',
                    'hora' => '',
                    'ubicacion' => '',
                    'aula' => '',
                    'cupos' => ''

                ],

                'errores' => []

            ];
        }

        $this->getHeader($data);

        $examen = $data['examen'];

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8 max-w-3xl mx-auto">

        <section class="space-y-1 px-1">

            <h2 class="font-headline-lg text-headline-lg text-primary">

                <?php echo $data['modo'] === 'editar'
                    ? 'Editar Examen'
                    : 'Nuevo Examen'; ?>

            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">

                Complete los datos del examen.

            </p>

        </section>

        <section class="app-vista-card">

            <form
                action="<?php echo $data['modo'] === 'editar'
                    ? '/manipulacionDeAlimentos/admin/examenes/' . (int) $examen['id']
                    : $this->getRoute('guardar'); ?>"
                method="post"
                class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label
                            class="app-form-label"
                            for="fecha">

                            Fecha

                        </label>

                        <input
                            class="app-form-input"
                            id="fecha"
                            name="fecha"
                            type="date"
                            required
                            value="<?php echo $this->e($examen['fecha']); ?>">

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="hora">

                            Hora

                        </label>

                        <input
                            class="app-form-input"
                            id="hora"
                            name="hora"
                            type="time"
                            required
                            value="<?php echo $this->e($examen['hora']); ?>">

                    </div>

                    <div class="md:col-span-2">

                        <label
                            class="app-form-label"
                            for="ubicacion">

                            Ubicación

                        </label>

                        <input
                            class="app-form-input"
                            id="ubicacion"
                            name="ubicacion"
                            maxlength="255"
                            required
                            type="text"
                            value="<?php echo $this->e($examen['ubicacion']); ?>">

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="aula">

                            Aula

                        </label>

                        <input
                            class="app-form-input"
                            id="aula"
                            name="aula"
                            maxlength="120"
                            required
                            type="text"
                            value="<?php echo $this->e($examen['aula']); ?>">

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="cupos">

                            Cupos

                        </label>

                        <input
                            class="app-form-input"
                            id="cupos"
                            min="1"
                            name="cupos"
                            required
                            type="number"
                            value="<?php echo $this->e($examen['cupos']); ?>">

                    </div>

                </div>

                <?php if (!empty($data['errores'])): ?>

                <div class="app-alert app-alert--danger">

                    <ul class="list-disc pl-5 space-y-1">

<?php foreach ($data['errores'] as $error): ?>

                        <li>

                            <?php echo $this->e($error); ?>

                        </li>

<?php endforeach; ?>

                    </ul>

                </div>

<?php endif; ?>

                <div class="flex justify-end gap-4 pt-4">

                    <a
                        href="<?php echo $this->getRoute('listar'); ?>"
                        class="app-vista-button app-vista-button--secondary">

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="app-vista-button app-vista-button--primary">

                        <span class="material-symbols-outlined">
                            save
                        </span>

                        <span>

                            <?php echo $data['modo'] === 'editar'
                                ? 'Guardar Cambios'
                                : 'Guardar Examen'; ?>

                        </span>

                    </button>

                </div>

            </form>

        </section>

    </div>

</main>

<?php

        $this->getFooter();
    }
}