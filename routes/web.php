<?php

declare(strict_types=1);

/* ==========================================================
   MIDDLEWARES
========================================================== */

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;

/* ==========================================================
   NORMALIZACIÓN DE URLS
========================================================== */

/*
 * Todas las rutas tienen una única URL canónica sin
 * trailing slash, excepto la raíz.
 *
 * Ejemplos:
 * /admin/                  -> /admin
 * /perfil/                 -> /perfil
 * /admin/examenes/15/     -> /admin/examenes/15
 *
 * Se utiliza 308 para conservar el método HTTP.
 */

$basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
$basePath = '/' . trim($basePath, '/');

$requestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

if ($basePath !== '/' && strpos($requestPath, $basePath) === 0) {
    $relativePath = substr(
        $requestPath,
        strlen($basePath)
    );

    if ($relativePath === '') {
        $relativePath = '/';
    }
} else {
    $relativePath = $requestPath;
}

if (
    $relativePath !== '/' &&
    substr($relativePath, -1) === '/'
) {
    $relativePath = rtrim(
        $relativePath,
        '/'
    );

    $location =
        rtrim(BASE_URL, '/') .
        $relativePath;

    if (!empty($_SERVER['QUERY_STRING'])) {
        $location .= '?' . $_SERVER['QUERY_STRING'];
    }

    header(
        'Location: ' . $location,
        true,
        308
    );

    exit;
}

/* ==========================================================
   INICIO
========================================================== */

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

        $vista->mostrar($datos);
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

        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $resultado =
            $controller->procesarLogin($_POST);

        if (!empty($resultado['success'])) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/'
            );

            exit;
        }

        $controller->mostrarLogin([
            'error' =>
                $resultado['error'] ?? null,

            'email' =>
                $_POST['email'] ?? ''
        ]);
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

        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/AuthControlador.php';

        $controller =
            new AuthControlador();

        $resultado =
            $controller->procesarRegistro($_POST);

        if (!empty($resultado['success'])) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
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
                $_POST['nombre'] ?? '',

            'apellido' =>
                $_POST['apellido'] ?? '',

            'dni' =>
                $_POST['dni'] ?? '',

            'email' =>
                $_POST['email'] ?? ''
        ];

        header(
            'Location: ' .
            rtrim(BASE_URL, '/') .
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

        AuthMiddleware::handle();
        CsrfMiddleware::validate();

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
 * Documentación del ciudadano.
 */
$router->map(
    'GET',
    '/documentacion',
    function () {

        AuthMiddleware::handle();

        require_once __DIR__ .
            '/../Controller/DocumentoControlador.php';

        $controller =
            new DocumentoControlador();

        $controller->mostrarDocumentacion();
    }
);


/**
 * Procesar subida de documentación.
 */
$router->map(
    'POST',
    '/documentos/subir',
    function () {

        AuthMiddleware::handle();
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/DocumentoControlador.php';

        $controller =
            new DocumentoControlador();

        $controller->procesarSubida();
    }
);


/**
 * Descargar documento propio.
 */
$router->map(
    'GET',
    '/documentos/[i:id]/descargar',
    function ($id) {

        AuthMiddleware::handle();

        require_once __DIR__ .
            '/../Controller/DocumentoControlador.php';

        $controller =
            new DocumentoControlador();

        $controller->descargarDocumento(
            (int)$id
        );
    }
);


/* ==========================================================
   PERFIL — CIUDADANO
========================================================== */

/**
 * Perfil del ciudadano.
 */
$router->map(
    'GET',
    '/perfil',
    function () {

        AuthMiddleware::handle();

        require_once __DIR__ .
            '/../Controller/UsuarioControlador.php';

        $controller =
            new UsuarioControlador();

        $controller->mostrarPerfil();
    }
);


/**
 * Actualizar perfil.
 */
$router->map(
    'POST',
    '/perfil/actualizar',
    function () {

        AuthMiddleware::handle();
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/UsuarioControlador.php';

        $controller =
            new UsuarioControlador();

        $controller->actualizarPerfil();
    }
);


/* ==========================================================
   EXÁMENES — CIUDADANO
========================================================== */

/**
 * Procesar inscripción a examen.
 *
 * La vista de detalle del examen realiza directamente
 * el POST a esta ruta.
 */
$router->map(
    'POST',
    '/inscripcion/confirmar',
    function () {

        AuthMiddleware::handle();
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/InscripcionControlador.php';

        $controller =
            new InscripcionControlador();

        $resultado =
            $controller->procesarInscripcionExamen(
                $_POST
            );

        if (!empty($resultado['success'])) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/?toast=inscripcion_exitosa'
            );

            exit;
        }

        switch (
            $resultado['mensaje'] ?? ''
        ) {

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
            'Location: ' .
            rtrim(BASE_URL, '/') .
            '/?toast=' .
            urlencode($toast)
        );

        exit;
    }
);


/**
 * Detalle de un examen para el ciudadano.
 */
$router->map(
    'GET',
    '/detalle_examen/[i:id]',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/ExamenControlador.php';

        require_once __DIR__ .
            '/../Views/detalle_examen.php';

        $controller =
            new ExamenControlador();

        $datos =
            $controller->obtenerDetalleCiudadano(
                (int)$id
            );

        if ($datos === null) {

            http_response_code(404);

            echo 'Examen no encontrado';

            return;
        }

        $vista =
            new DetalleExamenVista();

        $vista->mostrar($datos);
    }
);


/* ==========================================================
   CARNET — CIUDADANO AUTENTICADO
========================================================== */

/**
 * Descargar carnet del ciudadano autenticado.
 */
$router->map(
    'GET',
    '/carnet/descargar',
    function () {

        AuthMiddleware::handle();

        require_once __DIR__ .
            '/../Controller/CarnetControlador.php';

        $controller =
            new CarnetControlador();

        $controller->descargarCarnet();
    }
);


/* ==========================================================
   CONSULTA PÚBLICA DE CARNETS
========================================================== */

/**
 * Consulta pública por DNI.
 */
$router->map(
    'GET',
    '/consulta-publica',
    function () {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controller =
            new ConsultaPublicaControlador();

        $controller->mostrar();
    }
);


/**
 * Descargar carnet desde consulta pública.
 */
$router->map(
    'GET',
    '/consulta-publica/carnet/[i:id]/descargar',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controller =
            new ConsultaPublicaControlador();

        $controller->descargarCarnet(
            (int)$id
        );
    }
);


/**
 * Descargar foto de carnet desde consulta pública.
 */
$router->map(
    'GET',
    '/consulta-publica/carnet/[i:id]/foto',
    function ($id) {

        require_once __DIR__ .
            '/../Controller/ConsultaPublicaControlador.php';

        $controller =
            new ConsultaPublicaControlador();

        $controller->descargarFoto(
            (int)$id
        );
    }
);


/* ==========================================================
   ADMINISTRACIÓN
========================================================== */

/**
 * Panel principal.
 */
$router->map(
    'GET',
    '/admin',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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

        $vista->mostrar($datos);
    }
);


/**
 * Actividad reciente.
 */
$router->map(
    'GET',
    '/admin/actividad',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Views/actividad_reciente.php';

        $vista =
            new ActividadRecienteVista();

        $vista->mostrar();
    }
);


/* ==========================================================
   ADMINISTRACIÓN — EXÁMENES
========================================================== */

/**
 * Listado de exámenes.
 */
$router->map(
    'GET',
    '/admin/examenes',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarListado();
    }
);


/**
 * Formulario para crear examen.
 */
$router->map(
    'GET',
    '/admin/examenes/nuevo',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->mostrarFormularioCrear();
    }
);


/**
 * Crear examen.
 */
$router->map(
    'POST',
    '/admin/examenes',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/ExamenControlador.php';

        require_once __DIR__ .
            '/../Views/admin_examen_form.php';

        $controller =
            new ExamenControlador();

        $resultado =
            $controller->guardar();

        if (!empty($resultado['success'])) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/examenes?toast=examen_creado'
            );

            exit;
        }

        $datosFormulario =
            $resultado['data'] ?? [];

        $errores = [];

        if (!empty($resultado['message'])) {
            $errores[] =
                $resultado['message'];
        }

        $vista =
            new ExamenFormVista();

        $vista->mostrar([
            'page_title' => 'Nuevo Examen',

            'modo' => 'crear',

            'examen' => [
                'fecha' =>
                    $datosFormulario['fecha'] ?? '',

                'hora' =>
                    $datosFormulario['hora'] ?? '',

                'ubicacion' =>
                    $datosFormulario['ubicacion'] ?? '',

                'aula' =>
                    $datosFormulario['aula'] ?? '',

                'cupos' =>
                    $datosFormulario['cupos'] ?? ''
            ],

            'errores' => $errores
        ]);
    }
);


/**
 * Detalle administrativo de examen.
 */
$router->map(
    'GET',
    '/admin/examenes/[i:id]',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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
 * Editar examen.
 */
$router->map(
    'GET',
    '/admin/examenes/[i:id]/editar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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
 * Guardar edición de examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Activar examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]/activar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Desactivar examen.
 */
$router->map(
    'POST',
    '/admin/examenes/[i:id]/desactivar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Administrar inscripción a examen.
 */
$router->map(
    'GET',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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
 * Guardar administración de inscripción.
 */
$router->map(
    'POST',
    '/admin/inscripciones/[i:id]',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/AdminExamenControlador.php';

        $controller =
            new AdminExamenControlador();

        $controller->guardarAdministracionInscripcion(
            (int)$id
        );
    }
);

/**
 * Guardar configuración del plazo para recursantes.
 */
$router->map(
    'POST',
    '/admin/examenes/configuracion/recursante',
    function () {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ . '/../Controller/AdminExamenControlador.php';

        $controller = new AdminExamenControlador();

        $controller->guardarConfiguracionRecursante();

        exit;
    }
);


/* ==========================================================
   ADMINISTRACIÓN — CARNETS
========================================================== */

/**
 * Panel administrativo de carnets.
 */
$router->map(
    'GET',
    '/admin/carnets',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->mostrarIndex();
    }
);


/**
 * Formulario para cargar carnet.
 */
$router->map(
    'GET',
    '/admin/carnets/[i:id]/cargar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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
 * Emitir/cargar carnet.
 */
$router->map(
    'POST',
    '/admin/carnets/[i:id]/emitir',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Descargar carnet desde administración.
 */
$router->map(
    'GET',
    '/admin/carnets/[i:id]/descargar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminCarnetControlador.php';

        $controller =
            new AdminCarnetControlador();

        $controller->descargarCarnet(
            (int)$id
        );
    }
);


/**
 * Anular carnet.
 */
$router->map(
    'POST',
    '/admin/carnets/[i:id]/anular',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ .
            '/../Controller/CarnetControlador.php';

        $controller =
            new CarnetControlador();

        $resultado =
            $controller->anularCarnet(
                (int)$id
            );

        if (!empty($resultado['success'])) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/carnets?toast=carnet_anulado'
            );

            exit;
        }

        header(
            'Location: ' .
            rtrim(BASE_URL, '/') .
            '/admin/carnets?toast=error_anular_carnet'
        );

        exit;
    }
);


/* ==========================================================
   ADMINISTRACIÓN — DOCUMENTACIÓN
========================================================== */

/**
 * Panel administrativo de documentación.
 */
$router->map(
    'GET',
    '/admin/documentos',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->mostrarIndex();
    }
);


/**
 * Buscar documentación por DNI.
 */
$router->map(
    'GET',
    '/admin/documentos/buscar',
    function () {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ .
            '/../Controller/AdminDocumentoControlador.php';

        $controller =
            new AdminDocumentoControlador();

        $controller->buscarPorDni();
    }
);


/**
 * Aprobar documento.
 */
$router->map(
    'POST',
    '/admin/documentos/[i:id]/aprobar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Rechazar documento.
 */
$router->map(
    'POST',
    '/admin/documentos/[i:id]/rechazar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

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
 * Descargar documento desde administración.
 */
$router->map(
    'GET',
    '/admin/documentos/[i:id]/descargar',
    function ($id) {

        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

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
   FIN DE RUTAS
========================================================== */

/* ==========================================================
   ADMINISTRACIÓN — USUARIOS
========================================================== */

/**
 * =========================================================
 * ADMINISTRACIÓN DE USUARIOS
 * =========================================================
 */

/**
 * Listado de usuarios.
 */
$router->map(
    'GET',
    '/admin/usuarios',
    function () {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        require_once __DIR__ . '/../Controller/AdminUsuarioControlador.php';
        require_once __DIR__ . '/../Views/admin_usuarios.php';

        $controller = new AdminUsuarioControlador();
        $datos = $controller->gestionarUsuarios();

        $vista = new AdminUsuariosVista();
        $vista->mostrar($datos);
    }
);


/**
 * Crear usuario.
 */
$router->map(
    'POST',
    '/admin/usuarios',
    function () {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ . '/../Controller/AdminUsuarioControlador.php';
        require_once __DIR__ . '/../Views/admin_usuarios.php';

        $controller = new AdminUsuarioControlador();

        $resultado = $controller->crearUsuario($_POST);

        $datos = $controller->gestionarUsuarios();

        $datos['resultado'] = $resultado;

        $vista = new AdminUsuariosVista();
        $vista->mostrar($datos);
    }
);


/**
 * Actualizar usuario.
 */
$router->map(
    'POST',
    '/admin/usuarios/[i:id]',
    function ($id) {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ . '/../Controller/AdminUsuarioControlador.php';
        require_once __DIR__ . '/../Views/admin_usuarios.php';

        $controller = new AdminUsuarioControlador();

        $resultado = $controller->actualizarUsuario(
            (int)$id,
            $_POST
        );

        $datos = $controller->gestionarUsuarios();

        $datos['resultado'] = $resultado;

        $vista = new AdminUsuariosVista();
        $vista->mostrar($datos);
    }
);


/**
 * Desactivar usuario.
 */
$router->map(
    'POST',
    '/admin/usuarios/[i:id]/desactivar',
    function ($id) {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ . '/../Controller/AdminUsuarioControlador.php';

        $controller = new AdminUsuarioControlador();

        $resultado = $controller->desactivarUsuario(
            (int)$id
        );

        /*
         * PRG: volvemos al listado después de la operación.
         */
        header(
            'Location: /manipulacionDeAlimentos/admin/usuarios'
        );

        exit;
    }
);

/**
 * Activar usuario.
 */
$router->map(
    'POST',
    '/admin/usuarios/[i:id]/activar',
    function ($id) {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);
        CsrfMiddleware::validate();

        require_once __DIR__ . '/../Controller/AdminUsuarioControlador.php';

        $controller = new AdminUsuarioControlador();

        $resultado = $controller->activarUsuario(
            (int)$id
        );

        /*
         * PRG: volvemos al listado después de la operación.
         */
        if (!empty($resultado['success'])) {
            header(
                'Location: /manipulacionDeAlimentos/admin/usuarios?toast=usuario_activado'
            );
        } else {
            header(
                'Location: /manipulacionDeAlimentos/admin/usuarios?toast=error_activar_usuario'
            );
        }

        exit;
    }
);




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











