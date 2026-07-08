<?php
declare(strict_types=1);

/**
 * Servicio para gestionar notificaciones.
 *
 * Métodos:
 * crear()
 * enviarNotificacion()
 * obtenerPorId()
 * obtenerPendientes()
 * obtenerPorUsuario()
 * obtenerPorTipo()
 * marcarEnviada()
 * eliminar()
 * enviarRecuperacionPassword()
 * enviarResultadoExamen()
 * enviarCarnetEmitido()
 * enviarConfirmacionInscripcion()
 * enviarDocumentacionAprobada()
 * enviarDocumentacionRechazada()
 */

require_once __DIR__ . '/../Repository/NotificacionRepository.php';
require_once __DIR__ . '/../Repository/UsuarioRepository.php';
require_once __DIR__ . '/../Repository/InscripcionRepository.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';
require_once __DIR__ . '/../Repository/ResultadoExamenRepository.php';
require_once __DIR__ . '/../Repository/CarnetRepository.php';
require_once __DIR__ . '/../Repository/CursoRepository.php';

class NotificacionService
{
    private NotificacionRepository $notificacionRepository;
    private UsuarioRepository $usuarioRepository;
    private InscripcionRepository $inscripcionRepository;
    private DocumentoRepository $documentoRepository;
    private ResultadoExamenRepository $resultadoRepository;
    private CarnetRepository $carnetRepository;
    private CursoRepository $cursoRepository;

    public function __construct()
    {
        $this->notificacionRepository =
            new NotificacionRepository();

        $this->usuarioRepository =
            new UsuarioRepository();

        $this->inscripcionRepository =
            new InscripcionRepository();

        $this->documentoRepository =
            new DocumentoRepository();

        $this->resultadoRepository =
            new ResultadoExamenRepository();

        $this->carnetRepository =
            new CarnetRepository();

        $this->cursoRepository =
            new CursoRepository();
    }

    /**
     * Crear notificación.
     */
    public function crear(
        array $datos
    ): ?array
    {
        $id =
            $this->notificacionRepository
                ->crear($datos);

        if (!$id) {
            return null;
        }

        return
            $this->notificacionRepository
                ->obtenerPorId($id);
    }

    /**
     * Obtener una notificación.
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
     * Obtener pendientes.
     */
    public function obtenerPendientes(): array
    {
        return
            $this->notificacionRepository
                ->obtenerPendientes();
    }

    /**
     * Obtener por usuario.
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
     * Obtener por tipo.
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
        /**
     * Marcar una notificación como enviada.
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
     * Eliminar una notificación.
     */
    public function eliminar(
        int $id
    ): bool
    {
        return
            $this->notificacionRepository
                ->eliminar($id);
    }

    /**
     * Enviar una notificación.
     */
    public function enviarNotificacion(
        int $usuarioId,
        string $tipo,
        array $payload = []
    ): array
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorId($usuarioId);

        if (!$usuario) {
            return [
                'success' => false,
                'mensaje' => 'Usuario inexistente'
            ];
        }

        $asunto =
            $payload['asunto']
            ?? 'Notificación';

        $mensaje =
            $payload['mensaje']
            ?? '';

        $notificacion =
            $this->crear([
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'asunto' => $asunto,
                'mensaje' => $mensaje
            ]);

        if (!$notificacion) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo crear la notificación'
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Notificación registrada correctamente',
            'notificacion' => $notificacion
        ];
    }

    /**
     * Obtener notificaciones no enviadas.
     */
    public function obtenerNoEnviadas(): array
    {
        return
            $this->notificacionRepository
                ->obtenerNoEnviadas();
    }

    /**
     * Obtener por estado.
     */
    public function obtenerPorEstado(
        bool $enviado
    ): array
    {
        return
            $this->notificacionRepository
                ->obtenerPorEstado($enviado);
    }

    /**
     * Obtener últimas notificaciones.
     */
    public function obtenerUltimas(
        int $cantidad = 20
    ): array
    {
        return
            $this->notificacionRepository
                ->obtenerUltimas($cantidad);
    }
        /**
     * Enviar recuperación de contraseña.
     */
    public function enviarRecuperacionPassword(
        int $usuarioId,
        string $token,
        string $fechaExpiracion
    ): bool
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorId($usuarioId);

        if (!$usuario) {
            return false;
        }

        $this->notificacionRepository
            ->guardarRecoveryToken(
                $usuarioId,
                $token,
                $fechaExpiracion
            );

        return $this->crear([
            'usuario_id' => $usuarioId,
            'tipo' => 'email',
            'asunto' => 'Recuperación de contraseña',
            'mensaje' =>
                'Se solicitó una recuperación de contraseña.'
        ]) !== null;
    }

    /**
     * Notificar resultado de examen.
     */
    public function enviarResultadoExamen(
        int $resultadoId
    ): bool
    {
        $resultado =
            $this->resultadoRepository
                ->obtenerPorId($resultadoId);

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

        return $this->crear([
            'usuario_id' => $inscripcion['usuario_id'],
            'tipo' => 'sistema',
            'asunto' => 'Resultado de examen',
            'mensaje' =>
                'Su resultado de examen ya se encuentra disponible.'
        ]) !== null;
    }

    /**
     * Notificar emisión de carnet.
     */
    public function enviarCarnetEmitido(
        int $carnetId
    ): bool
    {
        $carnet =
            $this->carnetRepository
                ->obtenerPorId($carnetId);

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

        return $this->crear([
            'usuario_id' => $inscripcion['usuario_id'],
            'tipo' => 'sistema',
            'asunto' => 'Carnet emitido',
            'mensaje' =>
                'Su carnet fue emitido correctamente.'
        ]) !== null;
    }

    /**
     * Notificar confirmación de inscripción.
     */
    public function enviarConfirmacionInscripcion(
        int $inscripcionId
    ): bool
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($inscripcionId);

        if (!$inscripcion) {
            return false;
        }

        return $this->crear([
            'usuario_id' => $inscripcion['usuario_id'],
            'tipo' => 'sistema',
            'asunto' => 'Inscripción confirmada',
            'mensaje' =>
                'Su inscripción fue registrada correctamente.'
        ]) !== null;
    }

    /**
     * Notificar documentación aprobada.
     */
    public function enviarDocumentacionAprobada(
        int $usuarioId
    ): bool
    {
        $cantidad =
            $this->notificacionRepository
                ->contarDocumentos($usuarioId);

        $aprobados =
            $this->notificacionRepository
                ->contarDocumentosAprobados($usuarioId);

        if ($cantidad === 0 || $cantidad !== $aprobados) {
            return false;
        }

        return $this->crear([
            'usuario_id' => $usuarioId,
            'tipo' => 'sistema',
            'asunto' => 'Documentación aprobada',
            'mensaje' =>
                'Toda su documentación fue aprobada.'
        ]) !== null;
    }

    /**
     * Notificar documentación rechazada.
     */
    public function enviarDocumentacionRechazada(
        int $usuarioId,
        string $motivo
    ): bool
    {
        return $this->crear([
            'usuario_id' => $usuarioId,
            'tipo' => 'sistema',
            'asunto' => 'Documentación rechazada',
            'mensaje' => $motivo
        ]) !== null;
    }
}