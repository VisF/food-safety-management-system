<?php
declare(strict_types=1);

/**
 * ResultadoExamenModelo - Gestión de resultados de exámenes
 * 
 * Propiedades:
 * - id: identificador único
 * - id_inscripcion: ID de la inscripción
 * - id_examen: ID del examen
 * - nota: calificación obtenida
 * - aprobado: indicador de aprobación (1=aprobado, 0=reprobado)
 * - fecha_resultado: timestamp del resultado
 * - observaciones: notas adicionales
 */

class ResultadoExamenModelo
{
    private int $id;
    private int $id_inscripcion;
    private int $id_examen;
    private float $nota;
    private int $aprobado;
    private string $fecha_resultado;
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
     * Crear nuevo resultado de examen
     * @param array $data Datos: id_inscripcion, id_examen, nota, aprobado, observaciones (opcional)
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;
        $id_inscripcion = (int)($data['id_inscripcion'] ?? 0);
        $id_examen = (int)($data['id_examen'] ?? 0);
        $nota = isset($data['nota']) ? (float)$data['nota'] : null;
        $aprobado = isset($data['aprobado']) ? (int)$data['aprobado'] : 0;
        $observaciones = $data['observaciones'] ?? null;

        if ($id_inscripcion <= 0 || $id_examen <= 0 || $nota === null) return false;

        // Validar existencia de inscripcion y examen
        $stmt = $this->conexion->prepare('SELECT COUNT(*) as c FROM inscripciones WHERE id = :id');
        $stmt->execute([':id' => $id_inscripcion]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $stmt = $this->conexion->prepare('SELECT COUNT(*) as c FROM examenes WHERE id = :id');
        $stmt->execute([':id' => $id_examen]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        // Validar nota
        if ($nota < 0 || $nota > 100) return false;

        $sql = 'INSERT INTO resultado_examen (inscripcion_id, examen_id, nota, aprobado, fecha_resultado, observaciones) VALUES (:insc, :exam, :nota, :aprobado, NOW(), :obs)';
        $stmt = $this->conexion->prepare($sql);
        $ok = $stmt->execute([':insc' => $id_inscripcion, ':exam' => $id_examen, ':nota' => $nota, ':aprobado' => $aprobado, ':obs' => $observaciones]);
        if ($ok) {
            $id = (int)$this->conexion->lastInsertId();
            return ['id' => $id, 'inscripcion_id' => $id_inscripcion, 'examen_id' => $id_examen, 'nota' => $nota, 'aprobado' => $aprobado];
        }
        return false;
    }

    /**
     * Obtener resultado de examen por inscripción
     * @param int $id_inscripcion ID de la inscripción
     * @return array|null Datos del resultado o null si no existe
     */
    public function obtenerPorInscripcion(int $id_inscripcion): ?array
    {
        if (!$this->conexion) return null;
        $stmt = $this->conexion->prepare('SELECT * FROM resultado_examen WHERE inscripcion_id = :iid ORDER BY fecha_resultado DESC LIMIT 1');
        $stmt->execute([':iid' => $id_inscripcion]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtener resultados de un examen
     * @param int $id_examen ID del examen
     * @return array Array de resultados del examen
     */
    public function obtenerPorExamen(int $id_examen): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT * FROM resultado_examen WHERE examen_id = :eid ORDER BY fecha_resultado DESC');
        $stmt->execute([':eid' => $id_examen]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener solo resultados aprobados
     * @return array Array de resultados aprobados
     */
    public function obtenerAprobados(): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->query('SELECT * FROM resultado_examen WHERE aprobado = 1 ORDER BY fecha_resultado DESC');
        return $stmt->fetchAll();
    }

    /**
     * Obtener solo resultados reprobados
     * @return array Array de resultados reprobados
     */
    public function obtenerReprobados(): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->query('SELECT * FROM resultado_examen WHERE aprobado = 0 ORDER BY fecha_resultado DESC');
        return $stmt->fetchAll();
    }

    /**
     * Actualizar resultado de examen
     * @param int $id ID del resultado
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        if (!$this->conexion) return false;
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM resultado_examen WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $fields = [];
        $params = [':id' => $id];
        if (isset($data['nota'])) { $fields[] = 'nota = :nota'; $params[':nota'] = (float)$data['nota']; }
        if (isset($data['aprobado'])) { $fields[] = 'aprobado = :aprobado'; $params[':aprobado'] = (int)$data['aprobado']; }
        if (array_key_exists('observaciones', $data)) { $fields[] = 'observaciones = :obs'; $params[':obs'] = $data['observaciones']; }
        if (empty($fields)) return false;

        $sql = 'UPDATE resultado_examen SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $upd = $this->conexion->prepare($sql);
        return (bool)$upd->execute($params);
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
     * Obtener ID de examen
     * @return int
     */
    public function getIdExamen(): int
    {
        return $this->id_examen;
    }

    /**
     * Obtener nota
     * @return float
     */
    public function getNota(): float
    {
        return $this->nota;
    }

    /**
     * Obtener estado aprobado
     * @return int
     */
    public function getAprobado(): int
    {
        return $this->aprobado;
    }

    /**
     * Obtener fecha del resultado
     * @return string
     */
    public function getFechaResultado(): string
    {
        return $this->fecha_resultado;
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
     * Establecer ID de examen
     * @param int $id_examen
     * @return void
     */
    public function setIdExamen(int $id_examen): void
    {
        $this->id_examen = $id_examen;
    }

    /**
     * Establecer nota
     * @param float $nota
     * @return void
     */
    public function setNota(float $nota): void
    {
        $this->nota = $nota;
    }

    /**
     * Establecer estado aprobado
     * @param int $aprobado
     * @return void
     */
    public function setAprobado(int $aprobado): void
    {
        $this->aprobado = $aprobado;
    }

    /**
     * Establecer fecha del resultado
     * @param string $fecha_resultado
     * @return void
     */
    public function setFechaResultado(string $fecha_resultado): void
    {
        $this->fecha_resultado = $fecha_resultado;
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
