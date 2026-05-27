<?php
declare(strict_types=1);
class SubirArchivoVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Subir archivo',
            'description' => 'Seleccione un archivo y complete los campos requeridos.'
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
                <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($data['description']); ?></p>

                <form action="/Router.php?r=documento_subido" method="post" enctype="multipart/form-data" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Archivo</label>
                    <input type="file" name="archivo" class="mt-1 block w-full" required />

                    <button type="submit" class="app-vista-button app-vista-button--primary mt-4">Subir archivo</button>
                    <a href="/Router.php?r=subida_documentacion" class="app-vista-button app-vista-button--secondary mt-4 inline-block">Cancelar</a>
                </form>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

SubirArchivoVista::mostrar();
