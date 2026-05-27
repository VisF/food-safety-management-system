<?php
declare(strict_types=1);
class MotivoRechazoVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Motivo de rechazo',
			'reason' => ''
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
				<p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($data['reason']); ?></p>
				<div class="mt-4">
					<a href="/Router.php?r=subida_documentacion" class="app-vista-button app-vista-button--secondary">Reintentar</a>
				</div>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

MotivoRechazoVista::mostrar();

