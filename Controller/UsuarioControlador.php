<?php
declare(strict_types=1);


/**
 * UsuarioControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * UsuarioControlador - Gestión de usuarios del sistema
 * 
 * Dependencias esperadas:
 * - Servicio: Servicio/UsuarioService.php (clase UsuarioService)
 * 
 *
 * Vistas esperadas:
 * - vistas/usuarios_listado.php    (mostrar listado de usuarios)
 * - vistas/editar_usuario.php      (formulario editar usuario)
 * - vistas/usuario_detalle.php     (ver detalles de un usuario)
 * 
 * Métodos del controlador:
 * - listarUsuarios()                 -> Retorna array de usuarios
 * - obtenerUsuario($id)              -> Retorna array de un usuario
 * - buscarUsuarios($criterio, $valor)       -> Retorna array de usuarios encontrados
 * - actualizarUsuario($id, $datos)   -> Retorna array con resultado
 * - cambiarPassword($id, $datos)     -> Retorna array con resultado
 * - desactivarUsuario($id)           -> Retorna array con resultado
 * - asignarRol($id_usuario, $id_rol) -> Retorna array con resultado
 * - obtenerRolesUsuario($id)         -> Retorna array de roles
 */

class UsuarioControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/usuario_controller.log';
    
    private ?UsuarioService $usuarioService = null;


    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->usuarioService = new UsuarioService();
        
    }


    /**
     * Registrar eventos en log
     */
    private function log(string $event, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = "[$timestamp] [$level] {$event} | {$contextStr}\n";
        error_log($message, 3, self::LOG_FILE);
    }

    /**
     * Listar usuarios.
     */
    public function listarUsuarios(): array
    {
        try {

            $usuarios =
                $this->usuarioService
                    ->listarUsuarios();

            return [
                'success' => true,
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_LISTAR_USUARIOS',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtener usuario.
     */
    public function obtenerUsuario(
        int $id
    ): array
    {
        if ($id <= 0) {

            return [
                'success' => false,
                'usuario' => null,
                'roles' => []
            ];
        }

        try {

            $usuario =
                $this->usuarioService
                    ->obtenerPorId($id);

            if (!$usuario) {

                return [
                    'success' => false,
                    'usuario' => null,
                    'roles' => []
                ];
            }

            return [
                'success' => true,
                'usuario' => $usuario,
                'roles' =>
                    $this->usuarioService
                        ->obtenerRoles($id)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_OBTENER_USUARIO',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'usuario' => null,
                'roles' => []
            ];
        }
    }

    /**
     * Buscar usuarios.
     */
    public function buscarUsuarios(
        string $criterio,
        string $valor
    ): array
    {
        try {

            $usuarios =
                $this->usuarioService
                    ->buscar(
                        $criterio,
                        $valor
                    );

            return [
                'success' => true,
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_BUSCAR_USUARIOS',
                'ERROR',
                [
                    'criterio' => $criterio,
                    'valor' => $valor,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0
            ];
        }
    }
    /**
     * Actualizar usuario.
     */
    public function actualizarUsuario(
        int $id,
        array $datos
    ): array
    {
        if ($id <= 0) {

            return [
                'success' => false,
                'mensaje' => 'ID de usuario inválido.'
            ];
        }

        try {

            $usuario =
                $this->usuarioService
                    ->actualizar(
                        $id,
                        $datos
                    );

            if (!$usuario) {

                return [
                    'success' => false,
                    'mensaje' => 'No se pudo actualizar el usuario.'
                ];
            }

            $this->log(
                'USUARIO_ACTUALIZADO',
                'INFO',
                [
                    'id' => $id
                ]
            );

            return [
                'success' => true,
                'usuario' => $usuario
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_ACTUALIZAR_USUARIO',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Cambiar contraseña.
     */
    public function cambiarPassword(
        int $id,
        array $datos
    ): array
    {
        if ($id <= 0) {

            return [
                'success' => false,
                'mensaje' => 'ID de usuario inválido.'
            ];
        }

        try {

            $ok =
                $this->usuarioService
                    ->cambiarPassword(
                        $id,
                        $datos['password_actual'],
                        $datos['password_nueva']
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'La contraseña actual es incorrecta.'
                ];
            }

            $this->log(
                'PASSWORD_CAMBIADO',
                'INFO',
                [
                    'id' => $id
                ]
            );

            return [
                'success' => true
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_CAMBIAR_PASSWORD',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Desactivar usuario.
     */
    public function desactivarUsuario(
        int $id
    ): array
    {
        if ($id <= 0) {

            return [
                'success' => false,
                'mensaje' => 'ID de usuario inválido.'
            ];
        }

        try {

            $ok =
                $this->usuarioService
                    ->desactivar($id);

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No se pudo desactivar el usuario.'
                ];
            }

            $this->log(
                'USUARIO_DESACTIVADO',
                'INFO',
                [
                    'id' => $id
                ]
            );

            return [
                'success' => true
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_DESACTIVAR_USUARIO',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
    /**
     * Asignar rol.
     */
    public function asignarRol(
        int $usuarioId,
        int $rolId
    ): array
    {
        if ($usuarioId <= 0 || $rolId <= 0) {

            return [
                'success' => false,
                'mensaje' => 'Parámetros inválidos.'
            ];
        }

        try {

            $ok =
                $this->usuarioService
                    ->asignarRol(
                        $usuarioId,
                        $rolId
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' => 'No se pudo asignar el rol.'
                ];
            }

            $this->log(
                'ROL_ASIGNADO',
                'INFO',
                [
                    'usuario' => $usuarioId,
                    'rol' => $rolId
                ]
            );

            return [
                'success' => true
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_ASIGNAR_ROL',
                'ERROR',
                [
                    'usuario' => $usuarioId,
                    'rol' => $rolId,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Remover rol.
     */
    public function removerRol(
        int $usuarioId,
        int $rolId
    ): array
    {
        if ($usuarioId <= 0 || $rolId <= 0) {

            return [
                'success' => false,
                'mensaje' => 'Parámetros inválidos.'
            ];
        }

        try {

            $ok =
                $this->usuarioService
                    ->quitarRol(
                        $usuarioId,
                        $rolId
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' => 'No se pudo remover el rol.'
                ];
            }

            $this->log(
                'ROL_REMOVIDO',
                'INFO',
                [
                    'usuario' => $usuarioId,
                    'rol' => $rolId
                ]
            );

            return [
                'success' => true
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_REMOVER_ROL',
                'ERROR',
                [
                    'usuario' => $usuarioId,
                    'rol' => $rolId,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener roles del usuario.
     */
    public function obtenerRolesUsuario(
        int $usuarioId
    ): array
    {
        if ($usuarioId <= 0) {

            return [
                'success' => false,
                'roles' => []
            ];
        }

        try {

            $roles =
                $this->usuarioService
                    ->obtenerRoles(
                        $usuarioId
                    );

            return [
                'success' => true,
                'roles' => $roles,
                'total' => count($roles)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_OBTENER_ROLES',
                'ERROR',
                [
                    'usuario' => $usuarioId,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'roles' => []
            ];
        }
    }

    /**
     * Obtener estadísticas.
     */
    public function obtenerEstadisticas(): array
    {
        try {

            return
                $this->usuarioService
                    ->contarUsuarios();

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_OBTENER_ESTADISTICAS',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0
            ];
        }
    }
}
