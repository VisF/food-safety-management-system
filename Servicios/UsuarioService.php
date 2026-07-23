<?php
declare(strict_types=1);


/**
 * UsuarioService - Servicio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../Repository/UsuarioRepository.php';
require_once __DIR__ . '/../dto/UsuarioDTO.php';

class UsuarioService
{
    private UsuarioRepository $usuarioRepository;

    // Inicializa las dependencias de la clase.
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

    // Obtiene por email.
    public function obtenerPorEmail(string $email): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorEmail($email);

        return $usuario
            ? UsuarioDTO::fromArray($usuario)
            : null;
    }

    // Obtiene por dni.
    public function obtenerPorDni(string $dni): ?UsuarioDTO
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorDni($dni);

        return $usuario
            ? UsuarioDTO::fromArray($usuario)
            : null;
    }

    // Lista usuarios.
    public function listarUsuarios(): array
    {
        return
            $this->usuarioRepository
                ->listarUsuarios();
    }

    // Ejecuta contar usuarios.
    public function contarUsuarios(): array
    {
        return
            $this->usuarioRepository
                ->contarUsuarios();
    }

    // Obtiene usuarios por rol.
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

    public function crear(
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
        if (
            $this->usuarioRepository
                ->existeDni($datos['dni'])
        ) {
            throw new RuntimeException(
                'El DNI ya está registrado'
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

    public function actualizar(int $id,array $datos): ?UsuarioDTO
    {
        if (
            !$this->usuarioRepository
                ->actualizar($id, $datos)
        ) {
            return null;
        }

        return $this->obtenerPorId($id);
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

    // Ejecuta desactivar.
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

    // Ejecuta quitar rol.
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

    // Obtiene roles.
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

    // Ejecuta resetear password.
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
    // Gestiona usuarios.
    public function gestionarUsuarios(): array
    {
        $usuarios =
            $this->usuarioRepository
                ->listarUsuarios();

        $estadisticas =
            $this->usuarioRepository
                ->contarUsuarios();

        return [
            'usuarios' => $usuarios,
            'total' => count($usuarios),
            'activos' => $estadisticas['activos'],
            'inactivos' => $estadisticas['inactivos']
        ];
    }
        // =====================================================
    // BÚSQUEDAS
    // =====================================================

    /**
     * Busca usuarios por apellido.
     *
     * @param string $apellido
     * @return array
     */
    public function buscarPorApellido(
        string $apellido
    ): array
    {
        return
            $this->usuarioRepository
                ->buscarPorApellido(
                    $apellido
                );
    }

    /**
     * Obtiene los datos públicos de un usuario.
     *
     * @param string $dni
     * @return array|null
     */
    public function obtenerDatosPublicos(
        string $dni
    ): ?array
    {
        $usuario =
            $this->usuarioRepository
                ->obtenerPorDni($dni);

        if (!$usuario) {
            return null;
        }

        return [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'dni' => $usuario['dni']
        ];
    }
    public function buscar(string $criterio,string $valor): array
    {
        return
            $this->usuarioRepository
                ->buscar(
                    $criterio,
                    $valor
                );
    }
    public function existeEmail(string $email,?int $ignorarUsuario = null): bool
    {
        return
            $this->usuarioRepository
                ->existeEmail(
                    $email,
                    $ignorarUsuario
                );
    }

    public function existeDni(string $dni,?int $ignorarUsuario = null): bool
    {
        return
            $this->usuarioRepository
                ->existeDni(
                    $dni,
                    $ignorarUsuario
                );
    }

}
