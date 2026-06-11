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

$router->map(
    'GET',
    '/admin',
    function () {

        require_once __DIR__ . '/../controlador/AdminControlador.php';

        $controller = new AdminControlador();

        //$estadisticas = $controller->obtenerEstadisticas();

        require_once __DIR__ . '/../vistas/panel_admin.php';

        $vista = new PanelAdminVista();

        $vista->mostrar();
    }
);

$router->map(
    'GET',
    '/crear_examen',
    function () {

        require_once __DIR__ . '/../vistas/crear_examen.php';

        $vista = new CrearExamenVista();

        $vista->mostrar();
    }
);

$router->map(
    'GET',
    '/actividad_reciente',
    function () {

        require_once __DIR__ . '/../vistas/actividad_reciente.php';

        $vista = new ActividadRecienteVista();

        $vista->mostrar();
    }
);
/*
$router->map(
    'GET',
    '/detalle_examen',
    function () {

        require_once __DIR__ . '/../vistas/detalle_examen.php';

        DetalleExamenVista::mostrar();
    }
);
*/
$router->map(
    'GET',
    '/confirmar_inscripcion_examen',
    function () {

        require_once __DIR__ . '/../vistas/confirmar_inscripcion_examen.php';

        ConfirmarInscripcionExamenVista::mostrar();
    }
);


//---------- Vistas de ejemplo para desarrollo; eliminar o proteger con autenticación en producción.
$router->map(
    'GET',
    '/detalle_examen',
    function () {

        $datos = [
            'title' => 'Detalle del examen',
            'exam' => [
                'id' => 1,
                'nombre' => 'CRESTA',
                'fecha' => '24/10/2026',
                'hora' => '09:00',
                'lugar' => 'Aula 3',
                'cupos' => 30,
                'estado' => 'CUPOS DISPONIBLES'
            ]
        ];

        $_GET['data'] = json_encode($datos);

        require_once __DIR__ . '/../vistas/detalle_examen.php';

        DetalleExamenVista::mostrar();
    }
);

$router->map(
    'POST',
    '/inscripcion/confirmar',
    function () {

        require_once __DIR__ . '/../controlador/InscripcionControlador.php';

        $controller = new InscripcionControlador();

        $resultado = $controller->procesarInscripcionExamen($_POST);

        $_SESSION['success_message'] =
            'Te inscribiste correctamente al examen.';

        header('Location: /manipulacionDeAlimentos/');
        exit;
    }
);



$router->map(
    'GET',
    '/confirmar_inscripcion_examen',
    function () {

        $_GET['data'] = json_encode([
            'examName' => 'CRESTA'
        ]);

        console.log("Confirmar inscripción al examen CRESTA");
        require_once __DIR__ . '/../vistas/confirmar_inscripcion_examen.php';

        ConfirmarInscripcionExamenVista::mostrar();
    }
);