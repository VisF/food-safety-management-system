<?php
declare(strict_types=1);
/**
 * Vista: correccion_documentacion.php
 * Propósito: Interfaz para mostrar y corregir documentación rechazada.
 * Entradas: datos del documento vía GET 'data' o desde el controlador.
 * Nota: mantener logs de auditoría en el backend al realizar correcciones.
 */
class CorreccionDocumentacionVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Corrección de documentación',
			'instructions' => ''
		];
		$data = $defaults;
		// Si se recibe `data` vía GET (JSON), decodificar y mergear. Validar en controlador.
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

			<form action="<?= BASE_URL ?>/manipulacionDeAlimentos/documento_subido" method="post" enctype="multipart/form-data" class="mt-4">
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

