<?php
declare(strict_types=1);

/**
 * Administración de usuarios.
 *
 * Responsabilidades:
 * - Alta
 * - Modificación
 * - Baja
 * - Listado
 *
 * Dependencias:
 * - UsuarioRepository
 */

require_once __DIR__ . '/../Repository/UsuarioRepository.php';

class AdminUsuarioControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    
    private UsuarioRepository $usuarioRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        
        $this->usuarioRepository = new UsuarioRepository();

    }

    
    public function gestionarUsuarios(): array
    {
        try {

            $usuarios = $this->usuarioRepository->listarUsuarios();

            $total = count($usuarios);

            $estadisticas = $this->usuarioRepository->contarUsuarios();

            return [
                'success' => true,
                'usuarios' => $usuarios,
                'total' => $total,
                'activos' => $estadisticas['activos'],
                'inactivos' => $estadisticas['inactivos']
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al gestionar usuarios',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0
            ];
        }
    }

    /**
     * Crear un nuevo usuario
     * 
     * @param array $datos Array con datos: [
     *   'nombre' => string,
     *   'apellido' => string,
     *   'email' => string,
     *   'dni' => string,
     *   'password' => string,
     *   'rol' => string|int
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_usuario' => int|null
     * ]
     */
   public function crearUsuario(array $datos): array
    {
        try {

            $id = $this->usuarioRepository->crear($datos);

            $this->log(
                'Usuario creado',
                'INFO',
                [
                    'id_usuario' => $id,
                    'email' => $datos['email'] ?? null
                ]
            );

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'id_usuario' => $id
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al crear usuario',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
                'id_usuario' => null
            ];
        }
    }

    /**
     * Actualizar datos de un usuario
     * 
     * @param int $id ID del usuario
     * @param array $datos Datos a actualizar
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'usuario' => array
     * ]
     */
    public function actualizarUsuario(int $id, array $datos): array
    {
        try {

            $this->usuarioRepository->actualizar(
                $id,
                $datos
            );

            $usuario = $this->usuarioRepository
                ->obtenerPorId($id);

            $this->log(
                'Usuario actualizado',
                'INFO',
                ['id_usuario' => $id]
            );

            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'usuario' => $usuario
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al actualizar usuario',
                'ERROR',
                [
                    'id_usuario' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage(),
                'usuario' => []
            ];
        }
    }

    /**
     * Desactivar un usuario
     * 
     * @param int $id ID del usuario
     * @return array [
     *   'success' => bool,
     *   'message' => string
     * ]
     */
    public function desactivarUsuario(int $id): array
    {
        try {

            $this->usuarioRepository
                ->desactivarUsuario($id);

            $this->log(
                'Usuario desactivado',
                'INFO',
                ['id_usuario' => $id]
            );

            return [
                'success' => true,
                'message' => 'Usuario desactivado correctamente'
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al desactivar usuario',
                'ERROR',
                [
                    'id_usuario' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al desactivar usuario: ' . $e->getMessage()
            ];
        }
    }
}
