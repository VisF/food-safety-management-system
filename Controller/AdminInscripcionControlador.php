<?php
declare(strict_types=1);


/**
 * AdminInscripcionControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * AdminInscripcionControlador
 *
 * Administración de inscripciones.
 *
 * Responsabilidades:
 * - Listar inscripciones.
 * - Obtener detalle.
 * - Validar documentación.
 * - Rechazar documentación.
 * - Cambiar estados.
 *
 * Dependencias:
 * - InscripcionService: Servicio para operaciones de inscripciones.
 * - NotificacionControlador (opcional)
 */
require_once __DIR__ . '/../Service/InscripcionService.php';


class AdminInscripcionControlador
{
    private const LOG_FILE =
    __DIR__ . '/../logs/admin_inscripcion_controller.log';

    private InscripcionService $inscripcionService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->inscripcionService =
            new InscripcionService();
    }

    // Ejecuta log.
    private function log(
        string $evento,
        string $nivel = 'INFO',
        array $contexto = []
    ): void {

        $fecha = date('Y-m-d H:i:s');

        $mensaje = sprintf(
            "[%s] [%s] %s %s\n",
            $fecha,
            $nivel,
            $evento,
            json_encode(
                $contexto,
                JSON_UNESCAPED_UNICODE
            )
        );

        error_log(
            $mensaje,
            3,
            self::LOG_FILE
        );
    }

    // Lista inscripciones.
    public function listarInscripciones(array $filtros = []): array
    {
        try {

            $resultado =
                $this->inscripcionService
                    ->listarInscripciones($filtros);

            $limite =
                (int)($filtros['limite'] ?? 50);

            return [
                'success' => true,
                'inscripciones' =>
                    $resultado['inscripciones'],
                'total' =>
                    $resultado['total'],
                'paginas' =>
                    (int)ceil(
                        $resultado['total']
                        / max(1, $limite)
                    )
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al listar inscripciones',
                'ERROR',
                [
                    'filtros' => $filtros,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'inscripciones' => [],
                'total' => 0,
                'paginas' => 0
            ];
        }
    }

    // Obtiene inscripción.
    public function obtenerInscripcion(int $id): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerInscripcion($id);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'inscripcion' => []
                ];

            }

            return [
                'success' => true,
                'inscripcion' => $inscripcion
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al obtener inscripción',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'inscripcion' => []
            ];
        }
    }

    /**
     * Validar documentación de una inscripción.
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {

            $resultado =
                $this->inscripcionService
                    ->validarDocumentacion($id_inscripcion);

            if ($resultado['success']) {

                $this->log(
                    'Documentación validada',
                    'INFO',
                    [
                        'id_inscripcion' => $id_inscripcion
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Documentación validada correctamente',
                    'inscripcion' => []
                ];
            }

            switch ($resultado['codigo']) {

                case 'INSCRIPCION_INEXISTENTE':

                    return [
                        'success' => false,
                        'message' => 'Inscripción inexistente',
                        'inscripcion' => []
                    ];

                case 'DOCUMENTACION_INCOMPLETA':

                    return [
                        'success' => false,
                        'message' => 'Documentación incompleta: ' .
                            implode(', ', $resultado['faltantes']),
                        'inscripcion' => []
                    ];

                default:

                    return [
                        'success' => false,
                        'message' => 'No fue posible validar la documentación.',
                        'inscripcion' => []
                    ];
            }

        } catch (Throwable $e) {

            $this->log(
                'Error al validar documentación',
                'ERROR',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al validar documentación: ' . $e->getMessage(),
                'inscripcion' => []
            ];
        }
    }
    // Ejecuta rechazar documentación.
    public function rechazarDocumentacion(int $id, string $motivo): array
    {
        try {

            $resultado =
                $this->inscripcionService
                    ->rechazarDocumentacion(
                        $id,
                        $motivo
                    );

            if ($resultado['success']) {

                $this->log(
                    'Documentación rechazada',
                    'INFO',
                    [
                        'id_inscripcion' => $id,
                        'motivo' => $motivo
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Documentación rechazada correctamente',
                    'inscripcion' => []
                ];
            }

            switch ($resultado['codigo']) {

                case 'INSCRIPCION_INEXISTENTE':

                    return [
                        'success' => false,
                        'message' => 'Inscripción no encontrada',
                        'inscripcion' => []
                    ];

                default:

                    return [
                        'success' => false,
                        'message' => 'No fue posible rechazar la documentación.',
                        'inscripcion' => []
                    ];
            }

        } catch (Throwable $e) {

            $this->log(
                'Error al rechazar documentación',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al rechazar documentación: ' . $e->getMessage(),
                'inscripcion' => []
            ];
        }
    }
}
