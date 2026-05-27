<?php
class MoodleModelo {
    private ?\PDO $db = null;

    public function __construct() {
        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->db = Connection::getPDO();
        }
    }

    // Guarda metadata sobre un certificado Moodle usando la tabla `documentos`
    // Espera: ['id_inscripcion' => int, 'ruta' => string, 'tipo' => string]
    public function guardarCertificado(array $data) {
        if (!$this->db) return false;
        $id_inscripcion = (int)($data['id_inscripcion'] ?? 0);
        $ruta = $data['ruta'] ?? '';
        $tipo = $data['tipo'] ?? 'certificado_moodle';
        if ($id_inscripcion <= 0 || !$ruta) return false;

        $sql = 'INSERT INTO documentos (id_inscripcion, tipo_documento, ruta_archivo, validado, fecha_subida) VALUES (:iid, :tipo, :ruta, 0, NOW())';
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':iid' => $id_inscripcion, ':tipo' => $tipo, ':ruta' => $ruta]);
        if ($ok) {
            return ['id' => (int)$this->db->lastInsertId(), 'id_inscripcion' => $id_inscripcion, 'ruta' => $ruta, 'tipo' => $tipo];
        }
        return false;
    }

    // Descarga un certificado desde una URL y lo guarda en uploads/documentos
    public function descargarCertificado(string $url): ?string {
        try {
            $contents = @file_get_contents($url);
            if ($contents === false) return null;
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'pdf';
            $nombre = uniqid('moodle_cert_') . '.' . $ext;
            $carpeta = __DIR__ . '/../uploads/documentos';
            if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
            $ruta = $carpeta . DIRECTORY_SEPARATOR . $nombre;
            file_put_contents($ruta, $contents);
            return $ruta;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // Verifica hash SHA256 del archivo; retorna true si el archivo existe y el hash coincide (si se proporciona)
    public function verificarHash(string $filePath, ?string $expectedHash = null): bool {
        if (!file_exists($filePath)) return false;
        $hash = hash_file('sha256', $filePath);
        if ($expectedHash !== null) {
            return hash_equals($expectedHash, $hash);
        }
        // Si no hay hash esperado, consideramos que la verificación básica pasó
        return $hash !== '';
    }

    // Valida que el usuario completó el curso verificando existencia de documento tipo certificado_moodle validado
    public function validarCursoCompletado(int $idUsuario, int $idCurso): bool {
        if (!$this->db) return false;

        $sql = 'SELECT d.* FROM documentos d
                JOIN inscripciones i ON d.id_inscripcion = i.id
                WHERE i.usuario_id = :uid AND i.curso_id = :cid AND d.tipo_documento = :tipo AND d.validado = 1
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $idUsuario, ':cid' => $idCurso, ':tipo' => 'certificado_moodle']);
        $row = $stmt->fetch();
        return (bool)$row;
    }
}
