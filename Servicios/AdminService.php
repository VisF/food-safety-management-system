<?php

declare(strict_types=1);

require_once __DIR__.'/../Repository/AdminRepository.php';

class AdminService
{
    private AdminRepository $adminRepository;

    public function __construct(
        ?AdminRepository $adminRepository = null
    )
    {
        $this->adminRepository =
            $adminRepository
            ?? new AdminRepository();
    }

    //====================================================
    // DASHBOARD
    //====================================================

    public function obtenerDashboard(): array
    {
        return [
            'page_title' => 'Panel Administrativo',

            'stats' => $this->obtenerCardsDashboard(),

            'activities' =>
                $this->adminRepository
                    ->obtenerActividadReciente(10)
        ];
    }

    public function obtenerCardsDashboard(): array
    {
        $stats =
            $this->adminRepository
                ->obtenerEstadisticas();

        return [

            [
                'label' => 'TOTAL USUARIOS',
                'value' =>
                    number_format(
                        $stats['total_usuarios']
                    ),
                'icon' => 'groups',
                'style' => 'primary'
            ],

            [
                'label' => 'INSCRIPCIONES PENDIENTES',
                'value' =>
                    number_format(
                        $stats['inscripciones_pendientes']
                    ),
                'icon' => 'schedule',
                'style' => 'secondary'
            ],

            [
                'label' => 'APROBADOS',
                'value' =>
                    number_format(
                        $stats['inscripciones_aprobadas']
                    ),
                'icon' => 'check_circle',
                'style' => 'success'
            ],

            [
                'label' => 'CARNETS EMITIDOS',
                'value' =>
                    number_format(
                        $stats['carnets_emitidos']
                    ),
                'icon' => 'badge',
                'style' => 'primary'
            ]

        ];
    }

    public function obtenerActividadReciente(): array
    {
        return $this
            ->adminRepository
            ->obtenerActividadReciente();
    }

    public function obtenerResumenGeneral(): array
    {
        return $this
            ->adminRepository
            ->obtenerResumenGeneral();
    }

    public function obtenerEstadisticas(): array
    {
        return $this
            ->adminRepository
            ->obtenerEstadisticas();
    }
 
    //====================================================
    // USUARIOS
    //====================================================

    public function obtenerUsuarios(): array
    {
        return $this
            ->adminRepository
            ->obtenerUsuarios();
    }

    public function obtenerUsuarioPorId(int $id): ?array
    {
        return $this
            ->adminRepository
            ->obtenerUsuarioPorId($id);
    }

    public function buscarUsuarios(string $texto): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return [];
        }

        return $this
            ->adminRepository
            ->buscarUsuarios($texto);
    }

    public function actualizarUsuario(
        int $id,
        array $datos
    ): bool
    {
        return $this
            ->adminRepository
            ->actualizarUsuario(
                $id,
                $datos
            );
    }

    public function cambiarEstadoUsuario(
        int $id,
        bool $activo
    ): bool
    {
        return $this
            ->adminRepository
            ->cambiarEstadoUsuario(
                $id,
                $activo
            );
    }

    //====================================================
    // DOCUMENTOS
    //====================================================

    public function obtenerDocumentosPendientes(): array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentosPendientes();
    }

    public function obtenerDocumentosAprobados(): array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentosAprobados();
    }

    public function obtenerDocumentosRechazados(): array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentosRechazados();
    }

    public function obtenerDocumentoPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentoPorId($id);
    }

    public function aprobarDocumento(
        int $id,
        string $observaciones = ''
    ): bool
    {
        return $this
            ->adminRepository
            ->aprobarDocumento(
                $id,
                $observaciones
            );
    }

    public function rechazarDocumento(
        int $id,
        string $motivo
    ): bool
    {
        return $this
            ->adminRepository
            ->rechazarDocumento(
                $id,
                $motivo
            );
    }

    //====================================================
    // EXÁMENES
    //====================================================

    public function obtenerExamenes(): array
    {
        return $this
            ->adminRepository
            ->obtenerExamenes();
    }

    public function obtenerExamenPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerExamenPorId($id);
    }

    public function obtenerProximosExamenes(
        int $limite = 10
    ): array
    {
        return $this
            ->adminRepository
            ->obtenerProximosExamenes(
                $limite
            );
    }

    public function actualizarExamen(
        int $id,
        array $datos
    ): bool
    {
        return $this
            ->adminRepository
            ->actualizarExamen(
                $id,
                $datos
            );
    }

    public function eliminarExamen(
        int $id
    ): bool
    {
        return $this
            ->adminRepository
            ->eliminarExamen($id);
    }

    //====================================================
    // CARNETS
    //====================================================

    public function obtenerCarnets(): array
    {
        return $this
            ->adminRepository
            ->obtenerCarnets();
    }

    public function obtenerCarnetPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetPorId($id);
    }

    public function obtenerCarnetsVigentes(): array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetsVigentes();
    }

    public function obtenerCarnetsVencidos(): array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetsVencidos();
    }

    public function renovarCarnet(
        int $id,
        string $fecha
    ): bool
    {
        return $this
            ->adminRepository
            ->renovarCarnet(
                $id,
                $fecha
            );
    }

    public function anularCarnet(
        int $id
    ): bool
    {
        return $this
            ->adminRepository
            ->anularCarnet($id);
    }

        //====================================================
    // INSCRIPCIONES
    //====================================================

    public function obtenerInscripciones(): array
    {
        return $this->adminRepository
            ->obtenerInscripciones();
    }

    public function obtenerInscripcionPorId(
        int $id
    ): ?array
    {
        return $this->adminRepository
            ->obtenerInscripcionPorId($id);
    }

    public function obtenerInscripcionesPendientes(): array
    {
        return $this->adminRepository
            ->obtenerInscripcionesPendientes();
    }

    //====================================================
    // BUSQUEDAS
    //====================================================

    public function buscarPorDni(
        string $dni
    ): ?array
    {
        $dni = preg_replace('/\D/', '', $dni);

        if (strlen($dni) < 7) {
            return null;
        }

        return $this->adminRepository
            ->buscarPorDni($dni);
    }

    public function buscarUsuario(
        string $texto
    ): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return [];
        }

        return $this->adminRepository
            ->buscarUsuario($texto);
    }

    //====================================================
    // DASHBOARD
    //====================================================

    public function obtenerUltimosUsuarios(
        int $cantidad = 10
    ): array
    {
        return $this->adminRepository
            ->obtenerUltimosUsuarios($cantidad);
    }

    public function obtenerUltimosCarnets(
        int $cantidad = 10
    ): array
    {
        return $this->adminRepository
            ->obtenerUltimosCarnets($cantidad);
    }

    public function obtenerUltimosExamenes(
        int $cantidad = 10
    ): array
    {
        return $this->adminRepository
            ->obtenerUltimosExamenes($cantidad);
    }

    //====================================================
    // INDICADORES
    //====================================================

    public function obtenerIndicadores(): array
    {
        $estadisticas =
            $this->adminRepository
                ->obtenerEstadisticas();

        return [

            'usuarios' =>
                $estadisticas['total_usuarios'] ?? 0,

            'pendientes' =>
                $estadisticas['inscripciones_pendientes'] ?? 0,

            'aprobados' =>
                $estadisticas['inscripciones_aprobadas'] ?? 0,

            'carnets' =>
                $estadisticas['carnets_emitidos'] ?? 0,

            'porcentaje_aprobacion' =>
                $estadisticas['tasa_aprobacion'] ?? 0

        ];
    }

    public function obtenerDashboardCompleto(): array
    {
        return [

            'page_title' => 'Panel Administrativo',

            'stats' =>
                $this->obtenerCardsDashboard(),

            'activities' =>
                $this->obtenerActividadReciente(),

            'indicadores' =>
                $this->obtenerIndicadores(),

            'resumen' =>
                $this->obtenerResumenGeneral()

        ];
    }

    //====================================================
    // REPORTES
    //====================================================

    public function obtenerReporteGeneral(): array
    {
        return [

            'usuarios' =>
                $this->obtenerUsuarios(),

            'documentosPendientes' =>
                $this->obtenerDocumentosPendientes(),

            'proximosExamenes' =>
                $this->obtenerProximosExamenes(10),

            'carnetsVencidos' =>
                $this->obtenerCarnetsVencidos()

        ];
    }

    public function obtenerDatosInspector(
        string $dni
    ): ?array
    {
        return $this->buscarPorDni($dni);
    }

    //====================================================
    // EXPORTACION
    //====================================================

    public function exportarResumen(): array
    {
        return [

            'estadisticas' =>
                $this->obtenerEstadisticas(),

            'actividad' =>
                $this->obtenerActividadReciente(),

            'usuarios' =>
                count($this->obtenerUsuarios()),

            'documentosPendientes' =>
                count($this->obtenerDocumentosPendientes())

        ];
    }
    /**
     * Obtener solicitud.
     */
    public function obtenerSolicitud(int $id): ?array
    {
        return
            $this->adminRepository
                ->obtenerSolicitud($id);
    }
    /**
     * Responder solicitud.
     */
    public function responderSolicitud(int $idSolicitud, array $respuesta): ?int
    {
        return
            $this->adminRepository
                ->responderSolicitud(
                    $idSolicitud,
                    $respuesta
                );
    }
    /**
     * Obtener solicitudes pendientes.
     */
    public function obtenerSolicitudesPendientes(): array
    {
        return
            $this->adminRepository
                ->obtenerSolicitudesPendientes();
    }
    /**
     * Exportar datos.
     */
    public function obtenerDatosExportacion(): array
    {
        return
            $this->adminRepository
                ->obtenerDatosExportacion();
    }


}