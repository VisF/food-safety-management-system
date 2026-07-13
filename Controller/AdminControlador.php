<?php
declare(strict_types=1);


/**
 * AdminControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * AdminControlador - Gestión administrativa del sistema
 * 
 * Dependencias esperadas:
 * -  cursoService, FechacursoService, inscripcionService, 
 *   DocumentoService, usuarioService, AdminService 
 * 
 * Vistas esperadas:
 * - vistas/panel_admin.php
 * - vistas/crear_curso.php
 * - vistas/crear_examen.php
 * - vistas/validacion_documentos.php
 * - vistas/crear_respuesta_admin.php
 */

class AdminControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    
    private ?CursoService $cursoService = null;
    private ?FechacursoService $fechacursoService = null;
    private ?InscripcionService $inscripcionService = null;
    private ?DocumentoService $DocumentoService = null;
    private ?UsuarioService $usuarioService = null;
    private AdminService $adminService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->cursoService = new cursoService();
        $this->fechacursoService = new FechacursoService();
        $this->inscripcionService = new inscripcionService();
        $this->DocumentoService = new DocumentoService();
        $this->usuarioService = new usuarioService();
        $this->adminService = new AdminService();
    }



    /**
     * Registrar eventos en log
     */
    private function log(string $event, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = "[$timestamp] [$level] {$event} | {$contextStr}\n";
        error_log($message, 3, self::LOG_FILE);
    }

    // ==================== GESTIÓN DE CURSOS ====================

    // ==================== GESTIÓN DE EXÁMENES ====================


    // ==================== GESTIÓN DE INSCRIPCIONES ====================


    // ==================== GESTIÓN DE SOLICITUDES ====================

        public function responderSolicitud(int $id_solicitud, array $respuesta): array
        {
            try {

                $sol =
                    $this->adminService
                        ->obtenerSolicitud(
                            $id_solicitud
                        );

                if (!$sol) {

                    return [
                        'success' => false,
                        'message' => 'Solicitud no encontrada',
                        'id_respuesta' => null
                    ];

                }

                $idRespuesta =
                    $this->adminService
                        ->responderSolicitud(
                            $id_solicitud,
                            $respuesta
                        );

                if (
                    !empty($sol['usuario_id'])
                    &&
                    class_exists('NotificacionControlador')
                ) {

                    try {

                        $nc =
                            new NotificacionControlador();

                        if (
                            method_exists(
                                $nc,
                                'enviarNotificacion'
                            )
                        ) {

                            $nc->enviarNotificacion(
                                (int)$sol['usuario_id'],
                                'solicitud_respondida',
                                [
                                    'mensaje' =>
                                        $respuesta['contenido'] ?? ''
                                ]
                            );

                        }

                    } catch (Exception $e) {
                    }

                }

                $this->log(
                    'Solicitud respondida',
                    'INFO',
                    [
                        'id_solicitud' => $id_solicitud,
                        'id_respuesta' => $idRespuesta
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Respuesta registrada correctamente',
                    'id_respuesta' => $idRespuesta
                ];

            } catch (Exception $e) {

                $this->log(
                    'Error al responder solicitud',
                    'ERROR',
                    [
                        'id_solicitud' => $id_solicitud,
                        'error' => $e->getMessage()
                    ]
                );

                return [
                    'success' => false,
                    'message' => 'Error al responder solicitud: ' . $e->getMessage(),
                    'id_respuesta' => null
                ];

            }
        }

    // Obtiene solicitudes pendientes.
    public function obtenerSolicitudesPendientes(): array
        {
            try {

                $rows =
                    $this->adminService
                        ->obtenerSolicitudesPendientes();

                return [

                    'success' => true,

                    'solicitudes' => $rows,

                    'total' => count($rows)

                ];

            } catch (Exception $e) {

                $this->log(
                    'Error al obtener solicitudes pendientes',
                    'ERROR',
                    [
                        'error' => $e->getMessage()
                    ]
                );

                return [

                    'success' => false,

                    'solicitudes' => [],

                    'total' => 0

                ];

            }
        }

    // ==================== GESTIÓN DE USUARIOS ====================


    // ==================== EXPORTACIÓN DE DATOS ====================

    public function exportarDatos(string $formato): array
        {
            try {

                $formato =
                    strtolower(
                        trim($formato)
                    );

                if (
                    !in_array(
                        $formato,
                        [
                            'csv',
                            'json',
                            'excel'
                        ]
                    )
                ) {

                    return [
                        'success' => false,
                        'message' => 'Formato no soportado',
                        'archivo' => null
                    ];

                }

                $rows =
                    $this->adminService
                        ->obtenerDatosExportacion();

                $dir =
                    __DIR__ . '/../descargas';

                @mkdir(
                    $dir,
                    0755,
                    true
                );

                $archivo =
                    $dir .
                    '/export_' .
                    date('Ymd_His') .
                    '.' .
                    (
                        $formato === 'json'
                            ? 'json'
                            : 'csv'
                    );

                if ($formato === 'json') {

                    file_put_contents(
                        $archivo,
                        json_encode(
                            $rows,
                            JSON_UNESCAPED_UNICODE
                        )
                    );

                } else {

                    $fp =
                        fopen(
                            $archivo,
                            'w'
                        );

                    if ($fp === false) {

                        return [
                            'success' => false,
                            'message' => 'No se pudo crear el archivo',
                            'archivo' => null
                        ];

                    }

                    if (!empty($rows)) {

                        fputcsv(
                            $fp,
                            array_keys(
                                $rows[0]
                            )
                        );

                    }

                    foreach ($rows as $row) {

                        fputcsv(
                            $fp,
                            $row
                        );

                    }

                    fclose($fp);

                }

                $this->log(
                    'Datos exportados',
                    'INFO',
                    [
                        'formato' => $formato,
                        'archivo' => $archivo
                    ]
                );

                return [

                    'success' => true,

                    'message' => 'Datos exportados correctamente',

                    'archivo' => $archivo

                ];

            } catch (Exception $e) {

                $this->log(
                    'Error al exportar datos',
                    'ERROR',
                    [
                        'error' => $e->getMessage()
                    ]
                );

                return [

                    'success' => false,

                    'message' => 'Error al exportar datos: ' . $e->getMessage(),

                    'archivo' => null

                ];

            }
        }

    // ==================== ESTADÍSTICAS Y REPORTES ====================

   
    
}
