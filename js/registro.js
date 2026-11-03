
'use strict';

(() => {
    /**
     * Inicializa las funcionalidades del formulario de registro.
     */
    function inicializarRegistro() {
        const password = document.getElementById('password');
        const confirmacion = document.getElementById('password_confirm');

        if (!password || !confirmacion) {
            return;
        }

        configurarVisibilidadPassword();
        configurarValidacionPassword(password, confirmacion);
    }

    /**
     * Actualiza el estado visual de un requisito.
     *
     * @param {HTMLElement|null} elemento
     * @param {boolean} valido
     */
    function actualizarIndicador(elemento, valido) {
        if (!elemento) {
            return;
        }

        elemento.dataset.valid = String(valido);

        const icono = elemento.querySelector('.material-symbols-outlined');

        if (icono) {
            icono.textContent = valido ? 'check_circle' : 'cancel';
        }
    }

    /**
     * Comprueba los requisitos y la coincidencia de las contraseñas.
     *
     * @param {HTMLInputElement} password
     * @param {HTMLInputElement} confirmacion
     */
    function configurarValidacionPassword(password, confirmacion) {
        const longitud = document.getElementById('password-longitud');
        const mayuscula = document.getElementById('password-mayuscula');
        const minuscula = document.getElementById('password-minuscula');
        const mensajeConfirmacion = document.getElementById(
            'password-confirmacion'
        );

        function validarPassword() {
            const valor = password.value;

            actualizarIndicador(longitud, valor.length >= 8);
            actualizarIndicador(mayuscula, /[A-Z]/.test(valor));
            actualizarIndicador(minuscula, /[a-z]/.test(valor));

            validarConfirmacion();
        }

        function validarConfirmacion() {
            if (!mensajeConfirmacion) {
                confirmacion.setCustomValidity(
                    password.value === confirmacion.value
                        ? ''
                        : 'Las contraseñas no coinciden.'
                );

                return;
            }

            // No mostrar mensajes mientras la confirmación esté vacía.
            if (confirmacion.value === '') {
                mensajeConfirmacion.textContent = '';
                delete mensajeConfirmacion.dataset.valid;
                confirmacion.setCustomValidity('');
                return;
            }

            const coinciden = password.value === confirmacion.value;

            mensajeConfirmacion.textContent = coinciden
                ? 'Las contraseñas coinciden.'
                : 'Las contraseñas no coinciden.';

            mensajeConfirmacion.dataset.valid = String(coinciden);

            // Bloquea el envío si las contraseñas son diferentes.
            confirmacion.setCustomValidity(
                coinciden ? '' : 'Las contraseñas no coinciden.'
            );
        }

        password.addEventListener('input', validarPassword);
        confirmacion.addEventListener('input', validarConfirmacion);

        // Estado inicial y actualización al editar la contraseña original.
        validarPassword();
    }

    /**
     * Configura los botones para mostrar u ocultar las contraseñas.
     */
    function configurarVisibilidadPassword() {
        const botones = document.querySelectorAll('[data-password-toggle]');

        botones.forEach((boton) => {
            boton.addEventListener('click', () => {
                const idInput = boton.dataset.passwordToggle;
                const input = document.getElementById(idInput);

                if (!input) {
                    return;
                }

                const mostrar = input.type === 'password';
                const esConfirmacion = idInput === 'password_confirm';

                input.type = mostrar ? 'text' : 'password';

                boton.setAttribute('aria-pressed', String(mostrar));
                boton.setAttribute(
                    'aria-label',
                    `${mostrar ? 'Ocultar' : 'Mostrar'} ${
                        esConfirmacion
                            ? 'confirmación de contraseña'
                            : 'contraseña'
                    }`
                );

                const icono = boton.querySelector(
                    '.material-symbols-outlined'
                );

                if (icono) {
                    icono.textContent = mostrar
                        ? 'visibility_off'
                        : 'visibility';
                }
            });
        });
    }

    // El archivo se carga con defer desde la vista de registro.
    inicializarRegistro();
})();
