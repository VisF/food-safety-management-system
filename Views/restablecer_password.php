<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

/**
 * Vista: restablecer_password.php
 *
 * Responsabilidades:
 * - Mostrar el formulario para establecer una nueva contraseña.
 * - Mostrar errores de validación recibidos desde el controlador.
 * - Conservar el token de recuperación.
 * - Generar el campo CSRF mediante BaseVista.
 * - Mantener la presentación separada de la lógica de negocio.
 */
class RestablecerPasswordVista extends BaseVista
{
    /**
     * Obtiene los datos predeterminados de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Restablecer contraseña',
            'error' => null,
            'token' => '',
        ];
    }

    /**
     * Renderiza la vista.
     */
    public function mostrar(array $data = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $data = array_merge(
            $this->getDefaultData(),
            $data
        );

        /*
         * El error puede provenir del controlador
         * o de un mensaje temporal en la sesión.
         */
        $error = $data['error']
            ?? $_SESSION['recuperacion_error']
            ?? null;

        unset($_SESSION['recuperacion_error']);

        /*
         * Se prioriza el token proporcionado por el controlador.
         * Si no se proporciona, se obtiene de la URL.
         */
        $token = (string) (
            $data['token']
            ?: ($_GET['token'] ?? '')
        );

        $pageTitle = (string) $data['page_title'];
        include __DIR__ . '/header.php';
        ?>
        <link
            rel="stylesheet"
            href="<?= $this->baseURL ?>css/Views/recuperacion_password.css"
        >
        <body>
            <main class="recuperacion-container">
                <section
                    class="recuperacion-card"
                    aria-labelledby="titulo-restablecer"
                >
                    <h1 id="titulo-restablecer">
                        Crear nueva contraseña
                    </h1>

                    <p class="descripcion">
                        Elegí una contraseña nueva para recuperar
                        el acceso a tu cuenta.
                    </p>

                    <?php if ($error !== null && $error !== ''): ?>
                        <div
                            class="mensaje mensaje-error"
                            role="alert"
                        >
                            <?= $this->e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form
                        action="<?= $this->e(
                            $this->getRoute('procesar_restablecer_password')
                        ) ?>"
                        method="POST"
                    >
                        <?= $this->getCsrfInput() ?>

                        <input
                            type="hidden"
                            name="token"
                            value="<?= $this->e($token) ?>"
                        >

                        <div class="campo">
                            <label for="password">
                                Nueva contraseña
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="255"
                                required
                                aria-describedby="requisitos"
                            >
                        </div>

                        <div
                            class="requisitos"
                            id="requisitos"
                        >
                            <p>La contraseña debe contener:</p>

                            <ul>
                                <li>Al menos 8 caracteres.</li>
                                <li>Al menos una letra mayúscula.</li>
                                <li>Al menos una letra minúscula.</li>
                            </ul>
                        </div>

                        <div class="campo">
                            <label for="password_confirm">
                                Confirmar contraseña
                            </label>

                            <input
                                type="password"
                                id="password_confirm"
                                name="password_confirm"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="255"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn-principal"
                        >
                            Restablecer contraseña
                        </button>
                    </form>

                    <p class="volver">
                        <a href="<?= $this->e(
                            $this->getRoute('login')
                        ) ?>">
                            Volver al inicio de sesión
                        </a>
                    </p>
                </section>
            </main>
        </body>
        </html>
        <?php
    }
}
