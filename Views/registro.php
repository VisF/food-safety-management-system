<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

class RegistroVista extends BaseVista
{
    public function mostrar(array $data = []): void
    {
        $title = $data['title'] ?? 'Registro';
        $error = $data['error'] ?? null;
        $errors = $data['errors'] ?? [];
        $csrfToken = $data['csrf_token'] ?? '';

        $nombre = $data['nombre'] ?? '';
        $apellido = $data['apellido'] ?? '';
        $dni = $data['dni'] ?? '';
        $email = $data['email'] ?? '';

        $ocultarAccionesHeader = true;

        ?>
        <link
            rel="stylesheet"
            href="<?= $this->e($this->baseURL . 'css/Views/registro.css') ?>"
        >

        <?php
        include __DIR__ . '/header.php';
        ?>

        <main class="contenido-principal registro">

            <section class="registro__encabezado">
                <span
                    class="material-symbols-outlined registro__icono"
                    aria-hidden="true"
                >
                    person_add
                </span>

                <h1 class="registro__titulo">
                    Crear Cuenta
                </h1>

                <p class="registro__descripcion">
                    Complete los siguientes datos para registrarse.
                </p>
            </section>

            <article class="app-vista-card registro__card">

                <?php if (!empty($error)): ?>
                    <div
                        class="registro__error"
                        role="alert"
                    >
                        <?= $this->e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div
                        class="registro__errores"
                        role="alert"
                    >
                        <strong>
                            Revise los siguientes datos:
                        </strong>

                        <ul>
                            <?php foreach ($errors as $mensaje): ?>
                                <li>
                                    <?= $this->e((string) $mensaje) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form
                    method="POST"
                    action="<?= $this->e($this->getRoute('registro')) ?>"
                    class="registro__formulario"
                >
                    <?= $this->getCsrfInput() ?>
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= $this->e($csrfToken) ?>"
                    >

                    <div class="registro__campo">
                        <label for="nombre">
                            Nombre
                        </label>

                        <input
                            id="nombre"
                            name="nombre"
                            type="text"
                            value="<?= $this->e($nombre) ?>"
                            autocomplete="given-name"
                            required
                        >
                    </div>

                    <div class="registro__campo">
                        <label for="apellido">
                            Apellido
                        </label>

                        <input
                            id="apellido"
                            name="apellido"
                            type="text"
                            value="<?= $this->e($apellido) ?>"
                            autocomplete="family-name"
                            required
                        >
                    </div>

                    <div class="registro__campo">
                        <label for="dni">
                            DNI
                        </label>

                        <input
                            id="dni"
                            name="dni"
                            type="number"
                            value="<?= $this->e($dni) ?>"
                            min="1"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="registro__campo">
                        <label for="email">
                            Correo Electrónico
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?= $this->e($email) ?>"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="registro__campo">
                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="registro__campo">
                        <label for="password_confirm">
                            Confirmar Contraseña
                        </label>

                        <input
                            id="password_confirm"
                            name="password_confirm"
                            type="password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="app-vista-button app-vista-button--primary registro__boton"
                    >
                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            how_to_reg
                        </span>

                        Registrarse
                    </button>

                </form>

            </article>

            <article class="app-vista-card registro__login">

                <p class="registro__login-texto">
                    ¿Ya posee una cuenta?
                </p>

                <a
                    href="<?= $this->e($this->getRoute('login')) ?>"
                    class="app-vista-button registro__login-boton"
                >
                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        login
                    </span>

                    Iniciar Sesión
                </a>

            </article>

        </main>

        <?php
        $this->getFooter();
    }
}