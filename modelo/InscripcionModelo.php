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

require_once __DIR__ . '/../Config/Configuracion.php';

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
            ':estado2' => EstadoTramite::DOCUMENTACION_APROBADA,
            ':estado3' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':estado4' => EstadoTramite::APROBADO
        ]);
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
