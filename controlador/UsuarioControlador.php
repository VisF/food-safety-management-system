<?php
declare(strict_types=1);

/**
 * UsuarioControlador - Gestión de usuarios del sistema
 * 
 * Dependencias esperadas:
 * - Modelo: modelo/UsuarioModelo.php (clase UsuarioModelo)
 * - Modelo: modelo/UsuarioRolModelo.php (clase UsuarioRolModelo)
 *
 * Vistas esperadas:
 * - vistas/usuarios_listado.php    (mostrar listado de usuarios)
 * - vistas/editar_usuario.php      (formulario editar usuario)
 * - vistas/usuario_detalle.php     (ver detalles de un usuario)
 * 
 * Métodos del controlador:
 * - listarUsuarios()                 -> Retorna array de usuarios
 * - obtenerUsuario($id)              -> Retorna array de un usuario
 * - buscarUsuarios($termino)         -> Retorna array de usuarios encontrados
 * - actualizarUsuario($id, $datos)   -> Retorna array con resultado
 * - cambiarPassword($id, $datos)     -> Retorna array con resultado
 * - desactivarUsuario($id)           -> Retorna array con resultado
 * - asignarRol($id_usuario, $id_rol) -> Retorna array con resultado
 * - obtenerRolesUsuario($id)         -> Retorna array de roles
 */

class UsuarioControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/usuario_controller.log';
    
    private ?UsuarioModelo $usuarioModelo = null;
    private ?UsuarioRolModelo $usuarioRolModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar modelos si existen
     */
    private function inicializarModelos(): void
    {
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
        if (class_exists('UsuarioRolModelo')) {
            $this->usuarioRolModelo = new UsuarioRolModelo();
        }
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
     * Listar todos los usuarios activos
     * 
     * VISTA A LLAMAR: vistas/usuarios_listado.php
     * 
     * @return array Array con estructura:
     *   [
     *     'success' => bool,
     *     'usuarios' => [
     *       ['id' => 1, 'nombre' => '...', 'email' => '...', 'dni' => '...', ...],
     *       ...
     *     ],
     *     'total' => int
     *   ]
     */
    public function listarUsuarios(): array
    {
        // TODO: Llamar a $this->usuarioModelo->obtenerTodos()
        // TODO: Retornar array con usuarios
        
        return [
            'success' => false,
            'message' => 'Modelo de usuario no disponible',
            'usuarios' => [],
            'total' => 0
        ];
    }

    /**
     * Obtener detalles de un usuario específico
     * 
     * VISTA A LLAMAR: vistas/usuario_detalle.php
     * 
     * @param int $id ID del usuario
     * @return array Array con estructura:
     *   [
     *     'success' => bool,
     *     'usuario' => ['id' => ..., 'nombre' => ..., ...],
     *     'roles' => [['id' => 1, 'nombre' => '...'], ...],
     *     'error' => string|null
     *   ]
     */
    public function obtenerUsuario(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        // TODO: Llamar a $this->usuarioModelo->obtenerPorId($id)
        // TODO: Llamar a $this->usuarioRolModelo->obtenerRolesPorUsuario($id)
        // TODO: Retornar array con usuario y roles
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible',
            'usuario' => null,
            'roles' => []
        ];
    }

    /**
     * Buscar usuarios por término (nombre, email, dni)
     * 
     * VISTA A LLAMAR: vistas/usuarios_listado.php (con resultados de búsqueda)
     * 
     * @param string $termino Criterio de búsqueda
     * @return array Array con estructura:
     *   [
     *     'success' => bool,
     *     'usuarios' => [...],
     *     'total' => int,
     *     'termino' => string
     *   ]
     */
    public function buscarUsuarios(string $termino): array
    {
        $termino = trim($termino);
        if (strlen($termino) < 3) {
            return [
                'success' => false,
                'error' => 'El término debe tener al menos 3 caracteres',
                'usuarios' => [],
                'total' => 0
            ];
        }

        // TODO: Determinar si es email, dni o nombre
        // TODO: Llamar a $this->usuarioModelo->buscar($criterio, $termino)
        // TODO: Retornar array con usuarios encontrados
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible',
            'usuarios' => [],
            'total' => 0,
            'termino' => $termino
        ];
    }

    /**
     * Actualizar datos de un usuario (excepto password)
     * 
     * VISTA A LLAMAR: vistas/editar_usuario.php
     * 
     * @param int $id ID del usuario
     * @param array $datos Datos a actualizar: nombre, apellido, telefono, domicilio, email
     * @return array Array con resultado:
     *   ['success' => bool, 'message' => string, 'usuario' => array|null]
     */
    public function actualizarUsuario(int $id, array $datos): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        // TODO: Validar que el usuario exista
        // TODO: Validar cada campo de $datos
        // TODO: Llamar a $this->usuarioModelo->actualizar($id, $datos)
        // TODO: Registrar en log
        // TODO: Retornar resultado
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible'
        ];
    }

    /**
     * Cambiar contraseña de un usuario
     * 
     * @param int $id ID del usuario
     * @param array $datos Array con: password_actual, password_nueva, password_confirm
     * @return array Array con resultado:
     *   ['success' => bool, 'message' => string]
     */
    public function cambiarPassword(int $id, array $datos): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        $passwordActual = trim((string)($datos['password_actual'] ?? ''));
        $passwordNueva = trim((string)($datos['password_nueva'] ?? ''));
        $passwordConfirm = trim((string)($datos['password_confirm'] ?? ''));

        // TODO: Validar que password_nueva sea fuerte (min 8 caracteres, mayús, minús, números)
        // TODO: Validar que password_nueva === password_confirm
        // TODO: Obtener usuario actual y verificar password_actual con password_verify()
        // TODO: Llamar a $this->usuarioModelo->cambiarPassword($id, $passwordNueva)
        // TODO: Registrar en log
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible'
        ];
    }

    /**
     * Desactivar un usuario (marcar como inactivo)
     * 
     * @param int $id ID del usuario
     * @return array Array con resultado:
     *   ['success' => bool, 'message' => string]
     */
    public function desactivarUsuario(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        // TODO: Validar que el usuario exista
        // TODO: Llamar a $this->usuarioModelo->eliminar($id) [que marca como inactivo]
        // TODO: Registrar en log
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible'
        ];
    }

    /**
     * Asignar un rol a un usuario
     * 
     * @param int $id_usuario ID del usuario
     * @param int $id_rol ID del rol
     * @return array Array con resultado:
     *   ['success' => bool, 'message' => string]
     */
    public function asignarRol(int $id_usuario, int $id_rol): array
    {
        if ($id_usuario <= 0 || $id_rol <= 0) {
            return ['success' => false, 'error' => 'IDs inválidos'];
        }

        // TODO: Validar que usuario exista
        // TODO: Validar que rol exista
        // TODO: Llamar a $this->usuarioRolModelo->asignarRol($id_usuario, $id_rol)
        // TODO: Registrar en log
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible'
        ];
    }

    /**
     * Remover un rol de un usuario
     * 
     * @param int $id_usuario ID del usuario
     * @param int $id_rol ID del rol
     * @return array Array con resultado:
     *   ['success' => bool, 'message' => string]
     */
    public function removerRol(int $id_usuario, int $id_rol): array
    {
        if ($id_usuario <= 0 || $id_rol <= 0) {
            return ['success' => false, 'error' => 'IDs inválidos'];
        }

        // TODO: Validar que la asignación exista
        // TODO: Llamar a $this->usuarioRolModelo->removerRol($id_usuario, $id_rol)
        // TODO: Registrar en log
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible'
        ];
    }

    /**
     * Obtener todos los roles asignados a un usuario
     * 
     * @param int $id ID del usuario
     * @return array Array con estructura:
     *   [
     *     'success' => bool,
     *     'roles' => [['id' => 1, 'nombre' => '...', ...], ...],
     *     'total' => int
     *   ]
     */
    public function obtenerRolesUsuario(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido', 'roles' => [], 'total' => 0];
        }

        // TODO: Llamar a $this->usuarioRolModelo->obtenerRolesPorUsuario($id)
        // TODO: Retornar array con roles
        
        return [
            'success' => false,
            'error' => 'Modelo no disponible',
            'roles' => [],
            'total' => 0
        ];
    }

    /**
     * Obtener información de estadísticas de usuarios
     * 
     * @return array Array con estadísticas:
     *   ['total_usuarios' => int, 'usuarios_activos' => int, 'usuarios_inactivos' => int]
     */
    public function obtenerEstadisticas(): array
    {
        // TODO: Contar usuarios activos e inactivos
        // TODO: Retornar estadísticas
        
        return [
            'total_usuarios' => 0,
            'usuarios_activos' => 0,
            'usuarios_inactivos' => 0
        ];
    }
}
