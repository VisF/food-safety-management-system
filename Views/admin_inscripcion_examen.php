<?php

declare(strict_types=1);

/**
 * Vista: admin_inscripcion_examen.php
 *
 * Propósito:
 * Administrar la inscripción de un alumno
 * a un examen.
 *
 * Responsabilidades:
 * - Mostrar información del examen.
 * - Mostrar información del alumno.
 * - Mostrar estado de la documentación.
 * - Permitir aprobar o desaprobar.
 * - Registrar observaciones.
 */
class AdminInscripcionExamenVista
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

    private function getRoute(
        string $route,
        int $id = 0
    ): string
    {
        return match ($route) {

            'guardar' =>
                '/manipulacionDeAlimentos/admin/inscripciones/' . $id,

            'volver' =>
                '/manipulacionDeAlimentos/admin/examenes',

            default =>
                '#'
        };
    }
    public function mostrar(
    array $data = []
): void
{
    if (empty($data)) {

        $data = [

            'page_title' => 'Administrar inscripción',

            'examen' => [

                'id' => 0,
                'fecha' => '',
                'hora' => '',
                'ubicacion' => '',
                'aula' => ''

            ],

            'alumno' => [

                'id' => 0,
                'nombre' => '',
                'apellido' => '',
                'dni' => '',
                'email' => ''

            ],

            'documentacion' => [

                'dni' => false,
                'foto' => false,
                'curso' => false

            ],

            'inscripcion' => [

                'id' => 0,
                'estado' => 'INSCRIPTO_EXAMEN',
                'observaciones' => ''

            ],

            'errores' => []

        ];
    }

    $this->getHeader(
        $data
    );
    $inscripcionFinalizada =
    $data['inscripcion']['estado']
    === 'CARNET_EMITIDO';

?>

<main class="contenido-principal contenido-principal--ancho">

    <div class="space-y-8 max-w-5xl mx-auto">

        <section class="space-y-1">

            <h2 class="font-headline-lg text-headline-lg text-primary">

                Administrar inscripción

            </h2>

            <p class="font-body-md text-body-md text-on-surface-variant">

                Gestión del resultado del examen y documentación del alumno.

            </p>

        </section>

        <form
            action="<?php echo $this->getRoute(
                'guardar',
                (int)$data['inscripcion']['id']
            ); ?>"
            method="post"
            class="space-y-8">

            <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Examen

                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <div>

                        <label class="app-form-label">

                            Fecha

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['examen']['fecha']
                            ); ?>

                        </p>

                    </div>

                    <div>

                        <label class="app-form-label">

                            Hora

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['examen']['hora']
                            ); ?>

                        </p>

                    </div>

                    <div>

                        <label class="app-form-label">

                            Ubicación

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['examen']['ubicacion']
                            ); ?>

                        </p>

                    </div>

                    <div>

                        <label class="app-form-label">

                            Aula

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['examen']['aula']
                            ); ?>

                        </p>

                    </div>

                </div>

            </section>

            <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Alumno

                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>

                        <label class="app-form-label">

                            Nombre

                        </label>

                        <p>

                            <?php
                            echo $this->e(
                                $data['alumno']['apellido']
                            );

                            echo ', ';

                            echo $this->e(
                                $data['alumno']['nombre']
                            );
                            ?>

                        </p>

                    </div>

                    <div>

                        <label class="app-form-label">

                            DNI

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['alumno']['dni']
                            ); ?>

                        </p>

                    </div>

                    <div>

                        <label class="app-form-label">

                            Correo electrónico

                        </label>

                        <p>

                            <?php echo $this->e(
                                $data['alumno']['email']
                            ); ?>

                        </p>

                    </div>

                </div>

            </section>

            <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Documentación

                </h3>

                <div class="space-y-4">

                    <div>

                        <?= $data['documentacion']['dni']
                            ? '✅'
                            : '❌'; ?>

                        DNI

                    </div>

                    <div>

                        <?= $data['documentacion']['foto']
                            ? '✅'
                            : '❌'; ?>

                        Foto carnet

                    </div>

                    <div>

                        <?= $data['documentacion']['curso']
                            ? '✅'
                            : '❌'; ?>

                        Curso aprobado

                    </div>

                </div>

            </section>
            <?php if (!$inscripcionFinalizada): ?>
                 <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Resultado

                </h3>
                <?php if ($inscripcionFinalizada): ?>

                <div class="space-y-2">

                    <p class="text-green-700 font-semibold">

                        ✅ Aprobado

                    </p>

                </div>

                <?php else: ?>
                <div class="space-y-4">

                    <label
                        class="flex items-center gap-3 cursor-pointer">

                        <input
                            type="radio"
                            name="estado"
                            value="APROBADO"

                            <?php echo
                                $data['inscripcion']['estado'] === 'APROBADO'
                                ? 'checked'
                                : '';
                            ?>

                        >

                        <span>

                            Aprobado

                        </span>

                    </label>

                    <label
                        class="flex items-center gap-3 cursor-pointer">

                        <input
                            type="radio"
                            name="estado"
                            value="DESAPROBADO"

                            <?php echo
                                $data['inscripcion']['estado'] === 'DESAPROBADO'
                                ? 'checked'
                                : '';
                            ?>

                        >

                        <span>

                            Desaprobado

                        </span>

                    </label>

                    <?php if (
                        $data['inscripcion']['estado']
                        === 'INSCRIPTO_EXAMEN'
                    ): ?>

                        <p
                            class="text-sm text-on-surface-variant">

                            Estado actual:
                            <strong>

                                Inscripto al examen

                            </strong>

                        </p>

                    <?php endif; ?>

                </div>
                <?php endif; ?>

            </section>
            <?php else: ?>

            <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Resultado

                </h3>

                <p class="text-green-700 font-semibold">

                    ✔ El examen ya fue aprobado y el carnet fue emitido.

                </p>

            </section>

            <?php endif; ?>
            <section class="app-vista-card">

                <h3 class="font-title-lg mb-6">

                    Observaciones

                </h3>

                <textarea
                    class="app-form-input"
                    name="observaciones"
                    rows="6"
                    maxlength="1000"
                    placeholder="Ingrese observaciones sobre el examen..."
                    <?php echo $inscripcionFinalizada
                        ? 'readonly'
                        : '';
                    ?>></textarea>

            </section>

<?php if (!empty($data['errores'])): ?>

            <section
                class="app-vista-card border-red-300">

                <h3
                    class="text-red-700 font-semibold mb-3">

                    Se encontraron errores

                </h3>

                <ul
                    class="list-disc pl-6 space-y-2">

<?php foreach (
    $data['errores']
    as
    $error
): ?>

                    <li>

                        <?php
                        echo $this->e(
                            $error
                        );
                        ?>

                    </li>

<?php endforeach; ?>

                </ul>

            </section>

<?php endif; ?>


<?php if (
    !empty($data['requiere_confirmacion'])
): ?>

        <script>

            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const confirmar =
                        confirm(
                            'El alumno no posee toda la documentación requerida.\n\n'
                            + '¿Está seguro de aprobar el examen y emitir el carnet?'
                        );

                    if (confirmar) {

                        const formulario =
                            document.querySelector('form');

                        const input =
                            document.createElement('input');

                        input.type = 'hidden';

                        input.name =
                            'confirmar_aprobacion';

                        input.value =
                            '1';

                        formulario.appendChild(
                            input
                        );

                        formulario.submit();

                    }

                }
            );

        </script>

        <?php endif; ?>
            <div
                class="flex justify-end gap-4">

                <a
                    href="<?php
                        echo $this->getRoute(
                            'volver'
                        );
                    ?>"
                    class="app-vista-button app-vista-button--secondary">

                    Cancelar

                </a>
                <?php if ($inscripcionFinalizada): ?>

                <a
                    href="..."
                    class="app-vista-button app-vista-button--primary">

                    Ver carnet

                </a>

                <?php else: ?>

                <button
                    type="submit"
                    class="app-vista-button app-vista-button--primary">

                    Guardar

                </button>

                <?php endif; ?>

            </div>

        </form>

    </div>

</main>
<?php

    $this->getFooter();
}

}