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
    public function guardarCertificado(array $data)
    {
        if (!$this->db) {
            return false;
        }

        $usuarioId = (int)($data['usuario_id'] ?? 0);

        $ruta = $data['ruta'] ?? '';

        $nombreOriginal =
            $data['nombre_original']
            ?? basename($ruta);

        if ($usuarioId <= 0 || !$ruta) {
            return false;
        }

        $sql = "
            INSERT INTO documentos (
                usuario_id,
                tipo_documento,
                nombre_original,
                ruta_archivo,
                estado,
                fecha_subida
            )
            VALUES (
                :usuario_id,
                'moodle',
                :nombre_original,
                :ruta_archivo,
                'pendiente',
                NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nombre_original' => $nombreOriginal,
            ':ruta_archivo' => $ruta
        ]);

        if ($ok) {

            return [
                'id' => (int)$this->db->lastInsertId(),
                'usuario_id' => $usuarioId,
                'tipo_documento' => 'moodle',
                'ruta' => $ruta
            ];
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
    public function validarCursoCompletado(int $idUsuario,int $idCurso): bool {
        if (!$this->db) {
            return false;
        }
        $sql = "
            SELECT d.*
            FROM documentos d
            WHERE d.usuario_id = :uid
            AND d.tipo_documento = :tipo
            AND d.estado = 'aprobado'
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':uid' => $idUsuario,
            ':tipo' => 'moodle'
        ]);

        return (bool)$stmt->fetch();
    }
}
