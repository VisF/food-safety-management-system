<?php
require_once __DIR__ . '/../dto/UsuarioDTO.php';
require_once __DIR__ . '/../modelo/UsuarioModelo.php';

class UsuarioService
{
    private UsuarioModelo $usuarioModelo;

    public function __construct(
        UsuarioModelo $usuarioModelo
    ) {
        $this->usuarioModelo = $usuarioModelo;
    }

    // Consultas
    public function obtenerPorId(int $id): ?UsuarioDTO 
    {

        $usuario =
            $this->usuarioModelo
                ->obtenerPorId($id);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] =
            $this->usuarioModelo
                ->obtenerRoles($id);

        return UsuarioDTO::fromArray(
            $usuario
        );
    }
    public function obtenerPorEmail(string $email): ?UsuarioDTO 
    {
        $usuario =
            $this->usuarioModelo
                ->obtenerPorEmail($email);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] =
            $this->usuarioModelo
                ->obtenerRoles(
                    (int)$usuario['id']
                );

        return UsuarioDTO::fromArray(
            $usuario
        );
    }
    public function obtenerPorDni(string $dni): ?UsuarioDTO 
    {

        $usuario =
            $this->usuarioModelo
                ->obtenerPorDni($dni);

        if (!$usuario) {
            return null;
        }

        $usuario['roles'] =
            $this->usuarioModelo
                ->obtenerRoles(
                    (int)$usuario['id']
                );

        return UsuarioDTO::fromArray(
            $usuario
        );
    }
    public function obtenerTodos(): array
    {
        $usuarios =
            $this->usuarioModelo
                ->obtenerTodos();

        $resultado = [];

        foreach ($usuarios as $usuario) {

            $usuario['roles'] =
                $this->usuarioModelo
                    ->obtenerRoles(
                        (int)$usuario['id']
                    );

            $resultado[] =
                UsuarioDTO::fromArray(
                    $usuario
                );
        }

        return $resultado;
    }

    // Autenticación
    public function autenticar(string $email, string $password): ?UsuarioDTO {
        $usuario =
            $this->usuarioModelo
                ->obtenerPorEmail($email);

        if (!$usuario) {
            return null;
        }

        if (
            !password_verify(
                $password,
                $usuario['password']
            )
        ) {
            return null;
        }

        $usuario['roles'] =
            $this->usuarioModelo
                ->obtenerRoles(
                    (int)$usuario['id']
                );

        return UsuarioDTO::fromArray(
            $usuario
        );
    }

    // Registro
    public function registrar(array $datos): UsuarioDTO {
        $existente =
            $this->usuarioModelo
                ->obtenerPorEmail(
                    $datos['email']
                );

        if ($existente) {
            throw new RuntimeException(
                'El email ya está registrado'
            );
        }

        $datos['password'] =
            password_hash(
                $datos['password'],
                PASSWORD_BCRYPT
            );

        $id =
            $this->usuarioModelo
                ->crear($datos);

        $usuario =
            $this->obtenerPorId($id);

        if (!$usuario) {
            throw new RuntimeException(
                'Error al crear usuario'
            );
        }

        return $usuario;
    }

    // Actualización
    public function actualizar(int $id,array $datos): UsuarioDTO {
        $ok =
            $this->usuarioModelo
                ->actualizar(
                    $id,
                    $datos
                );

        if (!$ok) {
            throw new RuntimeException(
                'No se pudo actualizar el usuario'
            );
        }
        $usuario =
            $this->obtenerPorId($id);

        if (!$usuario) {
            throw new RuntimeException(
                'Usuario inexistente'
            );
        }

        return $usuario;
    }

    // Estado
    public function activar(int $id): bool {
        return
            $this->usuarioModelo
                ->cambiarEstado(
                    $id,
                    true
                );
    }   
    public function desactivar(int $id): bool{
    return
        $this->usuarioModelo
            ->cambiarEstado(
                $id,
                false
            );
    }

    // Roles
    public function asignarRol(int $usuarioId,int $rolId): bool {
        return
            $this->usuarioModelo
                ->asignarRol(
                    $usuarioId,
                    $rolId
                );
    }
    public function quitarRol(int $usuarioId, int $rolId): bool {
    return
        $this->usuarioModelo
            ->quitarRol(
                $usuarioId,
                $rolId
            );
    }

    public function obtenerRoles(int $usuarioId): array {
        return
            $this->usuarioModelo
                ->obtenerRoles(
                    $usuarioId
                );
    }

    // Password
    public function cambiarPassword(int $usuarioId, string $passwordActual, string $passwordNueva): bool {
        $usuario =
            $this->usuarioModelo
                ->obtenerPorId(
                    $usuarioId
                );

        if (!$usuario) {
            return false;
        }

        if (
            !password_verify(
                $passwordActual,
                $usuario['password']
            )
        ) {
            return false;
        }

        return
            $this->usuarioModelo
                ->cambiarPassword(
                    $usuarioId,
                    password_hash(
                        $passwordNueva,
                        PASSWORD_BCRYPT
                    )
                );
    }

    public function resetearPassword(int $usuarioId, string $passwordNueva): bool {
        return
            $this->usuarioModelo
                ->cambiarPassword(
                    $usuarioId,
                    password_hash(
                        $passwordNueva,
                        PASSWORD_BCRYPT
                    )
                );
    }
}