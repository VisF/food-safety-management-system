<?php
declare(strict_types=1);


// ----------------------------------------------------
// BLOQUE PARA Router.php (AuthControlador + UsuarioControlador)
// Pegar antes del fallback que incluye vistas directamente.
// ----------------------------------------------------

// Cargar controladores (si existen)
$authControllerFile = __DIR__ . '/controlador/AuthControlador.php';
$inscripcionControllerFile = __DIR__ . '/controlador/InscripcionControlador.php';
$reporteControllerFile = __DIR__ . '/controlador/ReporteControlador.php';
$tramiteControllerFile = __DIR__ . '/controlador/TramiteControlador.php';
$usuarioControllerFile = __DIR__ . '/controlador/UsuarioControlador.php';
$adminControllerFile = __DIR__ . '/controlador/AdminControlador.php';

// ---------- MIDDLEWARES ----------

require_once __DIR__ . '/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Middleware/GuestMiddleware.php';
require_once __DIR__ . '/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/Middleware/AuditMiddleware.php';
require_once __DIR__ . '/Middleware/MaintenanceMiddleware.php';



if (file_exists($authControllerFile)) {
    require_once $authControllerFile;
}
if (file_exists($inscripcionControllerFile)) {
    require_once $inscripcionControllerFile;
}
if (file_exists($reporteControllerFile)) {
    require_once $reporteControllerFile;
}
if (file_exists($tramiteControllerFile)) {
    require_once $tramiteControllerFile;
}
if (file_exists($usuarioControllerFile)) {
    require_once $usuarioControllerFile;
}
if (file_exists($adminControllerFile)) {
    require_once $adminControllerFile;
}

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\AuditMiddleware;

$route = $_GET['r'] ?? 'index';

MaintenanceMiddleware::handle();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Alias opcionales
$routeMap = [
    'home' => 'index',
    'inicio' => 'index',
];
$route = $routeMap[$route] ?? $route;

// ---------- AUTH ----------
if (in_array($route, ['login', 'login_post', 'registro', 'registro_post', 'perfil', 'logout'], true)) {
    if (!class_exists('AuthControlador')) {
        http_response_code(500);
        echo 'AuthControlador no encontrado';
        exit;
    }

    $auth = new AuthControlador();

    switch ($route) {
        case 'login':
            GuestMiddleware::handle();
            if ($method === 'POST') {
                $result = $auth->procesarLogin($_POST);
                if (!empty($result['success'])) {
                    header('Location: Router.php?r=perfil');
                    exit;
                }
                $auth->mostrarLogin([
                    'error' => $result['error'] ?? 'No se pudo iniciar sesión',
                    'email' => $_POST['email'] ?? '',
                ]);
                exit;
            }

            $auth->mostrarLogin();
            exit;

        case 'login_post':
            GuestMiddleware::handle();

            if ($method !== 'POST') {
                header('Location: Router.php?r=login');
                exit;
            }

            CsrfMiddleware::validate();
            $result = $auth->procesarLogin($_POST);
            if (!empty($result['success'])) {
                header('Location: Router.php?r=perfil');
                exit;
            }
            $auth->mostrarLogin([
                'error' => $result['error'] ?? 'No se pudo iniciar sesión',
                'email' => $_POST['email'] ?? '',
            ]);
            exit;

        case 'registro':
            GuestMiddleware::handle();
            if ($method === 'POST') {
                $result = $auth->procesarRegistro($_POST);
                if (!empty($result['success'])) {
                    header('Location: Router.php?r=login');
                    exit;
                }
                $auth->mostrarRegistro([
                    'error' => $result['error'] ?? null,
                    'errors' => $result['errors'] ?? [],
                    'nombre' => $_POST['nombre'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'dni' => $_POST['dni'] ?? '',
                ]);
                exit;
            }

            $auth->mostrarRegistro();
            exit;

        case 'registro_post':
            GuestMiddleware::handle();
            
            if ($method !== 'POST') {
                header('Location: Router.php?r=registro');
                exit;
            }
            CsrfMiddleware::validate();
            $result = $auth->procesarRegistro($_POST);
            if (!empty($result['success'])) {
                header('Location: Router.php?r=login');
                exit;
            }
            $auth->mostrarRegistro([
                'error' => $result['error'] ?? null,
                'errors' => $result['errors'] ?? [],
                'nombre' => $_POST['nombre'] ?? '',
                'email' => $_POST['email'] ?? '',
                'dni' => $_POST['dni'] ?? '',
            ]);
            exit;

        case 'perfil':
            AuthMiddleware::handle();
            $auth->mostrarPerfil();
            exit;

        case 'logout':
            AuthMiddleware::handle();   
            $auth->procesarLogout();
            exit;
    }
}

// ---------- USUARIO ----------
if (in_array($route, ['usuarios', 'usuario_ver', 'usuario_editar', 'usuario_buscar', 'usuario_eliminar'], true)) {
    if (!class_exists('UsuarioControlador')) {
        http_response_code(500);
        echo 'UsuarioControlador no encontrado';
        exit;
    }

    $usuarioController = new UsuarioControlador();

    switch ($route) {
        case 'usuarios':
            AuthMiddleware::handle();
            RoleMiddleware::handle([
                'admin'
            ]);
            $pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $porPagina = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 20;
            $usuarioController->mostrarListado($pagina, $porPagina);
            exit;

        case 'usuario_ver':
            AuthMiddleware::handle();
            RoleMiddleware::handle([
                'admin',
                'inspector'
            ]);
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo 'ID inválido';
                exit;
            }
            $usuarioController->mostrarPerfil($id);
            exit;

        case 'usuario_editar':
            AuthMiddleware::handle();
            RoleMiddleware::handle([
                'admin'
            ]);
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo 'ID inválido';
                exit;
            }
            if ($method === 'POST') {
                CsrfMiddleware::validate();
                $result = $usuarioController->actualizarPerfil(
                    $id,
                    $_POST
                );
                if (!empty($result['success'])) {
                    AuditMiddleware::log(
                        'UPDATE',
                        'usuarios',
                        [],
                        [
                            'id' => $id
                        ]
                    );
                    header(
                        'Location: Router.php?r=usuario_ver&id=' . $id
                    );
                    exit;
                }
                header(
                    'Location: Router.php?r=usuario_editar&id=' . $id
                );
                exit;
            }

    $usuarioController->mostrarEditar($id);

    exit;

        case 'usuario_buscar':
            AuthMiddleware::handle();
            RoleMiddleware::handle([
                'admin',
                'inspector'
            ]);
            $termino = trim((string)($_GET['q'] ?? ''));
            $limite = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
            $result = $usuarioController->buscar($termino, $limite);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;

        case 'usuario_eliminar':
            AuthMiddleware::handle();
            RoleMiddleware::handle([
                'admin'
            ]);
            CsrfMiddleware::validate();
            if ($method !== 'POST') {
                http_response_code(405);
                echo 'Método no permitido';
                exit;
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo 'ID inválido';
                exit;
            }

            $result = $usuarioController->eliminar($id);
            if (!empty($result['success'])) {
                AuditMiddleware::log(
                    'DELETE',
                    'usuarios',
                    [],
                    ['id' => $id]
                );
                header('Location: Router.php?r=usuarios');
                exit;
            }

            http_response_code(400);
            echo $result['error'] ?? 'No se pudo eliminar';
            exit;
    }
}

// ---------- REPORTES / TRAMITES ----------
if (
    $route === 'actividad_reciente' ||
    $route === 'historial_tramite' ||
    $route === 'comprobante_tramite' ||
    $route === 'carnet_emitido' ||
    $route === 'inscripcion_examen'
) {

    AuthMiddleware::handle();

    if ($route === 'inscripcion_examen' && class_exists('InscripcionControlador')) {

        $controller = new InscripcionControlador();

        $payload = [
            'page_title' => 'Inscripcion a examen - App Ciudadana',
            'exams' => $controller->obtenerExamenesDisponibles(),
        ];

        $_GET['data'] = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
        );
    }

    if ($route === 'actividad_reciente' && class_exists('ReporteControlador')) {

        RoleMiddleware::handle([
            'admin',
            'inspector'
        ]);

        $controller = new ReporteControlador();

        $resultado =
            $controller->obtenerActividadReciente(20);

        $payload = [
            'page_title' => 'Actividad Reciente - App Ciudadana',
            'activities' => $resultado['actividades'] ?? [],
        ];

        $_GET['data'] = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
        );
    }

    if ($route === 'historial_tramite' && class_exists('TramiteControlador')) {

        $idInscripcion =
            isset($_GET['id_inscripcion'])
                ? (int)$_GET['id_inscripcion']
                : 1;

        $controller = new TramiteControlador();

        $historial =
            $controller->obtenerHistorialTramite(
                $idInscripcion
            );

        $items = [];

        foreach ($historial as $item) {

            $items[] = [
                'titulo' =>
                    ($item['estado_anterior'] ?? 'Inicio')
                    . ' -> ' .
                    ($item['estado_nuevo'] ?? ''),

                'estado' =>
                    trim(
                        ($item['usuario_admin'] ?? 'Sistema')
                        . ' - ' .
                        ($item['fecha_cambio'] ?? '')
                    ),
            ];
        }

        $_GET['data'] = json_encode([
            'title' => 'Historial de trámites',
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    if ($route === 'comprobante_tramite' && class_exists('TramiteControlador')) {

        $idInscripcion =
            isset($_GET['id_inscripcion'])
                ? (int)$_GET['id_inscripcion']
                : 1;

        $controller = new TramiteControlador();

        $resultado =
            $controller->obtenerComprobanteDescargable(
                $idInscripcion
            );

        $_GET['data'] = json_encode([
            'title' => 'Comprobante de trámite',
            'comprobante' =>
                $resultado['comprobante']
                ?? ['id' => $idInscripcion],
        ], JSON_UNESCAPED_UNICODE);
    }

    if ($route === 'carnet_emitido' && class_exists('TramiteControlador')) {

        $idInscripcion =
            isset($_GET['id_inscripcion'])
                ? (int)$_GET['id_inscripcion']
                : 1;

        $controller = new TramiteControlador();

        $carnet =
            $controller->obtenerCarnet(
                $idInscripcion
            );

        if (is_array($carnet)) {

            $_GET['numero'] =
                $carnet['numero_carnet'] ?? '';

            $_GET['titular'] =
                $carnet['titular'] ?? '';

            $_GET['fecha_emision'] =
                $carnet['fecha_emision'] ?? '';

            $_GET['fecha_vencimiento'] =
                $carnet['fecha_vencimiento'] ?? '';
        }
    }
}


// ---------- INSCRIPCIONES ----------
if ($route === 'inscripcion_examen_inscribir') {

    AuthMiddleware::handle();

    if (!class_exists('InscripcionControlador')) {
        http_response_code(500);
        echo 'InscripcionControlador no encontrado';
        exit;
    }

    if ($method !== 'POST') {
        header(
            'Location: Router.php?r=inscripcion_examen'
        );
        exit;
    }

    CsrfMiddleware::validate();

    $inscripcionController =
        new InscripcionControlador();

    $resultado =
        $inscripcionController
            ->procesarInscripcionExamen($_POST);

    if (!empty($resultado['success'])) {

        AuditMiddleware::log(
            'INSERT',
            'inscripciones',
            [],
            $_POST
        );

        $payload = [
            'title' => 'Inscripción exitosa',
            'message' =>
                $resultado['mensaje']
                ?? 'Tu inscripción se registró correctamente.',
        ];

        header(
            'Location: Router.php?r=inscripcion_exitosa&data=' .
            rawurlencode(
                json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                )
            )
        );

        exit;
    }

    $mensaje =
        $resultado['mensaje']
        ?? 'No se pudo completar la inscripción';

    header(
        'Location: Router.php?r=inscripcion_examen&data=' .
        rawurlencode(
            json_encode(
                ['error' => $mensaje],
                JSON_UNESCAPED_UNICODE
            )
        )
    );

    exit;
}


// ---------- ADMIN: EXÁMENES ----------
if ($route === 'crear_examen_guardar') {

    AuthMiddleware::handle();

    RoleMiddleware::handle([
        'admin'
    ]);

    if (!class_exists('AdminControlador')) {
        http_response_code(500);
        echo 'AdminControlador no encontrado';
        exit;
    }

    if ($method !== 'POST') {
        header(
            'Location: Router.php?r=crear_examen'
        );
        exit;
    }

    CsrfMiddleware::validate();

    $admin = new AdminControlador();

    $formData = $_POST;

    if (
        empty($formData['fecha']) &&
        !empty($formData['fecha_display'])
    ) {

        $fechaDisplay =
            trim((string)$formData['fecha_display']);

        if (
            preg_match(
                '/^(\d{2})\/(\d{2})\/(\d{4})$/',
                $fechaDisplay,
                $matches
            ) === 1
        ) {

            $formData['fecha'] =
                $matches[3] . '-' .
                $matches[2] . '-' .
                $matches[1];
        }
    }

    $resultado =
        $admin->crearExamen($formData);

    if (!empty($resultado['success'])) {

        AuditMiddleware::log(
            'INSERT',
            'examenes',
            [],
            $formData
        );

        header(
            'Location: Router.php?r=panel_admin'
        );

        exit;
    }

    $mensaje =
        $resultado['message']
        ?? 'No se pudo crear el examen';

    $payload = [
        'error' => $mensaje,
        'fecha_display' => $_POST['fecha_display'] ?? '',
        'hora' => $_POST['hora'] ?? '',
        'ubicacion' => $_POST['ubicacion'] ?? '',
        'aula' => $_POST['aula'] ?? '',
        'cupos' => $_POST['cupos'] ?? '',
    ];

    header(
        'Location: Router.php?r=crear_examen&data=' .
        rawurlencode(
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            )
        )
    );

    exit;
}
$allowed = [
    'index',
    'inicio',
    'actividad_reciente',
    'carnet_emitido',
    'subida_documentacion',
    'subir_archivo',
    'preview_documento',
    'documento_subido',
    'crear_examen',
    'detalle_examen',
    'confirmar_inscripcion_examen',
    'inscripcion_examen',
    'inscripcion_exitosa',
    'detalle_tramite',
    'historial_tramite',
    'comprobante_tramite',
    'motivo_rechazo',
    'solicitar_revision',
    'correccion_documentacion',
    'detalle_actividad',
    'detalle_validacion',
    'crear_respuesta_admin',
    'panel_admin',
    'panel_inspector',
    'usuario_aprobado',
    'usuario_rechazado'
];

if (!in_array($route, $allowed, true)) {
    http_response_code(404);
    echo 'Ruta no encontrada';
    exit;
}

// ------------------------------------------
// Protección de vistas administrativas
// ------------------------------------------

if ($route === 'panel_admin') {

    AuthMiddleware::handle();

    RoleMiddleware::handle([
        'admin'
    ]);
}

if ($route === 'panel_inspector') {

    AuthMiddleware::handle();

    RoleMiddleware::handle([
        'admin',
        'inspector'
    ]);
}

if (
    $route === 'usuario_aprobado' ||
    $route === 'usuario_rechazado'
) {

    AuthMiddleware::handle();

    RoleMiddleware::handle([
        'admin'
    ]);
}

$routeMap = [
    'inicio' => 'index',
    'home' => 'index',
    'crear-examen' => 'crear_examen',
];

$actualRoute = $routeMap[$route] ?? $route;

$file =
    __DIR__ .
    '/vistas/' .
    $actualRoute .
    '.php';

if (!file_exists($file)) {

    http_response_code(404);

    echo 'Vista no encontrada: '
        . htmlspecialchars(
            $actualRoute,
            ENT_QUOTES,
            'UTF-8'
        );

    exit;
}

require $file;