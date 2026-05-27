<?php
declare(strict_types=1);

/**
 * TipoInscripcionModelo - Gestión de tipos de inscripción
 * 
 * Propiedades:
 * - id: identificador único
 * - nombre: nombre del tipo de inscripción (ej: "Curso Presencial", "Curso Virtual")
 * - descripcion: descripción detallada del tipo
 */

class TipoInscripcionModelo
{
    private int $id;
    private string $nombre;
    private string $descripcion;

    // Conexión a BD (placeholder)
    private ?object $conexion = null;

    public function __construct(?object $conexion = null)
    {
        $this->conexion = $conexion;
    }

    /**
     * Obtener todos los tipos de inscripción
     * @return array Array de tipos de inscripción
     */
    public function obtenerTodos(): array
    {
        // TODO: SELECT * FROM tipo_inscripcion ORDER BY nombre ASC
        // TODO: Retornar array de resultados
        
        return [];
    }

    /**
     * Obtener tipo de inscripción por ID
     * @param int $id ID del tipo de inscripción
     * @return array|null Datos del tipo o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        // TODO: SELECT * FROM tipo_inscripcion WHERE id = $id
        // TODO: Retornar array de datos o null
        
        return null;
    }

    /**
     * Crear nuevo tipo de inscripción
     * @param array $data Datos: nombre, descripcion
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        // TODO: Validar que nombre sea único
        // TODO: INSERT en tabla tipo_inscripcion
        // TODO: Retornar ['id' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion]
        
        return false;
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
     * Obtener descripción
     * @return string
     */
    public function getDescripcion(): string
    {
        return $this->descripcion;
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
     * Establecer descripción
     * @param string $descripcion
     * @return void
     */
    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }
}
?>
