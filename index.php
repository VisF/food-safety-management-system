<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/AltoRouter.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$router = new AltoRouter();


$router->setBasePath('/manipulacionDeAlimentos');

require_once __DIR__ . '/routes/web.php';


$match = $router->match();

if (!$match) {

    http_response_code(404);

    echo '404 - Página no encontrada';

    exit;
}

call_user_func_array(
    $match['target'],
    $match['params']
);

