<?php
declare(strict_types=1);

require_once __DIR__ . '/../Repository/NotificacionRepository.php';
require_once __DIR__ . '/../Repository/UsuarioRepository.php';

class NotificacionService
{
    private NotificacionRepository
        $notificacionRepository;

    private UsuarioRepository
        $usuarioRepository;

    public function __construct()
    {
        $this->notificacionRepository =
            new NotificacionRepository();

        $this->usuarioRepository =
            new UsuarioRepository();
    }

    // =====================================================
    // CONSULTAS
    // =====================================================

    /**
     * Obtiene una notificación.
     */
    public function obtenerPorId(
        int $id
    ): ?array
    {
        return
            $this->notificacionRepository
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
            $this->notificacionRepository
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
            $this->notificacionRepository
                ->obtenerHistorial(
                    $usuarioId
                );
    }

    /**
     * Obtiene todas las notificaciones
     * de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array
    {
        return
            $this->notificacionRepository
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
            $this->notificacionRepository
                ->obtenerPorTipo(
                    $tipo
                );
    }

    // =====================================================
    // CREACIÓN
    // =====================================================

    /**
     * Registra una notificación.
     */
    public function crear(
        array $datos
    ): ?array
    {
        $id =
            $this->notificacionRepository
                ->crear($datos);

        if ($id <= 0) {
            return null;
        }

        return
            $this->notificacionRepository
                ->obtenerPorId($id);
    }

    /**
     * Envía una notificación.
     */
    public function enviarNotificacion(
        int $usuarioId,
        string $tipo,
        array $datos = []
    ): array
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorId(
                    $usuarioId
                );

        if (!$usuario) {

            return [
                'success' => false,
                'mensaje' =>
                    'Usuario inexistente'
            ];
        }

        $notificacion =
            $this->crear([
                'usuario_id' =>
                    $usuarioId,
                'tipo' =>
                    $tipo,
                'asunto' =>
                    $datos['asunto']
                    ?? 'Notificación',
                'mensaje' =>
                    $datos['mensaje']
                    ?? ''
            ]);

        if ($notificacion === null) {

            return [
                'success' => false,
                'mensaje' =>
                    'No fue posible registrar la notificación'
            ];
        }

        return [
            'success' => true,
            'mensaje' =>
                'Notificación registrada correctamente',
            'notificacion' =>
                $notificacion
        ];
    }
    // =====================================================
    // MODIFICACIONES
    // =====================================================

    /**
     * Marca una notificación como enviada.
     */
    public function marcarEnviada(
        int $id
    ): bool
    {
        return
            $this->notificacionRepository
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
            $this->notificacionRepository
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
        if (
            !$this->notificacionRepository
                ->existe($id)
        ) {
            return false;
        }

        return
            $this->notificacionRepository
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
            $this->notificacionRepository
                ->eliminarPorUsuario(
                    $usuarioId
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
            $this->notificacionRepository
                ->contarPendientes();
    }

    /**
     * Cuenta las notificaciones enviadas.
     */
    public function contarEnviadas(): int
    {
        return
            $this->notificacionRepository
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
            $this->notificacionRepository
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
            $this->notificacionRepository
                ->contarPorTipo(
                    $tipo
                );
    }

    // =====================================================
    // COLA
    // =====================================================

    /**
     * Obtiene la cola de notificaciones.
     */
    public function obtenerColaPendiente(
        int $limite = 100
    ): array
    {
        return
            $this->notificacionRepository
                ->obtenerColaPendiente(
                    $limite
                );
    }
        // =====================================================
    // NOTIFICACIONES DEL SISTEMA
    // =====================================================

    /**
     * Notifica el resultado de un examen.
     */
    public function enviarResultadoExamen(
        int $resultadoId
    ): bool
    {
        $resultado =
            $this->resultadoRepository
                ->obtenerPorId(
                    $resultadoId
                );

        if (!$resultado) {
            return false;
        }

        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId(
                    (int)$resultado['inscripcion_id']
                );

        if (!$inscripcion) {
            return false;
        }

        return
            $this->crear([
                'usuario_id' =>
                    $inscripcion['usuario_id'],
                'tipo' =>
                    'resultado_examen',
                'asunto' =>
                    'Resultado de examen',
                'mensaje' =>
                    'El resultado de su examen ya se encuentra disponible.'
            ]) !== null;
    }

    /**
     * Notifica la emisión de un carnet.
     */
    public function enviarCarnetEmitido(
        int $carnetId
    ): bool
    {
        $carnet =
            $this->carnetRepository
                ->obtenerPorId(
                    $carnetId
                );

        if (!$carnet) {
            return false;
        }

        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId(
                    (int)$carnet['inscripcion_id']
                );

        if (!$inscripcion) {
            return false;
        }

        return
            $this->crear([
                'usuario_id' =>
                    $inscripcion['usuario_id'],
                'tipo' =>
                    'carnet_emitido',
                'asunto' =>
                    'Carnet emitido',
                'mensaje' =>
                    'Su carnet fue emitido correctamente.'
            ]) !== null;
    }

    /**
     * Notifica una inscripción confirmada.
     */
    public function enviarConfirmacionInscripcion(
        int $inscripcionId
    ): bool
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId(
                    $inscripcionId
                );

        if (!$inscripcion) {
            return false;
        }

        return
            $this->crear([
                'usuario_id' =>
                    $inscripcion['usuario_id'],
                'tipo' =>
                    'inscripcion_confirmada',
                'asunto' =>
                    'Inscripción confirmada',
                'mensaje' =>
                    'Su inscripción fue registrada correctamente.'
            ]) !== null;
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
            $this->crear([
                'usuario_id' =>
                    $usuarioId,
                'tipo' =>
                    'documentacion_aprobada',
                'asunto' =>
                    'Documentación aprobada',
                'mensaje' =>
                    'Toda su documentación fue aprobada correctamente.'
            ]) !== null;
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
            $this->crear([
                'usuario_id' =>
                    $usuarioId,
                'tipo' =>
                    'documentacion_rechazada',
                'asunto' =>
                    'Documentación rechazada',
                'mensaje' =>
                    $motivo
            ]) !== null;
    }
        // =====================================================
    // RECUPERACIÓN DE CONTRASEÑA
    // =====================================================

    /**
     * Registra una notificación de
     * recuperación de contraseña.
     */
    public function enviarRecuperacionPassword(
        int $usuarioId
    ): bool
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorId(
                    $usuarioId
                );

        if (!$usuario) {
            return false;
        }

        return
            $this->crear([
                'usuario_id' =>
                    $usuarioId,
                'tipo' =>
                    'recuperacion_password',
                'asunto' =>
                    'Recuperación de contraseña',
                'mensaje' =>
                    'Se solicitó una recuperación de contraseña para su cuenta.'
            ]) !== null;
    }

    // =====================================================
    // UTILIDADES
    // =====================================================

    /**
     * Verifica si existen notificaciones
     * pendientes para un usuario.
     */
    public function tienePendientes(
        int $usuarioId
    ): bool
    {
        return
            $this->contarPorUsuario(
                $usuarioId
            ) > 0;
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
            $this->notificacionRepository
                ->obtenerUltimaPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Verifica la existencia
     * de una notificación.
     */
    public function existe(
        int $id
    ): bool
    {
        return
            $this->notificacionRepository
                ->existe($id);
    }
}