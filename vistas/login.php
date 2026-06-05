<?php
declare(strict_types=1);

$title ??= 'Iniciar Sesión';
$email ??= '';
$error ??= null;
$csrf_token ??= '';

include __DIR__ . '/header.php';
?>

<main class="contenido-principal">

    <section style="text-align:center; margin-bottom:24px;">
        <span
            class="material-symbols-outlined"
            style="
                font-size:72px;
                color:#0a4e93;
                margin-bottom:12px;
                display:block;
            "
        >
            account_circle
        </span>

        <h2
            style="
                margin:0;
                color:#0a4e93;
                font-size:1.8rem;
                font-weight:800;
            "
        >
            Iniciar Sesión
        </h2>

        <p
            style="
                margin-top:8px;
                color:#5b6b80;
            "
        >
            Sistema de Carnet de Manipulador de Alimentos
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
            action="<?= BASE_URL ?>/Router.php?r=login"
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
                <label
                    for="email"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Correo Electrónico
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="ejemplo@correo.com"
                    style="
                        width:100%;
                        border:1px solid #d8e1ea;
                        border-radius:12px;
                        padding:12px;
                    "
                >
            </div>

            <div>
                <label
                    for="password"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Contraseña
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    placeholder="Ingrese su contraseña"
                    style="
                        width:100%;
                        border:1px solid #d8e1ea;
                        border-radius:12px;
                        padding:12px;
                    "
                >
            </div>

            <button
                type="submit"
                class="app-vista-button app-vista-button--primary"
                style="width:100%;"
            >
                <span class="material-symbols-outlined">
                    login
                </span>
                Ingresar
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
        <p
            style="
                margin:0 0 12px;
                color:#5b6b80;
            "
        >
            ¿No posee una cuenta?
        </p>

        <a
            href="<?= BASE_URL ?>/ Router.php?r=registro"
            class="app-vista-button"
        >
            <span class="material-symbols-outlined">
                person_add
            </span>
            Registrarse
        </a>
    </article>

</main>

<?php include __DIR__ . '/footer.php'; ?>