<?php
declare(strict_types=1);


/**
 * AdminDocumentoControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * AdminDocumentoControlador
 *
 * Responsabilidades:
 * - Listar documentos.
 * - Obtener documento.
 * - Obtener documentos pendientes.
 * - Validar documento.
 * - Rechazar documento.
 * - Descargar documento.
 * - Eliminar documento.
 *
 * Dependencias:
 * - DocumentoService
 */


require_once __DIR__ . '/../Servicios/DocumentoService.php';

class AdminDocumentoControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/admin_documento_controller.log';


    private DocumentoService $documentoService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->documentoService =
            new DocumentoService();
    }

    /**
     * Registrar evento en el log.
     */
    private function log(
        string $mensaje,
        string $nivel = 'INFO',
        array $contexto = []
    ): void {

        $linea =
            sprintf(
                "[%s] [%s] %s %s\n",
                date('Y-m-d H:i:s'),
                $nivel,
                $mensaje,
                json_encode(
                    $contexto,
                    JSON_UNESCAPED_UNICODE
                )
            );

        @file_put_contents(
            self::LOG_FILE,
            $linea,
            FILE_APPEND
        );
    }

    /**
     * Construye el rango de páginas que se mostrará
     * en la paginación administrativa.
     *
     * Mantiene siempre:
     * - Primera página.
     * - Última página.
     * - Dos páginas anteriores a la actual.
     * - Página actual.
     * - Dos páginas posteriores a la actual.
     *
     * Cuando existe un salto entre páginas,
     * agrega un separador "...".
     *
     * Ejemplo:
     *
     * 1 ... 11 12 13 14 15 ... 50
     *
     * @param int $pagina
     * @param int $totalPaginas
     * @return array
     */
    private function construirPaginacion(int $pagina,int $totalPaginas): array {

        if ($totalPaginas <= 0) {
            return [];
        }


        /*
        * Si la página solicitada está fuera
        * del rango válido, la ajustamos.
        */
        $pagina =
            max(
                1,
                min(
                    $pagina,
                    $totalPaginas
                )
            );


        /*
        * Si hay pocas páginas, mostramos
        * todas directamente.
        */
        if ($totalPaginas <= 7) {

            return range(
                1,
                $totalPaginas
            );
        }


        /*
        * Páginas que estarán alrededor
        * de la página actual.
        */
        $inicio =
            max(
                2,
                $pagina - 2
            );

        $fin =
            min(
                $totalPaginas - 1,
                $pagina + 2
            );


        $paginas = [];


        /*
        * Primera página.
        */
        $paginas[] = 1;


        /*
        * Hay un salto entre la primera página
        * y el rango cercano a la página actual.
        */
        if ($inicio > 2) {

            $paginas[] = '...';
        }


        /*
        * Páginas cercanas a la actual.
        */
        for (
            $i = $inicio;
            $i <= $fin;
            $i++
        ) {

            $paginas[] = $i;
        }


        /*
        * Hay un salto entre el rango cercano
        * y la última página.
        */
        if (
            $fin < $totalPaginas - 1
        ) {

            $paginas[] = '...';
        }


        /*
        * Última página.
        */
        $paginas[] =
            $totalPaginas;


        return $paginas;
    }

    /**
     * Muestra el panel administrativo de documentación.
     *
     * Soporta:
     * - Listado paginado de ciudadanos.
     * - Límite configurable.
     * - Conservación de la página actual.
     * - Paginación inteligente.
     */
    public function mostrarIndex(): void
    {
        try {

            /*
            * ==============================================
            * PAGINACIÓN
            * ==============================================
            */

            $pagina =
                filter_input(
                    INPUT_GET,
                    'pagina',
                    FILTER_VALIDATE_INT
                );

            if (
                $pagina === false
                || $pagina === null
                || $pagina < 1
            ) {

                $pagina = 1;
            }


            $limite =
                filter_input(
                    INPUT_GET,
                    'limite',
                    FILTER_VALIDATE_INT
                );

            if (
                $limite === false
                || $limite === null
                || $limite < 1
            ) {

                $limite = 10;
            }


            /*
            * ==============================================
            * OBTENER DOCUMENTACIÓN
            * ==============================================
            */

            $resultado =
                $this->documentoService
                    ->obtenerDocumentacionAdministracion(
                        null,
                        $pagina,
                        $limite
                    );


            /*
            * ==============================================
            * DATOS DE PAGINACIÓN
            * ==============================================
            */

            $paginaActual =
                (int)(
                    $resultado['pagina']
                    ?? $pagina
                );

            $totalPaginas =
                (int)(
                    $resultado['total_paginas']
                    ?? 0
                );


            /*
            * Construimos solamente las páginas
            * que deben aparecer visualmente.
            */
            $paginas =
                $this->construirPaginacion(
                    $paginaActual,
                    $totalPaginas
                );


            /*
            * ==============================================
            * DATOS PARA LA VISTA
            * ==============================================
            */

            $data = [

                'page_title' =>
                    'Gestión de Documentación',

                'usuarios' =>
                    $resultado['usuarios']
                    ?? [],

                'resultado' =>
                    null,

                'busqueda' =>
                    '',


                /*
                * Paginación.
                */
                'pagina' =>
                    $paginaActual,

                'limite' =>
                    (int)(
                        $resultado['limite']
                        ?? $limite
                    ),

                'total' =>
                    (int)(
                        $resultado['total']
                        ?? 0
                    ),

                'total_paginas' =>
                    $totalPaginas,

                'paginas' =>
                    $paginas,

                'tiene_anterior' =>
                    $resultado['tiene_anterior']
                    ?? false,

                'tiene_siguiente' =>
                    $resultado['tiene_siguiente']
                    ?? false,


                /*
                * Errores.
                */
                'errores' =>
                    []
            ];


            /*
            * ==============================================
            * VISTA
            * ==============================================
            */

            require_once __DIR__ .
                '/../Views/admin_documentos.php';


            $vista =
                new AdminDocumentosVista();


            $vista->mostrar(
                $data
            );


        } catch (Throwable $e) {

            $this->log(
                'Error al mostrar documentación administrativa',
                'ERROR',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );


            header(
                'Location: ' .
                '/manipulacionDeAlimentos/admin' .
                '?toast=error_documentacion'
            );

            exit;
        }
    }
   /**
     * Busca la documentación de un ciudadano por DNI.
     */
    public function buscarPorDni(): void
    {
        $dni = '';

        try {

            $dni =
                trim(
                    $_GET['dni']
                    ?? ''
                );


            if ($dni === '') {

                header(
                    'Location: ' .
                    '/manipulacionDeAlimentos/admin/documentos' .
                    '?toast=dni_requerido'
                );

                exit;
            }


            /*
            * La búsqueda por DNI no utiliza paginación.
            */
            $resultado =
                $this->documentoService
                    ->obtenerDocumentacionAdministracion(
                        $dni
                    );


            $usuarios =
                $resultado['usuarios']
                ?? [];


            /*
            * El resultado corresponde a un único ciudadano.
            */
            $ciudadano =
                $usuarios[0]
                ?? null;


            $data = [

                'page_title' =>
                    'Gestión de Documentación',

                /*
                * Dejamos vacío el listado general.
                *
                * La vista mostrará el resultado de búsqueda.
                */
                'usuarios' =>
                    [],

                'resultado' =>
                    $ciudadano,

                'busqueda' =>
                    $dni,

                /*
                * No hay paginación en una búsqueda
                * por DNI.
                */
                'pagina' =>
                    1,

                'limite' =>
                    10,

                'total' =>
                    $ciudadano !== null
                        ? 1
                        : 0,

                'total_paginas' =>
                    $ciudadano !== null
                        ? 1
                        : 0,

                'tiene_anterior' =>
                    false,

                'tiene_siguiente' =>
                    false,

                'errores' =>
                    []
            ];


            require_once __DIR__ .
                '/../Views/admin_documentos.php';


            $vista =
                new AdminDocumentosVista();


            $vista->mostrar(
                $data
            );


        } catch (Throwable $e) {

            $this->log(
                'Error al buscar documentación por DNI',
                'ERROR',
                [
                    'dni' =>
                        $dni,

                    'error' =>
                        $e->getMessage()
                ]
            );


            header(
                'Location: ' .
                '/manipulacionDeAlimentos/admin/documentos' .
                '?toast=error_busqueda_documentacion'
            );

            exit;
        }
    }
    /**
     * Procesa la aprobación de un documento.
     */
    public function aprobar(int $id): void
    {
        try {

            $observaciones =
                trim(
                    $_POST['observaciones']
                    ?? ''
                );

            $resultado =
                $this->validarDocumento(
                    $id,
                    $observaciones
                );

            if (
                empty(
                    $resultado['success']
                )
            ) {

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=error_aprobar_documento'
                );

                exit;
            }

            header(
                'Location: /manipulacionDeAlimentos/admin/documentos?toast=documento_aprobado'
            );

            exit;

        } catch (Throwable $e) {

            $this->log(
                'Error al procesar aprobación',
                'ERROR',
                [
                    'id_documento' =>
                        $id,

                    'error' =>
                        $e->getMessage()
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/documentos?toast=error_aprobar_documento'
            );

            exit;
        }
    }

    /**
     * Procesa el rechazo de un documento.
     */
    public function rechazar(int $id): void
    {
        try {

            $observaciones =
                trim(
                    $_POST['observaciones']
                    ?? ''
                );

            /*
            * Para rechazar necesitamos explicar
            * el motivo del rechazo.
            */
            if ($observaciones === '') {

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=observacion_requerida'
                );

                exit;
            }

            $resultado =
                $this->rechazarDocumento(
                    $id,
                    $observaciones
                );

            if (
                empty(
                    $resultado['success']
                )
            ) {

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=error_rechazar_documento'
                );

                exit;
            }

            header(
                'Location: /manipulacionDeAlimentos/admin/documentos?toast=documento_rechazado'
            );

            exit;

        } catch (Throwable $e) {

            $this->log(
                'Error al procesar rechazo',
                'ERROR',
                [
                    'id_documento' =>
                        $id,

                    'error' =>
                        $e->getMessage()
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/documentos?toast=error_rechazar_documento'
            );

            exit;
        }
    }
    /**
     * Descarga físicamente un documento.
     */
    public function descargarArchivo(int $id): void
    {
        try {

            $documento =
                $this->documentoService
                    ->descargarDocumento(
                        $id
                    );

            if (!$documento) {

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=documento_inexistente'
                );

                exit;
            }

            $rutaRelativa =
                trim(
                    (string)(
                        $documento['ruta_archivo']
                        ?? ''
                    )
                );

            if ($rutaRelativa === '') {

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=archivo_no_disponible'
                );

                exit;
            }

            /*
            * La BD guarda, por ejemplo:
            *
            * /uploads/documentos/archivo.pdf
            *
            * Lo convertimos en una ruta física.
            */
            $rutaRelativa =
                ltrim(
                    $rutaRelativa,
                    '/\\'
                );

            $rutaFisica =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $rutaRelativa
                );

            if (
                !is_file($rutaFisica)
                || !is_readable($rutaFisica)
            ) {

                $this->log(
                    'Archivo de documento no encontrado',
                    'ERROR',
                    [
                        'id_documento' =>
                            $id,

                        'ruta' =>
                            $rutaFisica
                    ]
                );

                header(
                    'Location: /manipulacionDeAlimentos/admin/documentos?toast=archivo_no_disponible'
                );

                exit;
            }

            $nombre =
                basename(
                    (string)(
                        $documento['nombre_original']
                        ?? 'documento'
                    )
                );

            $extension =
                strtolower(
                    pathinfo(
                        $rutaFisica,
                        PATHINFO_EXTENSION
                    )
                );

            $mimeTypes = [

                'pdf' =>
                    'application/pdf',

                'jpg' =>
                    'image/jpeg',

                'jpeg' =>
                    'image/jpeg',

                'png' =>
                    'image/png',

                'webp' =>
                    'image/webp'
            ];

            $mime =
                $mimeTypes[$extension]
                ?? 'application/octet-stream';

            $this->log(
                'Documento descargado',
                'INFO',
                [
                    'id_documento' =>
                        $id
                ]
            );

            header(
                'Content-Type: ' . $mime
            );

            header(
                'Content-Disposition: attachment; filename="' .
                $nombre .
                '"'
            );

            header(
                'Content-Length: ' .
                filesize($rutaFisica)
            );

            header(
                'Cache-Control: private, no-cache'
            );

            readfile(
                $rutaFisica
            );

            exit;

        } catch (Throwable $e) {

            $this->log(
                'Error al descargar documento',
                'ERROR',
                [
                    'id_documento' =>
                        $id,

                    'error' =>
                        $e->getMessage()
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/documentos?toast=error_descargar_documento'
            );

            exit;
        }
    }

    /**
     * Listar todos los documentos.
     */
    public function listarDocumentos(): array
    {
        try {
            $datos = $this->documentoService
                ->listarDocumentos();

            return [
                'success' => true,
                'documentos' => $datos['documentos'],
                'total' => $datos['total']
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al listar documentos',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'documentos' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtener un documento por ID.
     */
    public function obtenerDocumento(int $id): array
    {
        try {
            $documento =
                $this->documentoService
                    ->obtenerDocumento($id);

            if (!$documento) {

                return [
                    'success' => false,
                    'documento' => []
                ];
            }

            return [
                'success' => true,
                'documento' => $documento
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al obtener documento',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'documento' => []
            ];
        }
    }

    /**
     * Obtener documentos pendientes.
     */
    public function obtenerPendientes(): array
    {
        try {
            $datos =
                $this->documentoService
                    ->obtenerPendientes();

            return [
                'success' => true,
                'documentos' => $datos['documentos'],
                'total' => $datos['total']
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al obtener documentos pendientes',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'documentos' => [],
                'total' => 0
            ];
        }
    }
        /**
     * Aprobar un documento.
     */
    public function validarDocumento(int $id,string $observaciones = ''): array
    {
        try {
            $resultado =
                $this->documentoService
                    ->validarDocumento(
                        $id,
                        $observaciones
                    );

            if ($resultado['success']) {

                $this->log(
                    'Documento aprobado',
                    'INFO',
                    [
                        'id_documento' => $id
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Documento aprobado correctamente'
                ];
            }

            switch ($resultado['codigo']) {

                case 'DOCUMENTO_INEXISTENTE':
                    return [
                        'success' => false,
                        'message' => 'Documento no encontrado'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'No se pudo aprobar el documento'
                    ];
            }
        } catch (Throwable $e) {

            $this->log(
                'Error al aprobar documento',
                'ERROR',
                [
                    'id_documento' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Rechazar un documento.
     */
    public function rechazarDocumento(
        int $id,
        string $observaciones = ''
    ): array
    {
        try {

            $resultado =
                $this->documentoService
                    ->rechazarDocumento(
                        $id,
                        $observaciones
                    );

            if ($resultado['success']) {

                $this->log(
                    'Documento rechazado',
                    'INFO',
                    [
                        'id_documento' => $id,
                        'observaciones' => $observaciones
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Documento rechazado correctamente'
                ];
            }

            switch ($resultado['codigo']) {

                case 'DOCUMENTO_INEXISTENTE':
                    return [
                        'success' => false,
                        'message' => 'Documento no encontrado'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'No se pudo rechazar el documento'
                    ];
            }

        } catch (Throwable $e) {

            $this->log(
                'Error al rechazar documento',
                'ERROR',
                [
                    'id_documento' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
        /**
     * Obtener la información necesaria para descargar un documento.
     */
    public function descargarDocumento(int $id): array
    {
        try {

            $documento =
                $this->documentoService
                    ->descargarDocumento($id);
            if (!$documento) {

                return [
                    'success' => false,
                    'message' => 'Documento no encontrado',
                    'documento' => null
                ];
            }

            $this->log(
                'Documento descargado',
                'INFO',
                [
                    'id_documento' => $id
                ]
            );

            return [
                'success' => true,
                'message' => 'Documento encontrado',
                'documento' => $documento
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al descargar documento',
                'ERROR',
                [
                    'id_documento' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'documento' => null
            ];
        }
    }

    /**
     * Eliminar un documento.
     */
    public function eliminarDocumento(int $id): array
    {
        try {

            $resultado =
                $this->documentoService
                    ->eliminarDocumento($id);

            if ($resultado['success']) {

                $this->log(
                    'Documento eliminado',
                    'INFO',
                    [
                        'id_documento' => $id
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Documento eliminado correctamente'
                ];
            }

            switch ($resultado['codigo']) {

                case 'DOCUMENTO_INEXISTENTE':
                    return [
                        'success' => false,
                        'message' => 'Documento no encontrado'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'No se pudo eliminar el documento'
                    ];
            }

        } catch (Throwable $e) {

            $this->log(
                'Error al eliminar documento',
                'ERROR',
                [
                    'id_documento' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
