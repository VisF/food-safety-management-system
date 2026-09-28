<?php

require_once __DIR__ . '/Servicios/InscripcionService.php';

$service = new InscripcionService();

$resultado = $service->usuarioPuedeInscribirseExamen(8);

echo '<pre>';
var_dump($resultado);
echo '</pre>';