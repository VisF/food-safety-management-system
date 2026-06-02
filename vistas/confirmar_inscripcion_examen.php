<?php
declare(strict_types=1);
/**
 * Vista: confirmar_inscripcion_examen.php
 * Propósito: Confirmación antes de completar la inscripción a un examen.
 * Entradas: GET 'data' (JSON) puede contener 'examName'.
 * Nota: la acción del formulario redirige a `Router.php?r=inscripcion_exitosa`.
 */
class ConfirmarInscripcionExamenVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Confirmar inscripción',
            'examName' => ''
        ];
        $data = $defaults;
        // Decodifica `data` JSON pasado por GET para mostrar contextualización; el backend debe autorizar la acción.
        if (isset($_GET['data'])) {
            $incoming = json_decode($_GET['data'], true);
            if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
        }
        include __DIR__ . '/header.php';
        ?>
        <main class="app-container">
            <div class="app-vista-card">
                <h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($data['title']); ?></h1>
                <p class="mt-2">Desea inscribirse al examen: <?php echo htmlspecialchars($data['examName']); ?>?</p>

                <form action="Router.php?r=inscripcion_exitosa" method="post" class="mt-4">
                    <button type="submit" class="app-vista-button app-vista-button--primary">Confirmar inscripción</button>
                    <a href="Router.php?r=detalle_examen" class="app-vista-button app-vista-button--secondary">Cancelar</a>
                </form>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

ConfirmarInscripcionExamenVista::mostrar();
