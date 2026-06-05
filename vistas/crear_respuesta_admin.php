<?php
declare(strict_types=1);
/**
 * Vista: crear_respuesta_admin.php
 * Propósito: Formulario para que el administrador cree una respuesta o notificación.
 * Entradas: puede recibir valores por GET 'data' para inicializar campos.
 * Nota: el procesamiento real debe hacerse en el controlador; escapar entradas antes de mostrar.
 */
class CrearRespuestaAdminVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Crear respuesta (Admin)',
			'ticket' => []
		];
		$data = $defaults;
		// Si se suministra `data` por GET (JSON), usarla para inicializar el formulario. No confiar sin validar.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>

			<form action="<?= BASE_URL ?>/Router.php?r=panel_admin" method="post" class="mt-4">
					<label class="block text-sm font-medium">Respuesta</label>
					<textarea name="respuesta" class="mt-1 block w-full" rows="6"></textarea>
					<button type="submit" class="app-vista-button app-vista-button--primary mt-4">Enviar respuesta</button>
				</form>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

CrearRespuestaAdminVista::mostrar();

