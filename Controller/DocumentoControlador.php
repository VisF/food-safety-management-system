<?php
declare(strict_types=1);

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


require_once __DIR__ . '/../Services/DocumentoService.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';


class DocumentoControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/documento_controller.log';
    
    private DocumentoService $documentoService;
    private DocumentoRepository $documentoRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->documentoService = new DocumentoService();
        $this->documentoRepository = new DocumentoRepository();
    }



    /**
     * Registrar evento en el log
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
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

        $extension = strtolower(
            pathinfo(
                $archivo['name'],
                PATHINFO_EXTENSION
            )
        );

        $permitidas = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];

        if (!in_array($extension, $permitidas, true)) {

            header(
                'Location: ' .
                BASE_URL .
                '/subida_documentacion?toast=formato_invalido'
            );

            exit;
        }

        $directorio = __DIR__ . '/../uploads';

        if (!is_dir($directorio)) {

            mkdir(
                $directorio,
                0775,
                true
            );
        }

        $nombreArchivo =
            $tipoDocumento .
            '_usuario_' .
            $usuarioId .
            '_' .
            time() .
            '.' .
            $extension;

        $rutaFisica =
            $directorio .
            '/' .
            $nombreArchivo;

        move_uploaded_file(
            $archivo['tmp_name'],
            $rutaFisica
        );

        $rutaBD =
            '/uploads/' .
            $nombreArchivo;

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
                        'nombre_original' => $archivo['name'],
                        'ruta_archivo' => $rutaBD
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
}
