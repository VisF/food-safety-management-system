<?php
// Script CLI: validar recursantes (ejecutar desde cron cada noche)

require_once __DIR__ . '/../../Modelo/PlazoRecursanteModelo.php';

$model = new PlazoRecursanteModelo();
$recursantes = $model->listarRecursantesVigentes();
foreach ($recursantes as $r) {
    // Intentar verificar elegibilidad por usuario relacionado a la inscripción
    $inscripcionId = (int)($r['inscripcion_id'] ?? 0);
    if ($inscripcionId <= 0) continue;

    // Obtener usuario_id desde inscripciones
    $pdo = \Connection::getPDO();
    $stmt = $pdo->prepare('SELECT usuario_id FROM inscripciones WHERE id = :id');
    $stmt->execute([':id' => $inscripcionId]);
    $row = $stmt->fetch();
    if (!$row) continue;
    $usuarioId = (int)$row['usuario_id'];

    $eligible = $model->verificarElegibilidad($usuarioId);
    echo "Inscripcion {$inscripcionId} (usuario {$usuarioId}) elegible: " . ($eligible ? 'SI' : 'NO') . "\n";
}

echo "validar_recursantes: terminado\n";
