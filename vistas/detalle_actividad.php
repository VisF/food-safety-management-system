<?php
declare(strict_types=1);
/**
 * Vista: detalle_actividad.php
 * Propósito: Mostrar detalle de una actividad o evento relacionado al trámite.
 * Entradas: puede recibir datos vía GET 'data' con la actividad a mostrar.
 * Nota: mantener el marcado HTML limpio y escapar cualquier texto dinámico.
 */
class DetalleActividadVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Detalle de actividad',
			'actividad' => []
		];
		$data = $defaults;
		// Si se recibe `data` por GET (JSON), se usa para poblar la vista; validar antes de confiar.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['actividad']['titulo'] ?? $data['title']); ?></h1>
				<p class="mt-2"><?php echo htmlspecialchars($data['actividad']['descripcion'] ?? ''); ?></p>
				<div class="mt-4">
				<a href="Router.php?r=actividad_reciente" class="app-vista-button app-vista-button--secondary">Volver</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

DetalleActividadVista::mostrar();

