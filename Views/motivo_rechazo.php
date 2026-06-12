<?php
declare(strict_types=1);
/**
 * Vista: motivo_rechazo.php
 * Propósito: Mostrar la razón por la que un documento/trámite fue rechazado.
 * Entradas: GET 'data' con 'reason'.
 * Nota: incluir instrucciones claras para el siguiente paso (reintento/revisión).
 */
class MotivoRechazoVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Motivo de rechazo',
			'reason' => ''
		];
		$data = $defaults;
		// Mergea `data` de ejemplo desde GET; el motivo oficial debe provenir del backend.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
				<p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($data['reason']); ?></p>
				<div class="mt-4">
					<a href="<?= BASE_URL ?>/manipulacionDeAlimentos/subida_documentacion" class="app-vista-button app-vista-button--secondary">Reintentar</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

MotivoRechazoVista::mostrar();

