<?php

declare(strict_types=1);

require_once __DIR__ . '/../Servicios/ConsultaPublicaService.php';
require_once __DIR__ . '/../Views/consulta_publica.php';

class ConsultaPublicaControlador
{
    private ConsultaPublicaService $consultaPublicaService;

    public function __construct()
    {
        $this->consultaPublicaService =
            new ConsultaPublicaService();
    }

    /**
     * Muestra la consulta pública de carnets.
     */
    public function mostrar(): void
    {
        $dni =
            trim(
                (string)(
                    $_GET['dni'] ?? ''
                )
            );

        $data =
            $this->consultaPublicaService
                ->consultarPorDni($dni);

        $vista =
            new ConsultaPublicaVista();

        $vista->mostrar(
            $data
        );
    }

    /**
     * Descarga el PDF oficial del carnet.
     */
    public function descargarCarnet(
        int $idCarnet
    ): void {

        if ($idCarnet <= 0) {

            http_response_code(404);

            exit(
                'Carnet no encontrado.'
            );
        }

        $archivo =
            $this->consultaPublicaService
                ->descargarCarnet(
                    $idCarnet
                );

        if ($archivo === null) {

            http_response_code(404);

            exit(
                'Carnet no encontrado.'
            );
        }

        $rutaPdf =
            trim(
                (string)(
                    $archivo['ruta_pdf']
                    ?? ''
                )
            );

        if ($rutaPdf === '') {

            http_response_code(404);

            exit(
                'El archivo del carnet no está disponible.'
            );
        }

        $rutaRelativa =
            ltrim(
                $rutaPdf,
                '/\\'
            );

        $ruta =
            realpath(
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $rutaRelativa
                )
            );

        $directorioCarnets =
            realpath(
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                'uploads' .
                DIRECTORY_SEPARATOR .
                'carnets'
            );

        if (
            $ruta === false
            || $directorioCarnets === false
            || strpos(
                $ruta,
                $directorioCarnets .
                DIRECTORY_SEPARATOR
            ) !== 0
            || !is_file($ruta)
            || !is_readable($ruta)
        ) {

            http_response_code(404);

            exit(
                'El archivo del carnet no existe.'
            );
        }

        $nombreArchivo =
            basename(
                (string)(
                    $archivo['nombre_archivo']
                    ?? 'carnet.pdf'
                )
            );

        header(
            'Content-Type: application/pdf'
        );

        header(
            'Content-Disposition: attachment; filename="' .
            $nombreArchivo .
            '"'
        );

        header(
            'Content-Length: ' .
            filesize($ruta)
        );

        header(
            'Cache-Control: private, no-cache'
        );

        readfile(
            $ruta
        );

        exit;
    }

    /**
     * Descarga la foto asociada al carnet.
     */
    public function descargarFoto(
        int $idCarnet
    ): void {

        if ($idCarnet <= 0) {

            http_response_code(404);

            exit(
                'Foto no encontrada.'
            );
        }

        $foto =
            $this->consultaPublicaService
                ->descargarFotoPorCarnet(
                    $idCarnet
                );

        if ($foto === null) {

            http_response_code(404);

            exit(
                'Foto no encontrada.'
            );
        }

        $rutaFoto =
            trim(
                (string)(
                    $foto['ruta_archivo']
                    ?? ''
                )
            );

        if ($rutaFoto === '') {

            http_response_code(404);

            exit(
                'La foto del carnet no está disponible.'
            );
        }

        $rutaRelativa =
            ltrim(
                $rutaFoto,
                '/\\'
            );

        $ruta =
            realpath(
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $rutaRelativa
                )
            );

        $directorioDocumentos =
            realpath(
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                'uploads' .
                DIRECTORY_SEPARATOR .
                'documentos'
            );

        if (
            $ruta === false
            || $directorioDocumentos === false
            || strpos(
                $ruta,
                $directorioDocumentos .
                DIRECTORY_SEPARATOR
            ) !== 0
            || !is_file($ruta)
            || !is_readable($ruta)
        ) {

            http_response_code(404);

            exit(
                'El archivo de la foto no existe.'
            );
        }

        /*
         * La tabla documentos no tiene tipo_mime,
         * por lo que determinamos el MIME mediante
         * la extensión real del archivo.
         */
        $extension =
            strtolower(
                pathinfo(
                    $ruta,
                    PATHINFO_EXTENSION
                )
            );

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp'
        ];

        $mime =
            $mimeTypes[$extension]
            ?? 'application/octet-stream';

        $nombreArchivo =
            basename(
                (string)(
                    $foto['nombre_original']
                    ?? 'foto_carnet'
                )
            );

        header(
            'Content-Type: ' . $mime
        );

        header(
            'Content-Disposition: attachment; filename="' .
            $nombreArchivo .
            '"'
        );

        header(
            'Content-Length: ' .
            filesize($ruta)
        );

        header(
            'Cache-Control: private, no-cache'
        );

        readfile(
            $ruta
        );

        exit;
    }
}