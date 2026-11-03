<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

/**
 * Vista: recuperar_password.php
 *
 * Responsabilidades:
 * - Mostrar el formulario de recuperación de contraseña.
 * - Mostrar mensajes informativos y errores.
 * - Generar el campo CSRF mediante BaseVista.
 * - Mantener la presentación separada de la lógica de negocio.
 */
class RecuperarPasswordVista extends BaseVista
{
    /**
     * Obtiene los datos predeterminados de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Recuperar contraseña',
            'mensaje' => null,
            'error' => null,
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
         * Los mensajes pueden llegar desde el controlador
         * o mediante la sesión.
         */
        $mensaje = $data['mensaje']
            ?? $_SESSION['recuperacion_mensaje']
            ?? null;

        $error = $data['error']
            ?? $_SESSION['recuperacion_error']
            ?? null;

        unset(
            $_SESSION['recuperacion_mensaje'],
            $_SESSION['recuperacion_error']
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
                    aria-labelledby="titulo-recuperacion"
                >
                    <h1 id="titulo-recuperacion">
                        Recuperar contraseña
                    </h1>

                    <p class="descripcion">
                        Ingresá el correo electrónico asociado a tu
                        cuenta. Si está registrado, recibirás las
                        instrucciones para recuperar el acceso.
                    </p>

                    <?php if ($mensaje !== null && $mensaje !== ''): ?>
                        <div
                            class="mensaje mensaje-info"
                            role="status"
                        >
                            <?= $this->e($mensaje) ?>
                        </div>
                    <?php endif; ?>

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
                            $this->getRoute('procesar_recuperar_password')
                        ) ?>"
                        method="POST"
                    >
                        <?= $this->getCsrfInput() ?>

                        <div class="campo">
                            <label for="email">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="email"
                                maxlength="254"
                                required
                                placeholder="tu@correo.com"
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn-principal"
                        >
                            Solicitar recuperación
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
