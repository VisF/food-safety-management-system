<?php

/**
 * Menú principal de navegación.
 *
 * Responsabilidad:
 * - Mostrar las opciones de navegación correspondientes
 *   al rol del usuario autenticado.
 * - Mantener una única estructura de navegación
 *   para usuarios, inspectores y administradores.
 *
 * Roles contemplados:
 * - usuario
 * - inspector
 * - admin
 *
 * El cierre de sesión se muestra:
 * - Dentro del menú en dispositivos móviles.
 * - Como acción independiente en desktop.
 */


/*
 * ==========================================================
 * DATOS DE SESIÓN
 * ==========================================================
 */

$usuarioLogueado =
    !empty(
        $_SESSION['usuario_id']
    );


$rolUsuario =
    (string)(
        $_SESSION['usuario_roles']
        ?? 'usuario'
    );


/*
 * ==========================================================
 * DATOS DE NAVEGACIÓN
 * ==========================================================
 */

$menuActual =
    basename(
        parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        )
    );


/*
 * ==========================================================
 * FUNCIÓN AUXILIAR
 * ==========================================================
 */

/**
 * Determina si una ruta corresponde
 * a la página actualmente visitada.
 *
 * @param string $ruta
 * @return bool
 */
function menuRutaActiva(
    string $ruta
): bool {

    $rutaActual =
        parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        );


    /*
     * Eliminamos la BASE_URL de la
     * comparación cuando corresponde.
     */
    $baseUrl =
        defined('BASE_URL')
            ? rtrim(
                BASE_URL,
                '/'
            )
            : '';


    if (
        $baseUrl !== ''
        &&
        str_starts_with(
            $rutaActual,
            $baseUrl
        )
    ) {

        $rutaActual =
            substr(
                $rutaActual,
                strlen($baseUrl)
            );
    }


    /*
     * Normalizamos las barras.
     */
    $rutaActual =
        '/' .
        trim(
            $rutaActual,
            '/'
        );


    $ruta =
        '/' .
        trim(
            $ruta,
            '/'
        );


    /*
     * La página de inicio es un caso especial.
     */
    if ($ruta === '/') {

        return $rutaActual === '/';
    }


    /*
     * Una ruta se considera activa si:
     *
     * - coincide exactamente;
     * - o la URL actual es una subruta.
     *
     * Ejemplo:
     *
     * /admin/examenes
     * /admin/examenes/nuevo
     * /admin/examenes/12
     *
     * marcarán "Exámenes" como activo.
     */
    return
        $rutaActual === $ruta
        ||
        str_starts_with(
            $rutaActual,
            $ruta . '/'
        );
}


/**
 * Escapa una URL para utilizarla
 * correctamente dentro de HTML.
 *
 * @param string $ruta
 * @return string
 */
function menuUrl(string $ruta): string {

    return htmlspecialchars(
        BASE_URL . $ruta,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>


<nav
    id="menu-navegacion"
    class="menu-navegacion"
    aria-label="Navegación principal"
>

    <div
        class="menu-navegacion__contenido"
    >

        <!--
        ==================================================
        NAVEGACIÓN CIUDADANO
        ==================================================
        -->

        <?php if (
            $usuarioLogueado
            && $rolUsuario === 'usuario'
        ): ?>

            <div
                class="menu-navegacion__grupo"
            >

                <span
                    class="menu-navegacion__titulo"
                >
                    Mi cuenta
                </span>


                <a
                    href="<?= menuUrl('/perfil'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/perfil')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        person
                    </span>

                    <span>
                        Mis Datos
                    </span>

                </a>


                <a
                    href="<?= menuUrl('/subida_documentacion'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/subida_documentacion')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
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


                <!--
                 * Ruta planificada.
                 * Todavía no implementada.
                 -->
                <a
                    href="<?= menuUrl('/examen'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        assignment
                    </span>

                    <span>
                        Examen
                    </span>

                </a>


                <!--
                 * Ruta planificada.
                 * Todavía no implementada.
                 -->
                <a
                    href="<?= menuUrl('/carnet'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        badge
                    </span>

                    <span>
                        Carnet
                    </span>

                </a>

            </div>

        <?php endif; ?>


        <!--
        ==================================================
        NAVEGACIÓN INSPECTOR
        ==================================================
        -->

        <?php if (
            $usuarioLogueado
            && $rolUsuario === 'inspector'
        ): ?>

            <div
                class="menu-navegacion__grupo"
            >

                <span
                    class="menu-navegacion__titulo"
                >
                    Gestión
                </span>


                <!--
                 * Ruta planificada.
                 * Permitirá buscar ciudadanos
                 * y consultar su documentación
                 * y carnet.
                 -->
                <a
                    href="<?= menuUrl('/inspector/ciudadanos'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        person_search
                    </span>

                    <span>
                        Búsqueda de Ciudadanos
                    </span>

                </a>

            </div>

        <?php endif; ?>


        <!--
        ==================================================
        NAVEGACIÓN ADMINISTRADOR
        ==================================================
        -->

        <?php if (
            $usuarioLogueado
            && $rolUsuario === 'admin'
        ): ?>

            <div
                class="menu-navegacion__grupo"
            >

                <span
                    class="menu-navegacion__titulo"
                >
                    Administración
                </span>


                <a
                    href="<?= menuUrl('/'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
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


                <!--
                 * Ruta planificada.
                 -->
                <a
                    href="<?= menuUrl('/admin/usuarios'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        group
                    </span>

                    <span>
                        Ciudadanos
                    </span>

                </a>


                <a
                    href="<?= menuUrl('/admin/documentos'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/admin/documentos')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
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
                    href="<?= menuUrl('/admin/examenes'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/admin/examenes')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
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
                    href="<?= menuUrl('/admin/carnets'); ?>"
                    class="
                        menu-navegacion__enlace
                        <?= menuRutaActiva('/admin/carnets')
                            ? 'menu-navegacion__enlace--activo'
                            : '' ?>
                    "
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


                <!--
                 * Ruta planificada.
                 -->
                <a
                    href="<?= menuUrl('/admin/actividad'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        history
                    </span>

                    <span>
                        Actividad Reciente
                    </span>

                </a>


                <!--
                 * Ruta planificada.
                 -->
                <a
                    href="<?= menuUrl('/admin/reportes'); ?>"
                    class="menu-navegacion__enlace"
                >

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        analytics
                    </span>

                    <span>
                        Reportes
                    </span>

                </a>

            </div>

        <?php endif; ?>


        <!--
        ==================================================
        CERRAR SESIÓN — MOBILE
        ==================================================
        -->

        <?php if ($usuarioLogueado): ?>

            <div
                class="menu-navegacion__separador"
            ></div>


            <a
                href="<?= menuUrl('/logout'); ?>"
                class="
                    menu-navegacion__enlace
                    menu-navegacion__enlace--salir
                    menu-navegacion__solo-mobile
                "
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

        <?php endif; ?>

    </div>

</nav>