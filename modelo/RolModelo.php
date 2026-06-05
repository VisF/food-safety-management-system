<?php
declare(strict_types=1);

/**
 * RolModelo - Gestión de roles del sistema
 * 
 * Propiedades:
 * - id: identificador único del rol
 * - nombre: nombre del rol (usuario, administrador, inspector, acceso_publico)
 * - descripcion: descripción de las responsabilidades del rol
 * 
 * 
 * -  obtenerTodos()
 * -  obtenerPorId()
 * -  obtenerPorNombre()
 * -  crear()
 * -  actualizar()
 * -  eliminar()
 */

class RolModelo
{
    private int $id;
    private string $nombre;
    private string $descripcion;

    // Conexión a BD (placeholder)
    //private ?object $conexion = null;

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
        } else {
            throw new \Exception("Archivo de conexión no encontrado: " . $connFile);
    }
}

    /**
     * Obtener todos los roles
     * @return array Array de roles disponibles en el sistema
     */
    public function obtenerTodos(): array
    {
        if (!$this->conexion) {
            return [];
        }

        $stmt = $this->conexion->prepare(
            'SELECT
                id,
                nombre,
                descripcion
            FROM roles
            ORDER BY nombre'
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener rol por ID
     * @param int $id ID del rol
     * @return array|null Datos del rol o null si no existe
     */
    public function obtenerPorId(int $id): ?array 
    {
        if (!$this->conexion) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            'SELECT
                id,
                nombre,
                descripcion
            FROM roles
            WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $rol = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $rol ?: null;
    }
    public function obtenerPorNombre(string $nombre): ?array 
    {
        if (!$this->conexion) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            'SELECT
                id,
                nombre,
                descripcion
            FROM roles
            WHERE nombre = :nombre'
        );

        $stmt->execute([
            ':nombre' => $nombre
        ]);

        $rol = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $rol ?: null;
    }
    public function crear(string $nombre,string $descripcion): int|false 
    {
        if (!$this->conexion) {
            return false;
        }

        if ($this->obtenerPorNombre($nombre)) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            'INSERT INTO roles
            (
                nombre,
                descripcion
            )
            VALUES
            (
                :nombre,
                :descripcion
            )'
        );

        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);

        if (!$ok) {
            return false;
        }

        return (int)$this->conexion->lastInsertId();
    }

    public function actualizar(int $id,array $data    ): bool 
    {
        if (!$this->conexion) {
            return false;
        }

        $allowed = [
            'nombre',
            'descripcion'
        ];

        $sets = [];
        $params = [
            ':id' => $id
        ];

        foreach ($allowed as $field) {

            if (isset($data[$field])) {

                $sets[] =
                    "{$field} = :{$field}";

                $params[":{$field}"] =
                    $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            'UPDATE roles
            SET ' . implode(', ', $sets) . '
            WHERE id = :id'
        );

        return (bool)$stmt->execute(
            $params
        );
    }
    public function eliminar(int $id): bool 
    {
        if (!$this->conexion) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            'DELETE FROM roles
            WHERE id = :id'
        );

        return (bool)$stmt->execute([
            ':id' => $id
        ]);
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
    //public function obtenerPermisos(int $id_rol): array
    //{
        // TODO: SELECT p.* FROM permisos p 
        //       JOIN rol_permiso rp ON p.id = rp.id_permiso 
        //       WHERE rp.id_rol = $id_rol
        // TODO: Retornar array de permisos
        
     //   return [];
    //}

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
