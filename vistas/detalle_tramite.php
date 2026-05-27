<?php
declare(strict_types=1);
class DetalleTramiteVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Detalle del trámite',
			'tramite' => []
		];
		$data = $defaults;
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
					<a href="/Router.php?r=historial_tramite" class="app-vista-button app-vista-button--secondary">Historial</a>
					<a href="/Router.php?r=comprobante_tramite" class="app-vista-button app-vista-button--primary">Descargar comprobante</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

DetalleTramiteVista::mostrar();

