<?php
declare(strict_types=1);

/**
 * CursoModelo - Gestión de cursos
 * 
 * Propiedades:
 * - id: identificador único
 * - nombre: nombre del curso
 * - modalidad: modalidad del curso (presencial, virtual, híbrida)
 * - descripcion: descripción detallada del curso
 * - activo: estado del curso (1=activo, 0=inactivo)
 * - fecha_creacion: timestamp de creación del registro
 */
require_once __DIR__ . '/../db/Connection.php';


class CursoModelo
{
    private int $id;
    private string $nombre;
    private string $modalidad;
    private string $descripcion;
    private int $activo;
    private string $fecha_creacion;
    private ?string $fecha_inicio = null;
    private ?string $hora_inicio = null;
    private ?string $ubicacion = null;
    private int $cupos;

    // Conexión a BD (placeholder)
    private ?object $conexion = null;

    public function __construct(?object $conexion = null)
    {
        $this->conexion = $conexion;
    }

    /**
     * Obtener todos los cursos
     * @return array Array de todos los cursos
     */
    public function obtenerTodos(): array
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->query(
            "SELECT *
            FROM cursos
            ORDER BY nombre ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtener curso por ID
     * @param int $id ID del curso
     * @return array|null Datos del curso o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->prepare(
            "SELECT *
            FROM cursos
            WHERE id = :id
            LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    /**
     * Obtener solo cursos activos
     * @return array Array de cursos activos
     */
    public function obtenerActivos(): array
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->query(
            "SELECT *
            FROM cursos
            WHERE activo = 1
            ORDER BY nombre ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo curso
     * @param array $data Datos: nombre, modalidad, descripcion
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->prepare(
            "INSERT INTO cursos
            (
                nombre,
                modalidad,
                descripcion,
                activo,
                fecha_creacion,
                fecha_inicio
                hora_inicio
                ubicacion
                cupos
            )
            VALUES
            (
                :nombre,
                :modalidad,
                :descripcion,
                1,
                :fecha_inicio,
                :hora_inicio,
                :ubicacion,
                :cupos
            )"
        );

        $stmt->execute([
            ':nombre' => trim($data['nombre']),
            ':modalidad' => trim($data['modalidad']),
            ':descripcion' => trim($data['descripcion']),
            ':fecha_inicio' => trim($data['fecha_inicio']),
            ':hora_inicio' => trim($data['hora_inicio']),
            ':ubicacion' => trim($data['ubicacion']),
            ':cupos' => (int)$data['cupos']
        ]);

        $id = (int)$pdo->lastInsertId();

        return [
            'id' => $id,
            'nombre' => $data['nombre'],
            'modalidad' => $data['modalidad'],
            'descripcion' => $data['descripcion'],
            'fecha_inicio' => $data['fecha_inicio'],
            'hora_inicio' => $data['hora_inicio'],
            'ubicacion' => $data['ubicacion'],
            'cupos' => $data['cupos']
        ];
    }

    /**
     * Actualizar curso
     * @param int $id ID del curso
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->prepare(
            "UPDATE cursos
            SET
                nombre=:nombre,
                modalidad=:modalidad,
                descripcion=:descripcion,
                fecha_inicio=:fecha_inicio,
                hora_inicio=:hora_inicio,
                ubicacion=:ubicacion,
                cupos=:cupos,
                activo=:activo
            WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id,
            ':nombre' => trim($data['nombre']),
            ':modalidad' => trim($data['modalidad']),
            ':descripcion' => trim($data['descripcion']),
            ':activo' => (int)$data['activo'],
            ':fecha_inicio' => trim($data['fecha_inicio']),
            ':hora_inicio' => trim($data['hora_inicio']),
            ':ubicacion' => trim($data['ubicacion']),
            ':cupos' => (int)$data['cupos']
        ]);
    }

    /**
     * Obtener cursos por modalidad
     * @param string $modalidad presencial, virtual, híbrida
     * @return array Array de cursos de la modalidad especificada
     */
    public function obtenerCursosPorModalidad(string $modalidad ): array
    {
        $pdo = Connection::getPDO();

        $stmt = $pdo->prepare(
            "SELECT *
            FROM cursos
            WHERE modalidad = :modalidad
            AND activo = 1
            ORDER BY nombre ASC"
        );

        $stmt->execute([
            ':modalidad' => $modalidad
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Getters
    public function getFechaInicio(): ?string
    {
        return $this->fecha_inicio;
    }
    public function getHoraInicio(): ?string
    {
        return $this->hora_inicio;
    }
    public function getUbicacion(): ?string
    {
        return $this->ubicacion;
    }
    public function getCupos(): int
    {
        return $this->cupos;
    }
    
    /**
     * Obtener ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtener nombre
     * @return string
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * Obtener modalidad
     * @return string
     */
    public function getModalidad(): string
    {
        return $this->modalidad;
    }

    /**
     * Obtener descripción
     * @return string
     */
    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    /**
     * Obtener estado activo
     * @return int
     */
    public function getActivo(): int
    {
        return $this->activo;
    }

    /**
     * Obtener fecha de creación
     * @return string
     */
    public function getFechaCreacion(): string
    {
        return $this->fecha_creacion;
    }

    // Setters
    public function setFechaInicio(?string $fecha_inicio): void
    {
        $this->fecha_inicio = $fecha_inicio;
    }
    public function setHoraInicio(?string $hora_inicio): void
    {
        $this->hora_inicio = $hora_inicio;
    }
    public function setUbicacion(?string $ubicacion): void
    {
        $this->ubicacion = $ubicacion;
    }
    public function setCupos(int $cupos): void
    {
        $this->cupos = $cupos;
    }


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
     * Establecer nombre
     * @param string $nombre
     * @return void
     */
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    /**
     * Establecer modalidad
     * @param string $modalidad
     * @return void
     */
    public function setModalidad(string $modalidad): void
    {
        $this->modalidad = $modalidad;
    }

    /**
     * Establecer descripción
     * @param string $descripcion
     * @return void
     */
    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
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

    /**
     * Establecer fecha de creación
     * @param string $fecha_creacion
     * @return void
     */
    public function setFechaCreacion(string $fecha_creacion): void
    {
        $this->fecha_creacion = $fecha_creacion;
    }
}
?>
