<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

/**
 * Vista: restablecer_password_error.php
 *
 * Responsabilidades:
 * - Informar que el enlace de recuperación no es válido.
 * - Informar que el enlace ha expirado o no puede utilizarse.
 * - Permitir solicitar un nuevo enlace de recuperación.
 * - Mantener la presentación separada de la lógica de negocio.
 */
class RestablecerPasswordErrorVista extends BaseVista
{
    /**
     * Obtiene los datos predeterminados de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Enlace inválido o expirado',
            'mensaje' => null,
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
         * El mensaje puede provenir del controlador
         * o de un mensaje temporal en la sesión.
         */
        $mensaje = $data['mensaje']
            ?? $_SESSION['recuperacion_error']
            ?? null;

        unset($_SESSION['recuperacion_error']);

        if ($mensaje === null || $mensaje === '') {
            $mensaje = 'El enlace para restablecer tu contraseña '
                . 'no es válido o ha expirado. '
                . 'Solicitá uno nuevo para continuar.';
        }

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
                    aria-labelledby="titulo-error"
                >
                    <h1 id="titulo-error">
                        Enlace inválido o expirado
                    </h1>

                    <div
                        class="mensaje mensaje-error"
                        role="alert"
                    >
                        <?= $this->e($mensaje) ?>
                    </div>

                    <p class="descripcion">
                        Para proteger tu cuenta, los enlaces de
                        recuperación solo pueden utilizarse cuando
                        son válidos y se encuentran vigentes.
                    </p>

                    <div class="acciones-recuperacion">
                        <a
                            href="<?= $this->e(
                                $this->getRoute('recuperar_password')
                            ) ?>"
                            class="btn-principal"
                        >
                            Solicitar un nuevo enlace
                        </a>
                    </div>

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