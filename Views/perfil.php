<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseVista.php';

class PerfilVista extends BaseVista
{
    /**
     * Renderiza el perfil del usuario autenticado.
     */
    public function mostrar(array $data = []): void
    {
        $pageTitle =
            (string)($data['page_title'] ?? 'Mi perfil');

        $usuario =
            $data['usuario'] ?? [];

        $error =
            $data['error'] ?? null;

        $success =
            $data['success'] ?? null;

        $nombre =
            (string)($usuario['nombre'] ?? '');

        $apellido =
            (string)($usuario['apellido'] ?? '');

        $dni =
            (string)($usuario['dni'] ?? '');

        $email =
            (string)($usuario['email'] ?? '');

        $telefono =
            (string)($usuario['telefono'] ?? '');

        $domicilio =
            (string)($usuario['domicilio'] ?? '');

        $fechaCreacion =
            (string)($usuario['fecha_creacion'] ?? '');

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
            $this->baseURL . 'css/Views/perfil.css'
        ) ?>"
    >

</head>

<body
    class="bg-background min-h-screen text-on-surface pb-24 tema-ciudadano"
>

<?php

$page_title = $pageTitle;

include __DIR__ . '/header.php';

?>


<main class="contenido-principal perfil">

    <section class="perfil__cabecera">

        <div class="perfil__icono">

            <span
                class="material-symbols-outlined"
                aria-hidden="true"
            >
                person
            </span>

        </div>

        <div>

            <p class="perfil__etiqueta">
                Mi cuenta
            </p>

            <h1 class="perfil__titulo">
                Mi perfil
            </h1>

            <p class="perfil__descripcion">
                Consultá y actualizá tus datos personales.
            </p>

        </div>

    </section>


    <?php if (!empty($error)): ?>

        <div
            class="perfil__mensaje perfil__mensaje--error"
            role="alert"
        >

            <span
                class="material-symbols-outlined"
                aria-hidden="true"
            >
                error
            </span>

            <span>
                <?= $this->e((string)$error) ?>
            </span>

        </div>

    <?php endif; ?>


    <?php if (!empty($success)): ?>

        <div
            class="perfil__mensaje perfil__mensaje--success"
            role="status"
        >

            <span
                class="material-symbols-outlined"
                aria-hidden="true"
            >
                check_circle
            </span>

            <span>
                <?= $this->e((string)$success) ?>
            </span>

        </div>

    <?php endif; ?>


    <section class="perfil__seccion">

        <div class="perfil__seccion-cabecera">

            <div>

                <h2 class="perfil__seccion-titulo">
                    Datos personales
                </h2>

                <p class="perfil__seccion-descripcion">
                    Información asociada a tu cuenta.
                </p>

            </div>

        </div>


        <form
            method="POST"
            action="<?= $this->e(
                $this->getRoute('perfil_actualizar')
            ) ?>"
            class="perfil__formulario"
        >

            <?php if (
                !empty($_SESSION['csrf_token'])
            ): ?>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $this->e(
                        $_SESSION['csrf_token']
                    ) ?>"
                >

            <?php endif; ?>


            <div class="perfil__grid">

                <div class="perfil__campo">

                    <label
                        for="nombre"
                        class="perfil__label"
                    >
                        Nombre
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="perfil__input"
                        value="<?= $this->e($nombre) ?>"
                        autocomplete="given-name"
                        required
                    >

                </div>


                <div class="perfil__campo">

                    <label
                        for="apellido"
                        class="perfil__label"
                    >
                        Apellido
                    </label>

                    <input
                        type="text"
                        id="apellido"
                        name="apellido"
                        class="perfil__input"
                        value="<?= $this->e($apellido) ?>"
                        autocomplete="family-name"
                        required
                    >

                </div>


                <div class="perfil__campo">

                    <label
                        for="dni"
                        class="perfil__label"
                    >
                        DNI
                    </label>

                    <input
                        type="text"
                        id="dni"
                        name="dni"
                        class="perfil__input perfil__input--readonly"
                        value="<?= $this->e($dni) ?>"
                        readonly
                    >

                    <p class="perfil__ayuda">
                        El DNI no puede modificarse.
                    </p>

                </div>


                <div class="perfil__campo">

                    <label
                        for="email"
                        class="perfil__label"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="perfil__input"
                        value="<?= $this->e($email) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="perfil__campo">

                    <label
                        for="telefono"
                        class="perfil__label"
                    >
                        Teléfono
                    </label>

                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        class="perfil__input"
                        value="<?= $this->e($telefono) ?>"
                        autocomplete="tel"
                    >

                </div>


                <div class="perfil__campo perfil__campo--completo">

                    <label
                        for="domicilio"
                        class="perfil__label"
                    >
                        Domicilio
                    </label>

                    <input
                        type="text"
                        id="domicilio"
                        name="domicilio"
                        class="perfil__input"
                        value="<?= $this->e($domicilio) ?>"
                        autocomplete="street-address"
                    >

                </div>

            </div>


            <div class="perfil__acciones">

                <a
                    href="<?= $this->e(
                        $this->getRoute('inicio')
                    ) ?>"
                    class="app-vista-button app-vista-button--secondary"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="app-vista-button app-vista-button--primary"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        save
                    </span>

                    Guardar cambios

                </button>

            </div>

        </form>

    </section>


    <section class="perfil__seccion">

        <div class="perfil__seccion-cabecera">

            <div>

                <h2 class="perfil__seccion-titulo">
                    Seguridad
                </h2>

                <p class="perfil__seccion-descripcion">
                    Tu contraseña se gestiona desde esta cuenta.
                </p>

            </div>

        </div>


        <div class="perfil__seguridad">

            <div class="perfil__seguridad-icono">

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    lock
                </span>

            </div>

            <div class="perfil__seguridad-contenido">

                <h3>
                    Contraseña
                </h3>

                <p>
                    Para cambiar tu contraseña utilizá la opción
                    de seguridad de tu cuenta.
                </p>

            </div>

        </div>

    </section>


    <?php if ($fechaCreacion !== ''): ?>

        <p class="perfil__registro">

            Cuenta creada el

            <strong>
                <?= $this->e(
                    date(
                        'd/m/Y',
                        strtotime($fechaCreacion)
                    )
                ) ?>
            </strong>

        </p>

    <?php endif; ?>

</main>


<?php

$this->getFooter();

?>

</body>

</html>

<?php
    }
}