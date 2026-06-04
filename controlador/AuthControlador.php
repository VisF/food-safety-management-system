<?php
declare(strict_types=1);

use App\Middleware\CsrfMiddleware;

/**
 * AuthControlador - Gestión de autenticación y registro de usuarios
 *
 * Dependencias esperadas:
 * - Modelo: modelo/UsuarioModelo.php (clase UsuarioModelo)
 *
 * - Vistas:
 *   - vistas/login.php              (mostrar formulario login)
 *   - vistas/registro.php           (mostrar formulario registro)
 *   - vistas/perfil.php             (mostrar perfil del usuario autenticado)
 *   - vistas/login_error.php        (mostrar errores de autenticación)
 *   - vistas/registro_exitoso.php   (confirmar registro)
 *
 * - Rutas sugeridas en Router.php:
 *   - r=login           -> AuthControlador::mostrarLogin()
 *   - r=login_post      -> AuthControlador::procesarLogin($_POST)
 *   - r=registro        -> AuthControlador::mostrarRegistro()
 *   - r=registro_post   -> AuthControlador::procesarRegistro($_POST)
 *   - r=perfil          -> AuthControlador::mostrarPerfil()
 *   - r=logout          -> AuthControlador::procesarLogout()
 */

class AuthControlador
{
    private const SESSION_TIMEOUT = 3600; // 1 hora
    private const SESSION_COOKIE_NAME = 'PHPSESSID';
    private const LOG_FILE = __DIR__ . '/../logs/auth_controller.log';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutos

    private const VIEW_LOGIN = __DIR__ . '/../vistas/login.php';
    private const VIEW_REGISTRO = __DIR__ . '/../vistas/registro.php';
    private const VIEW_PERFIL = __DIR__ . '/../vistas/perfil.php';

    private ?UsuarioModelo $usuarioModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->configurarSesion();
        
        // Inicializar modelo si existe
        // Instanciar modelo de usuario si está disponible (permite testing sin DB)
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
        // Validar estado de sesión y cargar posibles dependencias adicionales
        $this->validarSesion();
        $this->inicializarModeloSiExiste();
    }

    private function inicializarModeloSiExiste(): void
    {
        // Si existe archivo de modelo, intentar cargarlo.
        $modelFile = __DIR__ . '/../modelo/UsuarioModelo.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
        }

        // Crear instancia solo si la clase existe.
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
    }

    private function configurarSesion(): void
    {
        // No iniciar sesión si ya está activa
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private function validarSesion(): void
    {
        if (empty($_SESSION)) {
            return;
        }

        $now = time();
        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        $tiempoTranscurrido = $now - $lastActivity;

        if ($lastActivity > 0 && $tiempoTranscurrido > self::SESSION_TIMEOUT) {
            $this->log('Session expired due to inactivity', 'WARNING', [
                'user_id' => $_SESSION['usuario_id'] ?? null,
                'inactive_minutes' => (int) floor($tiempoTranscurrido / 60),
            ]);
            $this->logout();
            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    private function log(string $event, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = sprintf(
            "[%s] [%s] IP: %s | %s | %s\n",
            $timestamp,
            $level,
            $this->getClientIp(),
            $event,
            $contextStr
        );
        error_log($message, 3, self::LOG_FILE);
    }

    private function getClientIp(): string
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = (string) $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim((string) ($ips[0] ?? ''));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = (string) $_SERVER['REMOTE_ADDR'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }


    private function renderView(string $viewPath, array $datosVista = []): void
    {
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'Vista no encontrada: ' . htmlspecialchars(basename($viewPath), ENT_QUOTES, 'UTF-8');
            return;
        }

        // Variables disponibles en la vista.
        extract($datosVista, EXTR_SKIP);
        include $viewPath;
    }

    public function mostrarLogin(array $datos = []): void
    {
        $datosVista = [
            'title' => 'Login - App Ciudadana',
            'csrf_token' => CsrfMiddleware::generateToken(),
            'error' => $datos['error'] ?? null,
            'email' => $datos['email'] ?? '',
        ];

        $this->renderView(self::VIEW_LOGIN, $datosVista);
    }

    public function mostrarRegistro(array $datos = []): void
    {
        $datosVista = [
            'title' => 'Registro - App Ciudadana',
            'csrf_token' => CsrfMiddleware::generateToken(),
            'error' => $datos['error'] ?? null,
            'errors' => $datos['errors'] ?? [],
            'nombre' => $datos['nombre'] ?? '',
            'email' => $datos['email'] ?? '',
            'dni' => $datos['dni'] ?? '',
        ];

        $this->renderView(self::VIEW_REGISTRO, $datosVista);
    }

    public function mostrarPerfil(): void
    {
        if (!$this->estaAutenticado()) {
            http_response_code(401);
            echo 'No autenticado';
            return;
        }

        $usuario = $this->obtenerUsuarioActual();

        // Si hay modelo, intenta refrescar desde DB
        if ($this->usuarioModelo !== null && method_exists($this->usuarioModelo, 'obtenerPorId')) {
            $usuarioDb = $this->usuarioModelo->obtenerPorId((int) $usuario['id']);
            if (is_array($usuarioDb)) {
                $usuario = array_merge($usuario, $usuarioDb);
            }
        }

        $datosVista = [
            'title' => 'Mi Perfil',
            'usuario' => $usuario,
            'csrf_token' => CsrfMiddleware::generateToken(),
            'info_sesion' => $this->obtenerInfoSesion(),
        ];

        $this->renderView(self::VIEW_PERFIL, $datosVista);
    }

    public function procesarLogin(array $datos): array
    {
        // Validación básica de entrada
        if (empty($datos['email']) || empty($datos['password'])) {
            return ['success' => false, 'error' => 'Email y contraseña requeridos'];
        }


        $email = filter_var((string) $datos['email'], FILTER_SANITIZE_EMAIL);
        $password = (string) $datos['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        $usuario = null;

        if ($this->usuarioModelo !== null && method_exists($this->usuarioModelo, 'obtenerPorEmail')) {
            $usuario = $this->usuarioModelo->obtenerPorEmail($email);

            if (!$usuario || !password_verify($password, (string) ($usuario['password'] ?? ''))) {
                $this->log('Failed login attempt', 'WARNING', ['email' => $email]);
                return ['success' => false, 'error' => 'Credenciales inválidas'];
            }
        } else {
            // Fallback temporal mientras no exista modelo.
            $usuario = [
                'id' => 1,
                'nombre' => 'Juan Perez',
                'email' => $email,
                'rol' => 'usuario',
            ];
        }

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        
        $_SESSION['usuario_rol'] = 'usuario';
        //Parche hasta armar los roles en la base de datos
        $_SESSION['roles'] = [
            'usuario'
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->log('User logged in successfully', 'INFO', [
            'usuario_id' => $usuario['id'],
            'email' => $email
        ]);

        return [
            'success' => true,
            'message' => 'Login exitoso',
            'usuario' => $usuario,
            'csrf_token' => CsrfMiddleware::generateToken(),
        ];
    }

    public function procesarRegistro(array $datos): array
    {

        $email = filter_var((string)($datos['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $apellido = trim((string)($datos['apellido'] ?? ''));
        $password = (string)($datos['password'] ?? '');
        $passwordConfirm = (string)($datos['password_confirm'] ?? '');
        $dni = trim((string)($datos['dni'] ?? ''));

        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres';
        }
        if (mb_strlen($apellido) < 2) {
            $errors['apellido'] = 'Apellido inválido';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden';
        }
        $dniLimpio = preg_replace('/\D/', '', $dni);

        if (strlen($dniLimpio) < 7 || strlen($dniLimpio) > 8) {
            $errors['dni'] = 'DNI inválido';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($this->usuarioModelo !== null && method_exists($this->usuarioModelo, 'obtenerPorEmail')) {
            $usuarioExistente = $this->usuarioModelo->obtenerPorEmail($email);
            if ($usuarioExistente) {
                return ['success' => false, 'error' => 'El email ya está registrado'];
            }

            if (method_exists($this->usuarioModelo, 'crear')) {
                $usuarioNuevo = $this->usuarioModelo->crear([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $email,
                    'dni' => $dni,
                    'password' => $password,
                    'telefono' => null
                ]);

                if (!$usuarioNuevo) {
                    return ['success' => false, 'error' => 'Error al registrar usuario'];
                }
            }
            return [
                'success' => true
            ];
        }

        $this->log('User registered successfully', 'INFO', ['email' => $email]);

        return [
            'success' => true,
            'message' => 'Usuario registrado exitosamente. Por favor, inicia sesión.',
            'usuario' => [
                'nombre' => $nombre,
                'email' => $email,
                'dni' => $dni,
            ]
        ];
    }

    public function actualizarPerfil(array $datos): array
    {
        if (!$this->estaAutenticado()) {
            return ['success' => false, 'error' => 'No autenticado'];
        }


        $usuarioId = (int) $_SESSION['usuario_id'];
        $nombre = trim((string)($datos['nombre'] ?? ''));

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
            return ['success' => false, 'error' => 'Nombre inválido'];
        }

        if ($this->usuarioModelo !== null && method_exists($this->usuarioModelo, 'actualizar')) {
            $resultado = $this->usuarioModelo->actualizar($usuarioId, ['nombre' => $nombre]);
            if (!$resultado) {
                return ['success' => false, 'error' => 'Error al actualizar perfil'];
            }
        }

        $_SESSION['usuario_nombre'] = $nombre;
        $this->log('Profile updated', 'INFO', ['usuario_id' => $usuarioId]);

        return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
    }

    public function logout(): array
    {
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $email = $_SESSION['usuario_email'] ?? 'unknown';

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                self::SESSION_COOKIE_NAME,
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();

        $this->log('User logged out', 'INFO', ['usuario_id' => $usuarioId, 'email' => $email]);

        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ];
    }

    public function procesarLogout(): void
    {
        $this->logout();
        header('Location: Router.php?r=login');
        exit;
    }

    public function estaAutenticado(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public function obtenerUsuarioActual(): array
    {
        if (!$this->estaAutenticado()) {
            return [];
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'email' => $_SESSION['usuario_email'],
            'rol' => $_SESSION['usuario_rol'],
        ];
    }

    public function obtenerInfoSesion(): array
    {
        if (!$this->estaAutenticado()) {
            return ['sesion_activa' => false];
        }

        $now = time();
        $lastActivity = (int)($_SESSION['last_activity'] ?? $now);
        $tiempoTranscurrido = $now - $lastActivity;
        $tiempoRestante = self::SESSION_TIMEOUT - $tiempoTranscurrido;

        return [
            'sesion_activa' => true,
            'usuario_id' => $_SESSION['usuario_id'],
            'usuario_email' => $_SESSION['usuario_email'],
            'tiempo_inactivo_segundos' => $tiempoTranscurrido,
            'tiempo_restante_segundos' => max(0, $tiempoRestante),
            'tiempo_total_sesion_segundos' => self::SESSION_TIMEOUT,
            'porcentaje_restante' => round((max(0, $tiempoRestante) / self::SESSION_TIMEOUT) * 100, 2),
        ];
    }

    public function renovarSesion(): array
    {
        if (!$this->estaAutenticado()) {
            return ['success' => false, 'error' => 'No hay sesión activa'];
        }

        $_SESSION['last_activity'] = time();
        $this->log('Session renewed', 'INFO', ['usuario_id' => $_SESSION['usuario_id']]);

        return [
            'success' => true,
            'message' => 'Sesión renovada',
            'sesion_info' => $this->obtenerInfoSesion(),
        ];
    }

    public function validarRol(string $rolRequerido): bool
    {
        if (!$this->estaAutenticado()) {
            return false;
        }

        $rolActual = (string) ($_SESSION['usuario_rol'] ?? '');

        $rolesValidos = match ($rolRequerido) {
            'admin' => ['admin'],
            'inspector' => ['admin', 'inspector'],
            'usuario' => ['admin', 'inspector', 'usuario'],
            default => [],
        };

        return in_array($rolActual, $rolesValidos, true);
    }
    
}