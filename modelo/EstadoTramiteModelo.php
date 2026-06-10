<?php
declare(strict_types=1);

/**
 * EstadoTramiteModelo - Gestión de estados de trámite
 * 
 * Propiedades:
 * - id: identificador único
 * - nombre: nombre del estado (pendiente, aprobado, rechazado, etc.)
 * - descripcion: descripción del estado
 */

class EstadoTramiteModelo
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
     * Obtener todos los estados de trámite
     * @return array Array de todos los estados
     */
    public function obtenerTodos(): array
    {
        // TODO: SELECT * FROM estados_tramite ORDER BY nombre ASC
        // TODO: Retornar array de resultados
        
        return [];
    }

    /**
     * Obtener estado de trámite por ID
     * @param int $id ID del estado
     * @return array|null Datos del estado o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        // TODO: SELECT * FROM estados_tramite WHERE id = $id
        // TODO: Retornar array de datos o null
        
        return null;
    }

    /**
     * Obtener historial de estados para un trámite
     * @param int $id_tramite ID del trámite
     * @return array Array con historial de cambios de estado
     */
    public function obtenerHistorial(int $id_tramite): array
    {
        // TODO: SELECT et.*, h.fecha_cambio FROM estados_tramite et JOIN historial_tramite h ON et.id = h.id_estado_tramite WHERE h.id_tramite = $id_tramite ORDER BY h.fecha_cambio DESC
        // TODO: Retornar array de resultados
        
        return [];
    }

    /**
     * Crear nuevo estado de trámite
     * @param array $data Datos: nombre, descripcion
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        // TODO: Validar que nombre sea único
        // TODO: INSERT en tabla estado_tramite
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
