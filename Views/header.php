<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/ToastHelper.php';

/*
 * ============================================================
 * Sesión y rol
 * ============================================================
 */

$usuarioLogueado = !empty($_SESSION['usuario_id']);

$rolesActuales = $_SESSION['usuario_roles'] ?? [];

if (!is_array($rolesActuales)) {
    $rolesActuales = [(string) $rolesActuales];
}

/*
 * El inicio depende del rol.
 *
 * Prioridad:
 *   admin      → panel administrativo
 *   inspector  → consulta pública
 *   usuario    → dashboard ciudadano
 */
if (in_array('admin', $rolesActuales, true)) {
    $inicioUrl = BASE_URL . '/admin';
} elseif (in_array('inspector', $rolesActuales, true)) {
    $inicioUrl = BASE_URL . '/consulta-publica';
} else {
    $inicioUrl = BASE_URL . '/';
}

/*
 * ============================================================
 * Assets
 * ============================================================
 *
 * Se conserva la resolución dinámica para mantener compatibilidad
 * con vistas que puedan ser servidas desde /vistas.
 */

$assetBase = rtrim(
    dirname($_SERVER['SCRIPT_NAME'] ?? ''),
    '/\\'
);

if (preg_match('#/vistas$#', $assetBase) === 1) {
    $assetBase = (string) preg_replace(
        '#/vistas$#',
        '',
        $assetBase
    );
}

if ($assetBase === '') {
    $assetBase = '';
}
?>

<header class="encabezado-principal app-shell-header encabezado-principal--alto encabezado-principal--fijo encabezado-principal--realzado encabezado-principal--primario text-on-primary topbar">

    <div class="encabezado-principal__grupo encabezado-principal__grupo--espaciado">

    <button
        type="button"
        class="encabezado-principal__boton"
        id="boton-menu-navegacion"
        aria-label="Abrir menú"
        aria-controls="menu-navegacion"
        aria-expanded="false"
    >
        <span
            class="encabezado-principal__icono material-symbols-outlined"
            aria-hidden="true"
        >
            menu
        </span>
    </button>

    <h1 class="encabezado-principal__titulo">
        <a
            href="<?= htmlspecialchars($inicioUrl, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Ir al inicio"
        >
            <?= isset($page_title)
                ? htmlspecialchars(
                    (string) $page_title,
                    ENT_QUOTES,
                    'UTF-8'
                )
                : 'App Ciudadana'; ?>
        </a>
    </h1>

</div>

    <?php if (empty($ocultarAccionesHeader)): ?>

        <?php if ($usuarioLogueado): ?>

            <a
                href="<?= BASE_URL ?>/logout"
                class="encabezado-principal__accion"
                aria-label="Cerrar sesión"
                title="Cerrar sesión"
            >
                <span class="encabezado-principal__accion-texto">
                    Cerrar sesión
                </span>

                <span
                    class="encabezado-principal__icono material-symbols-outlined"
                    aria-hidden="true"
                >
                    logout
                </span>
            </a>

        <?php else: ?>

            <a
                href="<?= BASE_URL ?>/login"
                class="encabezado-principal__accion"
                aria-label="Iniciar sesión"
                title="Iniciar sesión"
            >
                <span class="encabezado-principal__accion-texto">
                    Iniciar sesión
                </span>

                <span
                    class="encabezado-principal__icono material-symbols-outlined"
                    aria-hidden="true"
                >
                    login
                </span>
            </a>

        <?php endif; ?>

    <?php endif; ?>

</header>

<?php include __DIR__ . '/menu_navegacion.php'; ?>


<div id="toast-container"></div>


<?php

$toast = ToastHelper::obtenerToast(
    $_GET['toast'] ?? null
);

if ($toast !== null):
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    mostrarToast(
        <?= json_encode(
            $toast['mensaje'],
            JSON_UNESCAPED_UNICODE
        ) ?>,
        <?= json_encode(
            $toast['tipo'],
            JSON_UNESCAPED_UNICODE
        ) ?>
    );
});
</script>

<?php endif; ?>


<script src="<?= BASE_URL ?>/js/notificaciones.js"></script>
<script src="<?= $assetBase ?>/js/sample-data.js"></script>

<script>
(function () {
    const base = <?= json_encode(
        $assetBase,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) ?>;

    const add = (tag, attrs) => {
        const element = document.createElement(tag);

        Object.entries(attrs).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });

        document.head.appendChild(element);
    };

    const has = (selector) => {
        return Boolean(document.querySelector(selector));
    };

    if (!has('script[src$="tailwind-config.js"]')) {
        add('script', {
            src: base + '/js/tailwind-config.js'
        });
    }

    if (!has('script[src*="cdn.tailwindcss.com"]')) {
        add('script', {
            src: 'https://cdn.tailwindcss.com?plugins=forms,container-queries'
        });
    }

    if (!has('link[href*="family=Inter"]')) {
        add('link', {
            rel: 'stylesheet',
            href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
        });
    }

    if (!has('link[href*="family=Poppins"]')) {
        add('link', {
            rel: 'stylesheet',
            href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap'
        });
    }

    if (!has('link[href*="Material+Symbols+Outlined"]')) {
        add('link', {
            rel: 'stylesheet',
            href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap'
        });
    }

    if (!has('link[href$="/css/base.css"]')) {
        add('link', {
            rel: 'stylesheet',
            href: base + '/css/base.css'
        });
    }

    if (!has('link[href$="/css/components.css"]')) {
        add('link', {
            rel: 'stylesheet',
            href: base + '/css/components.css'
        });
    }

    if (!has('link[href$="/css/ui.css"]')) {
        add('link', {
            rel: 'stylesheet',
            href: base + '/css/ui.css'
        });
    }
    if (!has('link[href$="/css/Views/menu-navegacion.css"]')) {
        add('link', {
            rel: 'stylesheet',
            href: base + '/css/Views/menu-navegacion.css'
        });
    }
})();
</script>

<script src="<?= BASE_URL ?>/js/menu-navegacion.js"></script>

<link
    rel="stylesheet"
    href="<?= BASE_URL ?>/css/Views/menu-navegacion.css"
>