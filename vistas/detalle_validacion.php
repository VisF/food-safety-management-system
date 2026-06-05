<?php
declare(strict_types=1);
/**
 * Vista: detalle_validacion.php
 * Propósito: Mostrar resultado y metadatos de una validación realizada por el inspector.
 * Entradas: GET 'data' con clave 'validation' (array) opcional.
 * Nota: Evitar exponer información sensible en la interfaz pública.
 */
class DetalleValidacionVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Detalle de validación',
			'validation' => []
		];
		$data = $defaults;
		// Soporta inyección de datos por GET para pruebas; en producción obtener `validation` desde el controlador.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
				<p class="mt-2">Estado: <?php echo htmlspecialchars($data['validation']['status'] ?? ''); ?></p>
				<div class="mt-4">
					<a href="<?= BASE_URL ?>/Router.php?r=panel_inspector" class="app-vista-button app-vista-button--secondary">Volver</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

DetalleValidacionVista::mostrar();

