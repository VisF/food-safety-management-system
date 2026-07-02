<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

class UsuarioRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    // =====================================================
    // CONSULTAS
    // =====================================================

    /**
     * Lista todos los usuarios con sus roles.
     */
    public function listarUsuarios(): array
    {
        $sql = "
            SELECT
                u.id,
                u.nombre,
                u.apellido,
                u.dni,
                u.email,
                u.telefono,
                u.domicilio,
                u.activo,
                u.fecha_creacion,

                GROUP_CONCAT(
                    r.descripcion
                    ORDER BY r.descripcion
                    SEPARATOR ', '
                ) AS roles

            FROM usuarios u

            LEFT JOIN usuario_roles ur
                ON ur.usuario_id = u.id

            LEFT JOIN roles r
                ON r.id = ur.rol_id

            GROUP BY u.id

            ORDER BY
                u.apellido,
                u.nombre
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cantidad de usuarios.
     */
    public function contarUsuarios(): array
    {
        $sql = "
            SELECT

                COUNT(*) AS total,

                SUM(activo = 1) AS activos,

                SUM(activo = 0) AS inactivos

            FROM usuarios
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Usuarios según rol.
     */
    public function obtenerUsuariosPorRol(string $rol): array
    {
        $sql = "
            SELECT

                u.id,
                u.nombre,
                u.apellido,
                u.dni,
                u.email,
                u.telefono,
                u.domicilio,
                u.activo,
                u.fecha_creacion

            FROM usuarios u

            INNER JOIN usuario_roles ur
                ON ur.usuario_id = u.id

            INNER JOIN roles r
                ON r.id = ur.rol_id

            WHERE r.nombre = :rol

            ORDER BY
                u.apellido,
                u.nombre
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':rol', strtolower($rol));

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener usuario por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT *

            FROM usuarios

            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':id',
            $id,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] = $this->obtenerRoles($id);

        return $usuario;
    }

    /**
     * Obtener usuario por email.
     */
    public function obtenerPorEmail(string $email): ?array
    {
        $sql = "
            SELECT *

            FROM usuarios

            WHERE email = :email
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':email',
            $email
        );

        $stmt->execute();

        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] = $this->obtenerRoles(
            (int)$usuario['id']
        );

        return $usuario;
    }

    /**
     * Obtener usuario por DNI.
     */
    public function obtenerPorDni(string $dni): ?array
    {
        $sql = "
            SELECT *

            FROM usuarios

            WHERE dni = :dni
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':dni',
            $dni
        );

        $stmt->execute();

        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] = $this->obtenerRoles(
            (int)$usuario['id']
        );

        return $usuario;
    }

    /**
     * Verifica si un email ya existe.
     */
    public function existeEmail(
        string $email,
        ?int $ignorarUsuario = null
    ): bool
    {
        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE email = :email
        ";

        if ($ignorarUsuario !== null) {
            $sql .= " AND id <> :id";
        }

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':email',
            $email
        );

        if ($ignorarUsuario !== null) {
            $stmt->bindValue(
                ':id',
                $ignorarUsuario,
                \PDO::PARAM_INT
            );
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    // =====================================================
    // ALTAS / MODIFICACIONES
    // =====================================================
        /**
     * Crear usuario.
     */
    public function crear(array $datos): int
    {
        if ($this->existeEmail($datos['email'])) {
            throw new RuntimeException(
                'Ya existe un usuario con ese email.'
            );
        }

        try {

            $this->conexion->beginTransaction();

            $sql = "
                INSERT INTO usuarios
                (
                    dni,
                    nombre,
                    apellido,
                    domicilio,
                    email,
                    telefono,
                    password,
                    activo
                )
                VALUES
                (
                    :dni,
                    :nombre,
                    :apellido,
                    :domicilio,
                    :email,
                    :telefono,
                    :password,
                    1
                )
            ";

            $stmt = $this->conexion->prepare($sql);

            $stmt->execute([
                ':dni' => $datos['dni'],
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':domicilio' => $datos['domicilio'] ?? null,
                ':email' => strtolower(trim($datos['email'])),
                ':telefono' => $datos['telefono'] ?? null,
                ':password' => $datos['password']
            ]);

            $usuarioId = (int)$this->conexion->lastInsertId();

            if (!empty($datos['rol_id'])) {

                $this->asignarRol(
                    $usuarioId,
                    (int)$datos['rol_id']
                );

            } else {

                // Usuario común por defecto
                $this->asignarRol(
                    $usuarioId,
                    3
                );
            }

            $this->conexion->commit();

            return $usuarioId;

        } catch (\Throwable $e) {

            $this->conexion->rollBack();

            throw $e;
        }
    }

    /**
     * Actualizar usuario.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool
    {
        if (
            isset($datos['email'])
            &&
            $this->existeEmail(
                $datos['email'],
                $id
            )
        ) {
            throw new RuntimeException(
                'Ya existe un usuario con ese email.'
            );
        }

        $campos = [];
        $parametros = [
            ':id' => $id
        ];

        $permitidos = [
            'nombre',
            'apellido',
            'telefono',
            'domicilio',
            'email'
        ];

        foreach ($permitidos as $campo) {

            if (!array_key_exists($campo, $datos)) {
                continue;
            }

            $campos[] = "{$campo} = :{$campo}";
            $parametros[":{$campo}"] = $datos[$campo];
        }

        if (!empty($datos['password'])) {

            $campos[] = "password = :password";

            $parametros[':password'] =  $datos['password'];
        }

        if (empty($campos)) {
            return false;
        }

        $sql =
            "UPDATE usuarios
             SET " .
            implode(', ', $campos) .
            "
             WHERE id = :id";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute($parametros);
    }

    /**
     * Activar usuario.
     */
    public function activarUsuario(int $id): bool
    {
        $stmt = $this->conexion->prepare(
            "
            UPDATE usuarios

            SET activo = 1

            WHERE id = :id
            "
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Desactivar usuario.
     */
    public function desactivarUsuario(int $id): bool
    {
        try {

            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "
                UPDATE usuarios

                SET activo = 0

                WHERE id = :id
                "
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $stmt = $this->conexion->prepare(
                "
                UPDATE inscripciones

                SET estado_tramite_id = :estado

                WHERE usuario_id = :usuario

                AND estado_tramite_id IN
                (
                    :pendiente,
                    :documentacion,
                    :inscripto
                )
                "
            );

            $stmt->execute([
                ':estado' => EstadoTramite::RECHAZADO,
                ':usuario' => $id,
                ':pendiente' => EstadoTramite::PENDIENTE,
                ':documentacion' => EstadoTramite::DOCUMENTACION_APROBADA,
                ':inscripto' => EstadoTramite::INSCRIPTO_EXAMEN
            ]);

            $this->conexion->commit();

            return true;

        } catch (\Throwable $e) {

            $this->conexion->rollBack();

            throw $e;
        }
    }

    // =====================================================
    // ROLES
    // =====================================================
        /**
     * Obtener todos los roles de un usuario.
     */
    public function obtenerRoles(int $usuarioId): array
    {
        $sql = "
            SELECT
                r.id,
                r.nombre,
                r.descripcion
            FROM roles r
            INNER JOIN usuario_roles ur
                ON ur.rol_id = r.id
            WHERE ur.usuario_id = :usuario
            ORDER BY r.descripcion
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':usuario', $usuarioId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Asignar un rol a un usuario.
     */
    public function asignarRol(int $usuarioId, int $rolId): bool
    {
        // Evitar duplicados
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM usuario_roles
            WHERE usuario_id = :usuario
            AND rol_id = :rol
        ");

        $stmt->execute([
            ':usuario' => $usuarioId,
            ':rol' => $rolId
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        $stmt = $this->conexion->prepare("
            INSERT INTO usuario_roles
            (
                usuario_id,
                rol_id,
                fecha_asignacion
            )
            VALUES
            (
                :usuario,
                :rol,
                NOW()
            )
        ");

        return $stmt->execute([
            ':usuario' => $usuarioId,
            ':rol' => $rolId
        ]);
    }

    /**
     * Quitar un rol.
     */
    public function quitarRol(int $usuarioId, int $rolId): bool
    {
        $stmt = $this->conexion->prepare("
            DELETE
            FROM usuario_roles
            WHERE usuario_id = :usuario
            AND rol_id = :rol
        ");

        return $stmt->execute([
            ':usuario' => $usuarioId,
            ':rol' => $rolId
        ]);
    }

    /**
     * Reemplazar completamente los roles de un usuario.
     *
     * Muy útil para el panel de administración.
     */
    public function actualizarRoles(
        int $usuarioId,
        array $roles
    ): bool
    {
        try {

            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare("
                DELETE
                FROM usuario_roles
                WHERE usuario_id = :usuario
            ");

            $stmt->execute([
                ':usuario' => $usuarioId
            ]);

            foreach ($roles as $rolId) {

                $this->asignarRol(
                    $usuarioId,
                    (int)$rolId
                );
            }

            $this->conexion->commit();

            return true;

        } catch (\Throwable $e) {

            $this->conexion->rollBack();

            throw $e;
        }
    }

    /**
     * Obtener un rol por nombre.
     */
    public function obtenerRolPorNombre(string $nombre): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM roles
            WHERE nombre = :nombre
            LIMIT 1
        ");

        $stmt->execute([
            ':nombre' => strtolower($nombre)
        ]);

        $rol = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $rol ?: null;
    }

    /**
     * Obtener todos los roles del sistema.
     */
    public function listarRoles(): array
    {
        $stmt = $this->conexion->query("
            SELECT
                id,
                nombre,
                descripcion
            FROM roles
            ORDER BY descripcion
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}