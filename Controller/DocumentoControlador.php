<?php
declare(strict_types=1);


/**
 * DocumentoControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * DocumentoControlador
 *
 * Responsabilidades:
 * - Procesar subida de documentos.
 * - Obtener mis documentos.
 * - Obtener estado de mi documentación.
 *
 * Dependencias:
 * - DocumentoService
 * 
 * Metodos: __construct()
 * - procesarSubida()
 * - obtenerMisDocumentos()
 * - obtenerEstadoDocumentacion()
 */


require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';
require_once __DIR__ . '/../Servicios/UploadService.php';

class DocumentoControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/documento_controller.log';
    
    private DocumentoService $documentoService;
    private DocumentoRepository $documentoRepository;
    private UploadService $uploadService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->documentoService = new DocumentoService();
        $this->documentoRepository = new DocumentoRepository();
        $this->uploadService = new UploadService();
    }



    /**
     * Registrar evento en el log
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['usuario_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

  

    /**
     * Obtener estado de la documentación del usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function obtenerEstadoDocumentacion(int $usuarioId): array
    {
        try {

            return $this->documentoService
                ->obtenerEstadoDocumentacion($usuarioId);

        } catch (\Exception $e) {

            $this->registrarLog(
                'Error al obtener estado de documentación',
                [
                    'usuario_id' => $usuarioId,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'completos' => 0,
                'total' => 3,
                'porcentaje' => 0,
                'completo' => false,
                'dni' => false,
                'foto' => false,
                'asistencia' => false,
                'moodle' => false
            ];
        }
    }
    /**
     * Obtener los documentos del usuario autenticado.
     *
     * @return array [
     *     'success' => bool,
     *     'documentos' => array
     * ]
     */
    public function obtenerMisDocumentos(): array
    {
        try {

            if (empty($_SESSION['usuario_id'])) {

                return [
                    'success' => false,
                    'documentos' => []
                ];
            }

            $documentos = $this->documentoService
                ->obtenerPorUsuario(
                    (int)$_SESSION['usuario_id']
                );

            return [
                'success' => true,
                'documentos' => $documentos
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'Error al obtener documentos',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'documentos' => []
            ];
        }
    }



    // Procesa subida.
    public function procesarSubida(): void
    {
        try {

            if (empty($_SESSION['usuario_id'])) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/login'
                );

                exit;
            }

            if (
                empty($_FILES['archivo'])
                || empty($_POST['tipo_documento'])
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=error_archivo'
                );

                exit;
            }

            $usuarioId = (int)$_SESSION['usuario_id'];

            $tipoDocumento = trim($_POST['tipo_documento']);

            $archivo = $_FILES['archivo'];

            if ($archivo['error'] !== UPLOAD_ERR_OK) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=error_upload'
                );

                exit;
            }

            $resultado =
                $this->uploadService
                    ->procesarCarga(
                        $archivo,
                        UploadService::CARPETA_DOCUMENTOS,
                        $tipoDocumento
                    );

            if (!$resultado['success']) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=error_subida'
                );

                exit;
            }

            $rutaBD =
                '/uploads/documentos/' .
                $resultado['nombre'];

            $existente =
                $this->documentoRepository
                    ->obtenerPorUsuarioYTipo(
                        $usuarioId,
                        $tipoDocumento
                    );

            if ($existente) {

                $this->documentoRepository
                    ->actualizarDocumento(
                        (int)$existente['id'],
                        [
                            'nombre_original' =>
                                $resultado['nombre'],

                            'ruta_archivo' =>
                                $rutaBD
                        ]
                    );

            } else {

                $this->documentoRepository
                    ->crearDocumento([
                        'usuario_id' => $usuarioId,
                        'tipo_documento' => $tipoDocumento,
                        'nombre_original' => $archivo['name'],
                        'ruta_archivo' => $rutaBD
                    ]);
            }

            header(
                'Location: ' .
                BASE_URL .
                '/subida_documentacion?toast=documento_subido'
            );

            exit;

        } catch (\Exception $e) {

            $this->registrarLog(
                'Error al subir documento',
                [
                    'error' => $e->getMessage()
                ]
            );

            header(
                'Location: ' .
                BASE_URL .
                '/subida_documentacion?toast=error_subida'
            );

            exit;
        }
    }

    /**
     * Descarga un documento del ciudadano autenticado.
     *
     * El documento solamente puede ser descargado
     * si pertenece al usuario actualmente logueado.
     */
    public function descargarDocumento(int $documentoId): void
    {
        $usuarioId =
            (int)(
                $_SESSION['usuario_id']
                ?? 0
            );

        if ($usuarioId <= 0) {

            http_response_code(403);

            exit(
                'No autorizado.'
            );
        }

        if ($documentoId <= 0) {

            http_response_code(404);

            exit(
                'Documento no encontrado.'
            );
        }

        try {

            $documento =
                $this->documentoService
                    ->descargarDocumentoPorUsuario(
                        $documentoId,
                        $usuarioId
                    );

            if (
                empty($documento)
                || empty($documento['ruta_archivo'])
            ) {

                http_response_code(404);

                exit(
                    'Documento no encontrado.'
                );
            }

            $rutaRelativa =
                ltrim(
                    (string)$documento['ruta_archivo'],
                    '/\\'
                );

            $rutaArchivo =
                realpath(
                    __DIR__ .
                    '/../' .
                    $rutaRelativa
                );

            $directorioDocumentos =
                realpath(
                    __DIR__ .
                    '/../uploads/documentos'
                );

            if (
                $rutaArchivo === false
                || $directorioDocumentos === false
                || strpos(
                    $rutaArchivo,
                    $directorioDocumentos .
                    DIRECTORY_SEPARATOR
                ) !== 0
                || !is_file($rutaArchivo)
                || !is_readable($rutaArchivo)
            ) {

                $this->registrarLog(
                    'DOCUMENTO_NO_DISPONIBLE',
                    [
                        'usuario_id' =>
                            $usuarioId,

                        'documento_id' =>
                            $documentoId
                    ]
                );

                http_response_code(404);

                exit(
                    'El archivo del documento no está disponible.'
                );
            }

            $nombreArchivo =
                (string)(
                    $documento['nombre_original']
                    ?? 'documento'
                );

            $nombreArchivo =
                preg_replace(
                    '/[^a-zA-Z0-9._-]/',
                    '_',
                    $nombreArchivo
                );

            $this->registrarLog(
                'DOCUMENTO_DESCARGADO',
                [
                    'usuario_id' =>
                        $usuarioId,

                    'documento_id' =>
                        $documentoId
                ]
            );

            header(
                'Content-Type: application/octet-stream'
            );

            header(
                'Content-Disposition: attachment; filename="' .
                $nombreArchivo .
                '"'
            );

            header(
                'Content-Length: ' .
                filesize($rutaArchivo)
            );

            header(
                'Cache-Control: private, no-store, no-cache, must-revalidate'
            );

            header(
                'Pragma: no-cache'
            );

            header(
                'Expires: 0'
            );

            readfile(
                $rutaArchivo
            );

            exit;

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_DESCARGAR_DOCUMENTO',
                [
                    'usuario_id' =>
                        $usuarioId,

                    'documento_id' =>
                        $documentoId,

                    'error' =>
                        $e->getMessage()
                ]
            );

            http_response_code(500);

            exit(
                'No fue posible descargar el documento.'
            );
        }
    }
}
