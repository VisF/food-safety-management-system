'use strict';

/**
 * Gestión administrativa de carnets.
 *
 * Responsabilidad:
 * - Mantener la posición del usuario al recargar
 *   la página para cargar un carnet.
 * - Llevar al formulario de carga del carnet
 *   correspondiente.
 * - Mantener un máximo de:
 *      10 elementos en desktop.
 *       5 elementos en mobile.
 */
document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
         * ==================================================
         * CONFIGURACIÓN DEL LÍMITE
         * ==================================================
         */

        const limiteDesktop = 10;

        const limiteMobile = 5;


        /*
         * Detectamos el límite correspondiente
         * al tamaño actual de pantalla.
         */
        const limiteDeseado =
            window.innerWidth <= 768
                ? limiteMobile
                : limiteDesktop;


        /*
         * ==================================================
         * URL ACTUAL
         * ==================================================
         */

        const url =
            new URL(
                window.location.href
            );


        const limiteActual =
            parseInt(
                url.searchParams.get(
                    'limite'
                ),
                10
            );


        /*
         * ==================================================
         * CAMBIO DE LÍMITE
         * ==================================================
         *
         * Solamente redirigimos si el límite
         * actual no coincide con el que corresponde
         * al dispositivo.
         */
        if (
            limiteActual !== limiteDeseado
        ) {

            /*
             * Página actual.
             */
            const paginaActual =
                Math.max(
                    1,
                    parseInt(
                        url.searchParams.get(
                            'pagina'
                        ),
                        10
                    ) || 1
                );


            /*
             * ==================================================
             * DETERMINAR PÁGINA EQUIVALENTE
             * ==================================================
             *
             * Si no hay un carnet seleccionado,
             * podemos convertir directamente:
             *
             * 10 → 5
             * página 1 → página 1
             * página 2 → página 3
             * página 3 → página 5
             *
             * 5 → 10
             * página 1 → página 1
             * página 2 → página 1
             * página 3 → página 2
             */

            let nuevaPagina =
                paginaActual;


            /*
             * ==================================================
             * CARNET SELECCIONADO
             * ==================================================
             *
             * Si existe un hash #carnet-X,
             * intentamos mantener exactamente
             * ese carnet visible después del cambio.
             */
            const hash =
                window.location.hash;


            if (
                hash
                && hash.startsWith(
                    '#carnet-'
                )
            ) {

                const idInscripcion =
                    hash.replace(
                        '#carnet-',
                        ''
                    );


                /*
                 * Buscamos la tarjeta correspondiente.
                 */
                const tarjeta =
                    document.getElementById(
                        'carnet-' +
                        idInscripcion
                    );


                if (tarjeta) {

                    /*
                     * Obtenemos todas las tarjetas
                     * de pendientes que están actualmente
                     * renderizadas.
                     */
                    const tarjetas =
                        Array.from(
                            document.querySelectorAll(
                                '.admin-carnets__pendiente'
                            )
                        );


                    const indice =
                        tarjetas.indexOf(
                            tarjeta
                        );


                    /*
                     * Si encontramos la tarjeta,
                     * calculamos su posición relativa
                     * dentro de la página actual.
                     */
                    if (indice >= 0) {

                        const posicion =
                            indice + 1;


                        /*
                         * Si estamos pasando de
                         * 10 elementos a 5:
                         *
                         * página 1 / posiciones 1-5
                         * página 2 / posiciones 6-10
                         */
                        if (
                            limiteActual === 10
                            &&
                            limiteDeseado === 5
                        ) {

                            const bloque =
                                posicion <= 5
                                    ? 1
                                    : 2;


                            nuevaPagina =
                                (
                                    (paginaActual - 1)
                                    * 2
                                )
                                +
                                bloque;
                        }


                        /*
                         * Si estamos pasando de
                         * 5 elementos a 10:
                         */
                        else if (
                            limiteActual === 5
                            &&
                            limiteDeseado === 10
                        ) {

                            nuevaPagina =
                                Math.ceil(
                                    (
                                        (
                                            paginaActual - 1
                                        )
                                        +
                                        1
                                    )
                                    / 2
                                );
                        }

                    }

                }

            }


            /*
             * ==================================================
             * ACTUALIZAR URL
             * ==================================================
             */

            url.searchParams.set(
                'limite',
                limiteDeseado
            );


            url.searchParams.set(
                'pagina',
                nuevaPagina
            );


            /*
             * Conservamos el hash del carnet.
             */
            window.location.replace(
                url.toString()
            );


            return;
        }


        /*
         * ==================================================
         * MANTENER POSICIÓN DEL CARNET
         * ==================================================
         */

        const hash =
            window.location.hash;


        /*
         * No hacemos nada si la página
         * no tiene un alumno seleccionado.
         */
        if (!hash) {
            return;
        }


        /*
         * Solamente procesamos hashes
         * relacionados con carnets.
         *
         * Ejemplo:
         * #carnet-12
         */
        if (
            !hash.startsWith(
                '#carnet-'
            )
        ) {
            return;
        }


        /*
         * Extraemos el ID de la inscripción.
         *
         * #carnet-12
         *        ↓
         *      12
         */
        const idInscripcion =
            hash.replace(
                '#carnet-',
                ''
            );


        /*
         * Buscamos primero el formulario
         * correspondiente al alumno.
         */
        const formulario =
            document.getElementById(
                'formulario-carnet-' +
                idInscripcion
            );


        /*
         * Como respaldo, buscamos la tarjeta
         * del alumno.
         *
         * Esto evita que el comportamiento se
         * rompa si por alguna razón el formulario
         * no está presente.
         */
        const elemento =
            formulario
            ||
            document.getElementById(
                'carnet-' +
                idInscripcion
            );


        /*
         * Si no existe ni el formulario
         * ni la tarjeta, no hacemos nada.
         */
        if (!elemento) {
            return;
        }


        /*
         * Esperamos a que el navegador termine
         * de renderizar la página.
         */
        window.setTimeout(
            function () {

                elemento.scrollIntoView({
                    behavior: 'auto',
                    block: 'center'
                });

            },
            50
        );

    }
);