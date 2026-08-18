'use strict';

/**
 * Gestión administrativa de carnets.
 *
 * Responsabilidad:
 * - Mantener la posición del usuario al recargar
 *   la página para cargar un carnet.
 */
document.addEventListener(
    'DOMContentLoaded',
    function () {

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
            !hash.startsWith('#carnet-')
        ) {
            return;
        }

        const elemento =
            document.querySelector(hash);

        /*
         * Si el alumno no existe en la página,
         * no hacemos nada.
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