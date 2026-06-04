<?php
declare(strict_types=1);

/**
 * UsuarioModelo - Gestión de usuarios del sistema
 * 
 * Propiedades:
 * - id: identificador único
 * - nombre: nombre del usuario
 * - apellido: apellido del usuario
 * - dni: documento de identidad
 * - email: correo electrónico único
 * - password: contraseña (hasheada con password_hash)
 * - telefono: número de teléfono
 * - activo: estado del usuario (1=activo, 0=inactivo)
 * - fecha_creacion: timestamp de creación del registro
 */

class UsuarioModelo
{
    private int $id;
    private string $nombre;
    private string $apellido;
    private string $dni;
    private string $email;
    private string $password;
    private string $telefono;
    private int $activo;
    private string $fecha_creacion;

    // Conexión a BD (PDO)
    private ?\PDO $conexion = null;

    public function __construct(?\PDO $conexion = null)
    {
        if ($conexion instanceof \PDO) {
            $this->conexion = $conexion;
            return;
        }

        // Intentar crear conexión por defecto
        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->conexion = Connection::getPDO();
        }
    }

    /**
     * Crear nuevo usuario
     * @param array $data Datos del usuario: nombre, apellido, dni, email, password, telefono
     * @return array|false Retorna array con id y datos del usuario, false si falla
     */
    public function crear(array $data){
        if (!$this->conexion) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            'SELECT id
            FROM usuarios
            WHERE email = :email OR dni = :dni'
        );

        $stmt->execute([
            ':email' => $data['email'],
            ':dni' => $data['dni']
        ]);

        if ($stmt->fetch()) {
            return false;
        }

        $sql = '
            INSERT INTO usuarios
            (
                dni,
                nombre,
                apellido,
                email,
                telefono,
                password
            )
            VALUES
            (
                :dni,
                :nombre,
                :apellido,
                :email,
                :telefono,
                :password
            )
        ';

        $stmt = $this->conexion->prepare($sql);

        $ok = $stmt->execute([
            ':dni' => $data['dni'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'] ?? null,
            ':password' => password_hash(
                $data['password'],
                PASSWORD_BCRYPT
            )
        ]);

        if (!$ok) {
            return false;
        }

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtener usuario por ID
     * @param int $id ID del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        if (!$this->conexion) {
            return null;
        }

        $stmt = $this->conexion->prepare('SELECT id, nombre, apellido, dni, email, telefono, activo, fecha_creacion FROM usuarios WHERE id = :id AND activo = 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtener usuario por email
     * @param string $email Email del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function obtenerPorEmail(string $email): ?array
    {
        if (!$this->conexion) {
            return null;
        }

        $stmt = $this->conexion->prepare('SELECT
                                                id,
                                                dni,
                                                nombre,
                                                apellido,
                                                email,
                                                telefono,
                                                password,
                                                activo,
                                                fecha_creacion
                                            FROM usuarios
                                            WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function obtenerTodos(): array
    {
        if (!$this->conexion) {
            return [];
        }

        $stmt = $this->conexion->prepare(
            'SELECT
                id,
                nombre,
                apellido,
                dni,
                email,
                telefono,
                domicilio,
                activo,
                fecha_creacion
            FROM usuarios
            ORDER BY apellido, nombre'
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }





    public function obtenerRoles(int $usuarioId): array
    {
        if (!$this->conexion) {
            return [];
        }

        $stmt = $this->conexion->prepare('SELECT r.id, r.nombre FROM roles r JOIN usuario_rol ur ON r.id = ur.rol_id WHERE ur.usuario_id = :usuarioId');
        $stmt->execute([':usuarioId' => $usuarioId]);
        return $stmt->fetchAll();
    }
    /**
     * Obtener usuario por DNI
     * @param string $dni DNI del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function obtenerPorDNI(string $dni): ?array
    {
        if (!$this->conexion) {
            return null;
        }

        $stmt = $this->conexion->prepare('SELECT id, nombre, apellido, dni, email, telefono, activo, fecha_creacion FROM usuarios WHERE dni = :dni AND activo = 1');
        $stmt->execute([':dni' => $dni]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Actualizar datos del usuario
     * @param int $id ID del usuario
     * @param array $data Datos a actualizar: nombre, apellido, telefono, etc.
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $allowed = ['nombre','apellido','telefono','email'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $sets[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        if (empty($sets)) return false;

        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conexion->prepare($sql);
        return (bool)$stmt->execute($params);
    }

    /**
     * Eliminar usuario (marcar inactivo)
     * @param int $id ID del usuario
     * @return bool true si fue exitoso, false si falla
     */
    public function eliminar(int $id): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $stmt = $this->conexion->prepare('UPDATE usuarios SET activo = 0 WHERE id = :id');
        return (bool)$stmt->execute([':id' => $id]);
    }

    /**
     * Búsqueda genérica de usuarios
     * @param string $criterio Campo por el cual buscar: email, dni, nombre, apellido
     * @param string $valor Valor a buscar
     * @return array Array de usuarios que coinciden
     */
    public function buscar(string $criterio, string $valor): array
    {
        if (!$this->conexion) {
            return [];
        }

        $allowed = ['email','dni','nombre','apellido'];
        if (!in_array($criterio, $allowed, true)) return [];

        $sql = "SELECT id, nombre, apellido, dni, email, telefono, activo FROM usuarios WHERE {$criterio} LIKE :valor AND activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':valor' => "%{$valor}%"]);
        return $stmt->fetchAll();
    }

    /**
     * Autenticar usuario con email y password
     * @param string $email Email del usuario
     * @param string $password Contraseña sin encriptar
     * @return array|null Datos del usuario autenticado o null si falla
     */
    public function autenticar(string $email, string $password): ?array
    {
        if (!$this->conexion) {
            return null;
        }

        $user = $this->obtenerPorEmail($email);
        if (!$user) return null;

        if (!isset($user['password'])) return null;
        if (password_verify($password, $user['password'])) {
            // Remover password antes de retornar
            unset($user['password']);
            return $user;
        }

        return null;
    }

    /**
     * Cambiar contraseña del usuario
     * @param int $id ID del usuario
     * @param string $nuevo_password Nueva contraseña sin encriptar
     * @return bool true si fue exitoso, false si falla
     */
    public function cambiarPassword(int $id, string $nuevo_password): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $hash = password_hash($nuevo_password, PASSWORD_BCRYPT);
        $stmt = $this->conexion->prepare(
            'UPDATE usuarios
            SET password = :password
            WHERE id = :id'
        );
        return (bool)$stmt->execute([
            ':password' => $hash,
            ':id' => $id
        ]);
    }

    // Getters y Setters
    public function getId(): int { return $this->id ?? 0; }
    public function getNombre(): string { return $this->nombre ?? ''; }
    public function getApellido(): string { return $this->apellido ?? ''; }
    public function getDni(): string { return $this->dni ?? ''; }
    public function getEmail(): string { return $this->email ?? ''; }
    public function getTelefono(): string { return $this->telefono ?? ''; }
  
    public function getActivo(): int { return $this->activo ?? 0; }
    public function getFechaCreacion(): string { return $this->fecha_creacion ?? ''; }

    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setApellido(string $apellido): void { $this->apellido = $apellido; }
    public function setTelefono(string $telefono): void { $this->telefono = $telefono; }
    public function setActivo(int $activo): void { $this->activo = $activo; }
}
