<?php
declare(strict_types=1);

/**
 * InscripcionModelo - Gestión de inscripciones
 * 
 * Propiedades:
 * - id: identificador único
 * - id_usuario: ID del usuario que se inscribe
 * - id_curso: ID del curso
 * - id_examen: ID del examen asociado
 * - id_tipo_inscripcion: ID del tipo de inscripción
 * - fecha_inscripcion: timestamp de la inscripción
 * - id_estado: ID del estado de la inscripción
 * - observaciones: notas adicionales
 */

class InscripcionModelo
{
    private int $id;
    private int $id_usuario;
    private int $id_curso;
    private ?int $id_examen;
    private int $id_tipo_inscripcion;
    private string $fecha_inscripcion;
    private int $id_estado;
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
 * Crear nueva inscripción
 * @param array $data Datos: id_usuario, id_curso, id_tipo_inscripcion, id_examen (opcional)
 * @return array|false Retorna array con id y datos, false si falla
 */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;

        $id_usuario = (int)($data['usuario_id'] ?? $data['id_usuario'] ?? 0);

        $id_curso = $data['curso_id']
            ?? $data['id_curso']
            ?? null;

        $id_examen = $data['examen_id']
            ?? $data['id_examen']
            ?? null;

        $id_curso = $id_curso !== null
            ? (int)$id_curso
            : null;

        $id_examen = $id_examen !== null
            ? (int)$id_examen
            : null;

        $id_tipo = (int)($data['tipo_inscripcion_id'] ?? $data['id_tipo_inscripcion'] ?? 0);
        

        $estado_tramite = (int)($data['estado_tramite_id'] ?? EstadoTramite::PENDIENTE);

        if (
            $id_usuario <= 0 ||
            $id_tipo <= 0 ||
            (
                ($id_curso === null || $id_curso <= 0) &&
                ($id_examen === null || $id_examen <= 0)
            )
        ) {
            return false;
        }

        // verificar duplicado según el tipo de inscripción solicitado
        if ($id_examen > 0) {
            $stmt = $this->conexion->prepare(
                'SELECT id
                FROM inscripciones
                WHERE usuario_id = :uid
                AND examen_id = :eid
                AND estado_tramite_id != :estado_rechazado'
            );

            $stmt->execute([
                ':uid' => $id_usuario,
                ':eid' => $id_examen,
                ':estado_rechazado' => EstadoTramite::RECHAZADO
            ]);
        } else {
            $stmt = $this->conexion->prepare(
                'SELECT id
                FROM inscripciones
                WHERE usuario_id = :uid
                AND curso_id = :cid
                AND estado_tramite_id != :estado_rechazado'
            );

            $stmt->execute([
                ':uid' => $id_usuario,
                ':cid' => $id_curso,
                ':estado_rechazado' => EstadoTramite::RECHAZADO
            ]);
        }

        if ($stmt->fetch()) {
            return false;
        }

        $sql = '
            INSERT INTO inscripciones (
                usuario_id,
                curso_id,
                examen_id,
                tipo_inscripcion_id,
                fecha_inscripcion,
                estado_tramite_id
            )
            VALUES (
                :uid,
                :cid,
                :eid,
                :tid,
                NOW(),
                :estado
            )
        ';

        $stmt = $this->conexion->prepare($sql);

        $params = [
            ':uid' => $id_usuario,
            ':cid' => $id_curso,
            ':eid' => $id_examen,
            ':tid' => $id_tipo,
            ':estado' => $estado_tramite
        ];

        if ($stmt->execute($params)) {

            $id = (int)$this->conexion->lastInsertId();

            return [
                'id' => $id,
                'id_usuario' => $id_usuario,
                'id_curso' => $id_curso,
                'id_examen' => $id_examen,
                'id_tipo_inscripcion' => $id_tipo,
                'estado_tramite_id' => $estado_tramite
            ];
        }

        return false;
    }

    /// Obtener última inscripción de un usuario


    public function obtenerUltimaInscripcionPorUsuario(int $usuarioId): ?array
    {
        if (!$this->conexion) {
            return null;
        }
        $sql = "
            SELECT
                i.id,
                i.fecha_inscripcion,
                i.observaciones,

                et.id AS estado_id,
                et.nombre AS estado_nombre,


                ti.nombre AS tipo_inscripcion

            FROM inscripciones i

            INNER JOIN estados_tramite et
                ON i.estado_tramite_id = et.id

            INNER JOIN tipo_inscripcion ti
                ON i.tipo_inscripcion_id = ti.id

            WHERE i.usuario_id = :usuario_id

            ORDER BY i.fecha_inscripcion DESC

            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }



    /**
     * Obtener inscripciones de un usuario
     * @param int $id_usuario ID del usuario
     * @return array Array de inscripciones del usuario
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM inscripciones WHERE usuario_id = :uid ORDER BY fecha_inscripcion DESC');
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener inscripción por ID
     * @param int $id ID de la inscripción
     * @return array|null Datos de la inscripción o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        if (!$this->conexion) return null;

        $stmt = $this->conexion->prepare('SELECT * FROM inscripciones WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtener inscripciones por estado
     * @param int $id_estado ID del estado
     * @return array Array de inscripciones con ese estado
     */
    public function obtenerPorEstado(int $id_estado): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM inscripciones WHERE estado_tramite_id = :estado ORDER BY fecha_inscripcion DESC');
        $stmt->execute([':estado' => $id_estado]);
        return $stmt->fetchAll();
    }

    /**
     * Actualizar inscripción
     * @param int $id ID de la inscripción
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        if (!$this->conexion) return false;

        $allowed = ['id_estado','observaciones','id_examen'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $sets[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }
        if (empty($sets)) return false;

        $sql = 'UPDATE inscripciones SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conexion->prepare($sql);
        return (bool)$stmt->execute($params);
    }

    /**
     * Obtener inscripciones activas
     * @return array Array de inscripciones activas
     */
    public function obtenerInscripcionesActivas(): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM inscripciones  
                                            WHERE estado_tramite_id IN (:estado1, :estado2, :estado3, :estado4) 
                                            ORDER BY fecha_inscripcion DESC');
        $stmt->execute([
            ':estado1' => EstadoTramite::PENDIENTE,
            ':estado2' => EstadoTramite::HABILITADO_EXAMEN,
            ':estado3' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':estado4' => EstadoTramite::EXAMEN_APROBADO
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Cancelar inscripción
     * @param int $id ID de la inscripción
     * @param string $motivo Motivo de la cancelación
     * @return bool true si fue exitoso, false si falla
     */
    public function cancelar(int $id, string $motivo = ''): bool
    {
        if (!$this->conexion) return false;

        // suponer estado 'cancelado' = 3
        $stmt = $this->conexion->prepare('UPDATE inscripciones SET estado_tramite_id = :estado, observaciones = :motivo WHERE id = :id');
        return (bool)$stmt->execute([':estado' => EstadoTramite::CANCELADO, ':motivo' => $motivo, ':id' => $id]);
    }

    /**
     * Verificar si existe inscripción duplicada
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return bool true si existe duplicado, false si no existe
     */
    public function verificarDuplicado(int $id_usuario, int $id_curso): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('SELECT COUNT(*) as total FROM inscripciones WHERE usuario_id = :uid AND curso_id = :cid AND estado_tramite_id != :estado');
        $stmt->execute([':uid' => $id_usuario, ':cid' => $id_curso, ':estado' => EstadoTramite::CANCELADO]);
        $row = $stmt->fetch();
        return ((int)$row['total']) > 0;
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
     * Obtener ID de curso
     * @return int
     */
    public function getIdCurso(): int
    {
        return $this->id_curso;
    }

    /**
     * Obtener ID de examen
     * @return int|null
     */
    public function getIdExamen(): ?int
    {
        return $this->id_examen;
    }

    /**
     * Obtener ID de tipo de inscripción
     * @return int
     */
    public function getIdTipoInscripcion(): int
    {
        return $this->id_tipo_inscripcion;
    }

    /**
     * Obtener fecha de inscripción
     * @return string
     */
    public function getFechaInscripcion(): string
    {
        return $this->fecha_inscripcion;
    }

    /**
     * Obtener ID de estado
     * @return int
     */
    public function getIdEstado(): int
    {
        return $this->id_estado;
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
     * Establecer ID de usuario
     * @param int $id_usuario
     * @return void
     */
    public function setIdUsuario(int $id_usuario): void
    {
        $this->id_usuario = $id_usuario;
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
     * Establecer ID de examen
     * @param int|null $id_examen
     * @return void
     */
    public function setIdExamen(?int $id_examen): void
    {
        $this->id_examen = $id_examen;
    }

    /**
     * Establecer ID de tipo de inscripción
     * @param int $id_tipo_inscripcion
     * @return void
     */
    public function setIdTipoInscripcion(int $id_tipo_inscripcion): void
    {
        $this->id_tipo_inscripcion = $id_tipo_inscripcion;
    }

    /**
     * Establecer fecha de inscripción
     * @param string $fecha_inscripcion
     * @return void
     */
    public function setFechaInscripcion(string $fecha_inscripcion): void
    {
        $this->fecha_inscripcion = $fecha_inscripcion;
    }

    /**
     * Establecer ID de estado
     * @param int $id_estado
     * @return void
     */
    public function setIdEstado(int $id_estado): void
    {
        $this->id_estado = $id_estado;
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
