<?php
declare(strict_types=1);
class SolicitarRevisionVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Solicitar revisión',
			'message' => ''
		];
		$data = $defaults;
		// Soporta `data` por GET para pruebas; validar contenido antes de mostrar.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
				<p class="mt-2"><?php echo htmlspecialchars($data['message']); ?></p>

				<form action="<?= BASE_URL ?>/Router.php?r=detalle_tramite" method="post" class="mt-4">
					<label class="block text-sm font-medium">Comentario</label>
					<textarea name="comentario" class="mt-1 block w-full" rows="4"></textarea>
					<button class="app-vista-button app-vista-button--primary mt-4" type="submit">Enviar solicitud</button>
				</form>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

SolicitarRevisionVista::mostrar();

