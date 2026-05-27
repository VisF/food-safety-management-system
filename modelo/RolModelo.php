<?php
declare(strict_types=1);

/**
 * RolModelo - Gestión de roles del sistema
 * 
 * Propiedades:
 * - id: identificador único del rol
 * - nombre: nombre del rol (usuario, administrador, inspector, acceso_publico)
 * - descripcion: descripción de las responsabilidades del rol
 */

class RolModelo
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
     * Obtener todos los roles
     * @return array Array de roles disponibles en el sistema
     */
    public function obtenerTodos(): array
    {
        // TODO: SELECT * FROM roles
        // TODO: Retornar array de roles
        // Roles esperados: usuario, administrador, inspector, acceso_publico
        
        return [];
    }

    /**
     * Obtener rol por ID
     * @param int $id ID del rol
     * @return array|null Datos del rol o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        // TODO: SELECT * FROM roles WHERE id = $id
        // TODO: Retornar array de datos o null
        
        return null;
    }

    /**
     * Obtener permisos de un rol
     * @param int $id_rol ID del rol
     * @return array Array de permisos asignados al rol
     * 
     * Permisos esperados:
     * - usuario: crear_inscripcion, cargar_documentacion, consultar_estado
     * - administrador: gestionar_usuarios, gestionar_cursos, registrar_asistencia, cargar_carnets, ver_reportes
     * - inspector: verificar_carnet, consultar_usuarios
     * - acceso_publico: consultar_por_dni
     */
    public function obtenerPermisos(int $id_rol): array
    {
        // TODO: SELECT p.* FROM permisos p 
        //       JOIN rol_permiso rp ON p.id = rp.id_permiso 
        //       WHERE rp.id_rol = $id_rol
        // TODO: Retornar array de permisos
        
        return [];
    }

    /**
     * Crear nuevo rol
     * @param string $nombre Nombre del rol
     * @param string $descripcion Descripción del rol
     * @return array|false Array con id del rol creado o false si falla
     */
    public function crearRol(string $nombre, string $descripcion)
    {
        // TODO: Validar que el nombre sea único
        // TODO: INSERT INTO roles (nombre, descripcion) VALUES ($nombre, $descripcion)
        // TODO: Retornar ['id' => $id] o false
        
        return false;
    }

    // Getters y Setters
    public function getId(): int { return $this->id ?? 0; }
    public function getNombre(): string { return $this->nombre ?? ''; }
    public function getDescripcion(): string { return $this->descripcion ?? ''; }

    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setDescripcion(string $descripcion): void { $this->descripcion = $descripcion; }
}
