<?php
declare(strict_types=1);

require_once __DIR__ . '/../Servicios/AdminService.php';

class AdminDashboardControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/admin_controller.log';

    private AdminService $adminService;

    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->adminService =
            new AdminService();
    }

    private function log(
        string $evento,
        string $nivel = 'INFO',
        array $contexto = []
    ): void
    {
        $fecha = date('Y-m-d H:i:s');

        $mensaje =
            sprintf(
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

    //==================================================
    // DASHBOARD
    //==================================================

    public function obtenerDashboard(): array
    {
        try {

            return $this
                ->adminService
                ->obtenerDashboardCompleto();

        } catch (Throwable $e) {

            $this->log(
                'Error Dashboard',
                'ERROR',
                [
                    'mensaje' =>
                        $e->getMessage()
                ]
            );

            return [
                'page_title' => 'Panel Administrativo',
                'stats' => [],
                'activities' => [],
                'indicadores' => [],
                'resumen' => []
            ];
        }
    }

    public function obtenerEstadisticas(): array
    {
        return $this
            ->adminService
            ->obtenerCardsDashboard();
    }

    public function obtenerActividadReciente(
        int $limite = 10
    ): array
    {
        return $this
            ->adminService
            ->obtenerActividadReciente();
    }

    public function obtenerIndicadores(): array
    {
        return $this
            ->adminService
            ->obtenerIndicadores();
    }

    public function obtenerResumenGeneral(): array
    {
        return $this
            ->adminService
            ->obtenerResumenGeneral();
    }

    public function obtenerUltimosUsuarios(): array
    {
        return $this
            ->adminService
            ->obtenerUltimosUsuarios();
    }

    public function obtenerUltimosCarnets(): array
    {
        return $this
            ->adminService
            ->obtenerUltimosCarnets();
    }

    public function obtenerUltimosExamenes(): array
    {
        return $this
            ->adminService
            ->obtenerUltimosExamenes();
    }

}