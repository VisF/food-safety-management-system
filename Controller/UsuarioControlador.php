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
require_once __DIR__ . '/../Servicios/UsuarioService.php';

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
        /**
     * Mostrar perfil del usuario autenticado.
     */
    public function mostrarPerfil(): void
    {
        $usuarioId =
            (int)(
                $_SESSION['usuario_id']
                ?? 0
            );

        if ($usuarioId <= 0) {

            header(
                'Location: ' .
                BASE_URL .
                '/login'
            );

            exit;
        }

        try {

            $usuario =
                $this->usuarioService
                    ->obtenerPorId(
                        $usuarioId
                    );

            if (!$usuario) {

                http_response_code(404);

                echo 'Usuario no encontrado.';

                return;
            }

            require_once __DIR__ .
                '/../Views/perfil.php';

            $vista =
                new PerfilVista();

            $vista->mostrar([
                'page_title' =>
                    'Mi perfil',

                'usuario' =>
                    $usuario->toArray(),

                'error' =>
                    $_SESSION['perfil_error']
                    ?? null,

                'success' =>
                    $_SESSION['perfil_success']
                    ?? null
            ]);

            unset(
                $_SESSION['perfil_error'],
                $_SESSION['perfil_success']
            );

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_MOSTRAR_PERFIL',
                'ERROR',
                [
                    'usuario_id' =>
                        $usuarioId,

                    'error' =>
                        $e->getMessage()
                ]
            );

            http_response_code(500);

            echo 'No se pudo cargar el perfil.';
        }
    }


    /**
     * Actualizar datos del perfil del usuario autenticado.
     */
    public function actualizarPerfil(): void
    {
        $usuarioId =
            (int)(
                $_SESSION['usuario_id']
                ?? 0
            );

        if ($usuarioId <= 0) {

            header(
                'Location: ' .
                BASE_URL .
                '/login'
            );

            exit;
        }

        try {

            $datos = [

                'nombre' =>
                    trim(
                        (string)(
                            $_POST['nombre']
                            ?? ''
                        )
                    ),

                'apellido' =>
                    trim(
                        (string)(
                            $_POST['apellido']
                            ?? ''
                        )
                    ),

                'email' =>
                    trim(
                        (string)(
                            $_POST['email']
                            ?? ''
                        )
                    ),

                'telefono' =>
                    trim(
                        (string)(
                            $_POST['telefono']
                            ?? ''
                        )
                    ),

                'domicilio' =>
                    trim(
                        (string)(
                            $_POST['domicilio']
                            ?? ''
                        )
                    )
            ];


            if (
                $datos['nombre'] === ''
                ||
                $datos['apellido'] === ''
                ||
                $datos['email'] === ''
            ) {

                $_SESSION['perfil_error'] =
                    'Nombre, apellido y email son obligatorios.';

                header(
                    'Location: ' .
                    BASE_URL .
                    '/perfil'
                );

                exit;
            }


            if (
                !filter_var(
                    $datos['email'],
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $_SESSION['perfil_error'] =
                    'El email ingresado no es válido.';

                header(
                    'Location: ' .
                    BASE_URL .
                    '/perfil'
                );

                exit;
            }


            $usuario =
                $this->usuarioService
                    ->actualizar(
                        $usuarioId,
                        $datos
                    );


            if (!$usuario) {

                $_SESSION['perfil_error'] =
                    'No se pudieron actualizar los datos.';

                header(
                    'Location: ' .
                    BASE_URL .
                    '/perfil'
                );

                exit;
            }


            $_SESSION['perfil_success'] =
                'Los datos del perfil fueron actualizados correctamente.';

            $this->log(
                'PERFIL_ACTUALIZADO',
                'INFO',
                [
                    'usuario_id' =>
                        $usuarioId
                ]
            );

            header(
                'Location: ' .
                BASE_URL .
                '/perfil'
            );

            exit;

        } catch (\Throwable $e) {

            $this->log(
                'ERROR_ACTUALIZAR_PERFIL',
                'ERROR',
                [
                    'usuario_id' =>
                        $usuarioId,

                    'error' =>
                        $e->getMessage()
                ]
            );

            $_SESSION['perfil_error'] =
                $e->getMessage();

            header(
                'Location: ' .
                BASE_URL .
                '/perfil'
            );

            exit;
        }
    }
}
