<?php
//---------- Rutas relacionadas con inscripciones a exámenes ---------
$router->map(
    'GET',
    '/inscripciones',
    function () {
        
    require_once __DIR__ . '/../Controller/InscripcionControlador.php';

    $controller = new InscripcionControlador();

    $datos = $controller->obtenerCursosDisponibles();
    }
);

//---------- Ruta para inscribirse a un curso (sin examen) ---------

$router->map(
    'POST',
    '/curso/inscribirse',
    function () {

        require_once __DIR__ . '/../Controller/InscripcionControlador.php';

        $controlador =
            new InscripcionControlador();

        $controlador->inscribirseCurso();
    }
);




//---------- Rutas relacionadas con la página de inicio ---------

$router->map(
    'GET',
    '/',
    function () {

        require_once __DIR__ . '/../Controller/HomeControlador.php';

        $controller = new HomeControlador();

        $datos = $controller->mostrarIndex();

        require_once __DIR__ . '/../Views/index.php';

        $vista = new InicioVista();

        $vista->mostrar($datos);
    }
);
//---------- Rutas relacionadas con la subida de documentación ---------
$router->map(
    'GET',
    '/subida_documentacion',
    function () {

        require_once __DIR__ . '/../Views/subida_documentacion.php';
        require_once __DIR__ . '/../Servicios/DocumentoService.php';

        $usuarioId =
            (int)($_SESSION['usuario_id'] ?? 0);

        $documentoService =
            new DocumentoService();

        $documentos =
            $documentoService
                ->obtenerPorUsuario(
                    $usuarioId
                );

        $vista =
            new SubidaDocumentacionVista();

        $vista->mostrar(
            $documentos
        );
    }
);

/*$router->map(
    'POST',
    '/documentos/subir',
    function () {

        require_once __DIR__ . '/../Controller/DocumentoControlador.php';

        $controlador =
            new DocumentoControlador();

        $controlador->subirDocumento();
    }
);*/

$router->map(
    'POST',
    '/documentos/subir',
    function () {

        require_once __DIR__ .
            '/../Controller/DocumentoControlador.php';

        $controlador =
            new DocumentoControlador();

        $controlador->procesarSubida();
    }
);

//---------- Rutas relacionadas con la autenticación ---------
$router->map(
    'GET',
    '/login',
    function () {

        require_once __DIR__ . '/../Controller/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->mostrarLogin();
    }
);

$router->map(
    'POST',
    '/login',
    function () {

        require_once __DIR__ . '/../Controller/AuthControlador.php';

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

        require_once __DIR__ . '/../Controller/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->procesarLogout();
    }
);

//---------- Rutas relacionadas con el registro de usuarios ---------
$router->map(
    'POST',
    '/registro',
    function () {

        require_once __DIR__ . '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $resultado =
            $controller->procesarRegistro($_POST);

        if ($resultado['success']) {

            header(
                'Location: ' .
                BASE_URL .
                '/login?toast=' .
                urlencode(
                    $resultado['toast']
                    ?? 'registro_exitoso'
                )
            );

            exit;
        }
        $_SESSION['registro_old'] = [
                'nombre' => $_POST['nombre'] ?? '',
                'apellido' => $_POST['apellido'] ?? '',
                'dni' => $_POST['dni'] ?? '',
                'email' => $_POST['email'] ?? ''
            ];

        header(
            'Location: ' .
            BASE_URL .
            '/registro?toast=' .
            urlencode(
                $resultado['toast']
                ?? 'error_registro'
            )
        );

        exit;
    }
);

$router->map(
    'GET',
    '/registro',
    function () {

        require_once __DIR__ . '/../Controller/AuthControlador.php';

        $controller = new AuthControlador();

        $controller->mostrarRegistro();
    }
);
//---------- Rutas relacionadas con el panel de administración ---------
$router->map(
    'GET',
    '/admin',
    function () {

        require_once __DIR__ . '/../Controller/AdminDashboardControlador.php';
        require_once __DIR__ . '/../Views/panel_admin.php';

        $controller = new AdminDashboardControlador();

        $datos = $controller->obtenerDashboard();

        $vista = new PanelAdminVista();

        $vista->mostrar($datos);
    }
);
//---------- Rutas relacionadas con la gestión de documentos ---------

$router->map(
    'GET',
    '/admin/documentos',
    function () {

        require_once __DIR__.'/../Views/admin_documentos.php';

        $vista = new AdminDocumentosVista();

        $vista->mostrar();

    }
);

//----------Rutas relacionadas con la gestión de usuarios ---------
$router->map(
    'GET',
    '/admin/usuarios',
    function () {

        require_once __DIR__.'/../Views/admin_usuarios.php';

        $vista = new AdminUsuariosVista();

        $vista->mostrar();

    }
);

//----------- Rutas relacionadas con la gestión de carnets ---------
$router->map(
    'GET',
    '/admin/carnets',
    function () {

        require_once __DIR__.'/../Views/admin_carnets.php';

        $vista = new AdminCarnetsVista();

        $vista->mostrar();

    }
);

//----------- Rutas relacionadas con la gestión de reportes ---------
$router->map(
    'GET',
    '/admin/reportes',
    function () {

        require_once __DIR__.'/../Views/admin_reportes.php';

        $vista = new AdminReportesVista();

        $vista->mostrar();

    }
);

//---------- Rutas relacionadas con la gestión de exámenes ---------

$router->map(
    'GET',
    '/admin/examenes/nuevo',
    function () {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controlador = new AdminExamenControlador();

        $controlador->mostrarFormularioCrear();

    }
);

$router->map(
    'POST',
    '/admin/examenes',
    function () {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controlador = new AdminExamenControlador();

        $controlador->guardarNuevoExamen();

    }
);


//---------- Rutas relacionadas con otras vistas ---------
$router->map(
    'GET',
    '/admin/actividad',
    function () {

        require_once __DIR__ . '/../Views/actividad_reciente.php';

        $vista = new ActividadRecienteVista();

        $vista->mostrar();
    }
);
$router->map(
    'GET',
    '/confirmar_inscripcion_examen',
    function () {

        require_once __DIR__ . '/../Servicios/ExamenService.php';

        $idExamen =
            (int)($_GET['id'] ?? 0);

        $examenService =
            new ExamenService();

        $examen =
            $examenService
                ->obtenerExamen(
                    $idExamen
                );

        if ($examen === null) {

            http_response_code(404);
            exit('Examen no encontrado.');
        }

        $_GET['data'] = json_encode([
            'examId'   => $examen['id'],
            'examName' => 'Examen de Manipulación de Alimentos'
        ]);

        require_once __DIR__ . '/../Views/confirmar_inscripcion_examen.php';

        ConfirmarInscripcionExamenVista::mostrar();
    }
);


//---------- Vistas de ejemplo para desarrollo; eliminar o proteger con autenticación en producción.
$router->map(
    'GET',
    '/detalle_examen',
    function () {

        require_once __DIR__ . '/../Servicios/ExamenService.php';

        $idExamen =
            (int)($_GET['id'] ?? 0);

        $examenService =
            new ExamenService();

        $examen =
            $examenService
                ->obtenerExamen(
                    $idExamen
                );

        if ($examen === null) {

            http_response_code(404);
            echo 'Examen no encontrado';
            return;
        }

        $datos = [
            'title' => 'Detalle del examen',
            'exam' => [
                'id' => $examen['id'],
                'nombre' => 'Examen de Manipulación de Alimentos',
                'fecha' => date(
                    'd/m/Y',
                    strtotime($examen['fecha'])
                ),
                'hora' => substr(
                    $examen['hora'],
                    0,
                    5
                ),
                'lugar' =>
                    $examen['ubicacion'] .
                    (
                        !empty($examen['aula'])
                        ? ' - ' . $examen['aula']
                        : ''
                    ),
                'cupos' => $examen['cupos'],
                'estado' =>
                    (int)$examen['cupos'] > 0
                        ? 'CUPOS DISPONIBLES'
                        : 'SIN CUPOS'
            ]
        ];

        $_GET['data'] = json_encode($datos);

        require_once __DIR__ . '/../Views/detalle_examen.php';

        DetalleExamenVista::mostrar();
    }
);

$router->map(
    'POST',
    '/inscripcion/confirmar',
    function () {

         require_once __DIR__ . '/../Controller/InscripcionControlador.php';

        $controller = new InscripcionControlador();

        $resultado = $controller->procesarInscripcionExamen($_POST);

       if ($resultado['success']) {

            header(
                'Location: /manipulacionDeAlimentos/index.php?toast=inscripcion_exitosa'
            );

        } else {

            switch ($resultado['mensaje']) {
                                case 'Debe iniciar sesión para inscribirse a un examen.':
                                    $toast = 'login_requerido';
                                    break;

                                case 'Debe completar la documentación requerida':
                                    $toast = 'documentacion_incompleta';
                                    break;

                                case 'Ya posee una inscripción activa a un examen.':
                                    $toast = 'ya_inscripto';
                                    break;

                                default:
                                    $toast = 'error_inscripcion';
                                    break;
                            }

            header(
                "Location: /manipulacionDeAlimentos/index.php?toast={$toast}"
            );
        }

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
        require_once __DIR__ . '/../Views/confirmar_inscripcion_examen.php';

        ConfirmarInscripcionExamenVista::mostrar();
    }
);

$router->map(
    'GET',
    '/consulta-publica',
    function () {

        $_GET['data'] = json_encode([
            'page_title' => 'Consulta Pública de Carnets',

            'formulario' => [
                'dni' => '',
            ],

            'resultado' => [
                'encontrado' => false
            ]
        ]);

        require_once __DIR__ . '/../Views/consulta_publica.php';

        $vista = new ConsultaPublicaVista();
        $vista->mostrar();
    }
);

//BORRAR DESDE ACA

$router->map(
    'GET',
    '/consulta-publica-demo',
    function () {

        $_GET['data'] = json_encode([
            'page_title' => 'Consulta Pública de Carnets',

            'formulario' => [
                'dni' => '40123456',
            ],

            'resultado' => [
                'encontrado' => true,
                'id_carnet' => 1,
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'dni' => '40123456',
                'numero_carnet' => 'C-2026-000001',
                'fecha_emision' => '15/07/2026',
                'fecha_vencimiento' => '15/07/2029',
                'vigente' => true,
                'codigo_qr' => '/manipulacionDeAlimentos/img/qr-demo.png'
            ]
        ]);

        require_once __DIR__ . '/../Views/consulta_publica.php';

        $vista = new ConsultaPublicaVista();
        $vista->mostrar();
    }
);

//HASTA ACA



//Para borrar despues
$router->map(
    'GET',
    '/detalle_tramite',
    function () {

        $datos = [
            'title' => 'Detalle del trámite',
            'tramite' => [
                'id' => 123,
                'estado' => 'EN PROCESO',
                'fecha' => '15/09/2026',
                'dni' => '12345678',
                'curso' => 'Aprobado',
                'examen' => 'Pendiente',
                'documentacion' => 'Completa'
            ]
        ];

        $_GET['data'] = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        );

        require_once __DIR__ . '/../Views/detalle_tramite.php';

        DetalleTramiteVista::mostrar();
    }
);
/*==========================================================
    ADMIN - EXÁMENES
==========================================================*/

$router->map(
    'GET',
    '/admin/examenes',
    function () {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->mostrarListado();
    }
);

$router->map(
    'GET',
    '/admin/examenes/nuevo',
    function () {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->mostrarFormularioCrear();
    }
);

$router->map(
    'POST',
    '/admin/examenes/nuevo',
    function () {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->crearExamen($_POST);
    }
);

$router->map(
    'GET',
    '/admin/examenes/[i:id]',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->mostrarDetalle(
            (int) $id
        );
    }
);

$router->map(
    'GET',
    '/admin/examenes/[i:id]/editar',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->mostrarFormularioEditar(
            (int) $id
        );
    }
);

$router->map(
    'POST',
    '/admin/examenes/[i:id]',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->guardarEdicion(
            (int) $id
        );
    }
);

$router->map(
    'POST',
    '/admin/examenes/[i:id]/activar',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->activarExamen(
            (int) $id
        );
    }
);

$router->map(
    'POST',
    '/admin/examenes/[i:id]/desactivar',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->desactivarExamen(
            (int) $id
        );
    }
);

$router->map(
    'GET',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->mostrarAdministracionInscripcion(
            (int) $id
        );
    }
);

$router->map(
    'POST',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->guardarAdministracionInscripcion(
            (int) $id
        );
    }
);