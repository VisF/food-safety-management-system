<?php

declare(strict_types=1);


/**
 * AdminService - Servicio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__.'/../Repository/AdminRepository.php';

class AdminService
{
    private AdminRepository $adminRepository;

    // Inicializa las dependencias de la clase.
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

    // Obtiene cards dashboard.
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

    // Obtiene actividad reciente.
    public function obtenerActividadReciente(): array
    {
        return $this
            ->adminRepository
            ->obtenerActividadReciente();
    }

    // Obtiene resumen general.
    public function obtenerResumenGeneral(): array
    {
        return $this
            ->adminRepository
            ->obtenerResumenGeneral();
    }

    // Obtiene estadisticas.
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

    // Obtiene usuario por id.
    public function obtenerUsuarioPorId(int $id): ?array
    {
        return $this
            ->adminRepository
            ->obtenerUsuarioPorId($id);
    }

    // Busca usuarios.
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

    // Actualiza usuario.
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

    // Ejecuta cambiar estado usuario.
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

    // Obtiene documentos aprobados.
    public function obtenerDocumentosAprobados(): array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentosAprobados();
    }

    // Obtiene documentos rechazados.
    public function obtenerDocumentosRechazados(): array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentosRechazados();
    }

    // Obtiene documento por id.
    public function obtenerDocumentoPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerDocumentoPorId($id);
    }

    // Ejecuta aprobar documento.
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

    // Ejecuta rechazar documento.
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

    // Obtiene examen por id.
    public function obtenerExamenPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerExamenPorId($id);
    }

    // Obtiene proximos examenes.
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

    // Actualiza examen.
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

    // Elimina examen.
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

    // Obtiene carnet por id.
    public function obtenerCarnetPorId(
        int $id
    ): ?array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetPorId($id);
    }

    // Obtiene carnets vigentes.
    public function obtenerCarnetsVigentes(): array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetsVigentes();
    }

    // Obtiene carnets vencidos.
    public function obtenerCarnetsVencidos(): array
    {
        return $this
            ->adminRepository
            ->obtenerCarnetsVencidos();
    }

    // Ejecuta renovar carnet.
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

    // Ejecuta anular carnet.
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

    // Obtiene inscripcion por id.
    public function obtenerInscripcionPorId(
        int $id
    ): ?array
    {
        return $this->adminRepository
            ->obtenerInscripcionPorId($id);
    }

    // Obtiene inscripciones pendientes.
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

    // Busca usuario.
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

    // Obtiene ultimos carnets.
    public function obtenerUltimosCarnets(
        int $cantidad = 10
    ): array
    {
        return $this->adminRepository
            ->obtenerUltimosCarnets($cantidad);
    }

    // Obtiene ultimos examenes.
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

    // Obtiene dashboard completo.
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

    // Obtiene datos inspector.
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
