<?php
declare(strict_types=1);
/**
 * Vista: detalle_tramite.php
 * Propósito: Mostrar metadata detallada de un trámite.
 * Estructura esperada:
 *  - tramite: array{tipo?:string, estado?:string, ...}
 * Recomendaciones técnicas:
 *  - El controlador debe normalizar y validar la estructura `tramite` antes de inyectarla.
 *  - Escapar con `htmlspecialchars` (como ya hace la vista) para prevenir XSS.
 */
class DetalleTramiteVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Detalle del trámite',
			'tramite' => []
		];
		$data = $defaults;
		// Mergea `data` JSON enviada por GET para facilitar pruebas; el controlador debe normalizar la estructura.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
				<p class="mt-2">Tipo: <?php echo htmlspecialchars($data['tramite']['tipo'] ?? ''); ?></p>
				<p>Estado: <?php echo htmlspecialchars($data['tramite']['estado'] ?? ''); ?></p>

				<div class="mt-4">
					<a href="<?= BASE_URL ?>/manipulacionDeAlimentos/historial_tramite" class="app-vista-button app-vista-button--secondary">Historial</a>
					<a href="<?= BASE_URL ?>/manipulacionDeAlimentos/comprobante_tramite" class="app-vista-button app-vista-button--primary">Descargar comprobante</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

DetalleTramiteVista::mostrar();

