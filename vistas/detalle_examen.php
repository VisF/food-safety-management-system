<?php
declare(strict_types=1);
/**
 * Vista: detalle_examen.php
 * Propósito: Mostrar información de un examen y opciones para inscribirse.
 * Estructura esperada para `exam`:
 *  - nombre:string, fecha:string, lugar:string, id:int
 * Flujo técnico:
 *  - El enlace de inscripción apunta a `Router.php?r=confirmar_inscripcion_examen&data=`; el controlador debe validar cupos y permisos.
 * Seguridad:
 *  - No confiar en datos de disponibilidad enviados por el cliente; siempre validar en servidor.
 */
class DetalleExamenVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Detalle del examen',
            'exam' => []
        ];
        $data = $defaults;
        // Decodifica y fusiona `data` (JSON) pasada por GET; no usar para decisiones de negocio sin verificación.
        if (isset($_GET['data'])) {
            $incoming = json_decode($_GET['data'], true);
            if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
        }
        include __DIR__ . '/header.php';
        ?>
        <main class="app-container">
            <div class="app-vista-card">
                <h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
                <p class="mt-2">Nombre: <?php echo htmlspecialchars($data['exam']['nombre']); ?></p>
                <p>Fecha: <?php echo htmlspecialchars($data['exam']['fecha']); ?></p>
                <p>Lugar: <?php echo htmlspecialchars($data['exam']['lugar']); ?></p>

                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/Router.php?r=confirmar_inscripcion_examen&data=" class="app-vista-button app-vista-button--primary">Inscribirme</a>
                    <a href="<?= BASE_URL ?>/Router.php?r=index"class="app-vista-button app-vista-button--secondary">Volver</a>
                </div>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

DetalleExamenVista::mostrar();
