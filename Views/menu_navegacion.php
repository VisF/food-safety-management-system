<?php

declare(strict_types=1);

/**
 * Menú principal de navegación.
 *
 * El menú se incluye desde header.php, por lo que debe
 * funcionar independientemente de la vista actual.
 */

$usuarioLogueado =
    !empty($_SESSION['usuario_id']);


$rolesSesion =
    $_SESSION['usuario_roles'] ?? [];


if (!is_array($rolesSesion)) {
    $rolesSesion = [$rolesSesion];
}


$rolesSesion = array_map(
    static fn($rol): string => strtolower(trim((string)$rol)),
    $rolesSesion
);


/**
 * Determina el rol principal del usuario.
 *
 * Prioridad:
 * admin > inspector > usuario
 */
$rolPrincipal = null;

if (in_array('admin', $rolesSesion, true)) {

    $rolPrincipal = 'admin';

} elseif (
    in_array('inspector', $rolesSesion, true)
) {

    $rolPrincipal = 'inspector';

} elseif (
    in_array('usuario', $rolesSesion, true)
) {

    $rolPrincipal = 'usuario';
}


/**
 * URL base segura para las rutas del menú.
 */
$menuBaseUrl =
    rtrim(BASE_URL, '/') . '/';


/**
 * Construye una URL del sistema.
 *
 * Se utiliza para que los enlaces funcionen
 * independientemente de la vista desde la que
 * se abra el menú.
 */
$menuUrl = static function (
    string $ruta = '',
    string $fragmento = ''
) use ($menuBaseUrl): string {

    $ruta = ltrim($ruta, '/');

    $url =
        $menuBaseUrl . $ruta;

    if ($fragmento !== '') {

        $url .= '#' .
            ltrim($fragmento, '#');
    }

    return $url;
};


/**
 * Detecta la ruta actual para marcar el enlace activo.
 */
$uriActual =
    parse_url(
        $_SERVER['REQUEST_URI'] ?? '/',
        PHP_URL_PATH
    );

$uriActual =
    '/' . ltrim(
        (string)$uriActual,
        '/'
    );


$menuRutaActiva = static function (
    string $ruta
) use ($uriActual): bool {

    $ruta = '/' . ltrim($ruta, '/');

    if ($ruta !== '/' && str_ends_with($ruta, '/')) {
        $ruta = rtrim($ruta, '/');
    }

    if ($ruta === '/') {
        return $uriActual === '/';
    }

    return $uriActual === $ruta
        || str_starts_with(
            $uriActual,
            $ruta . '/'
        );
};


?>

<div
    id="menu-navegacion"
    class="menu-navegacion"
    aria-hidden="true"
>

    <div
        class="menu-navegacion__overlay"
        data-menu-cerrar
        aria-hidden="true"
    ></div>


    <aside
        class="menu-navegacion__panel"
        aria-label="Menú de navegación"
    >

        <header class="menu-navegacion__header">

            <div class="menu-navegacion__titulo">

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    menu
                </span>

                <span>
                    Menú
                </span>

            </div>


            <button
                type="button"
                class="menu-navegacion__cerrar"
                data-menu-cerrar
                aria-label="Cerrar menú"
            >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >
                    close
                </span>

            </button>

        </header>


        <nav class="menu-navegacion__contenido">


            <?php if ($usuarioLogueado): ?>


                <?php if ($rolPrincipal === 'usuario'): ?>

                    <!-- =================================================
                         CIUDADANO
                         ================================================= -->
                    <div class="menu-navegacion__grupo">

                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl(''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                home
                            </span>

                            <span>
                                Inicio
                            </span>

                        </a>

                    </div>
                    <div class="menu-navegacion__grupo">

                        <p class="menu-navegacion__grupo-titulo">
                            Mi cuenta
                        </p>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('perfil'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/perfil')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                person
                            </span>

                            <span>
                                Mi perfil
                            </span>

                        </a>

                    </div>


                    <div class="menu-navegacion__grupo">

                        <p class="menu-navegacion__grupo-titulo">
                            Mi trámite
                        </p>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('documentacion'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/documentacion')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                description
                            </span>

                            <span>
                                Documentación
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('', 'cursos-disponibles'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                school
                            </span>

                            <span>
                                Inscripciones
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('', 'proximos-examenes'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                assignment
                            </span>

                            <span>
                                Exámenes
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('', 'carnet-vigente'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                badge
                            </span>

                            <span>
                                Mi carnet
                            </span>

                        </a>

                    </div>


                <?php elseif ($rolPrincipal === 'inspector'): ?>

                    <!-- =================================================
                         INSPECTOR
                         ================================================= -->

                    <div class="menu-navegacion__grupo">

                        <p class="menu-navegacion__grupo-titulo">
                            Inspección
                        </p>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('consulta-publica'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/consulta-publica')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                fact_check
                            </span>

                            <span>
                                Consulta pública
                            </span>

                        </a>

                    </div>


                <?php elseif ($rolPrincipal === 'admin'): ?>

                    <!-- =================================================
                         ADMINISTRADOR
                         ================================================= -->

                    <div class="menu-navegacion__grupo">

                        <p class="menu-navegacion__grupo-titulo">
                            Administración
                        </p>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('admin'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/admin')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                dashboard
                            </span>

                            <span>
                                Inicio
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('admin/documentos'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/admin/documentos')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                description
                            </span>

                            <span>
                                Documentación
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('admin/examenes'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/admin/examenes')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                assignment
                            </span>

                            <span>
                                Exámenes
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('admin/carnets'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/admin/carnets')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                badge
                            </span>

                            <span>
                                Carnets
                            </span>

                        </a>


                        <a
                            href="<?= htmlspecialchars(
                                $menuUrl('admin/actividad'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="menu-navegacion__enlace<?= $menuRutaActiva('/admin/actividad')
                                ? ' menu-navegacion__enlace--activo'
                                : '' ?>"
                        >

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                history
                            </span>

                            <span>
                                Actividad
                            </span>

                        </a>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                     SESIÓN
                     ===================================================== -->

                <div class="menu-navegacion__grupo menu-navegacion__grupo--sesion">

                    <a
                        href="<?= htmlspecialchars(
                            $menuUrl('logout'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="menu-navegacion__enlace menu-navegacion__enlace--logout"
                    >

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            logout
                        </span>

                        <span>
                            Cerrar sesión
                        </span>

                    </a>

                </div>


            <?php else: ?>

                <!-- =====================================================
                     USUARIO NO AUTENTICADO
                     ===================================================== -->

                <div class="menu-navegacion__grupo">

                    <p class="menu-navegacion__grupo-titulo">
                        Cuenta
                    </p>


                    <a
                        href="<?= htmlspecialchars(
                            $menuUrl('login'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="menu-navegacion__enlace"
                    >

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            login
                        </span>

                        <span>
                            Iniciar sesión
                        </span>

                    </a>


                    <a
                        href="<?= htmlspecialchars(
                            $menuUrl('registro'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="menu-navegacion__enlace"
                    >

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            person_add
                        </span>

                        <span>
                            Registrarme
                        </span>

                    </a>

                </div>

            <?php endif; ?>


        </nav>

    </aside>

</div>