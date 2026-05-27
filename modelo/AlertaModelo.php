<?php
class AlertaModelo {
    private ?\PDO $db = null;

    public function __construct() {
        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->db = Connection::getPDO();
        }
        $this->ensureTable();
    }

    private function ensureTable(): void {
        if (!$this->db) return;
        $sql = "CREATE TABLE IF NOT EXISTS alertas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT UNSIGNED NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            payload JSON NULL,
            fecha_programada DATETIME NOT NULL,
            enviada TINYINT(1) NOT NULL DEFAULT 0,
            fecha_envio DATETIME NULL,
            PRIMARY KEY (id),
            KEY al_usuario_idx (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }

    public function programarAlerta(int $idUsuario, string $tipo, array $payload, string $fechaProgramada): int {
        if (!$this->db) return 0;
        $stmt = $this->db->prepare('INSERT INTO alertas (usuario_id, tipo, payload, fecha_programada) VALUES (:uid, :tipo, :payload, :fp)');
        $ok = $stmt->execute([':uid' => $idUsuario, ':tipo' => $tipo, ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), ':fp' => $fechaProgramada]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    public function obtenerAlertasPendientes(): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare('SELECT * FROM alertas WHERE enviada = 0 AND fecha_programada <= NOW() ORDER BY fecha_programada ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function marcarEnviada(int $idAlerta): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare('UPDATE alertas SET enviada = 1, fecha_envio = NOW() WHERE id = :id');
        return (bool)$stmt->execute([':id' => $idAlerta]);
    }
}

