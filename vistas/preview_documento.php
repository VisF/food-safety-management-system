<?php
declare(strict_types=1);
class PreviewDocumentoVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Previsualizar documento',
            'fileName' => ''
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
                <p class="mt-2 text-sm text-gray-600">Archivo: <?php echo htmlspecialchars($data['fileName']); ?></p>

                <div class="mt-4">
                    <iframe src="/uploads/<?php echo rawurlencode($data['fileName']); ?>" class="w-full h-96 border" title="Preview"></iframe>
                </div>

                <div class="mt-4">
                    <a href="/Router.php?r=subir_archivo" class="app-vista-button app-vista-button--secondary">Volver</a>
                    <a href="/Router.php?r=documento_subido" class="app-vista-button app-vista-button--primary">Confirmar</a>
                </div>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

PreviewDocumentoVista::mostrar();
