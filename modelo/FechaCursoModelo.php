<?php
declare(strict_types=1);

/**
 * FechaCursoModelo - Gestión de fechas de cursos
 * 
 * Propiedades:
 * - id: identificador único
 * - id_curso: ID del curso asociado
 * - fecha_inicio: fecha de inicio del curso
 * - fecha_fin: fecha de finalización del curso
 * - cupos: cantidad de cupos disponibles
 * - activo: estado de la fecha del curso (1=activo, 0=inactivo)
 */

class FechaCursoModelo
{
    private int $id;
    private int $id_curso;
    private string $fecha_inicio;
    private string $fecha_fin;
    private int $cupos;
    private int $activo;

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
     * Crear nueva fecha de curso
     * @param array $data Datos: id_curso, fecha_inicio, fecha_fin, cupos
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;
        $id_curso = (int)($data['id_curso'] ?? 0);
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;
        $cupos = isset($data['cupos']) ? (int)$data['cupos'] : 0;

        if ($id_curso <= 0 || !$fecha_inicio) return false;
        if ($fecha_fin && strtotime($fecha_inicio) > strtotime($fecha_fin)) return false;

        // Validar curso
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM cursos WHERE id = :id');
        $stmt->execute([':id' => $id_curso]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $sql = 'INSERT INTO fecha_cursos (curso_id, fecha_inicio, fecha_fin, cupos, activo) VALUES (:cid, :fi, :ff, :cupos, 1)';
        $ins = $this->conexion->prepare($sql);
        $ok = $ins->execute([':cid' => $id_curso, ':fi' => $fecha_inicio, ':ff' => $fecha_fin, ':cupos' => $cupos]);
        if ($ok) return ['id' => (int)$this->conexion->lastInsertId(), 'curso_id' => $id_curso, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'cupos' => $cupos];
        return false;
    }

    /**
     * Obtener fechas de un curso específico
     * @param int $id_curso ID del curso
     * @return array Array de fechas del curso
     */
    public function obtenerPorCurso(int $id_curso): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT * FROM fecha_cursos WHERE curso_id = :cid AND activo = 1 ORDER BY fecha_inicio ASC');
        $stmt->execute([':cid' => $id_curso]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener fechas disponibles (con cupos > 0)
     * @return array Array de fechas con cupos disponibles
     */
    public function obtenerDisponibles(): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->query('SELECT * FROM fecha_cursos WHERE cupos > 0 AND activo = 1 AND fecha_inicio > CURDATE() ORDER BY fecha_inicio ASC');
        return $stmt->fetchAll();
    }

    /**
     * Actualizar cupos de una fecha
     * @param int $id ID de la fecha del curso
     * @param int $nuevosCupos Nuevo valor de cupos
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizarCupos(int $id, int $nuevosCupos): bool
    {
        if (!$this->conexion) return false;
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM fecha_cursos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if (((int)$stmt->fetchColumn()) === 0) return false;
        $upd = $this->conexion->prepare('UPDATE fecha_cursos SET cupos = :cupos WHERE id = :id');
        return (bool)$upd->execute([':cupos' => $nuevosCupos, ':id' => $id]);
    }

    /**
     * Obtener la fecha de curso actual o próxima
     * @param int $id_curso ID del curso
     * @return array|null Datos de la fecha actual o próxima
     */
    public function obtenerFechaActual(int $id_curso): ?array
    {
        if (!$this->conexion) return null;
        $stmt = $this->conexion->prepare('SELECT * FROM fecha_cursos WHERE curso_id = :cid AND fecha_inicio <= CURDATE() AND (fecha_fin >= CURDATE() OR fecha_fin IS NULL) AND activo = 1 LIMIT 1');
        $stmt->execute([':cid' => $id_curso]);
        $row = $stmt->fetch();
        if ($row) return $row;
        $stmt = $this->conexion->prepare('SELECT * FROM fecha_cursos WHERE curso_id = :cid AND fecha_inicio > CURDATE() AND activo = 1 ORDER BY fecha_inicio ASC LIMIT 1');
        $stmt->execute([':cid' => $id_curso]);
        $row = $stmt->fetch();
        return $row ?: null;
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
     * Obtener ID de curso
     * @return int
     */
    public function getIdCurso(): int
    {
        return $this->id_curso;
    }

    /**
     * Obtener fecha de inicio
     * @return string
     */
    public function getFechaInicio(): string
    {
        return $this->fecha_inicio;
    }

    /**
     * Obtener fecha de fin
     * @return string
     */
    public function getFechaFin(): string
    {
        return $this->fecha_fin;
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
     * Obtener estado activo
     * @return int
     */
    public function getActivo(): int
    {
        return $this->activo;
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
     * Establecer ID de curso
     * @param int $id_curso
     * @return void
     */
    public function setIdCurso(int $id_curso): void
    {
        $this->id_curso = $id_curso;
    }

    /**
     * Establecer fecha de inicio
     * @param string $fecha_inicio
     * @return void
     */
    public function setFechaInicio(string $fecha_inicio): void
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    /**
     * Establecer fecha de fin
     * @param string $fecha_fin
     * @return void
     */
    public function setFechaFin(string $fecha_fin): void
    {
        $this->fecha_fin = $fecha_fin;
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
     * Establecer estado activo
     * @param int $activo
     * @return void
     */
    public function setActivo(int $activo): void
    {
        $this->activo = $activo;
    }
}
?>
