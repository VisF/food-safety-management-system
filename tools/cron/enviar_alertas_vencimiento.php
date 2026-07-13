<?php
// Script CLI: enviar alertas de vencimiento (ejecutar por cron)
require_once __DIR__ . '/../../Servicios/AlertaService.php';
require_once __DIR__ . '/../../Controller/NotificacionControlador.php';

$alertaService = new AlertaService();
$notiCtrl = new NotificacionControlador();

$pendientes = $alertaService->obtenerPendientes();

foreach ($pendientes as $a) {

    $uid = (int)($a['usuario_id'] ?? 0);
    $tipo = $a['tipo'] ?? 'vencimiento';
    $payload = isset($a['payload'])
        ? json_decode($a['payload'], true)
        : [];

    $resultado = $notiCtrl->enviarNotificacion(
        $uid,
        $tipo,
        $payload ?: []
    );

    $alertaService->marcarEnviada($a['id']);
}

echo "enviar_alertas_vencimiento: terminado\n";