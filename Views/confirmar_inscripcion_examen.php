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
     * Muestra la pantalla de confirmación.
     */
    public function mostrar(array $data = []): void
    {
        if (empty($data)) {
            $data = [
                'page_title' => 'Confirmar inscripción',
                'examName' => '',
                'examId' => 0
            ];
        }

        include __DIR__ . '/header.php';
        ?>

        <link
            rel="stylesheet"
            href="<?= $this->baseURL; ?>css/Views/confirmar_inscripcion_examen.css"
        >

        <main
            class="contenido-principal contenido-principal--ancho"
        >

            <div
                class="w-full max-w-2xl mx-auto"
            >

                <section
                    class="app-vista-card text-center confirmar-inscripcion"
                >

                    <div
                        class="confirmar-inscripcion__icono"
                        aria-hidden="true"
                    >
                        !
                    </div>


                    <h1
                        class="font-headline-lg text-primary"
                    >
                        Confirmar inscripción
                    </h1>


                    <p
                        class="confirmar-inscripcion__texto"
                    >
                        Está a punto de inscribirse al examen

                        <strong>
                            <?= $this->e(
                                $data['examName']
                                ?: 'seleccionado'
                            ); ?>
                        </strong>
                    </p>


                    <div
                        class="confirmar-inscripcion__acciones"
                    >

                        <form
                            action="<?= $this->getRoute(
                                'confirmar_inscripcion'
                            ); ?>"
                            method="post"
                        >

                            <input
                                type="hidden"
                                name="id_examen"
                                value="<?= (int)(
                                    $data['examId']
                                    ?? 0
                                ); ?>"
                            >

                            <button
                                type="submit"
                                class="app-vista-button app-vista-button--primary w-full"
                            >
                                Confirmar inscripción
                            </button>

                        </form>


                        <a
                            href="<?= $this->getRoute(
                                'detalle_examen',
                                (int)(
                                    $data['examId']
                                    ?? 0
                                )
                            ); ?>"
                            class="app-vista-button app-vista-button--secondary w-full text-center"
                        >
                            Volver
                        </a>

                    </div>

                </section>

            </div>

        </main>

        <?php
        $this->getFooter();
    }
}