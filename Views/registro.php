<?php
declare(strict_types=1);

$title ??= 'Registro';
$error ??= null;
$csrf_token ??= '';

$ocultarAccionesHeader = true;


include __DIR__ . '/header.php';
?>

<main class="contenido-principal">

    <section style="text-align:center; margin-bottom:24px;">
        <span
            class="material-symbols-outlined"
            style="
                font-size:72px;
                color:#0a4e93;
                display:block;
                margin-bottom:12px;
            "
        >
            person_add
        </span>

        <h2
            style="
                margin:0;
                color:#0a4e93;
                font-size:1.8rem;
                font-weight:800;
            "
        >
            Crear Cuenta
        </h2>

        <p
            style="
                margin-top:8px;
                color:#5b6b80;
            "
        >
            Complete los siguientes datos para registrarse.
        </p>
    </section>

    <article
        class="app-vista-card"
        style="
            padding:24px;
            margin-bottom:18px;
        "
    >

        <?php if (!empty($error)): ?>
            <div
                style="
                    background:#fdeaea;
                    color:#b42318;
                    border:1px solid #f5c2c7;
                    border-radius:12px;
                    padding:12px;
                    margin-bottom:18px;
                "
            >
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="<?= BASE_URL ?>/registro"
            style="
                display:flex;
                flex-direction:column;
                gap:16px;
            "
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"
            >

            <div>
                <label for="nombre">Nombre</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    value="<?= htmlspecialchars($nombre) ?>"
                    required
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <div>
                <label for="apellido">Apellido</label>
                <input
                    id="apellido"
                    name="apellido"
                    type="text"
                    value="<?= htmlspecialchars($apellido) ?>"
                    required
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <div>
                <label for="dni">DNI</label>
                <input
                    id="dni"
                    name="dni"
                    type="number"
                    value="<?= htmlspecialchars($dni) ?>"
                    required
                    min="1"
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <div>
                <label for="email">Correo Electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <div>
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    minlength="8"
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <div>
                <label for="password_confirm">Confirmar Contraseña</label>
                <input
                    id="password_confirm"
                    name="password_confirm"
                    type="password"
                    required
                    minlength="8"
                    style="width:100%; padding:12px; border:1px solid #d8e1ea; border-radius:12px;"
                >
            </div>

            <button
                type="submit"
                class="app-vista-button app-vista-button--primary"
            >
                <span class="material-symbols-outlined">
                    how_to_reg
                </span>
                Registrarse
            </button>

        </form>

    </article>

    <article
        class="app-vista-card"
        style="
            padding:18px;
            text-align:center;
        "
    >
        <p style="margin-bottom:12px;">
            ¿Ya posee una cuenta?
        </p>

        <a
            href="<?= BASE_URL ?>/manipulacionDeAlimentos/login"
            class="app-vista-button"
        >
            <span class="material-symbols-outlined">
                login
            </span>
            Iniciar Sesión
        </a>
    </article>

</main>

<?php include __DIR__ . '/footer.php'; ?>