<?php
declare(strict_types=1);
class DetalleExamenVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Detalle del examen',
            'exam' => []
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
                <p class="mt-2">Nombre: <?php echo htmlspecialchars($data['exam']['nombre']); ?></p>
                <p>Fecha: <?php echo htmlspecialchars($data['exam']['fecha']); ?></p>
                <p>Lugar: <?php echo htmlspecialchars($data['exam']['lugar']); ?></p>

                <div class="mt-4">
                    <a href="/Router.php?r=confirmar_inscripcion_examen&data=" class="app-vista-button app-vista-button--primary">Inscribirme</a>
                    <a href="/Router.php?r=index" class="app-vista-button app-vista-button--secondary">Volver</a>
                </div>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

DetalleExamenVista::mostrar();
