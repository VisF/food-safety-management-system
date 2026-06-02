<?php
declare(strict_types=1);

/**
 * AuditoriaAccionesModelo - Gestión de auditoría de acciones del sistema
 * 
 * Propiedades:
 * - id: identificador único
 * - id_usuario: ID del usuario que realizó la acción
 * - tabla_afectada: nombre de la tabla modificada
 * - accion: tipo de acción (INSERT, UPDATE, DELETE)
 * - datos_anteriores: valores antes de la modificación (JSON)
 * - datos_nuevos: valores después de la modificación (JSON)
 * - fecha: timestamp de la acción
 * - ip: dirección IP del usuario
 * - user_agent: navegador/cliente usado
 */

class AuditoriaAccionesModelo
{
    private int $id;
    private int $id_usuario;
    private string $tabla_afectada;
    private string $accion;
    private ?string $datos_anteriores;
    private ?string $datos_nuevos;
    private string $fecha;
    private string $ip;
    private string $user_agent;

    // Conexión a BD (placeholder)
    private ?object $conexion = null;

    public function __construct(?object $conexion = null)
    {
        $this->conexion = $conexion;
    }

    /**
     * Registrar una acción en la auditoría
     * @param array $data Datos: id_usuario, tabla_afectada, accion, datos_anteriores, datos_nuevos, ip, user_agent
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function registrar(array $data)
    {
        // preparar conexión PDO
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else {
            $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); }
        }
        if (!$pdo) return false;

        $id_usuario = isset($data['id_usuario']) ? ($data['id_usuario'] === null ? null : (int)$data['id_usuario']) : null;
        $tabla = $data['tabla_afectada'] ?? ($data['tabla'] ?? '');
        $accion = strtoupper($data['accion'] ?? '');
        $validAcc = in_array($accion, ['INSERT','UPDATE','DELETE','SELECT'], true) ? $accion : 'UPDATE';
        $datos_prev = isset($data['datos_anteriores']) ? (is_string($data['datos_anteriores']) ? $data['datos_anteriores'] : json_encode($data['datos_anteriores'])) : null;
        $datos_new = isset($data['datos_nuevos']) ? (is_string($data['datos_nuevos']) ? $data['datos_nuevos'] : json_encode($data['datos_nuevos'])) : null;
        $ip = $data['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = 'INSERT INTO auditoria_acciones (id_usuario, tabla_afectada, accion, datos_anteriores, datos_nuevos, fecha, ip, user_agent) VALUES (:uid, :tabla, :accion, :prev, :new, NOW(), :ip, :ua)';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([':uid' => $id_usuario, ':tabla' => $tabla, ':accion' => $validAcc, ':prev' => $datos_prev, ':new' => $datos_new, ':ip' => $ip, ':ua' => $ua]);
        if ($ok) {
            $id = (int)$pdo->lastInsertId();
            return ['id' => $id, 'id_usuario' => $id_usuario, 'tabla_afectada' => $tabla, 'accion' => $validAcc];
        }
        return false;
    }

    /**
     * Obtener acciones realizadas por un usuario
     * @param int $id_usuario ID del usuario
     * @return array Array de acciones del usuario
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else { $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); } }
        if (!$pdo) return [];
        $stmt = $pdo->prepare('SELECT * FROM auditoria_acciones WHERE id_usuario = :uid ORDER BY fecha DESC');
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener acciones realizadas sobre una tabla
     * @param string $tabla_afectada Nombre de la tabla
     * @return array Array de acciones sobre la tabla
     */
    public function obtenerPorTabla(string $tabla_afectada): array
    {
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else { $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); } }
        if (!$pdo) return [];
        $stmt = $pdo->prepare('SELECT * FROM auditoria_acciones WHERE tabla_afectada = :tabla ORDER BY fecha DESC');
        $stmt->execute([':tabla' => $tabla_afectada]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener acciones en un rango de fechas
     * @param string $fecha_inicio Fecha de inicio
     * @param string $fecha_fin Fecha de fin
     * @return array Array de acciones en el rango
     */
    public function obtenerPorFecha(string $fecha_inicio, string $fecha_fin): array
    {
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else { $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); } }
        if (!$pdo) return [];
        $stmt = $pdo->prepare('SELECT * FROM auditoria_acciones WHERE fecha >= :inicio AND fecha <= :fin ORDER BY fecha DESC');
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener acciones recientes
     * @param int $cantidad Cantidad de acciones a retornar
     * @return array Array de acciones recientes
     */
    public function obtenerRecientes(int $cantidad = 10): array
    {
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else { $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); } }
        if (!$pdo) return [];
        $stmt = $pdo->prepare('SELECT * FROM auditoria_acciones ORDER BY fecha DESC LIMIT :cant');
        $stmt->bindValue(':cant', (int)$cantidad, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Búsqueda genérica en auditoría
     * @param string $criterio Campo por el cual buscar: tabla_afectada, accion, id_usuario
     * @param string $valor Valor a buscar
     * @return array Array de resultados
     */
    public function buscar(string $criterio, string $valor): array
    {
        $allowed = ['tabla_afectada', 'accion', 'id_usuario'];
        if (!in_array($criterio, $allowed, true)) return [];
        $pdo = null;
        if ($this->conexion instanceof \PDO) $pdo = $this->conexion;
        else { $conn = __DIR__ . '/../db/Connection.php'; if (file_exists($conn)) { require_once $conn; $pdo = Connection::getPDO(); } }
        if (!$pdo) return [];
        if ($criterio === 'id_usuario') {
            $stmt = $pdo->prepare('SELECT * FROM auditoria_acciones WHERE id_usuario = :val ORDER BY fecha DESC');
            $stmt->execute([':val' => $valor]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $sql = "SELECT * FROM auditoria_acciones WHERE {$criterio} LIKE :val ORDER BY fecha DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':val' => "%{$valor}%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
     * Obtener tabla afectada
     * @return string
     */
    public function getTablaAfectada(): string
    {
        return $this->tabla_afectada;
    }

    /**
     * Obtener acción
     * @return string
     */
    public function getAccion(): string
    {
        return $this->accion;
    }

    /**
     * Obtener datos anteriores
     * @return string|null
     */
    public function getDatosAnteriores(): ?string
    {
        return $this->datos_anteriores;
    }

    /**
     * Obtener datos nuevos
     * @return string|null
     */
    public function getDatosNuevos(): ?string
    {
        return $this->datos_nuevos;
    }

    /**
     * Obtener fecha
     * @return string
     */
    public function getFecha(): string
    {
        return $this->fecha;
    }

    /**
     * Obtener IP
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Obtener User Agent
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->user_agent;
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
     * Establecer tabla afectada
     * @param string $tabla_afectada
     * @return void
     */
    public function setTablaAfectada(string $tabla_afectada): void
    {
        $this->tabla_afectada = $tabla_afectada;
    }

    /**
     * Establecer acción
     * @param string $accion
     * @return void
     */
    public function setAccion(string $accion): void
    {
        $this->accion = $accion;
    }

    /**
     * Establecer datos anteriores
     * @param string|null $datos_anteriores
     * @return void
     */
    public function setDatosAnteriores(?string $datos_anteriores): void
    {
        $this->datos_anteriores = $datos_anteriores;
    }

    /**
     * Establecer datos nuevos
     * @param string|null $datos_nuevos
     * @return void
     */
    public function setDatosNuevos(?string $datos_nuevos): void
    {
        $this->datos_nuevos = $datos_nuevos;
    }

    /**
     * Establecer fecha
     * @param string $fecha
     * @return void
     */
    public function setFecha(string $fecha): void
    {
        $this->fecha = $fecha;
    }

    /**
     * Establecer IP
     * @param string $ip
     * @return void
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * Establecer User Agent
     * @param string $user_agent
     * @return void
     */
    public function setUserAgent(string $user_agent): void
    {
        $this->user_agent = $user_agent;
    }
}
?>
