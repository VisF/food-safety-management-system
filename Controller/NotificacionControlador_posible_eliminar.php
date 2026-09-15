<?php
declare(strict_types=1);

require_once __DIR__ . '/../Servicios/NotificacionService.php';

class NotificacionControlador
{
    private NotificacionService
        $notificacionService;

    public function __construct()
    {
        $this->notificacionService =
            new NotificacionService();
    }

    // =====================================================
    // NOTIFICACIONES
    // =====================================================

    /**
     * Envía una notificación.
     */
    public function enviarNotificacion(
        int $usuarioId,
        string $tipo,
        array $datos = []
    ): array
    {
        return
            $this->notificacionService
                ->enviarNotificacion(
                    $usuarioId,
                    $tipo,
                    $datos
                );
    }

    /**
     * Obtiene una notificación.
     */
    public function obtenerPorId(
        int $id
    ): ?array
    {
        return
            $this->notificacionService
                ->obtenerPorId($id);
    }

    /**
     * Obtiene las notificaciones
     * pendientes de un usuario.
     */
    public function obtenerPendientes(
        int $usuarioId
    ): array
    {
        return
            $this->notificacionService
                ->obtenerPendientes(
                    $usuarioId
                );
    }

    /**
     * Obtiene el historial
     * de un usuario.
     */
    public function obtenerHistorial(
        int $usuarioId
    ): array
    {
        return
            $this->notificacionService
                ->obtenerHistorial(
                    $usuarioId
                );
    }

    /**
     * Obtiene las notificaciones
     * de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array
    {
        return
            $this->notificacionService
                ->obtenerPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Obtiene notificaciones
     * por tipo.
     */
    public function obtenerPorTipo(
        string $tipo
    ): array
    {
        return
            $this->notificacionService
                ->obtenerPorTipo(
                    $tipo
                );
    }

        // =====================================================
    // ADMINISTRACIÓN
    // =====================================================

    /**
     * Marca una notificación como enviada.
     */
    public function marcarEnviada(
        int $id
    ): bool
    {
        return
            $this->notificacionService
                ->marcarEnviada($id);
    }

    /**
     * Marca todas las notificaciones
     * de un usuario como enviadas.
     */
    public function marcarTodasEnviadas(
        int $usuarioId
    ): bool
    {
        return
            $this->notificacionService
                ->marcarTodasEnviadas(
                    $usuarioId
                );
    }

    /**
     * Elimina una notificación.
     */
    public function eliminar(
        int $id
    ): bool
    {
        return
            $this->notificacionService
                ->eliminar($id);
    }

    /**
     * Elimina todas las notificaciones
     * de un usuario.
     */
    public function eliminarPorUsuario(
        int $usuarioId
    ): bool
    {
        return
            $this->notificacionService
                ->eliminarPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Obtiene la cola
     * de notificaciones.
     */
    public function obtenerColaPendiente(
        int $limite = 100
    ): array
    {
        return
            $this->notificacionService
                ->obtenerColaPendiente(
                    $limite
                );
    }

    // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Cuenta las notificaciones pendientes.
     */
    public function contarPendientes(): int
    {
        return
            $this->notificacionService
                ->contarPendientes();
    }

    /**
     * Cuenta las notificaciones enviadas.
     */
    public function contarEnviadas(): int
    {
        return
            $this->notificacionService
                ->contarEnviadas();
    }

    /**
     * Cuenta las notificaciones
     * de un usuario.
     */
    public function contarPorUsuario(
        int $usuarioId
    ): int
    {
        return
            $this->notificacionService
                ->contarPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Cuenta las notificaciones
     * de un tipo.
     */
    public function contarPorTipo(
        string $tipo
    ): int
    {
        return
            $this->notificacionService
                ->contarPorTipo(
                    $tipo
                );
    }

        // =====================================================
    // NOTIFICACIONES DEL SISTEMA
    // =====================================================

    /**
     * Envía una notificación
     * de resultado de examen.
     */
    public function enviarResultadoExamen(
        int $resultadoId
    ): bool
    {
        return
            $this->notificacionService
                ->enviarResultadoExamen(
                    $resultadoId
                );
    }

    /**
     * Envía una notificación
     * de carnet emitido.
     */
    public function enviarCarnetEmitido(
        int $carnetId
    ): bool
    {
        return
            $this->notificacionService
                ->enviarCarnetEmitido(
                    $carnetId
                );
    }

    /**
     * Envía una confirmación
     * de inscripción.
     */
    public function enviarConfirmacionInscripcion(
        int $inscripcionId
    ): bool
    {
        return
            $this->notificacionService
                ->enviarConfirmacionInscripcion(
                    $inscripcionId
                );
    }

    /**
     * Notifica la aprobación
     * de la documentación.
     */
    public function enviarDocumentacionAprobada(
        int $usuarioId
    ): bool
    {
        return
            $this->notificacionService
                ->enviarDocumentacionAprobada(
                    $usuarioId
                );
    }

    /**
     * Notifica el rechazo
     * de la documentación.
     */
    public function enviarDocumentacionRechazada(
        int $usuarioId,
        string $motivo
    ): bool
    {
        return
            $this->notificacionService
                ->enviarDocumentacionRechazada(
                    $usuarioId,
                    $motivo
                );
    }

    /**
     * Registra una solicitud
     * de recuperación de contraseña.
     */
    public function enviarRecuperacionPassword(
        int $usuarioId
    ): bool
    {
        return
            $this->notificacionService
                ->enviarRecuperacionPassword(
                    $usuarioId
                );
    }

        // =====================================================
    // UTILIDADES
    // =====================================================

    /**
     * Verifica si un usuario
     * posee notificaciones pendientes.
     */
    public function tienePendientes(
        int $usuarioId
    ): bool
    {
        return
            $this->notificacionService
                ->tienePendientes(
                    $usuarioId
                );
    }

    /**
     * Obtiene la última notificación
     * de un usuario.
     */
    public function obtenerUltimaPorUsuario(
        int $usuarioId
    ): ?array
    {
        return
            $this->notificacionService
                ->obtenerUltimaPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Verifica si una notificación existe.
     */
    public function existe(
        int $id
    ): bool
    {
        return
            $this->notificacionService
                ->existe($id);
    }
}