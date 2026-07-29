<?php

class ConsultaPublicaVista
{
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

    private function getRoute(string $route): string
    {
        return match ($route) {

            'consulta_publica' =>
                '/manipulacionDeAlimentos/consulta-publica',

            'descargar_foto' =>
                '/manipulacionDeAlimentos/consulta-publica/foto',

            'descargar_carnet' =>
                '/manipulacionDeAlimentos/consulta-publica/carnet',

            default => '#'
        };
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    private function getIncomingData(): array
    {
        if (!isset($_GET['data'])) {
            return [];
        }

        $decodedData = json_decode((string)$_GET['data'], true);

        return is_array($decodedData)
            ? $decodedData
            : [];
    }

    private function getHeader(array $consultaData): void
    {
        $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

        if (preg_match('#/vistas$#', $assetBase) === 1) {
            $assetBase = (string)preg_replace('#/vistas$#', '', $assetBase);
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

        <title>
            <?= $this->e($consultaData['page_title']); ?>
        </title>

        <script src="<?= $assetBase; ?>/js/tailwind-config.js"></script>

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

        <link href="<?= $assetBase; ?>/css/base.css" rel="stylesheet">

        <link href="<?= $assetBase; ?>/css/components.css" rel="stylesheet">

        <link href="<?= $assetBase; ?>/css/ui.css" rel="stylesheet">

        <link href="<?= $assetBase; ?>/css/Views/consulta-publica.css" rel="stylesheet">

    </head>

    <body class="tema-ciudadano">

    <?php
    $page_title = 'Consulta Pública';
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

        private function renderFormulario(array $data): void
    {
    ?>

    <section class="consulta-publica-formulario app-vista-card">

        <form
            action="<?= $this->getRoute('consulta_publica'); ?>"
            method="GET"
            class="consulta-publica-form">

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
                    value="<?= $this->e($data['formulario']['dni']); ?>"
                    placeholder="Ingrese el DNI">

            </div>

            <button
                type="submit"
                class="app-vista-button app-vista-button--primary">

                <span class="material-symbols-outlined">
                    search
                </span>

                Consultar carnet

            </button>

        </form>

    </section>

    <?php
        }

        public function mostrar(array $consultaData = []): void
        {
            if (empty($consultaData)) {

                $consultaData = array_replace_recursive(
                    $this->getDefaultData(),
                    $this->getIncomingData()
                );

            }

            $this->getHeader($consultaData);

    ?>

    <main class="contenido-principal contenido-principal--medio">

        <div class="space-y-8">

            <?php $this->renderHero(); ?>

            <?php $this->renderFormulario($consultaData); ?>

            <?php
            if (
                !empty($consultaData['resultado']['consultado'])
                && $consultaData['resultado']['consultado']
            ) {
                $this->renderResultado($consultaData['resultado']);
            }
            ?>

        </div>

    </main>

    <?php

        $this->getFooter();
    }

       private function renderResultado(array $resultado): void
    {
        if (!$resultado['encontrado']) {
            $this->renderCarnetNoEncontrado();
            return;
        }

        $this->renderCarnet($resultado);
    }

    private function renderCarnet(array $resultado): void
    {
        $estadoTexto = $resultado['vigente']
            ? 'VIGENTE'
            : 'VENCIDO';

        $estadoClase = $resultado['vigente']
            ? 'estado-vigente'
            : 'estado-vencido';
    ?>

    <section class="consulta-resultado app-vista-card">

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

                    DNI <?= $this->e($resultado['dni']); ?>

                </p>

            </div>

            <span class="estado-chip <?= $estadoClase; ?>">

                <span class="material-symbols-outlined">

                    <?= $resultado['vigente']
                        ? 'verified'
                        : 'warning'; ?>

                </span>

                <?= $estadoTexto; ?>

            </span>

        </div>

        <div class="consulta-resultado__datos">

            <div class="dato">

                <span>Número de carnet</span>

                <strong>

                    <?= $this->e($resultado['numero_carnet']); ?>

                </strong>

            </div>

            <div class="dato">

                <span>Fecha de emisión</span>

                <strong>

                    <?= $this->e($resultado['fecha_emision']); ?>

                </strong>

            </div>

            <div class="dato">

                <span>Fecha de vencimiento</span>

                <strong>

                    <?= $this->e($resultado['fecha_vencimiento']); ?>

                </strong>

            </div>

        </div>

        <div class="consulta-resultado__acciones">

            <a
                class="app-vista-button app-vista-button--primary"
                href="<?= $this->getRoute('descargar_carnet'); ?>?id=<?= urlencode($resultado['id_carnet']); ?>">

                <span class="material-symbols-outlined">
                    picture_as_pdf
                </span>

                Descargar carnet

            </a>

            <a
                class="app-vista-button app-vista-button--secondary"
                href="<?= $this->getRoute('descargar_foto'); ?>?id=<?= urlencode($resultado['id_carnet']); ?>">

                <span class="material-symbols-outlined">
                    image
                </span>

                Descargar foto carnet

            </a>

        </div>

    </section>

    <?php
    }

    
    private function renderCarnetNoEncontrado(): void
    {
    ?>

    <section class="consulta-resultado app-vista-card">

        <div class="consulta-error">

            <span
                class="material-symbols-outlined consulta-error__icono">

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

