<?php
declare(strict_types=1);
/**
 * Vista: historial_tramite.php
 * Propósito: Listar historial de trámites del usuario.
 * Entradas: GET 'data' con clave 'items' (array) opcional.
 * Nota: paginar en backend si hay muchos registros.
 */
class HistorialTramiteVista
{
	public static function mostrar(): void
	{
		$defaults = [
			'title' => 'Historial de trámites',
			'items' => []
		];
		$data = $defaults;
		// Soporta datos de prueba vía GET['data']; en producción obtener el historial desde backend autenticado.
		if (isset($_GET['data'])) {
			$incoming = json_decode($_GET['data'], true);
			if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
		}
		include __DIR__ . '/header.php';
		?>
		<main class="app-container">
			<div class="app-vista-card">
				<h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
				<?php if (empty($data['items'])): ?>
					<p class="mt-2 text-sm text-gray-600">No hay trámites registrados.</p>
				<?php else: ?>
					<ul class="mt-4 space-y-2">
						<?php // Itera 'items' del historial. Backend debería paginar y normalizar cada entrada.
						foreach ($data['items'] as $it): ?>
							<li class="p-3 border rounded">
								<strong><?php echo htmlspecialchars($it['titulo'] ?? 'Trámite'); ?></strong>
								<div class="text-sm text-gray-600"><?php echo htmlspecialchars($it['estado'] ?? ''); ?></div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}

HistorialTramiteVista::mostrar();

