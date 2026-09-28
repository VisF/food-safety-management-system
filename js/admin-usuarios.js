document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const usuarios = Array.isArray(window.adminUsuarios)
        ? window.adminUsuarios
        : [];

    const baseURL = obtenerBaseURL();

    const modalNuevo = document.getElementById('modal-nuevo-usuario');
    const modalEditar = document.getElementById('modal-editar-usuario');
    const modalDesactivar = document.getElementById('modal-desactivar-usuario');
    const modalActivar = document.getElementById('modal-activar-usuario');

    const btnNuevo = document.getElementById('btn-nuevo-usuario');

    const buscador = document.getElementById('buscar-usuarios');
    const limpiarBusqueda = document.getElementById('limpiar-busqueda');
    const filas = document.querySelectorAll(
        '#usuarios-tbody .admin-usuarios__fila'
    );

    const sinResultados = document.getElementById('sin-resultados');

    const formEditar = document.getElementById('form-editar-usuario');

    const editarPassword = document.getElementById('editar-password');
    const editarPasswordConfirmacion = document.getElementById(
        'editar-password-confirmacion'
    );


    const formDesactivar = document.getElementById(
        'form-desactivar-usuario'
    );
    const formActivar = document.getElementById(
        'form-activar-usuario'
    );


    /*
     * =========================================================
     * MODAL NUEVO USUARIO
     * =========================================================
     */

    if (btnNuevo && modalNuevo) {
        btnNuevo.addEventListener('click', () => {
            abrirModal(modalNuevo);
        });
    }


    /*
     * =========================================================
     * BOTONES DE ACCIONES
     * =========================================================
     */

    document.querySelectorAll('[data-accion="editar"]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const id = Number(boton.dataset.id);

            abrirEditarUsuario(id);
        });
    });


    document.querySelectorAll('[data-accion="desactivar"]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const id = Number(boton.dataset.id);

            abrirDesactivarUsuario(id);
        });
    });

    document.querySelectorAll('[data-accion="activar"]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const id = Number(boton.dataset.id);

        abrirActivarUsuario(id);
        });
    });

    /*
     * =========================================================
     * CERRAR MODALES
     * =========================================================
     */

    document
        .querySelectorAll('[data-cerrar-modal]')
        .forEach((boton) => {
            boton.addEventListener('click', () => {
                const modalId = boton.dataset.cerrarModal;
                const modal = document.getElementById(modalId);

                if (modal) {
                    cerrarModal(modal);
                }
            });
        });


    /*
     * Cerrar haciendo click fuera del contenido.
     */

    [modalNuevo, modalEditar, modalDesactivar, modalActivar]
        .filter(Boolean)
        .forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    cerrarModal(modal);
                }
            });

            modal.addEventListener('cancel', (event) => {
                event.preventDefault();
                cerrarModal(modal);
            });
        });


    /*
     * =========================================================
     * BUSCADOR
     * =========================================================
     */

    if (buscador) {
        buscador.addEventListener('input', () => {
            filtrarUsuarios(buscador.value);
        });
    }

    if (limpiarBusqueda) {
        limpiarBusqueda.addEventListener('click', () => {
            if (!buscador) {
                return;
            }

            buscador.value = '';
            filtrarUsuarios('');
            buscador.focus();
        });
    }


    /*
     * =========================================================
     * FORMULARIO EDITAR
     * =========================================================
     */

    if (formEditar) {
        formEditar.addEventListener('submit', (event) => {

            const id = document.getElementById('editar-id');

            if (!id || !id.value) {
                event.preventDefault();

                alert('No se pudo identificar el usuario.');

                return;
            }


            const password = editarPassword
                ? editarPassword.value
                : '';

            const passwordConfirmacion = editarPasswordConfirmacion
                ? editarPasswordConfirmacion.value
                : '';


            /*
            * Si se ingresó una contraseña,
            * debe coincidir con la confirmación.
            */

            if (
                password !== ''
                || passwordConfirmacion !== ''
            ) {

                if (password.length < 8) {
                    event.preventDefault();

                    alert(
                        'La contraseña debe tener al menos 8 caracteres.'
                    );

                    if (editarPassword) {
                        editarPassword.focus();
                    }

                    return;
                }

                if (password !== passwordConfirmacion) {
                    event.preventDefault();

                    alert(
                        'Las contraseñas no coinciden.'
                    );

                    if (editarPasswordConfirmacion) {
                        editarPasswordConfirmacion.focus();
                    }

                    return;
                }
            }


            formEditar.action =
                baseURL + 'admin/usuarios/' + Number(id.value);
        });
    }


    /*
     * =========================================================
     * FORMULARIO DESACTIVAR
     * =========================================================
     */

    if (formDesactivar) {
        formDesactivar.addEventListener('submit', (event) => {
            const id = formDesactivar.dataset.usuarioId;

            if (!id) {
                event.preventDefault();

                alert('No se pudo identificar el usuario.');

                return;
            }

            formDesactivar.action =
                baseURL
                + 'admin/usuarios/'
                + Number(id)
                + '/desactivar';
        });
    }

    /**
     * Activa un usuario.
     *
     * @param {number} id - El ID del usuario a activar.
     */
    function abrirActivarUsuario(id) {
        if (!modalActivar) {
            return;
        }


        const usuario = usuarios.find(
            (item) => Number(item.id) === id
        );


        if (!usuario) {
            alert('No se encontró el usuario.');

            return;
        }


        const nombreCompleto = [
            usuario.nombre,
            usuario.apellido
        ]
            .filter(Boolean)
            .join(' ');


        establecerTexto(
            'activar-nombre-visible',
            nombreCompleto || 'Usuario'
        );


        if (formActivar) {

            formActivar.dataset.usuarioId =
                Number(usuario.id);

            formActivar.action =
                baseURL
                + 'admin/usuarios/'
                + Number(usuario.id)
                + '/activar';
        }


        abrirModal(modalActivar);
    }

    /*
     * =========================================================
     * FUNCIONES
     * =========================================================
     */

    function abrirEditarUsuario(id) {
        if (!modalEditar) {
            return;
        }

        const usuario = usuarios.find(
            (item) => Number(item.id) === id
        );

        if (!usuario) {
            alert('No se encontró el usuario.');

            return;
        }

        const nombreCompleto = [
            usuario.nombre,
            usuario.apellido
        ]
            .filter(Boolean)
            .join(' ');


        establecerValor(
            'editar-id',
            usuario.id
        );

        establecerValor(
            'editar-nombre',
            usuario.nombre
        );

        establecerValor(
            'editar-apellido',
            usuario.apellido
        );

        establecerValor(
            'editar-email',
            usuario.email
        );

        establecerValor(
            'editar-telefono',
            usuario.telefono
        );

        establecerValor(
            'editar-domicilio',
            usuario.domicilio
        );

        const rolActual =
            usuario.roles && usuario.roles.length > 0
                ? usuario.roles[0].toLowerCase()
                : 'usuario';

        const roles = {
            usuario: '1',
            admin: '2',
            inspector: '3'
        };

        establecerValor(
            'editar-rol',
            roles[rolActual] ?? '1'
        );


        establecerTexto(
            'editar-nombre-visible',
            nombreCompleto || 'Usuario'
        );

        establecerTexto(
            'editar-dni-visible',
            usuario.dni
                ? 'DNI ' + usuario.dni
                : 'DNI no disponible'
        );


        formEditar.action =
            baseURL
            + 'admin/usuarios/'
            + Number(usuario.id);

        if (editarPassword) {
            editarPassword.value = '';
        }

        if (editarPasswordConfirmacion) {
            editarPasswordConfirmacion.value = '';
        }
        
        abrirModal(modalEditar);
    }


    function abrirDesactivarUsuario(id) {
        if (!modalDesactivar) {
            return;
        }

        const usuario = usuarios.find(
            (item) => Number(item.id) === id
        );

        if (!usuario) {
            alert('No se encontró el usuario.');

            return;
        }

        const nombreCompleto = [
            usuario.nombre,
            usuario.apellido
        ]
            .filter(Boolean)
            .join(' ');


        establecerTexto(
            'desactivar-nombre-visible',
            nombreCompleto || 'Usuario'
        );


        if (formDesactivar) {
            formDesactivar.dataset.usuarioId =
                Number(usuario.id);

            formDesactivar.action =
                baseURL
                + 'admin/usuarios/'
                + Number(usuario.id)
                + '/desactivar';
        }


        abrirModal(modalDesactivar);
    }


    function abrirModal(modal) {
        if (!modal) {
            return;
        }

        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', '');
        }

        document.body.classList.add(
            'admin-usuarios-modal-abierto'
        );
    }


    function cerrarModal(modal) {
        if (!modal) {
            return;
        }

        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }

        /*
         * Solo quitamos la clase si no queda ningún
         * modal abierto.
         */

        const hayModalAbierto = document.querySelector(
            '.admin-usuarios__modal[open]'
        );

        if (!hayModalAbierto) {
            document.body.classList.remove(
                'admin-usuarios-modal-abierto'
            );
        }
    }


    function filtrarUsuarios(texto) {
        const termino = normalizar(texto);

        let encontrados = 0;


        filas.forEach((fila) => {
            const contenido =
                fila.dataset.busqueda || '';

            const coincide =
                termino === ''
                || normalizar(contenido).includes(termino);

            fila.hidden = !coincide;

            if (coincide) {
                encontrados++;
            }
        });


        if (sinResultados) {
            sinResultados.hidden =
                termino === ''
                || encontrados > 0;
        }


        if (limpiarBusqueda) {
            limpiarBusqueda.hidden =
                termino === '';
        }
    }


    function normalizar(texto) {
        return String(texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }


    function establecerValor(id, valor) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.value =
                valor !== null && valor !== undefined
                    ? String(valor)
                    : '';
        }
    }


    function establecerTexto(id, texto) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent =
                texto !== null && texto !== undefined
                    ? String(texto)
                    : '';
        }
    }


    function obtenerBaseURL() {
        /*
         * El proyecto utiliza:
         *
         * /manipulacionDeAlimentos/
         *
         * Buscamos esa ruta a partir del script actual para
         * no hardcodearla en las acciones.
         */

        const script =
            document.querySelector(
                'script[src*="admin-usuarios.js"]'
            );

        if (!script) {
            return '/manipulacionDeAlimentos/';
        }

        const src = script.getAttribute('src') || '';

        const marcador = 'js/admin-usuarios.js';

        const posicion = src.indexOf(marcador);

        if (posicion === -1) {
            return '/manipulacionDeAlimentos/';
        }

        return src.substring(0, posicion);
    }
});