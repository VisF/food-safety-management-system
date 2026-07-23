<?php
declare(strict_types=1);

/**
 * AdminReporteControlador
 *
 * Gestiona la generación de reportes administrativos.
 * Toda la lógica de negocio se delega al ReporteService.
 */
require_once __DIR__ . '/../services/ReporteService.php';
require_once __DIR__ . '/../services/CursoService.php';
require_once __DIR__ . '/../services/FechacursoService.php';
require_once __DIR__ . '/../services/InscripcionService.php';
require_once __DIR__ . '/../services/DocumentoService.php';
require_once __DIR__ . '/../services/UsuarioService.php';

class AdminReporteControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';

    private ?CursoService $cursoService = null;
    private ?FechacursoService $fechacursoService = null;
    private ?InscripcionService $inscripcionService = null;
    private ?DocumentoService $documentoService = null;
    private ?UsuarioService $usuarioService = null;
    private ?ReporteService $reporteService = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->cursoService       = new CursoService();
        $this->fechacursoService  = new FechacursoService();
        $this->inscripcionService = new InscripcionService();
        $this->documentoService   = new DocumentoService();
        $this->usuarioService     = new UsuarioService();
        $this->reporteService     = new ReporteService();
    }

    /**
     * Obtiene el reporte de actividad entre dos fechas.
     */
    public function obtenerReportePorFecha(string $fechaInicio, string $fechaFin): array
    {
        try {

            $resultado = $this->reporteService->obtenerReportePorFecha(
                $fechaInicio,
                $fechaFin
            );

            if ($resultado['success']) {
                $this->log(
                    'Reporte generado',
                    'INFO',
                    [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin'    => $fechaFin
                    ]
                );
            }

            return $resultado;

        } catch (Throwable $e) {

            $this->log(
                'Error al generar reporte',
                'ERROR',
                [
                    'error' => $e->getMessage(),
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin
                ]
            );

            return [
                'success' => false,
                'message' => 'Ocurrió un error al generar el reporte.',
                'reporte' => []
            ];
        }
    }

    // TODO
    // exportarDatos()
}