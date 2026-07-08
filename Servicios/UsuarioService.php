<?php
declare(strict_types=1);

require_once __DIR__ . '/../Repository/UsuarioRepository.php';
require_once __DIR__ . '/../dto/UsuarioDTO.php';

class UsuarioService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct()
    {
        $this->usuarioRepository =
            new UsuarioRepository();
    }

    // =====================================================
    // CONSULTAS
    // =====================================================

    public function obtenerPorId(int $id): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorId($id);

        return $usuario
            ? UsuarioDTO::fromArray($usuario)
            : null;
    }

    public function obtenerPorEmail(string $email): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorEmail($email);

        return $usuario
            ? UsuarioDTO::fromArray($usuario)
            : null;
    }

    public function obtenerPorDni(string $dni): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorDni($dni);

        return $usuario
            ? UsuarioDTO::fromArray($usuario)
            : null;
    }

    public function listarUsuarios(): array
    {
        return
            $this->usuarioRepository
                ->listarUsuarios();
    }

    public function contarUsuarios(): array
    {
        return
            $this->usuarioRepository
                ->contarUsuarios();
    }

    public function obtenerUsuariosPorRol(
        string $rol
    ): array
    {
        return
            $this->usuarioRepository
                ->obtenerUsuariosPorRol(
                    $rol
                );
    }
        // =====================================================
    // AUTENTICACIÓN
    // =====================================================

    public function autenticar(
        string $email,
        string $password
    ): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
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

        return UsuarioDTO::fromArray(
            $usuario
        );
    }

    // =====================================================
    // REGISTRO
    // =====================================================

    public function registrar(
        array $datos
    ): UsuarioDTO
    {
        $existente =
            $this->usuarioRepository
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
            $this->usuarioRepository
                ->crear($datos);

        $usuario =
            $this->obtenerPorId($id);

        if (!$usuario) {

            throw new RuntimeException(
                'Error al crear el usuario'
            );
        }

        return $usuario;
    }

    // =====================================================
    // ACTUALIZACIÓN
    // =====================================================

    public function actualizar(
        int $id,
        array $datos
    ): UsuarioDTO
    {
        $ok =
            $this->usuarioRepository
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
        // =====================================================
    // ESTADO
    // =====================================================

    public function activar(int $id): bool
    {
        return
            $this->usuarioRepository
                ->activarUsuario($id);
    }

    public function desactivar(int $id): bool
    {
        return
            $this->usuarioRepository
                ->desactivarUsuario($id);
    }

    // =====================================================
    // ROLES
    // =====================================================

    public function asignarRol(
        int $usuarioId,
        int $rolId
    ): bool
    {
        return
            $this->usuarioRepository
                ->asignarRol(
                    $usuarioId,
                    $rolId
                );
    }

    public function quitarRol(
        int $usuarioId,
        int $rolId
    ): bool
    {
        return
            $this->usuarioRepository
                ->quitarRol(
                    $usuarioId,
                    $rolId
                );
    }

    public function obtenerRoles(
        int $usuarioId
    ): array
    {
        return
            $this->usuarioRepository
                ->obtenerRoles(
                    $usuarioId
                );
    }

    // =====================================================
    // PASSWORD
    // =====================================================

    public function cambiarPassword(
        int $usuarioId,
        string $passwordActual,
        string $passwordNueva
    ): bool
    {
        $usuario =
            $this->usuarioRepository
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
            $this->usuarioRepository
                ->cambiarPassword(
                    $usuarioId,
                    password_hash(
                        $passwordNueva,
                        PASSWORD_BCRYPT
                    )
                );
    }

    public function resetearPassword(
        int $usuarioId,
        string $passwordNueva
    ): bool
    {
        return
            $this->usuarioRepository
                ->cambiarPassword(
                    $usuarioId,
                    password_hash(
                        $passwordNueva,
                        PASSWORD_BCRYPT
                    )
                );
    }
}