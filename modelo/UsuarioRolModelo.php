<?php
declare(strict_types=1);

/**
 * UsuarioRolModelo - Gestión de asignación de roles a usuarios
 * 
 * Propiedades:
 * - id: identificador único
 * - id_usuario: referencia a usuario
 * - id_rol: referencia a rol
 * - fecha_asignacion: timestamp de cuando se asignó el rol
 */

class UsuarioRolModelo
{
    private int $id;
    private int $id_usuario;
    private int $id_rol;
    private string $fecha_asignacion;

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
     * Asignar rol a usuario
     * @param int $id_usuario ID del usuario
     * @param int $id_rol ID del rol
     * @return bool true si fue exitoso, false si falla
     */
    public function asignarRol(int $id_usuario, int $id_rol): bool
    {
        if (!$this->conexion) return false;
        // Validar usuario
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id_usuario]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        // Validar rol
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM roles WHERE id = :id');
        $stmt->execute([':id' => $id_rol]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        // Validar que no exista ya
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM usuario_roles WHERE usuario_id = :uid AND rol_id = :rid');
        $stmt->execute([':uid' => $id_usuario, ':rid' => $id_rol]);
        if (((int)$stmt->fetchColumn()) > 0) return false;

        $ins = $this->conexion->prepare('INSERT INTO usuario_roles (usuario_id, rol_id, fecha_asignacion) VALUES (:uid, :rid, NOW())');
        return (bool)$ins->execute([':uid' => $id_usuario, ':rid' => $id_rol]);
    }

    /**
     * Remover rol de usuario
     * @param int $id_usuario ID del usuario
     * @param int $id_rol ID del rol
     * @return bool true si fue exitoso, false si falla
     */
    public function removerRol(int $id_usuario, int $id_rol): bool
    {
        if (!$this->conexion) return false;
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM usuario_roles WHERE usuario_id = :uid AND rol_id = :rid');
        $stmt->execute([':uid' => $id_usuario, ':rid' => $id_rol]);
        if (((int)$stmt->fetchColumn()) === 0) return false;

        $del = $this->conexion->prepare('DELETE FROM usuario_roles WHERE usuario_id = :uid AND rol_id = :rid');
        return (bool)$del->execute([':uid' => $id_usuario, ':rid' => $id_rol]);
    }

    /**
     * Obtener todos los roles de un usuario
     * @param int $id_usuario ID del usuario
     * @return array Array de roles asignados al usuario
     * 
     * Retorna array como:
     * [
     *   ['id' => 1, 'nombre' => 'usuario', 'descripcion' => '...'],
     *   ['id' => 2, 'nombre' => 'inspector', 'descripcion' => '...']
     * ]
     */
    public function obtenerRolesPorUsuario(int $id_usuario): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT r.* FROM roles r JOIN usuario_roles ur ON r.id = ur.rol_id WHERE ur.usuario_id = :uid');
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los usuarios con un rol específico
     * @param int $id_rol ID del rol
     * @return array Array de usuarios que tienen ese rol
     * 
     * Retorna array como:
     * [
     *   ['id' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => '...'],
     *   ['id' => 2, 'nombre' => 'María', 'apellido' => 'González', 'email' => '...']
     * ]
     */
    public function obtenerUsuariosPorRol(int $id_rol): array
    {
        if (!$this->conexion) return [];
        $stmt = $this->conexion->prepare('SELECT u.* FROM usuarios u JOIN usuario_roles ur ON u.id = ur.usuario_id WHERE ur.rol_id = :rid AND u.activo = 1');
        $stmt->execute([':rid' => $id_rol]);
        return $stmt->fetchAll();
    }

    // Getters y Setters
    public function getId(): int { return $this->id ?? 0; }
    public function getIdUsuario(): int { return $this->id_usuario ?? 0; }
    public function getIdRol(): int { return $this->id_rol ?? 0; }
    public function getFechaAsignacion(): string { return $this->fecha_asignacion ?? ''; }

    public function setIdUsuario(int $id_usuario): void { $this->id_usuario = $id_usuario; }
    public function setIdRol(int $id_rol): void { $this->id_rol = $id_rol; }
}
