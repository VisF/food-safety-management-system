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
			<div class="app-vista-card detalle-tramite-card">

				<h1 class="detalle-tramite-titulo">
					<?= htmlspecialchars($data['title']) ?>
				</h1>

				<div class="app-info-grid">

					<div>
						<strong>N° Trámite</strong>
						<span><?= htmlspecialchars((string)($data['tramite']['id'] ?? '-')) ?></span>
					</div>

					<div>
						<strong>Estado</strong>
						<span><?= htmlspecialchars($data['tramite']['estado'] ?? '-') ?></span>
					</div>

					<div>
						<strong>Fecha</strong>
						<span><?= htmlspecialchars($data['tramite']['fecha'] ?? '-') ?></span>
					</div>

					<div>
						<strong>DNI</strong>
						<span><?= htmlspecialchars($data['tramite']['dni'] ?? '-') ?></span>
					</div>

					<div>
						<strong>Curso</strong>
						<span><?= htmlspecialchars($data['tramite']['curso'] ?? '-') ?></span>
					</div>

					<div>
						<strong>Examen</strong>
						<span><?= htmlspecialchars($data['tramite']['examen'] ?? '-') ?></span>
					</div>

					<div>
						<strong>Documentación</strong>
						<span><?= htmlspecialchars($data['tramite']['documentacion'] ?? '-') ?></span>
					</div>

				</div>

				<div class="detalle-tramite-acciones">

					<a href="#" class="app-vista-button app-vista-button--secondary">
						Ver historial
					</a>

					<a href="#" class="app-vista-button app-vista-button--primary">
						Descargar comprobante
					</a>

				</div>

			</div>
		</main>
		<?php
		include __DIR__ . '/footer.php';
	}
}



