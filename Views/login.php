<?php

declare(strict_types=1);

/**
 * Vista: Inicio de sesión.
 *
 * Propósito:
 * Permitir al usuario autenticarse en la aplicación.
 *
 * Entradas:
 * - $title: título de la página.
 * - $email: email ingresado previamente.
 * - $error: mensaje de error de autenticación.
 * - $csrf_token: token CSRF generado por el controlador.
 */

require_once __DIR__ . '/BaseVista.php';

$vista = new class extends BaseVista
{
    public function render(
        string $title,
        string $email,
        ?string $error,
        string $csrfToken
    ): void {
        $pageTitle = $title !== ''
            ? $title
            : 'Iniciar Sesión';

        ?>

<!DOCTYPE html>

<html
    class="light"
    lang="es"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $this->e($pageTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= $this->e(
            $this->baseURL . 'css/app.css'
        ) ?>"
    >

    <link
        rel="stylesheet"
        href="<?= $this->e(
            $this->baseURL . 'css/Views/login.css'
        ) ?>"
    >

</head>

<body
    class="bg-background min-h-screen text-on-surface pb-24 tema-ciudadano"
>

<?php

$page_title = 'Iniciar Sesión';

$ocultarAccionesHeader = true;

include __DIR__ . '/header.php';

?>


<main class="contenido-principal contenido-principal-login">

    <section class="login-presentacion">

        <div
            class="login-presentacion__icono"
            aria-hidden="true"
        >

            <span class="material-symbols-outlined">
                account_circle
            </span>

        </div>

        <h2 class="login-presentacion__titulo">
            Iniciar Sesión
        </h2>

        <p class="login-presentacion__descripcion">
            Sistema de Carnet de Manipulador de Alimentos
        </p>

    </section>


    <article class="app-vista-card login-formulario">

        <?php if ($error !== null && $error !== ''): ?>

            <div
                class="login-formulario__error"
                role="alert"
            >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    error
                </span>

                <span>
                    <?= $this->e($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="<?= $this->e(
                $this->baseURL . 'login'
            ) ?>"
            class="login-formulario__form"
        >
            <?= $this->getCsrfInput() ?>
            <input
                type="hidden"
                name="csrf_token"
                value="<?= $this->e($csrfToken) ?>"
            >


            <div class="login-formulario__campo">

                <label
                    for="email"
                    class="login-formulario__label"
                >
                    Correo Electrónico
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    value="<?= $this->e($email) ?>"
                    placeholder="ejemplo@correo.com"
                    class="login-formulario__input"
                >

            </div>


            <div class="login-formulario__campo">

                <label
                    for="password"
                    class="login-formulario__label"
                >
                    Contraseña
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingrese su contraseña"
                    class="login-formulario__input"
                >

            </div>


            <button
                type="submit"
                class="app-vista-button app-vista-button--primary login-formulario__boton"
            >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    login
                </span>

                Ingresar

            </button>

        </form>

    </article>


    <article class="app-vista-card login-registro">

        <p class="login-registro__texto">
            ¿No posee una cuenta?
        </p>

        <a
            href="<?= $this->e(
                $this->baseURL . 'registro'
            ) ?>"
            class="app-vista-button login-registro__boton"
        >

            <span
                class="material-symbols-outlined"
                aria-hidden="true"
            >
                person_add
            </span>

            Registrarse

        </a>

    </article>

</main>


<?php include __DIR__ . '/footer.php'; ?>


</body>

</html>

        <?php
    }
};


$vista->render(
    (string)($title ?? 'Iniciar Sesión'),
    (string)($email ?? ''),
    isset($error)
        ? (string)$error
        : null,
    (string)($csrf_token ?? '')
);