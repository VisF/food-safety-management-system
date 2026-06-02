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
        try {
            // Preferir modelo si está disponible
            if ($this->usuarioModelo) {
                // No existe método obtenerTodos en el modelo, usar búsqueda amplia
                $usuarios = $this->usuarioModelo->buscar('nombre', '');
                // buscar con criterio vacío puede no retornar nada; fallback a consulta directa
                if (empty($usuarios)) {
                    $connFile = __DIR__ . '/../db/Connection.php';
                    if (file_exists($connFile)) {
                        require_once $connFile;
                        $pdo = Connection::getPDO();
                        $stmt = $pdo->prepare('SELECT id, nombre, apellido, dni, email, telefono, domicilio, activo FROM usuarios WHERE activo = 1 ORDER BY nombre ASC');
                        $stmt->execute();
                        $usuarios = $stmt->fetchAll();
                    }
                }
            } else {
                $usuarios = [];
                $connFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($connFile)) {
                    require_once $connFile;
                    $pdo = Connection::getPDO();
                    $stmt = $pdo->prepare('SELECT id, nombre, apellido, dni, email, telefono, domicilio, activo FROM usuarios WHERE activo = 1 ORDER BY nombre ASC');
                    $stmt->execute();
                    $usuarios = $stmt->fetchAll();
                }
            }

            return [
                'success' => true,
                'message' => 'Usuarios listados',
                'usuarios' => $usuarios ?: [],
                'total' => count($usuarios ?: [])
            ];
        } catch (\Exception $e) {
            $this->log('ERROR_LISTAR_USUARIOS', 'ERROR', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error listando usuarios', 'usuarios' => [], 'total' => 0];
        }
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
        // Validación de entrada: el ID debe ser positivo
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        try {
            if (!$this->usuarioModelo) return ['success' => false, 'error' => 'Modelo Usuario no disponible', 'usuario' => null, 'roles' => []];
            $usuario = $this->usuarioModelo->obtenerPorId($id);
            if (!$usuario) return ['success' => false, 'error' => 'Usuario no encontrado', 'usuario' => null, 'roles' => []];

            $roles = [];
            if ($this->usuarioRolModelo) {
                $roles = $this->usuarioRolModelo->obtenerRolesPorUsuario($id);
            }

            return ['success' => true, 'usuario' => $usuario, 'roles' => $roles, 'error' => null];
        } catch (\Exception $e) {
            $this->log('ERROR_OBTENER_USUARIO', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error obteniendo usuario', 'usuario' => null, 'roles' => []];
        }
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
        // Validación simple del término de búsqueda para evitar consultas muy amplias
        if (strlen($termino) < 3) {
            return [
                'success' => false,
                'error' => 'El término debe tener al menos 3 caracteres',
                'usuarios' => [],
                'total' => 0
            ];
        }

        try {
            if (!$this->usuarioModelo) return ['success' => false, 'error' => 'Modelo Usuario no disponible', 'usuarios' => [], 'total' => 0, 'termino' => $termino];

            $criterio = 'nombre';
            if (filter_var($termino, FILTER_VALIDATE_EMAIL)) {
                $criterio = 'email';
            } elseif (preg_match('/^[0-9]{6,10}$/', $termino)) {
                $criterio = 'dni';
            }

            $usuarios = $this->usuarioModelo->buscar($criterio, $termino);
            return ['success' => true, 'usuarios' => $usuarios, 'total' => count($usuarios), 'termino' => $termino];
        } catch (\Exception $e) {
            $this->log('ERROR_BUSCAR_USUARIOS', 'ERROR', ['termino' => $termino, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error en búsqueda', 'usuarios' => [], 'total' => 0, 'termino' => $termino];
        }
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
        // Validación de entrada: ID válido
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        try {
            if (!$this->usuarioModelo) return ['success' => false, 'error' => 'Modelo Usuario no disponible'];
            $existe = $this->usuarioModelo->obtenerPorId($id);
            if (!$existe) return ['success' => false, 'error' => 'Usuario no encontrado'];

            // Validar campos permitidos
            $allowed = ['nombre','apellido','telefono','domicilio','email'];
            $update = [];
            foreach ($allowed as $f) {
                if (isset($datos[$f])) {
                    $val = trim((string)$datos[$f]);
                    if ($f === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        return ['success' => false, 'error' => 'Email inválido'];
                    }
                    $update[$f] = $val;
                }
            }
            if (empty($update)) return ['success' => false, 'error' => 'No hay campos válidos para actualizar'];

            $ok = $this->usuarioModelo->actualizar($id, $update);
            if ($ok) {
                $this->log('USUARIO_ACTUALIZADO', 'INFO', ['id' => $id, 'campos' => array_keys($update)]);
                $usuario = $this->usuarioModelo->obtenerPorId($id);
                return ['success' => true, 'message' => 'Usuario actualizado', 'usuario' => $usuario];
            }
            return ['success' => false, 'error' => 'Error al actualizar usuario'];
        } catch (\Exception $e) {
            $this->log('ERROR_ACTUALIZAR_USUARIO', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error al actualizar usuario'];
        }
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
        // Validación de entrada: ID válido
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de usuario inválido'];
        }

        $passwordActual = trim((string)($datos['password_actual'] ?? ''));
        $passwordNueva = trim((string)($datos['password_nueva'] ?? ''));
        $passwordConfirm = trim((string)($datos['password_confirm'] ?? ''));

        try {
            if (!$this->usuarioModelo) return ['success' => false, 'error' => 'Modelo Usuario no disponible'];

            // Validaciones
            if (strlen($passwordNueva) < 8 || !preg_match('/[A-Z]/', $passwordNueva) || !preg_match('/[a-z]/', $passwordNueva) || !preg_match('/[0-9]/', $passwordNueva)) {
                return ['success' => false, 'error' => 'La nueva contraseña no cumple los requisitos de seguridad'];
            }
            if ($passwordNueva !== $passwordConfirm) return ['success' => false, 'error' => 'Las contraseñas no coinciden'];

            // Obtener hash actual desde BD
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['success' => false, 'error' => 'Conexión a BD no disponible'];
            require_once $connFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT password FROM usuarios WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row || !isset($row['password'])) return ['success' => false, 'error' => 'Usuario no encontrado'];

            if (!password_verify($passwordActual, $row['password'])) {
                return ['success' => false, 'error' => 'Contraseña actual incorrecta'];
            }

            $ok = $this->usuarioModelo->cambiarPassword($id, $passwordNueva);
            if ($ok) {
                $this->log('PASSWORD_CAMBIADO', 'INFO', ['id' => $id]);
                return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
            }
            return ['success' => false, 'error' => 'Error al actualizar contraseña'];
        } catch (\Exception $e) {
            $this->log('ERROR_CAMBIAR_PASSWORD', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error cambiando contraseña'];
        }
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

        try {
            if (!$this->usuarioModelo) return ['success' => false, 'error' => 'Modelo Usuario no disponible'];
            $existe = $this->usuarioModelo->obtenerPorId($id);
            if (!$existe) return ['success' => false, 'error' => 'Usuario no encontrado'];

            $ok = $this->usuarioModelo->eliminar($id);
            if ($ok) {
                $this->log('USUARIO_DESACTIVADO', 'INFO', ['id' => $id]);
                return ['success' => true, 'message' => 'Usuario desactivado'];
            }
            return ['success' => false, 'error' => 'Error al desactivar usuario'];
        } catch (\Exception $e) {
            $this->log('ERROR_DESACTIVAR_USUARIO', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error desactivando usuario'];
        }
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

        try {
            if (!$this->usuarioRolModelo) return ['success' => false, 'error' => 'Modelo UsuarioRol no disponible'];
            // Validar existencia de usuario
            if ($this->usuarioModelo && !$this->usuarioModelo->obtenerPorId($id_usuario)) return ['success' => false, 'error' => 'Usuario no encontrado'];

            $ok = $this->usuarioRolModelo->asignarRol($id_usuario, $id_rol);
            if ($ok) {
                $this->log('ROL_ASIGNADO', 'INFO', ['usuario_id' => $id_usuario, 'rol_id' => $id_rol]);
                return ['success' => true, 'message' => 'Rol asignado correctamente'];
            }
            return ['success' => false, 'error' => 'Error al asignar rol (posible duplicado o rol inexistente)'];
        } catch (\Exception $e) {
            $this->log('ERROR_ASIGNAR_ROL', 'ERROR', ['usuario_id' => $id_usuario, 'rol_id' => $id_rol, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error asignando rol'];
        }
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

        try {
            if (!$this->usuarioRolModelo) return ['success' => false, 'error' => 'Modelo UsuarioRol no disponible'];
            $ok = $this->usuarioRolModelo->removerRol($id_usuario, $id_rol);
            if ($ok) {
                $this->log('ROL_REMOVIDO', 'INFO', ['usuario_id' => $id_usuario, 'rol_id' => $id_rol]);
                return ['success' => true, 'message' => 'Rol removido correctamente'];
            }
            return ['success' => false, 'error' => 'Asignación no existe o error al remover'];
        } catch (\Exception $e) {
            $this->log('ERROR_REMOVER_ROL', 'ERROR', ['usuario_id' => $id_usuario, 'rol_id' => $id_rol, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error removiendo rol'];
        }
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

        try {
            if (!$this->usuarioRolModelo) return ['success' => false, 'error' => 'Modelo UsuarioRol no disponible', 'roles' => [], 'total' => 0];
            $roles = $this->usuarioRolModelo->obtenerRolesPorUsuario($id);
            return ['success' => true, 'roles' => $roles, 'total' => count($roles)];
        } catch (\Exception $e) {
            $this->log('ERROR_OBTENER_ROLES_USUARIO', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error obteniendo roles', 'roles' => [], 'total' => 0];
        }
    }

    /**
     * Obtener información de estadísticas de usuarios
     * 
     * @return array Array con estadísticas:
     *   ['total_usuarios' => int, 'usuarios_activos' => int, 'usuarios_inactivos' => int]
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['total_usuarios' => 0, 'usuarios_activos' => 0, 'usuarios_inactivos' => 0];
            require_once $connFile;
            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM usuarios');
            $stmt->execute();
            $total = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) as activos FROM usuarios WHERE activo = 1');
            $stmt->execute();
            $activos = (int)$stmt->fetchColumn();

            $inactivos = $total - $activos;
            return ['total_usuarios' => $total, 'usuarios_activos' => $activos, 'usuarios_inactivos' => $inactivos];
        } catch (\Exception $e) {
            $this->log('ERROR_OBTENER_ESTADISTICAS', 'ERROR', ['error' => $e->getMessage()]);
            return ['total_usuarios' => 0, 'usuarios_activos' => 0, 'usuarios_inactivos' => 0];
        }
    }
}
