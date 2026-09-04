<?php

/**
 * Vista: Subida de documentación.
 *
 * Propósito:
 * Mostrar la documentación requerida por el ciudadano
 * y permitir cargar o corregir los archivos correspondientes.
 */

require_once __DIR__ . '/BaseVista.php';

class SubidaDocumentacionVista extends BaseVista
{
    /**
     * Configuración de los documentos requeridos.
     */
    private const TIPOS_REQUERIDOS = [
        'dni' => [
            'icon' => 'badge',
            'title' => 'DNI frente y dorso',
            'description' =>
                'Ambos lados en una misma imagen o PDF.'
        ],

        'foto_carnet' => [
            'icon' => 'account_circle',
            'title' => 'Foto carnet',
            'description' =>
                'Fondo blanco, frente despejado.'
        ],

        'moodle' => [
            'icon' => 'school',
            'title' => 'Certificado Moodle',
            'description' =>
                'Constancia de aprobación del curso.'
        ]
    ];

    /**
     * Datos por defecto de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' =>
                'Subida de documentación - App Ciudadana',

            'hero_title' =>
                'Subida de documentación',

            'hero_text' =>
                'Complete los campos requeridos para avanzar con su trámite municipal.',

            'info_title' =>
                'Formatos aceptados',

            'info_text' =>
                'Solo se permiten archivos en formato JPG, PNG y PDF. Peso máximo: 5MB.',

            'documents_title' =>
                'Documentación requerida',

            'documents' => [],

            'footer_button' =>
                'Enviar documentación',

            'footer_note' =>
                'Usted será notificado una vez que los documentos sean validados.'
        ];
    }

    /**
     * Muestra la documentación del ciudadano.
     */
    public function mostrar(array $documentos = []): void
    {
        $data =
            $this->getDefaultData();

        $data['documents'] =
            $this->prepararDocumentos(
                $documentos
            );

        include __DIR__ . '/header.php';
        ?>

        <link
            rel="stylesheet"
            href="<?= $this->baseURL; ?>css/Views/subida-documentacion.css"
        >

        <main
            class="contenido-principal contenido-principal--estrecho subida-documentacion"
        >

            <section
                class="subida-documentacion__hero"
            >

                <h2 class="app-vista-section-title">
                    <?= $this->e(
                        $data['hero_title']
                    ); ?>
                </h2>

                <p
                    class="app-vista-section-subtitle subida-documentacion__hero-texto"
                >
                    <?= $this->e(
                        $data['hero_text']
                    ); ?>
                </p>

            </section>


            <article
                class="app-vista-card app-vista-card--surface subida-documentacion__info"
            >

                <span
                    class="material-symbols-outlined subida-documentacion__info-icono"
                    data-icon="info"
                    aria-hidden="true"
                >
                    info
                </span>

                <div
                    class="subida-documentacion__info-contenido"
                >

                    <p
                        class="subida-documentacion__info-titulo"
                    >
                        <?= $this->e(
                            $data['info_title']
                        ); ?>
                    </p>

                    <p
                        class="subida-documentacion__info-texto"
                    >
                        <?= $this->e(
                            $data['info_text']
                        ); ?>
                    </p>

                </div>

            </article>


            <section
                class="subida-documentacion__documentos"
            >

                <h3
                    class="subida-documentacion__documentos-titulo"
                >
                    <?= $this->e(
                        $data['documents_title']
                    ); ?>
                </h3>


                <?php foreach (
                    $data['documents']
                    as $document
                ): ?>

                    <?php
                    $documento =
                        $document['documento'];

                    $observaciones =
                        $documento
                            ? $documento->getObservaciones()
                            : null;
                    ?>

                    <article
                        class="app-vista-card documento-card"
                    >

                        <div
                            class="documento-card__header"
                        >

                            <div
                                class="documento-card__icono"
                            >

                                <span
                                    class="material-symbols-outlined documento-card__icono-simbolo"
                                    data-icon="<?= $this->e(
                                        $document['icon']
                                    ); ?>"
                                    aria-hidden="true"
                                >
                                    <?= $this->e(
                                        $document['icon']
                                    ); ?>
                                </span>

                            </div>


                            <div
                                class="documento-card__contenido"
                            >

                                <h4
                                    class="documento-card__titulo"
                                >
                                    <?= $this->e(
                                        $document['title']
                                    ); ?>
                                </h4>

                                <p
                                    class="documento-card__descripcion"
                                >
                                    <?= $this->e(
                                        $document['description']
                                    ); ?>
                                </p>


                                <?php if ($documento): ?>


                                    <p class="documento-card__archivo">

                                        Archivo:

                                        <a
                                            href="<?= $this->getRoute(
                                                'descargar_documento_ciudadano',
                                                $documento->getId()
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



                                <?php if (
                                    !empty($observaciones)
                                ): ?>

                                    <p
                                        class="documento-card__observacion"
                                    >
                                        <strong>
                                            Observación:
                                        </strong>

                                        <?= $this->e(
                                            $observaciones
                                        ); ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div
                            class="documento-card__footer"
                        >

                            <span
                                class="app-vista-chip documento-card__estado
                                <?= $document['status_icon'] === 'check_circle'
                                    ? 'app-vista-chip--vigente'
                                    : ''; ?>"
                            >

                                <span
                                    class="material-symbols-outlined documento-card__estado-icono
                                    <?= $document['status_icon'] !== 'pending'
                                        ? 'icono-relleno'
                                        : ''; ?>"
                                    data-icon="<?= $this->e(
                                        $document['status_icon']
                                    ); ?>"
                                    aria-hidden="true"
                                >
                                    <?= $this->e(
                                        $document['status_icon']
                                    ); ?>
                                </span>

                                <?= $this->e(
                                    $document['status']
                                ); ?>

                            </span>


                            <?php if (
                                $document['estado']
                                !== 'aprobado'
                            ): ?>

                                <form
                                    method="POST"
                                    action="<?= $this->baseURL; ?>documentos/subir"
                                    enctype="multipart/form-data"
                                    class="documento-card__form"
                                >

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


            <section
                class="subida-documentacion__footer"
            >

                <button
                    type="button"
                    class="app-vista-button app-vista-button--primary"
                >
                    <?= $this->e(
                        $data['footer_button']
                    ); ?>
                </button>

                <p
                    class="subida-documentacion__footer-nota"
                >
                    <?= $this->e(
                        $data['footer_note']
                    ); ?>
                </p>

            </section>

        </main>

        <script
            src="<?= $this->baseURL; ?>js/subida-documentacion.js"
            defer
        ></script>


        <?php
        $this->getFooter();
    }

    /**
     * Prepara los documentos requeridos para la presentación.
     *
     * Esta lógica se mantiene temporalmente en la vista
     * para no alterar todavía el contrato existente.
     */
    private function prepararDocumentos(
        array $documentos
    ): array {

        $documentosVista = [];

        foreach (
            self::TIPOS_REQUERIDOS
            as $tipo => $config
        ) {

            $documentoEncontrado = null;

            foreach ($documentos as $documento) {

                if (
                    strtolower(
                        $documento->getTipoDocumento()
                    ) === $tipo
                ) {
                    $documentoEncontrado =
                        $documento;

                    break;
                }
            }

            $estado =
                $documentoEncontrado
                    ? $documentoEncontrado->getEstado()
                    : 'pendiente';

            $documentosVista[] = [

                'tipo' =>
                    $tipo,

                'icon' =>
                    $config['icon'],

                'title' =>
                    $config['title'],

                'description' =>
                    $config['description'],

                'status' =>
                    match ($estado) {
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        default => 'Pendiente'
                    },

                'status_icon' =>
                    match ($estado) {
                        'aprobado' => 'check_circle',
                        'rechazado' => 'cancel',
                        default => 'pending'
                    },

                'estado' =>
                    $estado,

                'documento' =>
                    $documentoEncontrado

            ];
        }

        return $documentosVista;
    }
}