<?php

declare(strict_types=1);

/**
 * Vista: detalle_examen.php
 *
 * Propósito:
 * Mostrar al ciudadano la información de un examen
 * y permitirle iniciar el proceso de inscripción.
 *
 * Responsabilidades:
 * - Mostrar datos preparados por el Service.
 * - Generar rutas mediante BaseVista.
 * - Escapar valores dinámicos.
 *
 * No realiza:
 * - Validaciones de negocio.
 * - Consultas a base de datos.
 * - Lectura de $_GET['data'].
 */

require_once __DIR__ . '/BaseVista.php';

class DetalleExamenVista extends BaseVista
{
    public function mostrar(array $data = []): void
    {
        if (empty($data)) {
            $data = [
                'page_title' => 'Detalle del examen',
                'examen' => [
                    'id' => 0,
                    'nombre' => 'Examen de Manipulación de Alimentos',
                    'fecha' => '',
                    'hora' => '',
                    'lugar' => '',
                    'cupos' => 0,
                    'estado' => ''
                ]
            ];
        }

        $examen = $data['examen'] ?? [];

        include __DIR__ . '/header.php';
        ?>

        <link
            rel="stylesheet"
            href="<?= $this->baseURL; ?>css/Views/detalle-examen.css"
        >

        <main class="contenido-principal contenido-principal--ancho">

            <div class="detalle-examen">

                <section class="detalle-examen__intro">

                    <p class="detalle-examen__eyebrow">
                        Próximo examen
                    </p>

                    <h1 class="font-headline-lg text-primary">
                        <?= $this->e(
                            $examen['nombre']
                            ?? 'Examen de Manipulación de Alimentos'
                        ); ?>
                    </h1>

                </section>


                <section class="app-vista-card detalle-examen__hero">

                    <div class="detalle-examen__hero-contenido">

                        <div class="detalle-examen__principal">

                            <span class="detalle-examen__estado">
                                <?= $this->e(
                                    $examen['estado'] ?? ''
                                ); ?>
                            </span>

                            <h2 class="detalle-examen__nombre">
                                <?= $this->e(
                                    $examen['nombre']
                                    ?? 'Examen de Manipulación de Alimentos'
                                ); ?>
                            </h2>

                            <p class="detalle-examen__lugar">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    location_on
                                </span>

                                <span>
                                    <?= $this->e(
                                        $examen['lugar'] ?? ''
                                    ); ?>
                                </span>

                            </p>

                        </div>


                        <div class="detalle-examen__fecha">

                            <div class="detalle-examen__fecha-valor">
                                <?= $this->e(
                                    $examen['fecha'] ?? ''
                                ); ?>
                            </div>

                            <div class="detalle-examen__hora">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    schedule
                                </span>

                                <?= $this->e(
                                    $examen['hora'] ?? ''
                                ); ?>
                                hs

                            </div>

                            <div class="detalle-examen__cupos">

                                <span
                                    class="material-symbols-outlined"
                                    aria-hidden="true"
                                >
                                    groups
                                </span>

                                Cupos disponibles:
                                <?= $this->e(
                                    (string)($examen['cupos'] ?? 0)
                                ); ?>

                            </div>

                        </div>

                    </div>

                </section>


                <section class="app-vista-card detalle-examen__informacion">

                    <h2 class="detalle-examen__informacion-titulo">
                        Información importante
                    </h2>

                    <ul class="detalle-examen__lista">

                        <li>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                schedule
                            </span>

                            Presentarse 15 minutos antes del horario indicado.
                        </li>

                        <li>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                badge
                            </span>

                            Llevar DNI físico.
                        </li>

                        <li>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                verified
                            </span>

                            La inscripción será validada por el sistema.
                        </li>

                        <li>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                groups
                            </span>

                            Los cupos son limitados.
                        </li>

                    </ul>

                </section>


                <div class="detalle-examen__acciones">

                    <form
                        action="<?= $this->getRoute('confirmar_inscripcion'); ?>"
                        method="post"
                        class="detalle-examen__form-inscripcion"
                    >
                    <?= $this->getCsrfInput() ?>
                        <input
                            type="hidden"
                            name="id_examen"
                            value="<?= (int)($examen['id'] ?? 0); ?>"
                        >

                        <button
                            type="submit"
                            class="app-vista-button app-vista-button--primary"
                        >
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                how_to_reg
                            </span>

                            Inscribirme al examen
                        </button>
                    </form>

                    <a
                        href="<?= $this->getRoute('inicio'); ?>"
                        class="app-vista-button app-vista-button--secondary"
                    >
                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            arrow_back
                        </span>

                        Volver
                    </a>

                </div>

            </div>

        </main>

        <?php

        $this->getFooter();
    }
}