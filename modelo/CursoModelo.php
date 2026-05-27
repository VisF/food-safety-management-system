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

class CursoModelo
{
    private int $id;
    private string $nombre;
    private string $modalidad;
    private string $descripcion;
    private int $activo;
    private string $fecha_creacion;

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
        // TODO: SELECT * FROM cursos ORDER BY nombre ASC
        // TODO: Retornar array de resultados
        
        return [];
    }

    /**
     * Obtener curso por ID
     * @param int $id ID del curso
     * @return array|null Datos del curso o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        // TODO: SELECT * FROM cursos WHERE id = $id
        // TODO: Retornar array de datos o null
        
        return null;
    }

    /**
     * Obtener solo cursos activos
     * @return array Array de cursos activos
     */
    public function obtenerActivos(): array
    {
        // TODO: SELECT * FROM cursos WHERE activo = 1 ORDER BY nombre ASC
        // TODO: Retornar array de resultados
        
        return [];
    }

    /**
     * Crear nuevo curso
     * @param array $data Datos: nombre, modalidad, descripcion
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        // TODO: Validar que nombre sea único
        // TODO: Validar que modalidad sea presencial, virtual o híbrida
        // TODO: INSERT en tabla cursos
        // TODO: Retornar ['id' => $id, 'nombre' => $nombre, ...]
        
        return false;
    }

    /**
     * Actualizar curso
     * @param int $id ID del curso
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        // TODO: Validar que el curso exista
        // TODO: UPDATE cursos SET ... WHERE id = $id
        // TODO: Retornar true/false según resultado
        
        return false;
    }

    /**
     * Obtener cursos por modalidad
     * @param string $modalidad presencial, virtual, híbrida
     * @return array Array de cursos de la modalidad especificada
     */
    public function obtenerCursosPorModalidad(string $modalidad): array
    {
        // TODO: SELECT * FROM cursos WHERE modalidad = $modalidad AND activo = 1
        // TODO: Retornar array de resultados
        
        return [];
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
