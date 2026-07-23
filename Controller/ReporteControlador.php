<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ReporteService.php';

class ReporteControlador
{
    private ReporteService $reporteService;

    public function __construct()
    {
        $this->reporteService = new ReporteService();
    }

    // =====================================================
    // ACTIVIDAD Y AUDITORÍA
    // =====================================================

    /**
     * Actividad reciente.
     */
    public function obtenerActividadReciente(
        int $limite = 50
    ): array
    {
        return $this->reporteService
            ->obtenerActividadReciente(
                $limite
            );
    }

    /**
     * Detalle de una actividad.
     */
    public function obtenerDetalleActividad(
        int $idAuditoria
    ): array
    {
        return $this->reporteService
            ->obtenerDetalleActividad(
                $idAuditoria
            );
    }

    /**
     * Auditoría de un usuario.
     */
    public function obtenerAuditoriaUsuario(
        int $usuarioId
    ): array
    {
        return $this->reporteService
            ->obtenerAuditoriaUsuario(
                $usuarioId
            );
    }

    /**
     * Auditoría de una tabla.
     */
    public function obtenerAuditoriaTabla(
        string $tabla
    ): array
    {
        return $this->reporteService
            ->obtenerAuditoriaTabla(
                $tabla
            );
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
        return $this->reporteService
            ->generarReporte(
                $tipo,
                $filtros
            );
    }

    /**
     * Genera un reporte completo.
     */
    public function generarReporteCompleto(
        array $filtros = []
    ): array
    {
        return $this->reporteService
            ->generarReporteCompleto(
                $filtros
            );
    }

    /**
     * Genera un reporte por período.
     */
    public function obtenerReportePorFecha(
        string $fechaInicio,
        string $fechaFin
    ): array
    {
        return $this->reporteService
            ->obtenerReportePorFecha(
                $fechaInicio,
                $fechaFin
            );
    }

        // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Obtiene las estadísticas generales.
     */
    public function obtenerEstadisticas(): array
    {
        return $this->reporteService
            ->obtenerEstadisticas();
    }

    /**
     * Obtiene las estadísticas por rol.
     */
    public function obtenerEstadisticasPorRol(): array
    {
        return $this->reporteService
            ->obtenerEstadisticasPorRol();
    }

    /**
     * Obtiene las estadísticas por estado.
     */
    public function obtenerEstadisticasPorEstado(): array
    {
        return $this->reporteService
            ->obtenerEstadisticasPorEstado();
    }

    /**
     * Obtiene las estadísticas por curso.
     */
    public function obtenerEstadisticasPorCurso(): array
    {
        return $this->reporteService
            ->obtenerEstadisticasPorCurso();
    }

        // =====================================================
    // INDICADORES ESPECÍFICOS
    // =====================================================

    /**
     * Obtiene las estadísticas de carnets emitidos.
     */
    public function obtenerCertificadosEmitidos(): array
    {
        return $this->reporteService
            ->obtenerCertificadosEmitidos();
    }

    /**
     * Obtiene las inscripciones activas.
     */
    public function obtenerInscripcionesActivas(): array
    {
        return $this->reporteService
            ->obtenerInscripcionesActivas();
    }

    /**
     * Obtiene los documentos pendientes.
     */
    public function obtenerDocumentosPendientes(): array
    {
        return $this->reporteService
            ->obtenerDocumentosPendientes();
    }

        // =====================================================
    // EXPORTACIONES
    // =====================================================

    /**
     * Genera la documentación para DIPA.
     */
    public function generarDocumentacionParaDIPA(): array
    {
        return $this->reporteService
            ->generarDocumentacionParaDIPA();
    }

    /**
     * Exporta un examen para DIPA.
     */
    public function exportarParaDIPA(
        int $idExamen
    ): array
    {
        return $this->reporteService
            ->exportarParaDIPA(
                $idExamen
            );
    }

    /**
     * Descarga un reporte.
     */
    public function descargarReporte(
        string $tipo,
        array $filtros = []
    ): array
    {
        return $this->reporteService
            ->descargarReporte(
                $tipo,
                $filtros
            );
    }
}