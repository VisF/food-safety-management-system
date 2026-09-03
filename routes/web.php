<?php

declare(strict_types=1);
/* ==========================================================
   MIDDLEWARES
========================================================== */

require_once __DIR__ .
    '/../Middleware/AuthMiddleware.php';

require_once __DIR__ .
    '/../Middleware/RoleMiddleware.php';

require_once __DIR__ .
    '/../Middleware/CsrfMiddleware.php';

require_once __DIR__ .
    '/../Middleware/AuditMiddleware.php';

require_once __DIR__ .
    '/../Middleware/MaintenanceMiddleware.php';

require_once __DIR__ .
    '/../Middleware/GuestMiddleware.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AuditMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\GuestMiddleware;

/* ==========================================================
   INICIO
========================================================== */

/**
 * Página principal.
 */
$router->map(
    'GET',
    '/',
    function () {

        require_once __DIR__ .
            '/../Controller/HomeControlador.php';

        require_once __DIR__ .
            '/../Views/index.php';

        $controller =
            new HomeControlador();

        $datos =
            $controller->mostrarIndex();

        $vista =
            new InicioVista();

        $vista->mostrar(
            $datos
        );
    }
);


/* ==========================================================
   AUTENTICACIÓN
========================================================== */

/**
 * Mostrar login.
 */
$router->map(
    'GET',
    '/login',
    function () {

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $controller->mostrarLogin();
    }
);


/**
 * Procesar login.
 */
$router->map(
    'POST',
    '/login',
    function () {

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $resultado =
            $controller->procesarLogin(
                $_POST
            );

        if (
            $resultado['success']
        ) {

            header(
                'Location: ' .
                BASE_URL .
                '/'
            );

            exit;
        }

        $controller->mostrarLogin(
            [
                'error' =>
                    $resultado['error']
                    ?? null,

                'email' =>
                    $_POST['email']
                    ?? ''
            ]
        );
    }
);


/**
 * Mostrar registro.
 */
$router->map(
    'GET',
    '/registro',
    function () {

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $controller->mostrarRegistro();
    }
);


/**
 * Procesar registro.
 */
$router->map(
    'POST',
    '/registro',
    function () {

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $resultado =
            $controller->procesarRegistro(
                $_POST
            );

        if (
            $resultado['success']
        ) {

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

            'nombre' =>
                $_POST['nombre']
                ?? '',

            'apellido' =>
                $_POST['apellido']
                ?? '',

            'dni' =>
                $_POST['dni']
                ?? '',

            'email' =>
                $_POST['email']
                ?? ''
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


/**
 * Cerrar sesión.
 */
$router->map(
    'GET',
    '/logout',
    function () {

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $controller->procesarLogout();
    }
);


/* ==========================================================
   INSCRIPCIONES A CURSOS
========================================================== */

/**
 * Cursos disponibles para inscripción.
 */
$router->map(
    'GET',
    '/inscripciones',
    function () {

        require_once __DIR__ .
            '/../Controller/InscripcionControlador.php';

        $controller =
            new InscripcionControlador();

        $controller->obtenerCursosDisponibles();
    }
);


/**
 * Inscribirse a un curso presencial.
 */
$router->map(
    'POST',
    '/curso/inscribirse',
    function () {

        require_once __DIR__ .
            '/../Controller/InscripcionControlador.php';

        $controller =
            new InscripcionControlador();

        $controller->inscribirseCurso();
    }
);


/* ==========================================================
   DOCUMENTACIÓN — CIUDADANO
========================================================== */

/**
 * Mostrar documentación del usuario.
 */
$router->map(
    'GET',
    '/subida_documentacion',
    function () {

        require_once __DIR__ .
            '/../Views/subida_documentacion.php';

        require_once __DIR__ .
            '/../Servicios/DocumentoService.php';

        $usuarioId =
            (int)(
                $_SESSION['usuario_id']
                ?? 0
            );

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


/**
 * Procesar subida de documentación.
 */
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

/* ==========================================================
   PANEL DE ADMINISTRACIÓN
========================================================== */

/**
 * Panel principal de administración.
 */
$router->map(
    'GET',
    '/admin',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminDashboardControlador.php';

        require_once __DIR__ .
            '/../Views/panel_admin.php';

        $controller =
            new AdminDashboardControlador();

        $datos =
            $controller->obtenerDashboard();

        $vista =
            new PanelAdminVista();

        $vista->mostrar(
            $datos
        );
    }
);


/**
 * Actividad reciente de administración.
 */
$router->map(
    'GET',
    '/admin/actividad',
    function () {

        require_once __DIR__ .
            '/../Views/actividad_reciente.php';

        $vista =
            new ActividadRecienteVista();

        $vista->mostrar();
    }
);


/* ==========================================================
   EXÁMENES — CIUDADANO
========================================================== */
/**
 * Confirmación de inscripción a examen.
 */
$router->map(
    'GET',
    '/confirmar_inscripcion_examen',
    function () {

        require_once __DIR__ .
            '/../Servicios/ExamenService.php';

        require_once __DIR__ .
            '/../Views/confirmar_inscripcion_examen.php';


        $idExamen =
            (int)(
                $_GET['id']
                ?? 0
            );


        $examenService =
            new ExamenService();


        $examen =
            $examenService
                ->obtenerExamen(
                    $idExamen
                );


        if (
            $examen === null
        ) {

            http_response_code(404);

            exit(
                'Examen no encontrado.'
            );
        }


        $data = [

            'page_title' =>
                'Confirmar inscripción',

            'examId' =>
                (int)$examen['id'],

            'examName' =>
                'Examen de Manipulación de Alimentos'
        ];


        $vista =
            new ConfirmarInscripcionExamenVista();


        $vista->mostrar(
            $data
        );
    }
);

/**
 * Procesar inscripción a examen.
 */
$router->map(
    'POST',
    '/inscripcion/confirmar',
    function () {

        require_once __DIR__ .
            '/../Controller/InscripcionControlador.php';

        $controller =
            new InscripcionControlador();

        $resultado =
            $controller
                ->procesarInscripcionExamen(
                    $_POST
                );

        if (
            $resultado['success']
        ) {

            header(
                'Location: ' .
                BASE_URL .
                '/?toast=inscripcion_exitosa'
            );

            exit;
        }


        switch (
            $resultado['mensaje']
            ?? ''
        ) {

            case
                'Debe iniciar sesión para inscribirse a un examen.':

                $toast =
                    'login_requerido';

                break;


            case
                'Debe completar la documentación requerida':

                $toast =
                    'documentacion_incompleta';

                break;


            case
                'Ya posee una inscripción activa a un examen.':

                $toast =
                    'ya_inscripto';

                break;


            default:

                $toast =
                    'error_inscripcion';

                break;
        }


        header(
            'Location: ' .
            BASE_URL .
            '/?toast=' .
            urlencode(
                $toast
            )
        );

        exit;
    }
);


/**
 * Detalle de un examen para el ciudadano.
 */
$router->map(
    'GET',
    '/detalle_examen',
    function () {

        require_once __DIR__ .
            '/../Servicios/ExamenService.php';

        $idExamen =
            (int)(
                $_GET['id']
                ?? 0
            );

        $examenService =
            new ExamenService();

        $examen =
            $examenService
                ->obtenerExamen(
                    $idExamen
                );

        if (
            $examen === null
        ) {

            http_response_code(404);

            echo 'Examen no encontrado';

            return;
        }


        $datos = [

            'title' =>
                'Detalle del examen',

            'exam' => [

                'id' =>
                    $examen['id'],

                'nombre' =>
                    'Examen de Manipulación de Alimentos',

                'fecha' =>
                    date(
                        'd/m/Y',
                        strtotime(
                            $examen['fecha']
                        )
                    ),

                'hora' =>
                    substr(
                        $examen['hora'],
                        0,
                        5
                    ),

                'lugar' =>
                    $examen['ubicacion'] .
                    (
                        !empty(
                            $examen['aula']
                        )
                            ? ' - ' .
                              $examen['aula']
                            : ''
                    ),

                'cupos' =>
                    $examen['cupos'],

                'estado' =>
                    (int)$examen['cupos'] > 0
                        ? 'CUPOS DISPONIBLES'
                        : 'SIN CUPOS'
            ]
        ];


        $_GET['data'] =
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            );


        require_once __DIR__ .
            '/../Views/detalle_examen.php';

        DetalleExamenVista::mostrar();
    }
);

/* ==========================================================
   CONSULTA PÚBLICA DE CARNETS
========================================================== */

/**
 * Consulta pública de carnets por DNI.
 */
$router->map(
    'GET',
    '/consulta-publica',
    function () {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controlador =
            new ConsultaPublicaControlador();

        $controlador->mostrar();
    }
);

/**
 * Descargar carnet desde la consulta pública.
 */
$router->map(
    'GET',
    '/consulta-publica/carnet/[i:id]/descargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controlador =
            new ConsultaPublicaControlador();

        $controlador->descargarCarnet(
                    (int)$id
                );
    }
);


/**
 * Descargar foto de carnet desde la consulta pública.
 */
$router->map(
    'GET',
    '/consulta-publica/carnet/[i:id]/foto',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controlador =
            new ConsultaPublicaControlador();

        $controlador->descargarFoto(
                    (int)$id
                );
    }
);

/* ==========================================================
   CONSULTA DE CARNET POR NÚMERO
========================================================== */

/**
 * Consulta un carnet por número.
 */
$router->map(
    'GET',
    '/consulta-carnet',
    function () {

        $numeroCarnet =
            trim(
                $_GET['numero']
                ?? ''
            );

        $resultado = null;

        if ($numeroCarnet !== '') {

            require_once __DIR__ .
                '/../Controller/CarnetControlador.php';

            $controller =
                new CarnetControlador();

            $resultado =
                $controller
                    ->obtenerPorNumero(
                        $numeroCarnet
                    );
        }


        $datos = [

            'page_title' =>
                'Consulta de Carnet',

            'numero_carnet' =>
                $numeroCarnet,

            'resultado' =>
                $resultado
        ];


        $_GET['data'] =
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            );


        require_once __DIR__ .
            '/../Views/consulta_carnet.php';

        $vista =
            new ConsultaCarnetVista();

        $vista->mostrar();
    }
);
/* ==========================================================
   CARNET — CIUDADANO AUTENTICADO
========================================================== */

/**
 * Descargar el carnet del ciudadano autenticado.
 */
$router->map(
    'GET',
    '/carnet/descargar',
    function () {

        require_once __DIR__ .
            '/../Controller/CarnetControlador.php';

        $controller =
            new CarnetControlador();

        $controller->descargarCarnet();
    }
);



/* ==========================================================
   DESCARGA DE DOCUMENTOS — CIUDADANO
========================================================== */

/**
 * Descargar un documento propio.
 *
 * La validación de pertenencia del documento
 * debe realizarse en la capa correspondiente
 * antes de entregar el archivo.
 */
$router->map(
    'GET',
    '/documentos/[i:id]/descargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/DocumentoControlador.php';

        $controller =
            new DocumentoControlador();

        /*
         * Si DocumentoControlador todavía no tiene
         * este método, esta ruta queda pendiente de
         * implementación.
         */
        if (
            method_exists(
                $controller,
                'descargar'
            )
        ) {

            $controller->descargar(
                (int)$id
            );

            return;
        }


        http_response_code(501);

        echo
            'La descarga de documentos todavía no está implementada.';
    }
);


/* ==========================================================
   PERFIL / DATOS DEL CIUDADANO
========================================================== */

/**
 * Perfil del usuario autenticado.
 */
$router->map(
    'GET',
    '/perfil',
    function () {

        require_once __DIR__ .
            '/../Controller/UsuarioControlador.php';

        $controller =
            new UsuarioControlador();

        $controller->mostrarPerfil();
    }
);


/**
 * Actualizar perfil del usuario.
 */
$router->map(
    'POST',
    '/perfil/actualizar',
    function () {

        require_once __DIR__ .
            '/../Controller/UsuarioControlador.php';

        $controller =
            new UsuarioControlador();

        $controller->actualizarPerfil();
    }
);


/* ==========================================================
   FIN DEL FLUJO CIUDADANO
========================================================== */

/* ==========================================================
   ADMINISTRACIÓN — EXÁMENES
========================================================== */

/**
 * Listado administrativo de exámenes.
 */
$router->map(
    'GET',
    '/admin/examenes',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarListado();
    }
);


/**
 * Formulario para crear un examen.
 */
$router->map(
    'GET',
    '/admin/examenes/nuevo',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarFormularioCrear();
    }
);


/**
 * Crear un examen.
 *
 * POST /admin/examenes
 */
$router->map(
    'POST',
    '/admin/examenes',
    function () {

        AuthMiddleware::handle();

        RoleMiddleware::handle([
            'admin'
        ]);

        require_once __DIR__ .
            '/../Controller/ExamenControlador.php';

        require_once __DIR__ .
            '/../Views/admin_examen_form.php';

        $controller =
            new ExamenControlador();

        $resultado =
            $controller->guardar();

        if (
            !empty(
                $resultado['success']
            )
        ) {

            header(
                'Location: ' .
                BASE_URL .
                '/admin/examenes?toast=examen_creado'
            );

            exit;
        }

        $datosFormulario =
            $resultado['data']
            ?? [];

        $errores = [];

        if (
            !empty(
                $resultado['message']
            )
        ) {
            $errores[] =
                $resultado['message'];
        }

        $vista =
            new ExamenFormVista();

        $vista->mostrar([

            'page_title' =>
                'Nuevo Examen',

            'modo' =>
                'crear',

            'examen' => [

                'fecha' =>
                    $datosFormulario['fecha']
                    ?? '',

                'hora' =>
                    $datosFormulario['hora']
                    ?? '',

                'ubicacion' =>
                    $datosFormulario['ubicacion']
                    ?? '',

                'aula' =>
                    $datosFormulario['aula']
                    ?? '',

                'cupos' =>
                    $datosFormulario['cupos']
                    ?? ''

            ],

            'errores' =>
                $errores

        ]);
    }
);
/**
 * Detalle administrativo de un examen.
 */
$router->map(
    'GET',
    '/admin/examenes/[i:id]',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarDetalle(
            (int)$id
        );
    }
);


/**
 * Formulario de edición de un examen.
 */
$router->map(
    'GET',
    '/admin/examenes/[i:id]/editar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarFormularioEditar(
            (int)$id
        );
    }
);


/**
 * Guardar edición de un examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->guardarEdicion(
            (int)$id
        );
    }
);


/**
 * Activar un examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]/activar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->activarExamen(
            (int)$id
        );
    }
);


/**
 * Desactivar un examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]/desactivar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->desactivarExamen(
            (int)$id
        );
    }
);


/**
 * Administración de una inscripción a examen.
 */
$router->map(
    'GET',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarAdministracionInscripcion(
            (int)$id
        );
    }
);


/**
 * Guardar la administración de una inscripción.
 */
$router->map(
    'POST',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->guardarAdministracionInscripcion(
            (int)$id
        );
    }
);


/* ==========================================================
   ADMINISTRACIÓN — FIN DE EXÁMENES
========================================================== */

/* ==========================================================
   ADMINISTRACIÓN — CARNETS
========================================================== */

/**
 * Panel administrativo de carnets.
 *
 * Muestra:
 * - Inscripciones aprobadas pendientes de carnet.
 * - Carnets ya emitidos.
 * - Búsqueda por DNI.
 */
$router->map(
    'GET',
    '/admin/carnets',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->mostrarIndex();
    }
);


/**
 * Formulario para cargar un carnet.
 *
 * [i:id] corresponde al ID interno
 * de la inscripción.
 */
$router->map(
    'GET',
    '/admin/carnets/[i:id]/cargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->mostrarCarga(
            (int)$id
        );
    }
);


/**
 * Procesar la emisión/carga de un carnet.
 */
$router->map(
    'POST',
    '/admin/carnets/[i:id]/emitir',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->emitirCarnet(
            (int)$id
        );
    }
);

/**
 * Descargar/visualizar el PDF de un carnet emitido.
 */
$router->map(
    'GET',
    '/admin/carnets/[i:id]/descargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->descargarCarnet(
            (int)$id
        );
    }
);


/* ==========================================================
   CARNETS — CONSULTA ADMINISTRATIVA
========================================================== */

/**
 * Anular un carnet.
 */
$router->map(
    'POST',
    '/admin/carnets/[i:id]/anular',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/CarnetControlador.php';

        $controller =
            new CarnetControlador();

        $resultado =
            $controller->anularCarnet(
                (int)$id
            );

        if (
            !empty(
                $resultado['success']
            )
        ) {

            header(
                'Location: ' .
                BASE_URL .
                '/admin/carnets?toast=carnet_anulado'
            );

            exit;
        }

        header(
            'Location: ' .
            BASE_URL .
            '/admin/carnets?toast=error_anular_carnet'
        );

        exit;
    }
);


/* ==========================================================
   FIN ADMINISTRACIÓN — CARNETS
========================================================== */
/* ==========================================================
   ADMINISTRACIÓN — DOCUMENTACIÓN
========================================================== */

/**
 * Panel administrativo de documentación.
 *
 * Muestra:
 * - Ciudadanos con documentación pendiente.
 * - Estado de cada documento.
 * - Acciones administrativas.
 */
$router->map(
    'GET',
    '/admin/documentos',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->mostrarIndex();
    }
);


/**
 * Buscar documentación de un ciudadano por DNI.
 */
$router->map(
    'GET',
    '/admin/documentos/buscar',
    function () {

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->buscarPorDni();
    }
);


/**
 * Aprobar un documento.
 *
 * La observación es opcional.
 */
$router->map(
    'POST',
    '/admin/documentos/[i:id]/aprobar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->aprobar(
            (int)$id
        );
    }
);


/**
 * Rechazar un documento.
 *
 * El controlador exige una observación
 * indicando el motivo del rechazo.
 */
$router->map(
    'POST',
    '/admin/documentos/[i:id]/rechazar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->rechazar(
            (int)$id
        );
    }
);


/**
 * Descargar un documento desde
 * el panel administrativo.
 */
$router->map(
    'GET',
    '/admin/documentos/[i:id]/descargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->descargarArchivo(
            (int)$id
        );
    }
);


/* ==========================================================
   ADMINISTRACIÓN — USUARIOS
========================================================== */

/*
 * Las rutas de administración de usuarios
 * se incorporarán aquí una vez que trabajemos
 * sobre el UsuarioControlador/AdminUsuarioControlador
 * real y confirmemos sus métodos.
 */


/* ==========================================================
   ADMINISTRACIÓN — REPORTES
========================================================== */

/*
 * Las rutas de reportes se incorporarán aquí
 * una vez que trabajemos sobre el controlador
 * de reportes real.
 */


/* ==========================================================
   FIN DE RUTAS ADMINISTRATIVAS
========================================================== */











