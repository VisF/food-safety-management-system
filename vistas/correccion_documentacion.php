<?php
declare(strict_types=1);
class CorreccionDocumentacionVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Corrección de documentación',
			'instructions' => ''
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
				<p class="mt-2"><?php echo htmlspecialchars($data['instructions']); ?></p>

				<form action="/Router.php?r=documento_subido" method="post" enctype="multipart/form-data" class="mt-4">
					<label class="block text-sm font-medium">Archivo corregido</label>
					<input type="file" name="archivo" class="mt-1 block w-full" required />
					<button class="app-vista-button app-vista-button--primary mt-4" type="submit">Enviar corrección</button>
				</form>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

CorreccionDocumentacionVista::mostrar();

