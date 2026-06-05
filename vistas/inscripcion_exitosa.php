<?php
declare(strict_types=1);
/**
 * Vista: inscripcion_exitosa.php
 * Propósito: Mensaje de confirmación tras completar una inscripción a examen.
 * Entradas: GET 'data' con 'title' y 'message' opcionalmente.
 * Nota: usar esta vista sólo para UX; la lógica de negocio reside en el controlador.
 */
class InscripcionExitosaVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Inscripción exitosa',
            'message' => 'Tu inscripción fue registrada correctamente.',
        ];
        $data = $defaults;
        // Permite pasar `data` por GET para títulos/mensajes en pruebas; producción debe usar datos del controlador.
        if (isset($_GET['data'])) {
            $incoming = json_decode($_GET['data'], true);
            if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
        }
        include __DIR__ . '/header.php';
        ?>
        <main class="container" style="padding-top:1rem;padding-bottom:1rem;">
            <section class="card" style="text-align:center;">
                <h1 style="margin:0;font-size:1.5rem;font-weight:800;">
                    <?php echo htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <p style="margin:.75rem 0 0;color:var(--color-muted);">
                    <?php echo htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div style="margin-top:1.25rem;display:flex;justify-content:center;">
                    <a href="<?= BASE_URL ?>/Router.php?r=index"class="btn" role="button">Ir al inicio</a>
                </div>
            </section>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

InscripcionExitosaVista::mostrar();
