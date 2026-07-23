<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ReporteRepository.php';

class ReporteService
{
    private ReporteRepository $reporteRepository;

    public function __construct()
    {
        $this->reporteRepository = new ReporteRepository();
    }

    // =====================================================
    // ACTIVIDAD Y AUDITORÍA
    // =====================================================

    /**
     * Obtiene la actividad reciente.
     */
    public function obtenerActividadReciente(
        int $limite = 50
    ): array
    {
        try {

            if ($limite <= 0) {
                $limite = 50;
            }

            $actividades =
                $this->reporteRepository
                    ->obtenerActividadReciente(
                        $limite
                    );

            return [
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'actividades' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtiene el detalle de una actividad.
     */
    public function obtenerDetalleActividad(
        int $idAuditoria
    ): array
    {
        try {

            $actividad =
                $this->reporteRepository
                    ->obtenerDetalleActividad(
                        $idAuditoria
                    );

            if (!$actividad) {

                return [
                    'success' => true,
                    'actividad' => null
                ];
            }

            $actividad['valores_anteriores'] =
                !empty($actividad['datos_anteriores'])
                    ? json_decode(
                        $actividad['datos_anteriores'],
                        true
                    )
                    : [];

            $actividad['valores_nuevos'] =
                !empty($actividad['datos_nuevos'])
                    ? json_decode(
                        $actividad['datos_nuevos'],
                        true
                    )
                    : [];

            $actividad['usuario'] = [
                'id' =>
                    $actividad['usuario_id'] ?? null,

                'nombre' =>
                    trim(
                        ($actividad['nombre'] ?? '') .
                        ' ' .
                        ($actividad['apellido'] ?? '')
                    )
            ];

            return [
                'success' => true,
                'actividad' => $actividad
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'actividad' => null
            ];
        }
    }

    /**
     * Obtiene la auditoría de un usuario.
     */
    public function obtenerAuditoriaUsuario(
        int $usuarioId
    ): array
    {
        try {

            $usuario =
                $this->reporteRepository
                    ->obtenerUsuario(
                        $usuarioId
                    );

            $auditorias =
                $this->reporteRepository
                    ->obtenerAuditoriaUsuario(
                        $usuarioId
                    );

            foreach ($auditorias as &$auditoria) {

                $auditoria['datos_anteriores'] =
                    !empty($auditoria['datos_anteriores'])
                        ? json_decode(
                            $auditoria['datos_anteriores'],
                            true
                        )
                        : [];

                $auditoria['datos_nuevos'] =
                    !empty($auditoria['datos_nuevos'])
                        ? json_decode(
                            $auditoria['datos_nuevos'],
                            true
                        )
                        : [];
            }

            return [
                'success' => true,
                'usuario' => $usuario,
                'auditorias' => $auditorias,
                'total' => count($auditorias)
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'usuario' => [],
                'auditorias' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtiene la auditoría de una tabla.
     */
    public function obtenerAuditoriaTabla(
        string $tabla
    ): array
    {
        try {

            $cambios =
                $this->reporteRepository
                    ->obtenerAuditoriaTabla(
                        $tabla
                    );

            foreach ($cambios as &$cambio) {

                $cambio['datos_anteriores'] =
                    !empty($cambio['datos_anteriores'])
                        ? json_decode(
                            $cambio['datos_anteriores'],
                            true
                        )
                        : [];

                $cambio['datos_nuevos'] =
                    !empty($cambio['datos_nuevos'])
                        ? json_decode(
                            $cambio['datos_nuevos'],
                            true
                        )
                        : [];
            }

            return [
                'success' => true,
                'tabla' => $tabla,
                'cambios' => $cambios,
                'total' => count($cambios)
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'tabla' => $tabla,
                'cambios' => [],
                'total' => 0
            ];
        }
    } 
        // =====================================================
    // REPORTES PERSONALIZADOS
    // =====================================================

    /**
     * Genera un reporte personalizado.
     */
    public function generarReporte(
        string $tipo,
        array $filtros = []
    ): array
    {
        try {

            $tiposPermitidos = [
                'inscripciones',
                'usuarios',
                'carnets',
                'examenes'
            ];

            if (
                !in_array(
                    $tipo,
                    $tiposPermitidos,
                    true
                )
            ) {
                return [
                    'success' => false,
                    'message' => 'Tipo de reporte inválido.',
                    'reporte' => []
                ];
            }

            if (!empty($filtros['fecha_desde'])) {

                $fecha =
                    DateTime::createFromFormat(
                        'Y-m-d',
                        $filtros['fecha_desde']
                    );

                if (!$fecha) {
                    return [
                        'success' => false,
                        'message' => 'Fecha desde inválida.',
                        'reporte' => []
                    ];
                }
            }

            if (!empty($filtros['fecha_hasta'])) {

                $fecha =
                    DateTime::createFromFormat(
                        'Y-m-d',
                        $filtros['fecha_hasta']
                    );

                if (!$fecha) {
                    return [
                        'success' => false,
                        'message' => 'Fecha hasta inválida.',
                        'reporte' => []
                    ];
                }
            }

            $datos =
                $this->reporteRepository
                    ->obtenerReporte(
                        $tipo,
                        $filtros
                    );

            return [
                'success' => true,
                'reporte' => [
                    'tipo' => $tipo,
                    'filtros_aplicados' => $filtros,
                    'datos' => $datos,
                    'total_registros' => count($datos),
                    'fecha_generacion' =>
                        date('Y-m-d H:i:s')
                ]
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => 'Error al generar el reporte.',
                'reporte' => []
            ];
        }
    }

    /**
     * Genera un reporte completo.
     */
    public function generarReporteCompleto(
        array $filtros = []
    ): array
    {
        try {

            return [
                'success' => true,
                'reporte' => [
                    'inscripciones' =>
                        $this->reporteRepository
                            ->obtenerReporte(
                                'inscripciones',
                                $filtros
                            ),

                    'usuarios' =>
                        $this->reporteRepository
                            ->obtenerReporte(
                                'usuarios',
                                $filtros
                            ),

                    'carnets' =>
                        $this->reporteRepository
                            ->obtenerReporte(
                                'carnets',
                                $filtros
                            ),

                    'examenes' =>
                        $this->reporteRepository
                            ->obtenerReporte(
                                'examenes',
                                $filtros
                            ),

                    'fecha_generacion' =>
                        date('Y-m-d H:i:s')
                ]
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'reporte' => []
            ];
        }
    }

    /**
     * Genera un reporte para un período.
     */
    public function obtenerReportePorFecha(
        string $fechaInicio,
        string $fechaFin
    ): array
    {
        try {

            $inicio =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $fechaInicio
                );

            $fin =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $fechaFin
                );

            if (!$inicio || !$fin) {

                return [
                    'success' => false,
                    'message' => 'Las fechas son inválidas.',
                    'reporte' => []
                ];
            }

            return [
                'success' => true,
                'reporte' =>
                    $this->reporteRepository
                        ->obtenerReportePorFecha(
                            $inicio->format('Y-m-d 00:00:00'),
                            $fin->format('Y-m-d 23:59:59')
                        )
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => 'Error al generar el reporte.',
                'reporte' => []
            ];
        }
    }
        // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Obtiene las estadísticas generales.
     */
    public function obtenerEstadisticas(): array
    {
        try {

            $usuariosTotales =
                $this->reporteRepository
                    ->contarUsuarios();

            $usuariosActivos =
                $this->reporteRepository
                    ->contarUsuariosActivos();

            $inscripcionesActivas =
                $this->reporteRepository
                    ->contarInscripcionesActivas();

            $examenesPendientes =
                $this->reporteRepository
                    ->contarExamenesPendientes();

            $carnetsVigentes =
                $this->reporteRepository
                    ->contarCarnetsVigentes();

            $totalExamenes =
                $this->reporteRepository
                    ->contarExamenes();

            $aprobados =
                $this->reporteRepository
                    ->contarExamenesAprobados();

            $reprobados =
                $this->reporteRepository
                    ->contarExamenesReprobados();

            $tasaAprobacion =
                $totalExamenes > 0
                    ? round(
                        ($aprobados / $totalExamenes) * 100,
                        2
                    )
                    : 0;

            $tasaReprobacion =
                $totalExamenes > 0
                    ? round(
                        ($reprobados / $totalExamenes) * 100,
                        2
                    )
                    : 0;

            return [
                'success' => true,
                'estadisticas' => [
                    'usuarios_totales' =>
                        $usuariosTotales,

                    'usuarios_activos' =>
                        $usuariosActivos,

                    'inscripciones_activas' =>
                        $inscripcionesActivas,

                    'examenes_pendientes' =>
                        $examenesPendientes,

                    'carnets_vigentes' =>
                        $carnetsVigentes,

                    'tasa_aprobacion' =>
                        $tasaAprobacion,

                    'tasa_reprobacion' =>
                        $tasaReprobacion,

                    'promedio_tiempo_tramite' =>
                        round(
                            $this->reporteRepository
                                ->obtenerPromedioTiempoTramite(),
                            2
                        )
                ]
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Estadísticas por rol.
     */
    public function obtenerEstadisticasPorRol(): array
    {
        try {

            return [
                'success' => true,
                'estadisticas' =>
                    $this->reporteRepository
                        ->obtenerEstadisticasPorRol()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Estadísticas por estado.
     */
    public function obtenerEstadisticasPorEstado(): array
    {
        try {

            return [
                'success' => true,
                'estadisticas' =>
                    $this->reporteRepository
                        ->obtenerEstadisticasPorEstado()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Estadísticas por curso.
     */
    public function obtenerEstadisticasPorCurso(): array
    {
        try {

            return [
                'success' => true,
                'estadisticas' =>
                    $this->reporteRepository
                        ->obtenerEstadisticasPorCurso()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }
        // =====================================================
    // INDICADORES ESPECÍFICOS
    // =====================================================

    /**
     * Obtiene estadísticas de carnets.
     */
    public function obtenerCertificadosEmitidos(): array
    {
        try {

            return [
                'success' => true,
                'carnets_emitidos' =>
                    $this->reporteRepository
                        ->contarCarnetsEmitidos(),

                'carnets_vigentes' =>
                    $this->reporteRepository
                        ->contarCarnetsVigentes(),

                'carnets_vencidos' =>
                    $this->reporteRepository
                        ->contarCarnetsVencidos(),

                'en_tramite' =>
                    $this->reporteRepository
                        ->contarCarnetsEnTramite()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'carnets_emitidos' => 0,
                'carnets_vigentes' => 0,
                'carnets_vencidos' => 0,
                'en_tramite' => 0
            ];
        }
    }

    /**
     * Obtiene las inscripciones activas.
     */
    public function obtenerInscripcionesActivas(): array
    {
        try {

            $detalles =
                $this->reporteRepository
                    ->obtenerInscripcionesActivas();

            $total = 0;

            foreach ($detalles as $detalle) {

                $total +=
                    (int) $detalle['cantidad'];
            }

            return [
                'success' => true,
                'inscripciones_activas' => $total,
                'detalles' => $detalles
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'inscripciones_activas' => 0,
                'detalles' => []
            ];
        }
    }

    /**
     * Obtiene los documentos pendientes.
     */
    public function obtenerDocumentosPendientes(): array
    {
        try {

            return [
                'success' => true,
                'documentos_pendientes' =>
                    $this->reporteRepository
                        ->contarDocumentosPendientes(),

                'detalles' =>
                    $this->reporteRepository
                        ->obtenerDocumentosPendientesPorTipo()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'documentos_pendientes' => 0,
                'detalles' => []
            ];
        }
    }
        // =====================================================
    // EXPORTACIONES
    // =====================================================

    /**
     * Genera la documentación para DIPA.
     */
    public function generarDocumentacionParaDIPA(): array
    {
        try {

            $datos =
                $this->reporteRepository
                    ->obtenerDatosParaDIPA();

            return [
                'success' => true,
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'total_registros' => count($datos),
                'datos' => $datos
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'fecha_generacion' => null,
                'total_registros' => 0,
                'datos' => []
            ];
        }
    }

    /**
     * Exporta los datos para DIPA.
     */
    public function exportarParaDIPA(): array
    {
        try {

            $resultado =
                $this->generarDocumentacionParaDIPA();

            if (!$resultado['success']) {
                return $resultado;
            }

            return [
                'success' => true,
                'archivo' => $resultado['datos'],
                'total_registros' => $resultado['total_registros'],
                'fecha_generacion' => $resultado['fecha_generacion']
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'archivo' => [],
                'total_registros' => 0
            ];
        }
    }

    /**
     * Obtiene un reporte listo para descargar.
     */
    public function descargarReporte(
        string $tipo,
        array $filtros = []
    ): array
    {
        try {

            $reporte =
                $this->generarReporte(
                    $tipo,
                    $filtros
                );

            if (!$reporte['success']) {
                return $reporte;
            }

            return [
                'success' => true,
                'nombre_archivo' =>
                    sprintf(
                        '%s_%s',
                        $tipo,
                        date('Ymd_His')
                    ),
                'fecha_generacion' =>
                    date('Y-m-d H:i:s'),
                'contenido' =>
                    $reporte['reporte']
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'contenido' => []
            ];
        }
    }
}