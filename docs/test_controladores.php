<?php
declare(strict_types=1);

require_once 'controlador/DipaControlador.php';
require_once 'controlador/NotificacionControlador.php';

$dipa = new DipaControlador();
$notif = new NotificacionControlador();

echo "=== CONTROLADORES FINALES CREADOS ===\n\n";
echo "DipaControlador - Métodos públicos: " . $dipa->countMethods() . "\n";
echo "NotificacionControlador - Métodos públicos: " . $notif->countMethods() . "\n\n";
echo "TOTAL DE MÉTODOS: " . ($dipa->countMethods() + $notif->countMethods()) . "\n";
echo "\n✓ Ambos controladores creados exitosamente\n";
