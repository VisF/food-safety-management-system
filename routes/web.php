<?php

$router->map(
    'GET',
    '/inscripciones',
    function () {
        
    require_once __DIR__ . '/../controlador/InscripcionControlador.php';

    $controller = new InscripcionControlador();

    $datos = $controller->obtenerCursosDisponibles();
    }
);

$router->map(
    'POST',
    '/inscripciones',
    function () {
        
    require_once __DIR__ . '/../controlador/InscripcionControlador.php';

    $controller = new InscripcionControlador();

    $controller->procesarInscripcionExamen($_POST);
    }
);






$router->map(
    'GET',
    '/',
    function () {

        require_once __DIR__ . '/../controlador/HomeControlador.php';

        $controller = new HomeControlador();

        $datos = $controller->mostrarIndex();

        require_once __DIR__ . '/../vistas/index.php';

        $vista = new InicioVista();

        $vista->mostrar($datos);
    }
);

$router->map(
    'GET',
    '/login',
    function () {

        require_once __DIR__ . '/../controlador/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->mostrarLogin();
    }
);

$router->map(
    'POST',
    '/login',
    function () {

        require_once __DIR__ . '/../controlador/AuthControlador.php';

        $controller = new AuthControlador();

        $resultado = $controller->procesarLogin($_POST);

        if ($resultado['success']) {

            header('Location: /manipulacionDeAlimentos/');
            exit;
        }

        $controller->mostrarLogin([
            'error' => $resultado['error'] ?? null,
            'email' => $_POST['email'] ?? ''
        ]);
    }
);

$router->map(
    'GET',
    '/logout',
    function () {

        require_once __DIR__ . '/../controlador/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->procesarLogout();
    }
);


$router->map(
    'POST',
    '/registro',
    function () {

        require_once __DIR__ . '/../controlador/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->procesarRegistro($_POST);
    }
);

$router->map(
    'GET',
    '/registro',
    function () {

        require_once __DIR__ . '/../controlador/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->mostrarRegistro();
    }
);

