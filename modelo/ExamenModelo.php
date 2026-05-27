<?php
declare(strict_types=1);

/**
 * ExamenModelo - Gestión de exámenes
 * 
 * Propiedades:
 * - id: identificador único
 * - fecha: fecha del examen
 * - hora: hora del examen
 * - ubicacion: ubicación física del examen
 * - cupos: cantidad de cupos disponibles
 * - estado: estado del examen (pendiente, en progreso, finalizado)
 * - id_estado_tramite: ID del estado de trámite asociado
 */

class ExamenModelo
{
    private int $id;
    private string $fecha;
    private string $hora;
    private string $ubicacion;
    private int $cupos;
    private string $estado;
    private int $id_estado_tramite;

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
     * Obtener todos los exámenes
     * @return array Array de todos los exámenes
     */
    public function obtenerTodos(): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->query('SELECT * FROM examenes ORDER BY fecha DESC');
        return $stmt->fetchAll();
    }

    /**
     * Obtener examen por ID
     * @param int $id ID del examen
     * @return array|null Datos del examen o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        if (!$this->conexion) return null;

        $stmt = $this->conexion->prepare('SELECT * FROM examenes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtener exámenes disponibles (con cupos > 0)
     * @return array Array de exámenes con cupos disponibles
     */
    public function obtenerExamenesDisponibles(): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->query("SELECT * FROM examenes WHERE cupos > 0 AND fecha >= NOW() ORDER BY fecha ASC");
        return $stmt->fetchAll();
    }

    /**
     * Crear nuevo examen
     * @param array $data Datos: fecha, hora, ubicacion, aula, cupos
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;

        $fecha = $data['fecha'] ?? null;
        $hora = $data['hora'] ?? null;
        $ubicacion = $data['ubicacion'] ?? '';
        $aula = $data['aula'] ?? '';
        $cupos = (int)($data['cupos'] ?? 0);

        if (!$fecha || !$hora || $cupos <= 0) return false;

        $sql = 'INSERT INTO examenes (fecha, hora, ubicacion, aula, cupos, estado) VALUES (:fecha, :hora, :ubicacion, :aula, :cupos, :estado)';
        $stmt = $this->conexion->prepare($sql);
        $params = [':fecha' => $fecha, ':hora' => $hora, ':ubicacion' => $ubicacion, ':aula' => $aula, ':cupos' => $cupos, ':estado' => 'pendiente'];
        if ($stmt->execute($params)) {
            $id = (int)$this->conexion->lastInsertId();
            return ['id' => $id, 'fecha' => $fecha, 'hora' => $hora, 'ubicacion' => $ubicacion, 'aula' => $aula, 'cupos' => $cupos];
        }

        return false;
    }

    /**
     * Actualizar examen
     * @param int $id ID del examen
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        if (!$this->conexion) return false;

        $allowed = ['fecha','hora','ubicacion','aula','cupos','estado'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $sets[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }
        if (empty($sets)) return false;

        $sql = 'UPDATE examenes SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conexion->prepare($sql);
        return (bool)$stmt->execute($params);
    }

    /**
     * Actualizar cupos del examen
     * @param int $id ID del examen
     * @param int $nuevosCupos Nuevo valor de cupos
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizarCupos(int $id, int $nuevosCupos): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('UPDATE examenes SET cupos = :cupos WHERE id = :id');
        return (bool)$stmt->execute([':cupos' => $nuevosCupos, ':id' => $id]);
    }

    /**
     * Obtener próximos exámenes
     * @param int $cantidad Cantidad de exámenes a retornar
     * @return array Array de próximos exámenes
     */
    public function obtenerProximos(int $cantidad = 5): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM examenes WHERE fecha >= NOW() ORDER BY fecha ASC LIMIT :lim');
        $stmt->bindValue(':lim', (int)$cantidad, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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
     * Obtener fecha
     * @return string
     */
    public function getFecha(): string
    {
        return $this->fecha;
    }

    /**
     * Obtener hora
     * @return string
     */
    public function getHora(): string
    {
        return $this->hora;
    }

    /**
     * Obtener ubicación
     * @return string
     */
    public function getUbicacion(): string
    {
        return $this->ubicacion;
    }

    /**
     * Obtener cupos
     * @return int
     */
    public function getCupos(): int
    {
        return $this->cupos;
    }

    /**
     * Obtener estado
     * @return string
     */
    public function getEstado(): string
    {
        return $this->estado;
    }

    /**
     * Obtener ID de estado de trámite
     * @return int
     */
    public function getIdEstadoTramite(): int
    {
        return $this->id_estado_tramite;
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
     * Establecer fecha
     * @param string $fecha
     * @return void
     */
    public function setFecha(string $fecha): void
    {
        $this->fecha = $fecha;
    }

    /**
     * Establecer hora
     * @param string $hora
     * @return void
     */
    public function setHora(string $hora): void
    {
        $this->hora = $hora;
    }

    /**
     * Establecer ubicación
     * @param string $ubicacion
     * @return void
     */
    public function setUbicacion(string $ubicacion): void
    {
        $this->ubicacion = $ubicacion;
    }

    /**
     * Establecer cupos
     * @param int $cupos
     * @return void
     */
    public function setCupos(int $cupos): void
    {
        $this->cupos = $cupos;
    }

    /**
     * Establecer estado
     * @param string $estado
     * @return void
     */
    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    /**
     * Establecer ID de estado de trámite
     * @param int $id_estado_tramite
     * @return void
     */
    public function setIdEstadoTramite(int $id_estado_tramite): void
    {
        $this->id_estado_tramite = $id_estado_tramite;
    }
}
?>
