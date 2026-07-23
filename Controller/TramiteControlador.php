<?php
declare(strict_types=1);

/**
 * TramiteControlador
 *
 * Gestiona las solicitudes HTTP del módulo de trámites.
 */
class TramiteControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/tramite_controller.log';

    private TramiteService $tramiteService;

    /**
     * Inicializa las dependencias.
     */
    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->tramiteService =
            new TramiteService();
    }



    // =====================================================
    // DETALLE DEL TRÁMITE
    // =====================================================

    /**
     * Obtiene el detalle de un trámite.
     */
    public function obtenerDetalleTramite(
        int $idInscripcion
    ): array
    {
        
       $resultado =
            $this->tramiteService
                ->obtenerDetalleTramite(
                    $idInscripcion
                );

        $this->tramiteService->registrarLog(
            'DETALLE_TRAMITE_OBTENIDO',
            [
                'id_inscripcion' =>
                    $idInscripcion
            ]
        );
        return $resultado;
  
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
        $resultado =
            $this->tramiteService
                ->obtenerHistorialTramite(
                    $idInscripcion
                );

        $this->tramiteService->registrarLog(
            'HISTORIAL_TRAMITE_OBTENIDO',
            [
                'id_inscripcion' =>
                    $idInscripcion
            ]
        );

        return $resultado;

    }
        // =====================================================
    // CAMBIO DE ESTADO
    // =====================================================

    /**
     * Actualiza el estado de un trámite.
     */
    public function actualizarEstadoTramite(
        int $idInscripcion,
        int $idEstadoNuevo
    ): array
    {
        return $this->tramiteService
            ->actualizarEstadoTramite(
                $idInscripcion,
                $idEstadoNuevo
            );

    }

    /**
     * Cambia el estado de un trámite.
     */
    public function cambiarEstadoTramite(
        int $idInscripcion,
        string $estado
    ): array
    {


        return $this->tramiteService
            ->cambiarEstadoTramite(
                $idInscripcion,
                $estado
            );

        
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

        return $this->tramiteService
            ->registrarCambioEstado(
                $idInscripcion,
                $estadoAnterior,
                $estadoNuevo
            );

    }
}
