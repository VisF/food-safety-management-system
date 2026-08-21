'use strict';

/**
 * JavaScript administrativo de gestión de documentación.
 *
 * Responsabilidades:
 * - Abrir formularios de rechazo.
 * - Cerrar formularios de rechazo.
 * - Dar foco al campo de observaciones.
 * - Gestionar los botones de rechazo de documentos.
 * - Mantener un máximo de:
 *      10 ciudadanos en desktop.
 *       5 ciudadanos en mobile.
 *
 * La vista admin_documentos.php no contiene
 * JavaScript inline.
 */


/*
 * ==========================================================
 * INICIALIZACIÓN
 * ==========================================================
 */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        inicializarRechazos();

        ajustarLimiteDocumentacion();

    }
);


/*
 * ==========================================================
 * PAGINACIÓN RESPONSIVE
 * ==========================================================
 */

/**
 * Ajusta el límite de ciudadanos de acuerdo
 * al tamaño de pantalla.
 *
 * Desktop:
 * - 10 ciudadanos por página.
 *
 * Mobile:
 * - 5 ciudadanos por página.
 *
 * La búsqueda por DNI no se modifica,
 * ya que no utiliza paginación.
 */
function ajustarLimiteDocumentacion() {

    const url =
        new URL(
            window.location.href
        );


    /*
     * Si estamos realizando una búsqueda
     * por DNI, no modificamos la URL.
     */
    const dni =
        url.searchParams.get(
            'dni'
        );


    if (
        dni !== null
        && dni.trim() !== ''
    ) {

        return;
    }


    /*
     * ==============================================
     * LÍMITE SEGÚN DISPOSITIVO
     * ==============================================
     */

    const limiteDesktop = 10;

    const limiteMobile = 5;


    const limiteDeseado =
        window.innerWidth <= 768
            ? limiteMobile
            : limiteDesktop;


    /*
     * ==============================================
     * LÍMITE ACTUAL
     * ==============================================
     */

    const limiteActual =
        parseInt(
            url.searchParams.get(
                'limite'
            ),
            10
        );


    /*
     * Si la URL ya tiene el límite correcto,
     * no hacemos nada.
     */
    if (
        limiteActual === limiteDeseado
    ) {

        return;
    }


    /*
     * ==============================================
     * PÁGINA ACTUAL
     * ==============================================
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
     * ==============================================
     * PÁGINA EQUIVALENTE
     * ==============================================
     *
     * Intentamos conservar el primer elemento
     * que estaba viendo el usuario.
     *
     * Ejemplo:
     *
     * Desktop:
     * página 2, límite 10
     * elementos 11-20
     *
     * Mobile:
     * página 3, límite 5
     * elementos 11-15
     *
     * Por lo tanto:
     *
     * 10 → 5
     * página 2 → página 3
     *
     * Y al revés:
     *
     * 5 → 10
     * página 3 → página 2
     */


    let nuevaPagina =
        paginaActual;


    if (
        limiteActual === 10
        && limiteDeseado === 5
    ) {

        nuevaPagina =
            (
                (
                    paginaActual - 1
                )
                * 2
            )
            + 1;

    } else if (
        limiteActual === 5
        && limiteDeseado === 10
    ) {

        nuevaPagina =
            Math.floor(
                (
                    paginaActual - 1
                ) / 2
            ) + 1;

    }


    /*
     * ==============================================
     * ACTUALIZAR URL
     * ==============================================
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
     * Recargamos utilizando la nueva URL.
     *
     * window.location.replace()
     * mantiene el resto de parámetros y
     * evita agregar una entrada innecesaria
     * al historial.
     */
    window.location.replace(
        url.toString()
    );

}


/*
 * ==========================================================
 * RECHAZOS
 * ==========================================================
 */

/**
 * Inicializa los botones relacionados
 * con el rechazo de documentos.
 */
function inicializarRechazos() {

    const botonesRechazo =
        document.querySelectorAll(
            '.js-rechazar-documento'
        );

    const botonesCancelar =
        document.querySelectorAll(
            '.js-cancelar-rechazo'
        );


    botonesRechazo.forEach(
        function (boton) {

            boton.addEventListener(
                'click',
                function () {

                    const documentoId =
                        this.dataset.documentoId;

                    const contexto =
                        this.dataset.contexto
                        || '';


                    mostrarFormularioRechazo(
                        documentoId,
                        contexto
                    );

                }
            );

        }
    );


    botonesCancelar.forEach(
        function (boton) {

            boton.addEventListener(
                'click',
                function () {

                    const documentoId =
                        this.dataset.documentoId;

                    const contexto =
                        this.dataset.contexto
                        || '';


                    ocultarFormularioRechazo(
                        documentoId,
                        contexto
                    );

                }
            );

        }
    );

}


/**
 * Muestra el formulario de rechazo
 * correspondiente a un documento.
 *
 * @param {string|number} documentoId
 * @param {string} contexto
 */
function mostrarFormularioRechazo(
    documentoId,
    contexto = ''
) {

    const sufijo =
        contexto === 'listado'
            ? '-listado'
            : '';


    const formulario =
        document.getElementById(
            'rechazo'
            + sufijo
            + '-'
            + documentoId
        );


    if (!formulario) {
        return;
    }


    formulario.hidden =
        false;


    const textarea =
        formulario.querySelector(
            'textarea'
        );


    if (textarea) {

        textarea.focus();

    }

}


/**
 * Oculta el formulario de rechazo
 * correspondiente a un documento.
 *
 * @param {string|number} documentoId
 * @param {string} contexto
 */
function ocultarFormularioRechazo(
    documentoId,
    contexto = ''
) {

    const sufijo =
        contexto === 'listado'
            ? '-listado'
            : '';


    const formulario =
        document.getElementById(
            'rechazo'
            + sufijo
            + '-'
            + documentoId
        );


    if (!formulario) {
        return;
    }


    formulario.hidden =
        true;


    const textarea =
        formulario.querySelector(
            'textarea'
        );


    if (textarea) {

        textarea.value =
            '';

    }

}