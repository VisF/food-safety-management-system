<?php
declare(strict_types=1);

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
 * - InscripcionRepository
 * - DocumentoRepository
 * - NotificacionControlador (opcional)
 */
require_once __DIR__ . '/../Repository/InscripcionRepository.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';

class AdminInscripcionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_inscripcion_controller.log';
    
    private InscripcionRepository $inscripcionRepository;
    private DocumentoRepository $documentoRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->inscripcionRepository = new InscripcionRepository();

        $this->documentoRepository = new DocumentoRepository();
    }

    
    public function listarInscripciones(array $filtros = []): array
{
    try {

        $inscripciones = $this->inscripcionRepository
            ->listarInscripciones($filtros);

        $total = $this->inscripcionRepository
            ->contarInscripciones($filtros);

        $limite = (int)($filtros['limite'] ?? 50);

        return [
            'success' => true,
            'inscripciones' => $inscripciones,
            'total' => $total,
            'paginas' => (int)ceil($total / max(1, $limite))
        ];

    } catch (Exception $e) {

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

    public function obtenerInscripcion(int $id): array
    {
        try {

            $inscripcion = $this->inscripcionRepository
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

        } catch (Exception $e) {

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
     * Validar documentación de una inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'inscripcion' => array
     * ]
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {

            $inscripcion = $this->inscripcionRepository
                ->obtenerInscripcion($id_inscripcion);

            
            if (!$inscripcion) {
                return [
                    'success' => false,
                    'message' => 'Inscripción inexistente',
                    'inscripcion' => []
                ];
            }

            $docs = $this->documentoRepository
                ->obtenerPorUsuario(
                    (int)$inscripcion['usuario_id']
                );

            $faltantes = [];

            foreach ($docs as $documento) {

                if (($documento['estado'] ?? 'pendiente') !== 'aprobado') {

                    $faltantes[] = $documento['tipo_documento'];

                }

            }

            if (!empty($faltantes)) {

                return [
                    'success' => false,
                    'message' => 'Documentación incompleta: ' . implode(', ', $faltantes),
                    'inscripcion' => $docs
                ];

            }

            $this->inscripcionRepository->actualizarEstadoInscripcion(
                $id_inscripcion,
                EstadoTramite::DOCUMENTACION_APROBADA
            );

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
                'inscripcion' => $docs
            ];

        } catch (Exception $e) {

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

    public function rechazarDocumentacion(int $id, string $motivo): array
    {
        try {

            $ins = $this->inscripcionRepository->obtenerInscripcion($id);

            if (!$ins) {
                return [
                    'success' => false,
                    'message' => 'Inscripción no encontrada',
                    'inscripcion' => []
                ];
            }

            $this->inscripcionRepository->actualizarEstadoInscripcion(
                $id,
                EstadoTramite::RECHAZADO
            );

            $this->inscripcionRepository->agregarObservacion(
                $id,
                "\nRechazo: " . $motivo
            );

            $this->log(
                'Documentación rechazada',
                'INFO',
                [
                    'id_inscripcion' => $id,
                    'motivo' => $motivo
                ]
            );

            if (class_exists('NotificacionControlador')) {

                try {

                    $nc = new NotificacionControlador();

                    if (method_exists($nc, 'enviarNotificacion')) {

                        $nc->enviarNotificacion(
                            (int)$ins['usuario_id'],
                            'documentacion_rechazada',
                            [
                                'motivo' => $motivo
                            ]
                        );

                    }

                } catch (Exception $e) {
                    // Se ignora el error de notificación para no afectar el flujo principal.
                }

            }

            return [
                'success' => true,
                'message' => 'Documentación rechazada, usuario notificado',
                'inscripcion' => $ins
            ];

        } catch (Exception $e) {

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
