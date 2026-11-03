<?php

declare(strict_types=1);

require_once __DIR__ . '/../Servicios/RecoveryTokenService.php';
require_once __DIR__ . '/../Servicios/EmailService.php';
require_once __DIR__ . '/../Repository/UsuarioRepository.php';
require_once __DIR__ . '/../middleware/CsrfMiddleware.php';

class RecuperacionPasswordControlador
{
    private RecoveryTokenService $recoveryTokenService;
    private EmailService $emailService;
    private UsuarioRepository $usuarioRepository;

    public function __construct()
    {
        $this->recoveryTokenService = new RecoveryTokenService();
        $this->emailService = new EmailService();
        $this->usuarioRepository = new UsuarioRepository();
    }

    /**
     * Muestra el formulario para solicitar la recuperación.
     */
    public function mostrarSolicitud(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        require_once __DIR__ . '/../Views/recuperar_password.php';

        $vista = new RecuperarPasswordVista();
        $vista->mostrar();
    }

    /**
     * Procesa la solicitud de recuperación.
     *
     * No informa si el correo existe o no.
     */
    public function procesarSolicitud(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        CsrfMiddleware::validate();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $email = trim((string) ($_POST['email'] ?? ''));

        $_SESSION['recuperacion_mensaje'] =
            'Si el correo corresponde a una cuenta registrada, recibirás las instrucciones para recuperar tu contraseña.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirigir('/recuperar-password');
        }

        try {
            $usuario = $this->usuarioRepository->obtenerPorEmail($email);

            if ($usuario !== null && (int) ($usuario['activo'] ?? 0) === 1) {
                $usuarioId = (int) ($usuario['id'] ?? 0);
                $emailUsuario = (string) ($usuario['email'] ?? '');
                $nombreUsuario = (string) ($usuario['nombre'] ?? '');

                if (
                    $usuarioId > 0
                    && filter_var($emailUsuario, FILTER_VALIDATE_EMAIL)
                ) {
                    $datosToken = $this->recoveryTokenService
                        ->crearToken($usuarioId);

                    $token = (string) ($datosToken['token'] ?? '');

                    if ($token !== '') {
                        $baseUrl = defined('BASE_URL')
                            ? rtrim((string) BASE_URL, '/')
                            : '';

                        $urlRecuperacion = $baseUrl
                            . '/restablecer-password?token='
                            . rawurlencode($token);

                        $enviado = $this->emailService->enviarRecuperacion(
                            $emailUsuario,
                            $urlRecuperacion,
                            $nombreUsuario
                        );

                        if (!$enviado) {
                            error_log(
                                'No se pudo enviar el correo de recuperación.'
                            );
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log(
                'Error al procesar la solicitud de recuperación: '
                . $e->getMessage()
            );
        }

        $this->redirigir('/recuperar-password');
    }

    /**
     * Valida el token y muestra el formulario para cambiar la contraseña.
     */
    public function mostrarRestablecimiento(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = trim((string) ($_GET['token'] ?? ''));

        if (
            $token === ''
            || (int) ($this->recoveryTokenService->validarToken($token) ?? 0) === 0
        ) {
            http_response_code(400);

            require_once __DIR__ . '/../Views/restablecer_password_error.php';

            $vista = new RestablecerPasswordErrorVista();
            $vista->mostrar([
                'mensaje' =>
                    'El enlace de recuperación es inválido o ha vencido.',
            ]);

            return;
        }

        require_once __DIR__ . '/../Views/restablecer_password.php';

        $vista = new RestablecerPasswordVista();
        $vista->mostrar([
            'token' => $token,
        ]);
    }

    /**
     * Procesa el cambio de contraseña.
     */
    public function procesarRestablecimiento(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        CsrfMiddleware::validate();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmacion =
            (string) ($_POST['password_confirm'] ?? '');

        if (
            $password !== $passwordConfirmacion
            || strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
        ) {
            $_SESSION['recuperacion_error'] =
                'La contraseña debe tener al menos 8 caracteres, '
                . 'una mayúscula y una minúscula. '
                . 'Ambas contraseñas deben coincidir.';

            $this->redirigir(
                '/restablecer-password?token=' . rawurlencode($token)
            );
        }

        try {
            $resultado = $this->recoveryTokenService
                ->restablecerPassword($token, $password);

            if (!$resultado) {
                $_SESSION['recuperacion_error'] =
                    'El enlace de recuperación es inválido, '
                    . 'venció o ya fue utilizado.';

                $this->redirigir('/recuperar-password');
            }

            $_SESSION['recuperacion_exito'] =
                'Tu contraseña se actualizó correctamente. '
                . 'Ya podés iniciar sesión.';

            $this->redirigir('/login');
        } catch (Throwable $e) {
            error_log(
                'Error al restablecer la contraseña: '
                . $e->getMessage()
            );

            $_SESSION['recuperacion_error'] =
                'No se pudo actualizar la contraseña. Intentá nuevamente.';

            $this->redirigir('/recuperar-password');
        }
    }

    /**
     * Redirige utilizando BASE_URL cuando está definida.
     */
    private function redirigir(string $ruta): void
    {
        $baseUrl = defined('BASE_URL')
            ? rtrim((string) BASE_URL, '/')
            : '';

        header('Location: ' . $baseUrl . $ruta);
        exit;
    }
}