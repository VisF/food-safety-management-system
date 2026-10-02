<?php

declare(strict_types=1);

/**
 * Vista: admin_curso_form.php
 *
 * Propósito:
 * Formulario de alta y edición de cursos.
 *
 * Responsabilidades:
 * - Mostrar formulario.
 * - Precargar datos cuando corresponda.
 * - Mostrar errores de validación.
 */

require_once __DIR__ . '/BaseVista.php';

class CursoFormVista extends BaseVista
{
    private function getHeader(array $data): void
    {
        $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

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
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap">

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

                'page_title' => 'Nuevo Curso',

                'modo' => 'crear',

                'curso' => [

                    'id' => 0,
                    'nombre' => '',
                    'modalidad' => 'presencial',
                    'descripcion' => '',
                    'fecha_inicio' => '',
                    'hora_inicio' => '',
                    'ubicacion' => '',
                    'cupos' => ''

                ],

                'errores' => []

            ];

        }

        $this->getHeader($data);

        $curso = $data['curso'];

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8 max-w-3xl mx-auto">

        <section class="space-y-1 px-1">

            <h2 class="font-headline-lg text-headline-lg text-primary">

                <?php echo $data['modo'] === 'editar'
                    ? 'Editar Curso'
                    : 'Nuevo Curso'; ?>

            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">

                Complete los datos del curso.

            </p>

        </section>

        <section class="app-vista-card">

            <form
                action="<?php echo $data['modo'] === 'editar'
                    ? $this->getRoute(
                        'guardar_curso',
                        (int)$curso['id']
                    )
                    : $this->getRoute(
                        'guardar_curso_nuevo'
                    ); ?>"
                method="post"
                class="space-y-6">

                <?= $this->getCsrfInput() ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">

                        <label
                            class="app-form-label"
                            for="nombre">

                            Nombre del curso

                        </label>

                        <input
                            class="app-form-input"
                            id="nombre"
                            name="nombre"
                            type="text"
                            maxlength="255"
                            required
                            value="<?php echo $this->e(
                                $curso['nombre']
                            ); ?>">

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="modalidad">

                            Modalidad

                        </label>

                        <select
                            class="app-form-input"
                            id="modalidad"
                            name="modalidad"
                            required>

                            <option
                                value="presencial"
                                <?php echo ($curso['modalidad'] ?? '') === 'presencial'
                                    ? 'selected'
                                    : ''; ?>>

                                Presencial

                            </option>

                            <option
                                value="virtual"
                                <?php echo ($curso['modalidad'] ?? '') === 'virtual'
                                    ? 'selected'
                                    : ''; ?>>

                                Virtual

                            </option>

                        </select>

                    </div>

                    <div>

                        <div>
                            <label
                                class="app-form-label"
                                for="cupos">

                                Cupos

                            </label>

                            <input
                                class="app-form-input"
                                id="cupos"
                                name="cupos"
                                type="number"
                                min="1"
                                required
                                value="<?php echo $this->e(
                                    (string)($curso['cupos'] ?? '')
                                ); ?>">
                        </div>

                        <div>
                            <label class="app-form-label">
                                Inscriptos
                            </label>

                            <div
                                class="app-form-input flex items-center bg-gray-100"
                                aria-readonly="true"
                            >
                                <?php echo $this->e(
                                    (string)($curso['inscriptos'] ?? 0)
                                ); ?>
                            </div>
                        </div>

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="fecha_inicio">

                            Fecha de inicio

                        </label>

                        <input
                            class="app-form-input"
                            id="fecha_inicio"
                            name="fecha_inicio"
                            type="date"
                            required
                            value="<?php echo $this->e(
                                $curso['fecha_inicio'] ?? ''
                            ); ?>">

                    </div>

                    <div>

                        <label
                            class="app-form-label"
                            for="hora_inicio">

                            Hora de inicio

                        </label>

                        <input
                            class="app-form-input"
                            id="hora_inicio"
                            name="hora_inicio"
                            type="time"
                            required
                            value="<?php echo $this->e(
                                $curso['hora_inicio'] ?? ''
                            ); ?>">

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
                            type="text"
                            maxlength="255"
                            required
                            value="<?php echo $this->e(
                                $curso['ubicacion'] ?? ''
                            ); ?>">

                    </div>

                    <div class="md:col-span-2">

                        <label
                            class="app-form-label"
                            for="descripcion">

                            Descripción

                        </label>

                        <textarea
                            class="app-form-input"
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            maxlength="1000"><?php echo $this->e(
                                $curso['descripcion'] ?? ''
                            ); ?></textarea>

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
                        href="<?php echo $this->getRoute(
                            'admin_cursos'
                        ); ?>"
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
                                : 'Guardar Curso'; ?>

                        </span>

                    </button>

                </div>

            </form>

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