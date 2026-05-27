<?php
// Script CLI: enviar alertas de vencimiento (ejecutar por cron)
require_once __DIR__ . '/../../modelo/AlertaModelo.php';
require_once __DIR__ . '/../../controlador/NotificacionControlador.php';

$alertModel = new AlertaModelo();
$notiCtrl = new NotificacionControlador();

$pendientes = $alertModel->obtenerAlertasPendientes();
foreach ($pendientes as $a) {
    $uid = (int)($a['usuario_id'] ?? 0);
    $tipo = $a['tipo'] ?? 'vencimiento';
    $payload = isset($a['payload']) ? json_decode($a['payload'], true) : [];

    // Enviar notificación genérica; NotificacionControlador devuelve estructura de resultado
    $resultado = $notiCtrl->enviarNotificacion($uid, $tipo, $payload ?: []);
    if (!empty($resultado['éxito']) || !empty($resultado['success'])) {
        $alertModel->marcarEnviada($a['id']);
    } else {
        // Intentaremos marcar como enviada igualmente para evitar reintentos infinitos
        $alertModel->marcarEnviada($a['id']);
    }
}

echo "enviar_alertas_vencimiento: terminado\n";
