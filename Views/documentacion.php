<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

class SubidaDocumentacionVista extends BaseVista
{
    public function mostrar(array $data = []): void
    {
        $data = array_merge(
            $this->getDefaultData(),
            $data
        );

        $documentos = $data['documents'] ?? [];

        $estado = $data['estado'] ?? [
            'completos' => 0,
            'total' => 3,
            'porcentaje' => 0,
            'completo' => false
        ];

        include __DIR__ . '/header.php';
        ?>

        <link
            rel="stylesheet"
            href="<?= $this->baseURL; ?>css/Views/subida-documentacion.css"
        >

        <main
            class="contenido-principal contenido-principal--estrecho subida-documentacion"
        >

            <section class="subida-documentacion__hero">

                <h2 class="app-vista-section-title">
                    <?= $this->e($data['hero_title']); ?>
                </h2>

                <p class="app-vista-section-subtitle subida-documentacion__hero-texto">
                    <?= $this->e($data['hero_text']); ?>
                </p>

            </section>


            <article
                class="app-vista-card app-vista-card--surface subida-documentacion__info"
            >

                <span
                    class="material-symbols-outlined subida-documentacion__info-icono"
                    aria-hidden="true"
                >
                    info
                </span>

                <div class="subida-documentacion__info-contenido">

                    <p class="subida-documentacion__info-titulo">
                        <?= $this->e($data['info_title']); ?>
                    </p>

                    <p class="subida-documentacion__info-texto">
                        <?= $this->e($data['info_text']); ?>
                    </p>

                </div>

            </article>


            <section
                class="app-vista-card app-vista-card--surface subida-documentacion__progreso"
            >

                <div class="subida-documentacion__progreso-header">

                    <span class="subida-documentacion__progreso-label">
                        Progreso
                    </span>

                    <strong class="subida-documentacion__progreso-porcentaje">
                        <?= (int)($estado['porcentaje'] ?? 0); ?>%
                    </strong>

                </div>

                <div class="subida-documentacion__progreso-barra">

                    <div
                        class="subida-documentacion__progreso-fill"
                        style="width: <?= (int)($estado['porcentaje'] ?? 0); ?>%;"
                    ></div>

                </div>

                <p class="subida-documentacion__progreso-texto">

                    <?= (int)($estado['completos'] ?? 0); ?>

                    de

                    <?= (int)($estado['total'] ?? 3); ?>

                    requisitos completos

                </p>

            </section>


            <section class="subida-documentacion__documentos">

                <h3 class="subida-documentacion__documentos-titulo">
                    <?= $this->e($data['documents_title']); ?>
                </h3>


                <?php foreach ($documentos as $document): ?>

                    <?php
                    $documento = $document['documento'] ?? null;

                    $observaciones = $documento
                        ? $documento->getObservaciones()
                        : null;

                    $estadoDocumento =
                        (string)($document['estado'] ?? 'pendiente');

                    $statusIcon =
                        (string)($document['status_icon'] ?? 'pending');
                    ?>


                    <article class="app-vista-card documento-card">

                        <div class="documento-card__header">

                            <div class="documento-card__icono">

                                <span
                                    class="material-symbols-outlined documento-card__icono-simbolo"
                                    aria-hidden="true"
                                >
                                    <?= $this->e($document['icon']); ?>
                                </span>

                            </div>


                            <div class="documento-card__contenido">

                                <h4 class="documento-card__titulo">
                                    <?= $this->e($document['title']); ?>
                                </h4>

                                <p class="documento-card__descripcion">
                                    <?= $this->e($document['description']); ?>
                                </p>


                                <?php if ($documento): ?>

                                    <p class="documento-card__archivo">

                                        Archivo:

                                        <a
                                            href="<?= $this->e(
                                                $this->getRoute(
                                                    'descargar_documento_ciudadano',
                                                    $documento->getId()
                                                )
                                            ); ?>"
                                            class="documento-card__archivo-enlace"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <?= $this->e(
                                                $documento->getNombreOriginal()
                                            ); ?>
                                        </a>

                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($observaciones)): ?>

                                    <p class="documento-card__observacion">

                                        <strong>Observación:</strong>

                                        <?= $this->e($observaciones); ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="documento-card__footer">

                            <span
                                class="app-vista-chip <?= $statusIcon === 'check_circle'
                                    ? 'app-vista-chip--vigente'
                                    : ''; ?>"
                            >

                                <span
                                    class="material-symbols-outlined documento-card__estado-icono <?= $statusIcon !== 'pending'
                                        ? 'icono-relleno'
                                        : ''; ?>"
                                    aria-hidden="true"
                                >
                                    <?= $this->e($statusIcon); ?>
                                </span>

                                <?= $this->e($document['status']); ?>

                            </span>


                            <?php if ($estadoDocumento !== 'aprobado'): ?>

                                <form
                                    method="POST"
                                    action="<?= $this->e(
                                        $this->getRoute('guardar_documento')
                                    ); ?>"
                                    enctype="multipart/form-data"
                                    class="documento-card__form"
                                >
                                    <?= $this->getCsrfInput() ?>
                                    <input
                                        type="hidden"
                                        name="tipo_documento"
                                        value="<?= $this->e(
                                            $document['tipo']
                                        ); ?>"
                                    >

                                    <input
                                        type="file"
                                        name="archivo"
                                        id="archivo_<?= $this->e(
                                            $document['tipo']
                                        ); ?>"
                                        class="documento-card__archivo-input"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required
                                    >

                                    <label
                                        for="archivo_<?= $this->e(
                                            $document['tipo']
                                        ); ?>"
                                        class="app-vista-button app-vista-button--primary documento-card__boton"
                                    >
                                        Subir archivo
                                    </label>

                                </form>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="app-vista-button app-vista-button--secondary documento-card__boton"
                                    disabled
                                >
                                    Documento aprobado
                                </button>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>


            <section class="subida-documentacion__footer">

                <a
                    href="<?= $this->e(
                        $this->getRoute('inicio')
                    ); ?>"
                    class="app-vista-button app-vista-button--secondary"
                >
                    Volver al inicio
                </a>

                <p class="subida-documentacion__footer-nota">
                    <?= $this->e($data['footer_note']); ?>
                </p>

            </section>

        </main>


        <script
            src="<?= $this->e(
                $this->baseURL . 'js/subida-documentacion.js'
            ); ?>"
            defer
        ></script>


        <?php
        $this->getFooter();
    }


    private function getDefaultData(): array
    {
        return [
            'page_title' =>
                'Subida de documentación - App Ciudadana',

            'hero_title' =>
                'Subida de documentación',

            'hero_text' =>
                'Para obtener tu carnet de manipulación de alimentos necesitamos que presentes la documentación requerida.',

            'info_title' =>
                'Documentación requerida',

            'info_text' =>
                'Subí cada documento en el formato indicado. Una vez enviados, serán revisados por un inspector.',

            'documents_title' =>
                'Tus documentos',

            'documents' =>
                [],

            'footer_note' =>
                '¿Necesitás ayuda? Comunicate con nosotros.',

            'estado' => [
                'completos' => 0,
                'total' => 3,
                'porcentaje' => 0,
                'completo' => false
            ]
        ];
    }
}