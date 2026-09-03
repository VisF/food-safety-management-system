<?php

declare(strict_types=1);

/**
 * Vista: confirmar_inscripcion_examen.php
 *
 * Propósito:
 * Confirmación antes de completar la inscripción
 * a un examen.
 *
 * Responsabilidades:
 * - Mostrar el examen seleccionado.
 * - Permitir confirmar la inscripción.
 * - Permitir volver al detalle del examen.
 */

require_once __DIR__ . '/BaseVista.php';

class ConfirmarInscripcionExamenVista extends BaseVista
{
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
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        <?php
        echo $this->e(
            $data['page_title']
            ?? 'Confirmar inscripción'
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
        href="<?php
            echo $assetBase;
        ?>/css/base.css"
    >


    <link
        rel="stylesheet"
        href="<?php
            echo $assetBase;
        ?>/css/components.css"
    >


    <link
        rel="stylesheet"
        href="<?php
            echo $assetBase;
        ?>/css/ui.css"
    >


    <link
        rel="stylesheet"
        href="<?php
            echo $assetBase;
        ?>/css/app.css"
    >

</head>


<body
    class="bg-background text-on-surface pb-24 md:pb-0 md:pt-20 tema-ciudadano"
>

<?php

$page_title =
    $data['page_title']
    ?? 'Confirmar inscripción';

include __DIR__ .
    '/header.php';

    }


    /**
     * Muestra la pantalla de confirmación.
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
                    'Confirmar inscripción',

                'examName' =>
                    '',

                'examId' =>
                    0
            ];
        }

        $this->getHeader(
            $data
        );

?>

<main
    class="contenido-principal contenido-principal--ancho"
>

    <div
        class="w-full max-w-2xl mx-auto"
    >

        <section
            class="app-vista-card text-center"
        >

            <div
                style="
                    width:72px;
                    height:72px;
                    margin:0 auto 1.5rem auto;
                    border-radius:50%;
                    background:linear-gradient(
                        135deg,
                        #005596 0%,
                        #3a5f94 100%
                    );
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    color:white;
                    font-size:2rem;
                    font-weight:bold;
                "
            >

                !

            </div>


            <h1
                class="font-headline-lg text-primary"
            >

                Confirmar inscripción

            </h1>


            <p
                style="
                    margin-top:1rem;
                    margin-bottom:2rem;
                "
            >

                Está a punto de inscribirse al examen

                <strong>

                    <?php
                    echo $this->e(
                        $data['examName']
                        ?: 'seleccionado'
                    );
                    ?>

                </strong>

            </p>


            <div
                style="
                    display:flex;
                    flex-direction:column;
                    gap:12px;
                "
            >

                <form
                    action="<?php
                        echo $this->getRoute(
                            'confirmar_inscripcion'
                        );
                    ?>"
                    method="post"
                >

                    <input
                        type="hidden"
                        name="id_examen"
                        value="<?php
                            echo (int)(
                                $data['examId']
                                ?? 0
                            );
                        ?>"
                    >

                    <button
                        type="submit"
                        class="
                            app-vista-button
                            app-vista-button--primary
                            w-full
                        "
                    >

                        Confirmar inscripción

                    </button>

                </form>


                <a
                    href="<?php
                        echo $this->getRoute(
                            'detalle_examen',
                            (int)(
                                $data['examId']
                                ?? 0
                            )
                        );
                    ?>"
                    class="
                        app-vista-button
                        app-vista-button--secondary
                        w-full
                        text-center
                    "
                >

                    Volver

                </a>

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