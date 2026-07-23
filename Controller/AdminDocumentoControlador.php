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
