<?php
// Script CLI: validar recursantes (ejecutar desde cron cada noche)

require_once __DIR__ . '/../../Servicios/PlazoRecursanteService.php';
require_once __DIR__ . '/../../Repository/InscripcionRepository.php';

$plazoService = new PlazoRecursanteService();
$inscripcionRepository = new InscripcionRepository();

$recursantes = $plazoService->listarRecursantesVigentes();

foreach ($recursantes as $recursante) {

    $inscripcionId = (int)($recursante['inscripcion_id'] ?? 0);

    if ($inscripcionId <= 0) {
        continue;
    }

    $usuarioId = $inscripcionRepository->obtenerUsuarioIdPorInscripcion($inscripcionId);

    if ($usuarioId === null) {
        continue;
    }

    $elegible = $plazoService->verificarElegibilidad($usuarioId);

    echo "Inscripcion {$inscripcionId} (usuario {$usuarioId}) elegible: "
        . ($elegible ? 'SI' : 'NO')
        . PHP_EOL;
}

echo "validar_recursantes: terminado" . PHP_EOL;
