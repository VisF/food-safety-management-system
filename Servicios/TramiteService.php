<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/TramiteRepository.php';

class TramiteService
{
    private const LOG_FILE =
        __DIR__ . '/../logs/tramite_service.log';

    private TramiteRepository $tramiteRepository;

    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->tramiteRepository =
            new TramiteRepository();
    }

    /**
     * Registra un evento.
     */
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void
    {
        $timestamp =
            date('Y-m-d H:i:s');

        $usuario =
            $_SESSION['user_id']
            ?? 'anonimo';

        $mensaje =
            "[$timestamp] " .
            "Usuario: {$usuario} | " .
            "Evento: {$evento} | " .
            "Datos: " .
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            ) .
            PHP_EOL;

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }

    // =====================================================
    // DETALLE DEL TRÁMITE
    // =====================================================

    /**
     * Obtiene el detalle completo
     * de un trámite.
     */
    public function obtenerDetalleTramite(
        int $idInscripcion
    ): array
    {
        try {

            $inscripcion =
                $this->tramiteRepository
                    ->obtenerInscripcion(
                        $idInscripcion
                    );

            if (!$inscripcion) {
                return [];
            }

            $estado =
                $this->tramiteRepository
                    ->obtenerEstado(
                        (int)(
                            $inscripcion['estado_tramite_id']
                            ??
                            $inscripcion['id_estado']
                            ??
                            0
                        )
                    );

            $estadisticas =
                $this->tramiteRepository
                    ->obtenerEstadisticasDocumentacion(
                        (int)$inscripcion['usuario_id']
                    );

            $resultadoExamen =
                $this->tramiteRepository
                    ->obtenerResultadoExamen(
                        $idInscripcion
                    );

            $carnet =
                $this->tramiteRepository
                    ->obtenerCarnet(
                        $idInscripcion
                    );

            $detalle = [

                'inscripcion' =>
                    $inscripcion,

                'estado' =>
                    $estado,

                'documentacion' => [

                    'documentos_totales' =>
                        (int)$estadisticas['total'],

                    'documentos_validados' =>
                        (int)$estadisticas['validados'],

                    'documentos_pendientes' =>
                        max(
                            0,
                            (int)$estadisticas['total']
                            -
                            (int)$estadisticas['validados']
                        ),

                    'completada' =>
                        (
                            (int)$estadisticas['total'] > 0
                            &&
                            (int)$estadisticas['total']
                            ===
                            (int)$estadisticas['validados']
                        )
                ],

                'resultado_examen' =>
                    $resultadoExamen,

                'carnet' =>
                    $carnet,

                'fecha_ultima_modificacion' =>
                    $inscripcion['fecha_ultima_modificacion']
                    ??
                    $inscripcion['fecha_inscripcion']
            ];

            $this->registrarLog(
                'DETALLE_TRAMITE_OBTENIDO',
                [
                    'id_inscripcion' =>
                        $idInscripcion
                ]
            );

            return $detalle;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_DETALLE_TRAMITE',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [];
        }
    }
        // =====================================================
    // HISTORIAL
    // =====================================================

    /**
     * Obtiene el historial de un trámite.
     */
    public function obtenerHistorialTramite(
        int $idInscripcion
    ): array
    {
        try {

            $historial =
                $this->tramiteRepository
                    ->obtenerHistorialTramite(
                        $idInscripcion
                    );

            $this->registrarLog(
                'HISTORIAL_TRAMITE_OBTENIDO',
                [
                    'id_inscripcion' =>
                        $idInscripcion
                ]
            );

            return $historial;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_HISTORIAL_TRAMITE',
                [
                    'id_inscripcion' =>
                        $idInscripcion,
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [];
        }
    }

    // =====================================================
    // COMPROBANTES
    // =====================================================

    public function obtenerComprobanteDescargable(
        int $idInscripcion
    ): array
    {
        try {

            $resultado =
                $this->tramiteRepository
                    ->obtenerComprobante(
                        $idInscripcion
                    );

            if (!$resultado) {
                return [
                    'success' => false,
                    'ruta_pdf' => null,
                    'nombre' => null,
                    'mensaje' =>
                        'No se encontró comprobante'
                ];
            }

            $this->registrarLog(
                'COMPROBANTE_GENERADO',
                [
                    'id_inscripcion' =>
                        $idInscripcion
                ]
            );

            return [
                'success' => true,
                'ruta_pdf' =>
                    $resultado['ruta_pdf'],
                'nombre' =>
                    'comprobante_tramite_' .
                    $idInscripcion .
                    '.pdf',
                'mensaje' =>
                    'Comprobante generado',
                'comprobante' =>
                    $resultado
            ];

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_GENERAR_COMPROBANTE',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'ruta_pdf' => null,
                'nombre' => null,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    // =====================================================
    // CARNET
    // =====================================================

    public function obtenerCarnet(
        int $idInscripcion
    ): ?array
    {
        return $this->tramiteRepository
            ->obtenerCarnetPorInscripcion(
                $idInscripcion
            );
    }

    public function verificarVigenciaCarnet(
        int $idCarnet
    ): array
    {
        $carnet =
            $this->tramiteRepository
                ->obtenerCarnetPorId(
                    $idCarnet
                );

        if (!$carnet) {
            return [
                'vigente' => false,
                'mensaje' =>
                    'Carnet no encontrado'
            ];
        }

        $hoy = new DateTime();

        $vencimiento =
            new DateTime(
                $carnet['fecha_vencimiento']
            );

        $vigente =
            $vencimiento >= $hoy;

        $dias =
            max(
                0,
                $hoy->diff($vencimiento)->days
            );

        $this->tramiteRepository
            ->actualizarVigenciaCarnet(
                $idCarnet,
                $vigente
            );

        return [
            'vigente' =>
                $vigente,
            'fecha_vencimiento' =>
                $carnet['fecha_vencimiento'],
            'dias_restantes' =>
                $dias,
            'mensaje' =>
                $vigente
                    ? "Carnet vigente ($dias días)"
                    : "Carnet vencido"
        ];
    }

    // =====================================================
    // LISTADOS
    // =====================================================

    public function obtenerTramitesUsuario(
        int $usuarioId
    ): array
    {
        return $this->tramiteRepository
            ->obtenerTramitesUsuario(
                $usuarioId
            );
    }

    public function obtenerTramitesPendientes(): array
    {
        return $this->tramiteRepository
            ->obtenerTramitesPendientes();
    }

    public function obtenerEstadisticasTramites(): array
    {
        return [
            'total_tramites' =>
                $this->tramiteRepository
                    ->contarTramites(),

            'por_estado' =>
                $this->tramiteRepository
                    ->obtenerCantidadPorEstado(),

            'aprobados' =>
                $this->tramiteRepository
                    ->contarPorEstado(
                        EstadoTramite::APROBADO
                    ),

            'rechazados' =>
                $this->tramiteRepository
                    ->contarPorEstado(
                        EstadoTramite::RECHAZADO
                    ),

            'en_tramite' =>
                $this->tramiteRepository
                    ->contarPorEstados([
                        EstadoTramite::PENDIENTE,
                        EstadoTramite::DOCUMENTACION_PENDIENTE,
                        EstadoTramite::DOCUMENTACION_APROBADA,
                        EstadoTramite::INSCRIPTO_EXAMEN
                    ]),

            'dias_promedio_tramite' =>
                round(
                    $this->tramiteRepository
                        ->obtenerPromedioDiasTramite(),
                    2
                )
        ];
    }
  /**
     * Actualiza el estado de un trámite.
     */
    public function actualizarEstadoTramite(
        int $idInscripcion,
        int $idEstadoNuevo
    ): array
    {
        try {

            $inscripcion =
                $this->tramiteRepository
                    ->obtenerInscripcion(
                        $idInscripcion
                    );

            if (!$inscripcion) {
                return [
                    'success' => false,
                    'mensaje' => 'Inscripción no encontrada',
                    'estado_anterior' => null,
                    'estado_nuevo' => $idEstadoNuevo
                ];
            }

            $estadoAnterior =
                (int)(
                    $inscripcion['estado_tramite_id']
                    ??
                    $inscripcion['id_estado']
                    ??
                    0
                );

            if (
                !$this->tramiteRepository
                    ->existeEstado(
                        $idEstadoNuevo
                    )
            ) {
                return [
                    'success' => false,
                    'mensaje' => 'Estado destino inválido',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $idEstadoNuevo
                ];
            }

            $usuarioAdmin =
                $_SESSION['user_id']
                ?? null;

            $this->tramiteRepository
                ->iniciarTransaccion();

            $ok =
                $this->tramiteRepository
                    ->actualizarEstadoInscripcion(
                        $idInscripcion,
                        $idEstadoNuevo
                    );

            if (!$ok) {
                throw new RuntimeException(
                    'No se pudo actualizar el estado de la inscripción.'
                );
            }

            $historial =
                $this->tramiteRepository
                    ->registrarHistorial(
                        $idInscripcion,
                        $estadoAnterior,
                        $idEstadoNuevo,
                        null,
                        $usuarioAdmin
                    );

            if (!$historial) {
                throw new RuntimeException(
                    'No se pudo registrar el historial.'
                );
            }

            $auditoria =
                $this->tramiteRepository
                    ->registrarAuditoria(
                        $usuarioAdmin,
                        'inscripciones',
                        $idInscripcion,
                        'CAMBIO_ESTADO',
                        [
                            'estado_anterior' =>
                                $estadoAnterior
                        ],
                        [
                            'estado_nuevo' =>
                                $idEstadoNuevo
                        ]
                    );

            if (!$auditoria) {
                throw new RuntimeException(
                    'No se pudo registrar la auditoría.'
                );
            }

            $this->tramiteRepository
                ->confirmarTransaccion();

            return [
                'success' => true,
                'mensaje' =>
                    'Estado actualizado exitosamente',
                'estado_anterior' =>
                    $estadoAnterior,
                'estado_nuevo' =>
                    $idEstadoNuevo
            ];

        } catch (Throwable $e) {

            $this->tramiteRepository
                ->cancelarTransaccion();

            $this->registrarLog(
                'ERROR_ACTUALIZAR_ESTADO_TRAMITE',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' =>
                    'Error al actualizar estado: '
                    . $e->getMessage(),
                'estado_anterior' =>
                    $estadoAnterior ?? null,
                'estado_nuevo' =>
                    $idEstadoNuevo
            ];
        }
    }

    /**
     * Registra un cambio de estado.
     */
    public function registrarCambioEstado(
        int $idInscripcion,
        int $estadoAnterior,
        int $estadoNuevo
    ): array
    {
        try {

            $usuarioAdmin =
                $_SESSION['user_id']
                ?? null;

            $this->tramiteRepository
                ->iniciarTransaccion();

            $historial =
                $this->tramiteRepository
                    ->registrarHistorial(
                        $idInscripcion,
                        $estadoAnterior,
                        $estadoNuevo,
                        null,
                        $usuarioAdmin
                    );

            if (!$historial) {
                throw new RuntimeException(
                    'No se pudo registrar el historial.'
                );
            }

            $auditoria =
                $this->tramiteRepository
                    ->registrarAuditoria(
                        $usuarioAdmin,
                        'inscripciones',
                        $idInscripcion,
                        'CAMBIO_ESTADO',
                        [
                            'estado_anterior' =>
                                $estadoAnterior
                        ],
                        [
                            'estado_nuevo' =>
                                $estadoNuevo
                        ]
                    );

            if (!$auditoria) {
                throw new RuntimeException(
                    'No se pudo registrar la auditoría.'
                );
            }

            $idRegistro =
                $this->tramiteRepository
                    ->obtenerUltimoId();

            $this->tramiteRepository
                ->confirmarTransaccion();

            return [
                'success' => true,
                'mensaje' =>
                    'Cambio de estado registrado',
                'id_registro' =>
                    $idRegistro
            ];

        } catch (Throwable $e) {

            $this->tramiteRepository
                ->cancelarTransaccion();

            $this->registrarLog(
                'ERROR_REGISTRAR_CAMBIO_ESTADO',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' =>
                    'Error al registrar cambio de estado: '
                    . $e->getMessage(),
                'id_registro' => null
            ];
        }
    }
    /**
     * Cambia el estado de un trámite.
     */
    public function cambiarEstadoTramite(
        int $idInscripcion,
        string $estado
    ): array
    {
        try {

            $idEstado =
                EstadoTramite::desdeNombre(
                    $estado
                );

            if ($idEstado === null) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'Estado no válido'
                ];
            }

            return $this->actualizarEstadoTramite(
                $idInscripcion,
                $idEstado
            );

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_CAMBIAR_ESTADO_TRAMITE',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' =>
                    $e->getMessage()
            ];
        }
    }
    
}
