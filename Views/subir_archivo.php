<?php
declare(strict_types=1);
/**
 * Vista: Subir archivo
 * Propósito: Interfaz mínima para subir un archivo (campo POST `archivo`).
 * Técnica/entregables:
 *  - Formulario con `enctype="multipart/form-data"` y `method="post"`.
 *  - Espera que el controlador maneje `$_FILES['archivo']` usando `is_uploaded_file()` y `move_uploaded_file()`.
 * Recomendaciones de seguridad:
 *  - Validar mime type y extensión, comprobar tamaño máximo (p.ej. 5MB) y usar nombres canónicos en servidor.
 *  - Implementar CSRF token en formularios antes de producción.
 *  - No confiar en valores de `$_GET['data']` sin validarlos.
 */
class SubirArchivoVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Subir archivo',
            'description' => 'Seleccione un archivo y complete los campos requeridos.'
        ];
        $data = $defaults;
        // Si se recibe `data` en GET (JSON), se puede inicializar texto/labels. Validar antes de usar.
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

                <form action="<?= BASE_URL ?>/manipulacionDeAlimentos/documento_subido" method="post" enctype="multipart/form-data" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Archivo</label>
                    <input type="file" name="archivo" class="mt-1 block w-full" required />

                    <button type="submit" class="app-vista-button app-vista-button--primary mt-4">Subir archivo</button>
                    <a href="<?= BASE_URL ?>/manipulacionDeAlimentos/subida_documentacion" class="app-vista-button app-vista-button--secondary mt-4 inline-block">Cancelar</a>
                </form>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}

SubirArchivoVista::mostrar();
