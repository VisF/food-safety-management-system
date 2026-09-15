document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('menu-navegacion');
    const botonAbrir = document.getElementById('boton-menu-navegacion');

    if (!menu || !botonAbrir) {
        return;
    }

    const botonesCerrar = menu.querySelectorAll('[data-menu-cerrar]');

    const abrirMenu = () => {
        menu.classList.add('menu-navegacion--abierto');
        menu.setAttribute('aria-hidden', 'false');
        botonAbrir.setAttribute('aria-expanded', 'true');
        botonAbrir.setAttribute('aria-label', 'Cerrar menú');

        document.body.classList.add('menu-navegacion-abierto');
    };

    const cerrarMenu = () => {
        menu.classList.remove('menu-navegacion--abierto');
        menu.setAttribute('aria-hidden', 'true');
        botonAbrir.setAttribute('aria-expanded', 'false');
        botonAbrir.setAttribute('aria-label', 'Abrir menú');

        document.body.classList.remove('menu-navegacion-abierto');
    };

    const alternarMenu = () => {
        const abierto = menu.classList.contains('menu-navegacion--abierto');

        if (abierto) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    };

    botonAbrir.addEventListener('click', alternarMenu);

    botonesCerrar.forEach((boton) => {
        boton.addEventListener('click', cerrarMenu);
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            cerrarMenu();
        }
    });

    menu.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('a');

        if (enlace) {
            cerrarMenu();
        }
    });
});