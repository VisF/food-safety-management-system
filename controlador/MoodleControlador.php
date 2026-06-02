<?php
/**
 * MoodleControlador - Integración ligera con Moodle
 *
 * Responsabilidades:
 * - Descargar y registrar certificados desde Moodle
 * - Importar certificados ya subidos
 * - Listar certificados pendientes para validación
 *
 * Dependencias: MoodleModelo, DocumentoModelo
 */
require_once __DIR__ . '/../modelo/MoodleModelo.php';
require_once __DIR__ . '/../modelo/DocumentoModelo.php';

class MoodleControlador {
    protected $moodleModel;
    protected $documentoModel;

    public function __construct() {
        $this->moodleModel = new MoodleModelo();
        $this->documentoModel = new DocumentoModelo();
    }

    // Endpoint para recibir certificado (p.e. subida manual o webhook)
    // $payload debe contener: ['id_inscripcion' => int, 'url' => string]
    public function webhookRecepcionCertificado(array $payload) {
        $id_inscripcion = (int)($payload['id_inscripcion'] ?? 0);
        $url = $payload['url'] ?? '';
        // Validación mínima del payload recibido por webhook
        if ($id_inscripcion <= 0 || !$url) return ['success' => false, 'message' => 'Payload inválido'];

        $ruta = $this->moodleModel->descargarCertificado($url);
        if (!$ruta) return ['success' => false, 'message' => 'No se pudo descargar certificado'];

        $res = $this->moodleModel->guardarCertificado(['id_inscripcion' => $id_inscripcion, 'ruta' => $ruta, 'tipo' => 'certificado_moodle']);
        if ($res) return ['success' => true, 'documento' => $res];
        return ['success' => false, 'message' => 'Error al guardar metadata del certificado'];
    }

    // Importar certificado manualmente (archivo ya subido)
    public function importarCertificadoManual(int $idInscripcion, string $filePath) {
        // Validar existencia del archivo antes de delegar a modelo
        if ($idInscripcion <= 0 || !file_exists($filePath)) return ['success' => false, 'message' => 'Parámetros inválidos'];
        $res = $this->moodleModel->guardarCertificado(['id_inscripcion' => $idInscripcion, 'ruta' => $filePath, 'tipo' => 'certificado_moodle']);
        return $res ? ['success' => true, 'documento' => $res] : ['success' => false, 'message' => 'Error al guardar certificado'];
    }

    public function listarCertificadosPendientes(): array {
        // Reutilizar DocumentoModelo para obtener documentos tipo 'certificado_moodle' pendientes
        $docs = $this->documentoModel->obtenerPorTipo('certificado_moodle');
        $pendientes = array_filter($docs, function($d){ return (int)($d['validado'] ?? 0) === 0; });
        return array_values($pendientes);
    }
}

