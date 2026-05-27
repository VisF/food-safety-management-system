<?php
declare(strict_types=1);

/**
 * NotificacionModelo - Gestión de notificaciones
 * 
 * Propiedades:
 * - id: identificador único
 * - id_usuario: ID del usuario destinatario
 * - tipo: tipo de notificación (email, sms, sistema)
 * - asunto: asunto de la notificación
 * - mensaje: cuerpo del mensaje
 * - enviado: estado de envío (1=enviado, 0=pendiente)
 * - fecha_creacion: timestamp de creación
 * - fecha_envio: timestamp del envío (nullable)
 */

class NotificacionModelo
{
    private int $id;
    private int $id_usuario;
    private string $tipo;
    private string $asunto;
    private string $mensaje;
    private int $enviado;
    private string $fecha_creacion;
    private ?string $fecha_envio;

    // Conexión a BD (PDO)
    private ?\PDO $conexion = null;

    public function __construct(?\PDO $conexion = null)
    {
        if ($conexion instanceof \PDO) {
            $this->conexion = $conexion;
            return;
        }
        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->conexion = Connection::getPDO();
        }
    }

    /**
     * Crear nueva notificación
     * @param array $data Datos: id_usuario, tipo, asunto, mensaje
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;
        $id_usuario = (int)($data['id_usuario'] ?? 0);
        $tipo = $data['tipo'] ?? 'sistema';
        $asunto = $data['asunto'] ?? '';
        $mensaje = $data['mensaje'] ?? '';

        $validTypes = ['email', 'sms', 'sistema'];
        if ($id_usuario <= 0 || !in_array($tipo, $validTypes, true)) return false;

        // Validar usuario
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id_usuario]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $ins = $this->conexion->prepare('INSERT INTO notificaciones (usuario_id, tipo, asunto, mensaje, enviado, fecha_creacion) VALUES (:uid, :tipo, :asunto, :mensaje, 0, NOW())');
        $ok = $ins->execute([':uid' => $id_usuario, ':tipo' => $tipo, ':asunto' => $asunto, ':mensaje' => $mensaje]);
        if ($ok) return ['id' => (int)$this->conexion->lastInsertId(), 'id_usuario' => $id_usuario, 'tipo' => $tipo, 'asunto' => $asunto];
        return false;
    }

    /**
     * Obtener notificaciones pendientes de envío
     * @return array Array de notificaciones no enviadas
     */
    public function obtenerPendientes(): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->query('SELECT * FROM notificaciones WHERE enviado = 0 ORDER BY fecha_creacion ASC');
        return $stmt->fetchAll();
    }

    /**
     * Marcar notificación como enviada
     * @param int $id ID de la notificación
     * @return bool true si fue exitoso, false si falla
     */
    public function marcarEnviada(int $id): bool
    {
        if (!$this->conexion) return false;
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM notificaciones WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if (((int)$stmt->fetchColumn()) === 0) return false;
        $upd = $this->conexion->prepare('UPDATE notificaciones SET enviado = 1, fecha_envio = NOW() WHERE id = :id');
        return (bool)$upd->execute([':id' => $id]);
    }

    /**
     * Obtener notificaciones de un usuario
     * @param int $id_usuario ID del usuario
     * @return array Array de notificaciones del usuario
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT * FROM notificaciones WHERE usuario_id = :uid ORDER BY fecha_creacion DESC');
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener notificaciones por tipo
     * @param string $tipo Tipo de notificación (email, sms, sistema)
     * @return array Array de notificaciones del tipo especificado
     */
    public function obtenerPorTipo(string $tipo): array
    {
        if (!$this->conexion) return [];
        $validTypes = ['email', 'sms', 'sistema'];
        if (!in_array($tipo, $validTypes, true)) return [];
        $stmt = $this->conexion->prepare('SELECT * FROM notificaciones WHERE tipo = :tipo ORDER BY fecha_creacion DESC');
        $stmt->execute([':tipo' => $tipo]);
        return $stmt->fetchAll();
    }

    /**
     * Eliminar notificación
     * @param int $id ID de la notificación
     * @return bool true si fue exitoso, false si falla
     */
    public function eliminar(int $id): bool
    {
        if (!$this->conexion) return false;
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM notificaciones WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if (((int)$stmt->fetchColumn()) === 0) return false;
        $del = $this->conexion->prepare('DELETE FROM notificaciones WHERE id = :id');
        return (bool)$del->execute([':id' => $id]);
    }

    // Getters
    /**
     * Obtener ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtener ID de usuario
     * @return int
     */
    public function getIdUsuario(): int
    {
        return $this->id_usuario;
    }

    /**
     * Obtener tipo
     * @return string
     */
    public function getTipo(): string
    {
        return $this->tipo;
    }

    /**
     * Obtener asunto
     * @return string
     */
    public function getAsunto(): string
    {
        return $this->asunto;
    }

    /**
     * Obtener mensaje
     * @return string
     */
    public function getMensaje(): string
    {
        return $this->mensaje;
    }

    /**
     * Obtener estado enviado
     * @return int
     */
    public function getEnviado(): int
    {
        return $this->enviado;
    }

    /**
     * Obtener fecha de creación
     * @return string
     */
    public function getFechaCreacion(): string
    {
        return $this->fecha_creacion;
    }

    /**
     * Obtener fecha de envío
     * @return string|null
     */
    public function getFechaEnvio(): ?string
    {
        return $this->fecha_envio;
    }

    // Setters
    /**
     * Establecer ID
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Establecer ID de usuario
     * @param int $id_usuario
     * @return void
     */
    public function setIdUsuario(int $id_usuario): void
    {
        $this->id_usuario = $id_usuario;
    }

    /**
     * Establecer tipo
     * @param string $tipo
     * @return void
     */
    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    /**
     * Establecer asunto
     * @param string $asunto
     * @return void
     */
    public function setAsunto(string $asunto): void
    {
        $this->asunto = $asunto;
    }

    /**
     * Establecer mensaje
     * @param string $mensaje
     * @return void
     */
    public function setMensaje(string $mensaje): void
    {
        $this->mensaje = $mensaje;
    }

    /**
     * Establecer estado enviado
     * @param int $enviado
     * @return void
     */
    public function setEnviado(int $enviado): void
    {
        $this->enviado = $enviado;
    }

    /**
     * Establecer fecha de creación
     * @param string $fecha_creacion
     * @return void
     */
    public function setFechaCreacion(string $fecha_creacion): void
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    /**
     * Establecer fecha de envío
     * @param string|null $fecha_envio
     * @return void
     */
    public function setFechaEnvio(?string $fecha_envio): void
    {
        $this->fecha_envio = $fecha_envio;
    }
}
?>
