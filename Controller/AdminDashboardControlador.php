<?php
declare(strict_types=1);

/**
 * Dashboard administrativo.
 *
 * Responsabilidades:
 * - Obtener estadísticas generales.
 * - Obtener actividad reciente.
 *
 * Dependencias:
 * - AdminRepository
 */
require_once __DIR__ . '/../Repository/AdminRepository.php';


class AdminDashboardControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    


    private AdminRepository $adminRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->adminRepository = new AdminRepository();

    }
    /**
     * Obtener estadísticas generales del sistema
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     'total_usuarios' => int,
     *     'usuarios_activos' => int,
     *     'total_inscripciones' => int,
     *     'inscripciones_pendientes' => int,
     *     'inscripciones_aprobadas' => int,
     *     'total_exámenes' => int,
     *     'tasa_aprobacion' => float,
     *     'carnets_emitidos' => int
     *   ]
     * ]
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $estadisticas = $this->adminRepository->obtenerEstadisticas();

            return [
                'success' => true,
                'estadisticas' => $estadisticas
            ];
        } catch (Exception $e) {
            $this->log(
                'Error al obtener estadísticas',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }
    public function obtenerActividadReciente(int $limite = 20): array
    {
        try {

            $actividades = $this->adminRepository
                ->obtenerActividadReciente($limite);

            return [
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al obtener actividad reciente',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'actividades' => [],
                'total' => 0
            ];
        }
    }

}
