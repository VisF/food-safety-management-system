<?php
declare(strict_types=1);

/**
 * AsistenciaModelo - Gestión de asistencia a cursos
 * 
 * Propiedades:
 * - id: identificador único
 * - id_inscripcion: ID de la inscripción
 * - fecha: fecha de asistencia
 * - presente: indicador de asistencia (1=presente, 0=ausente)
 * - observaciones: notas adicionales
 */

class AsistenciaModelo
{
    private int $id;
    private int $id_inscripcion;
    private string $fecha;
    private int $presente;
    private ?string $observaciones;

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
     * Crear registro de asistencia
     * @param array $data Datos: id_inscripcion, fecha, presente, observaciones (opcional)
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;
        $id_inscripcion = (int)($data['id_inscripcion'] ?? 0);
        $fecha = $data['fecha'] ?? date('Y-m-d');
        $presente = isset($data['presente']) ? (int)$data['presente'] : 0;
        $observaciones = $data['observaciones'] ?? null;
        if ($id_inscripcion <= 0) return false;

        // Validar existencia de inscripcion
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM inscripciones WHERE id = :id');
        $stmt->execute([':id' => $id_inscripcion]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $ins = $this->conexion->prepare('INSERT INTO asistencias (inscripcion_id, fecha, presente, observaciones) VALUES (:iid, :fecha, :presente, :obs)');
        $ok = $ins->execute([':iid' => $id_inscripcion, ':fecha' => $fecha, ':presente' => $presente, ':obs' => $observaciones]);
        if ($ok) return ['id' => (int)$this->conexion->lastInsertId(), 'id_inscripcion' => $id_inscripcion, 'fecha' => $fecha, 'presente' => $presente];
        return false;
    }

    /**
     * Obtener asistencias de una inscripción
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array de asistencias de esa inscripción
     */
    public function obtenerPorInscripcion(int $id_inscripcion): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT * FROM asistencias WHERE inscripcion_id = :iid ORDER BY fecha ASC');
        $stmt->execute([':iid' => $id_inscripcion]);
        return $stmt->fetchAll();
    }

    /**
     * Verificar si cumple con el mínimo porcentaje de asistencia
     * @param int $id_inscripcion ID de la inscripción
     * @param float $porcentajeMinimo Porcentaje mínimo requerido (ej: 80.0)
     * @return bool true si cumple, false si no
     */
    public function verificarMinimoPorcentaje(int $id_inscripcion, float $porcentajeMinimo = 80.0): bool
    {
        $tot = $this->obtenerTotalAsistencias($id_inscripcion);
        $presentes = (int)($tot['presentes'] ?? 0);
        $total = (int)($tot['total'] ?? 0);
        if ($total <= 0) return false;
        $porcentaje = ($presentes / $total) * 100.0;
        return $porcentaje >= $porcentajeMinimo;
    }

    /**
     * Obtener asistencia por curso
     * @param int $id_curso ID del curso
     * @return array Array con datos de asistencia del curso
     */
    public function obtenerAsistenciaPorCurso(int $id_curso): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT a.* FROM asistencias a JOIN inscripciones i ON a.inscripcion_id = i.id WHERE i.curso_id = :cid ORDER BY a.fecha ASC');
        $stmt->execute([':cid' => $id_curso]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener total de asistencias de una inscripción
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array con 'presentes' y 'total'
     */
    public function obtenerTotalAsistencias(int $id_inscripcion): array
    {
        if (!$this->conexion) return ['presentes' => 0, 'total' => 0];
        $stmt = $this->conexion->prepare('SELECT SUM(CASE WHEN presente = 1 THEN 1 ELSE 0 END) as presentes, COUNT(*) as total FROM asistencias WHERE inscripcion_id = :iid');
        $stmt->execute([':iid' => $id_inscripcion]);
        $row = $stmt->fetch();
        return ['presentes' => (int)($row['presentes'] ?? 0), 'total' => (int)($row['total'] ?? 0)];
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
     * Obtener ID de inscripción
     * @return int
     */
    public function getIdInscripcion(): int
    {
        return $this->id_inscripcion;
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
     * Obtener presente
     * @return int
     */
    public function getPresente(): int
    {
        return $this->presente;
    }

    /**
     * Obtener observaciones
     * @return string|null
     */
    public function getObservaciones(): ?string
    {
        return $this->observaciones;
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
     * Establecer ID de inscripción
     * @param int $id_inscripcion
     * @return void
     */
    public function setIdInscripcion(int $id_inscripcion): void
    {
        $this->id_inscripcion = $id_inscripcion;
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
     * Establecer presente
     * @param int $presente
     * @return void
     */
    public function setPresente(int $presente): void
    {
        $this->presente = $presente;
    }

    /**
     * Establecer observaciones
     * @param string|null $observaciones
     * @return void
     */
    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }
}
?>
