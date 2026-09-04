<?php

declare(strict_types=1);

/**
 * Vista: Consulta Pública de Carnets.
 *
 * Propósito:
 * Permitir consultar públicamente la vigencia
 * de un carnet de manipulación de alimentos.
 *
 * Responsabilidades:
 * - Mostrar el formulario de consulta.
 * - Mostrar el resultado de la consulta.
 * - Mostrar los datos del carnet encontrado.
 * - Permitir descargar el carnet y la foto asociada.
 */

require_once __DIR__ . '/BaseVista.php';

class ConsultaPublicaVista extends BaseVista
{
    /**
     * Datos por defecto de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Consulta Pública de Carnets',

            'formulario' => [
                'dni' => '',
            ],

            'resultado' => [
                'consultado' => false,
                'encontrado' => false
            ],
        ];
    }

    /**
     * Muestra la pantalla de consulta pública.
     */
    public function mostrar(array $consultaData = []): void
    {
        if (empty($consultaData)) {
            $consultaData =
                $this->getDefaultData();
        }

        include __DIR__ . '/header.php';
        ?>

        <link
            rel="stylesheet"
            href="<?= $this->baseURL; ?>css/Views/consulta-publica.css"
        >

        <main
            class="contenido-principal contenido-principal--medio"
        >

            <div class="space-y-8">

                <?php
                $this->renderHero();
                ?>

                <?php
                $this->renderFormulario(
                    $consultaData
                );
                ?>

                <?php
                if (
                    isset(
                        $consultaData['resultado']['encontrado']
                    )
                ) {
                    $this->renderResultado(
                        $consultaData['resultado']
                    );
                }
                ?>

            </div>

        </main>

        <?php
        $this->getFooter();
    }

    /**
     * Renderiza el encabezado de la consulta.
     */
    private function renderHero(): void
    {
        ?>

        <section class="consulta-publica-hero">

            <p class="app-vista-section-subtitle">
                CONSULTA PÚBLICA
            </p>

            <h1 class="app-vista-section-title">
                Consulta de Carnets de Manipulación de Alimentos
            </h1>

            <p class="consulta-publica-hero__descripcion">
                Verifique la autenticidad y vigencia de un carnet oficial
                ingresando el DNI del titular. Si el carnet existe podrá
                consultar su estado y descargar la documentación asociada.
            </p>

        </section>

        <?php
    }

    /**
     * Renderiza el formulario de consulta.
     */
    private function renderFormulario(array $data): void
    {
        ?>

        <section
            class="consulta-publica-formulario app-vista-card"
        >

            <form
                action="<?= $this->getRoute(
                    'consulta_publica'
                ); ?>"
                method="GET"
                class="consulta-publica-form"
            >

                <div>

                    <label for="dni">
                        DNI del titular
                    </label>

                    <input
                        id="dni"
                        name="dni"
                        type="text"
                        maxlength="8"
                        pattern="[0-9]{7,8}"
                        inputmode="numeric"
                        autocomplete="off"
                        required
                        value="<?= $this->e(
                            $data['formulario']['dni']
                            ?? ''
                        ); ?>"
                        placeholder="Ingrese el DNI"
                    >

                </div>

                <button
                    type="submit"
                    class="app-vista-button app-vista-button--primary"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        search
                    </span>

                    Consultar carnet

                </button>

            </form>

        </section>

        <?php
    }

    /**
     * Renderiza el resultado de la consulta.
     */
    private function renderResultado(
        array $resultado
    ): void {

        if (
            empty(
                $resultado['encontrado']
            )
        ) {
            $this->renderCarnetNoEncontrado();
            return;
        }

        $this->renderCarnet(
            $resultado
        );
    }

    /**
     * Renderiza los datos del carnet encontrado.
     */
    private function renderCarnet(
        array $resultado
    ): void {

        $estadoTexto =
            $resultado['vigente']
                ? 'VIGENTE'
                : 'VENCIDO';

        $estadoClase =
            $resultado['vigente']
                ? 'estado-vigente'
                : 'estado-vencido';
        ?>

        <section
            class="consulta-resultado app-vista-card"
        >

            <div class="consulta-resultado__header">

                <div>

                    <p class="app-vista-section-subtitle">
                        TITULAR DEL CARNET
                    </p>

                    <h2 class="app-vista-section-title">

                        <?= $this->e(
                            $resultado['nombre'] .
                            ' ' .
                            $resultado['apellido']
                        ); ?>

                    </h2>

                    <p class="consulta-resultado__dni">
                        DNI <?= $this->e(
                            $resultado['dni']
                        ); ?>
                    </p>

                </div>

                <span
                    class="estado-chip <?= $this->e(
                        $estadoClase
                    ); ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        <?= $resultado['vigente']
                            ? 'verified'
                            : 'warning'; ?>
                    </span>

                    <?= $this->e(
                        $estadoTexto
                    ); ?>

                </span>

            </div>


            <div class="consulta-resultado__datos">

                <div class="dato">

                    <span>
                        Número de carnet
                    </span>

                    <strong>
                        <?= $this->e(
                            $resultado['numero_carnet']
                        ); ?>
                    </strong>

                </div>


                <div class="dato">

                    <span>
                        Fecha de emisión
                    </span>

                    <strong>
                        <?= $this->e(
                            $resultado['fecha_emision']
                        ); ?>
                    </strong>

                </div>


                <div class="dato">

                    <span>
                        Fecha de vencimiento
                    </span>

                    <strong>
                        <?= $this->e(
                            $resultado['fecha_vencimiento']
                        ); ?>
                    </strong>

                </div>

            </div>


            <div class="consulta-resultado__acciones">

                <a
                    class="app-vista-button app-vista-button--primary"
                    href="<?= $this->e(
                        $this->getRoute(
                            'descargar_carnet',
                            (int)$resultado['id_carnet']
                        )
                    ); ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        picture_as_pdf
                    </span>

                    Descargar carnet

                </a>


                <a
                    class="app-vista-button app-vista-button--secondary"
                    href="<?= $this->e(
                        $this->getRoute(
                            'descargar_foto',
                            (int)$resultado['id_carnet']
                        )
                    ); ?>"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        image
                    </span>

                    Descargar foto carnet

                </a>

            </div>

        </section>

        <?php
    }

    /**
     * Renderiza el mensaje cuando no existe el carnet.
     */
    private function renderCarnetNoEncontrado(): void
    {
        ?>

        <section
            class="consulta-resultado app-vista-card"
        >

            <div class="consulta-error">

                <span
                    class="material-symbols-outlined consulta-error__icono"
                    aria-hidden="true"
                >
                    search_off
                </span>

                <h2 class="app-vista-section-title">
                    Carnet no encontrado
                </h2>

                <p class="app-vista-section-subtitle">
                    No existe un carnet asociado al DNI ingresado.
                    Verifique el número e intente nuevamente.
                </p>

            </div>

        </section>

        <?php
    }
}