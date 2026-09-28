<?php
declare(strict_types=1);


/**
 * AdminUsuarioControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

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

require_once __DIR__ . '/../Servicios/UsuarioService.php';

class AdminUsuarioControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    
    private UsuarioService $usuarioService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        
        $this->usuarioService = new UsuarioService();

    }
    private function log(string $event,string $level = 'INFO',array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');

        $contextStr = !empty($context)
            ? json_encode($context, JSON_UNESCAPED_UNICODE)
            : '';

        $message = sprintf(
            "[%s] [%s] %s | %s\n",
            $timestamp,
            $level,
            $event,
            $contextStr
        );

        error_log(
            $message,
            3,
            self::LOG_FILE
        );
    }

    
    // Ejecuta gestionar usuarios.
    public function gestionarUsuarios(): array
    {
        try {
            $datos =
                $this->usuarioService
                    ->gestionarUsuarios();

            return [
                'success' => true,
                'usuarios' => $datos['usuarios'],
                'total' => $datos['total'],
                'activos' => $datos['activos'],
                'inactivos' => $datos['inactivos']
            ];

        } catch (Throwable $e) {

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

            $usuario =
                $this->usuarioService
                    ->crear($datos);

            $this->log(
                'Usuario creado',
                'INFO',
                [
                    'id_usuario' => $usuario->getId(),
                    'email' => $usuario->getEmail()
                ]
            );

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'usuario' => $usuario
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al crear usuario',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
                'usuario' => null
            ];
        }
    }

    /**
     * Actualizar datos, rol y contraseña de un usuario.
     *
     * @param int $id ID del usuario.
     * @param array $datos Datos a actualizar.
     * @return array
     */
    public function actualizarUsuario(int $id, array $datos): array
    {
        try {

            /*
            * =====================================================
            * DATOS PERSONALES
            * =====================================================
            */

            $datosActualizar = [
                'nombre' => trim((string)($datos['nombre'] ?? '')),
                'apellido' => trim((string)($datos['apellido'] ?? '')),
                'email' => strtolower(trim((string)($datos['email'] ?? ''))),
                'telefono' => trim((string)($datos['telefono'] ?? '')),
                'domicilio' => trim((string)($datos['domicilio'] ?? ''))
            ];

            if (
                $datosActualizar['nombre'] === ''
                || $datosActualizar['apellido'] === ''
                || $datosActualizar['email'] === ''
            ) {
                throw new InvalidArgumentException(
                    'Los datos personales obligatorios no pueden estar vacíos.'
                );
            }

            $resultadoActualizacion =
                $this->usuarioService->actualizar(
                    $id,
                    $datosActualizar
                );

            if (!$resultadoActualizacion) {
                throw new RuntimeException(
                    'No se pudieron actualizar los datos del usuario.'
                );
            }


            /*
            * =====================================================
            * ROL
            * =====================================================
            */

            $rolId = (int)($datos['rol'] ?? 0);

            if (!in_array($rolId, [1, 2, 3], true)) {
                throw new InvalidArgumentException(
                    'El rol seleccionado no es válido.'
                );
            }

            $resultadoRol =
                $this->usuarioService->actualizarRoles(
                    $id,
                    [$rolId]
                );

            if (!$resultadoRol) {
                throw new RuntimeException(
                    'No se pudo actualizar el rol del usuario.'
                );
            }


            /*
            * =====================================================
            * CONTRASEÑA
            * =====================================================
            */

            $passwordNueva =
                (string)($datos['password'] ?? '');

            $passwordConfirmacion =
                (string)($datos['password_confirmacion'] ?? '');

            /*
            * La contraseña es opcional.
            * Si ambos campos están vacíos,
            * se mantiene la contraseña actual.
            */

            if (
                $passwordNueva !== ''
                || $passwordConfirmacion !== ''
            ) {

                if (strlen($passwordNueva) < 8) {
                    throw new InvalidArgumentException(
                        'La contraseña debe tener al menos 8 caracteres.'
                    );
                }

                if ($passwordNueva !== $passwordConfirmacion) {
                    throw new InvalidArgumentException(
                        'Las contraseñas no coinciden.'
                    );
                }

                $resultadoPassword =
                    $this->usuarioService->resetearPassword(
                        $id,
                        $passwordNueva
                    );

                if (!$resultadoPassword) {
                    throw new RuntimeException(
                        'No se pudo actualizar la contraseña.'
                    );
                }
            }


            /*
            * =====================================================
            * USUARIO ACTUALIZADO
            * =====================================================
            */

            $usuario =
                $this->usuarioService
                    ->obtenerPorId($id);

            if (!$usuario) {
                throw new RuntimeException(
                    'No se pudo obtener el usuario actualizado.'
                );
            }


            /*
            * =====================================================
            * LOG
            * =====================================================
            */

            $this->log(
                'Usuario actualizado',
                'INFO',
                [
                    'id_usuario' => $id,
                    'id_rol' => $rolId,
                    'password_actualizada' => $passwordNueva !== ''
                ]
            );


            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'usuario' => $usuario
            ];

        } catch (Throwable $e) {

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
                'usuario' => null
            ];
        }
    }

    public function activarUsuario(int $id): array
    {
        try {

            $this->usuarioService->activar($id);

            $this->log(
                'Usuario activado',
                'INFO',
                ['id_usuario' => $id]
            );

            return [
                'success' => true,
                'message' => 'Usuario activado correctamente'
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al activar usuario',
                'ERROR',
                [
                    'id_usuario' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => 'Error al activar usuario: ' . $e->getMessage()
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

            $this->usuarioService
                ->desactivar($id);

            $this->log(
                'Usuario desactivado',
                'INFO',
                ['id_usuario' => $id]
            );

            return [
                'success' => true,
                'message' => 'Usuario desactivado correctamente'
            ];

        } catch (Throwable $e) {

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
