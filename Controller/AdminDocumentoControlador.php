<?php
declare(strict_types=1);

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
 * - DocumentoRepository
 */

require_once __DIR__ . '/../Repository/DocumentoRepository.php';

class AdminDocumentoControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/admin_documento_controller.log';

    private DocumentoRepository $documentoRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->documentoRepository =
            new DocumentoRepository();
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

            $documentos =
                $this->documentoRepository
                    ->listarDocumentos();

            return [
                'success' => true,
                'documentos' => $documentos,
                'total' => count($documentos)
            ];

        } catch (Exception $e) {

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
                $this->documentoRepository
                    ->obtenerPorId($id);

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

        } catch (Exception $e) {

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

            $documentos =
                $this->documentoRepository
                    ->obtenerPendientes();

            return [
                'success' => true,
                'documentos' => $documentos,
                'total' => count($documentos)
            ];

        } catch (Exception $e) {

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
    public function validarDocumento(
        int $id,
        string $observaciones = ''
    ): array
    {
        try {

            $documento =
                $this->documentoRepository
                    ->obtenerPorId($id);

            if (!$documento) {

                return [
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ];
            }

            $ok =
                $this->documentoRepository
                    ->validarDocumento(
                        $id,
                        $observaciones
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'message' => 'No se pudo aprobar el documento'
                ];
            }

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

        } catch (Exception $e) {

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

            $documento =
                $this->documentoRepository
                    ->obtenerPorId($id);

            if (!$documento) {

                return [
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ];
            }

            $ok =
                $this->documentoRepository
                    ->rechazarDocumento(
                        $id,
                        $observaciones
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'message' => 'No se pudo rechazar el documento'
                ];
            }

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

        } catch (Exception $e) {

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
                $this->documentoRepository
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

        } catch (Exception $e) {

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

            $documento =
                $this->documentoRepository
                    ->obtenerPorId($id);

            if (!$documento) {

                return [
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ];
            }

            $ok =
                $this->documentoRepository
                    ->eliminarDocumento($id);

            if (!$ok) {

                return [
                    'success' => false,
                    'message' => 'No se pudo eliminar el documento'
                ];
            }

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

        } catch (Exception $e) {

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