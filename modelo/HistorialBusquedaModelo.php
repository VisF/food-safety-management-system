<?php
class HistorialBusquedaModelo {
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
        $sql = "CREATE TABLE IF NOT EXISTS historial_busquedas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            inspector_id INT UNSIGNED NOT NULL,
            criterio TEXT NOT NULL,
            result_count INT NOT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hb_inspector_idx (inspector_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }

    public function registrarBusqueda(int $idInspector, array $criterio, int $resultCount, string $ip): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare('INSERT INTO historial_busquedas (inspector_id, criterio, result_count, ip) VALUES (:iid, :criterio, :rc, :ip)');
        return (bool)$stmt->execute([':iid' => $idInspector, ':criterio' => json_encode($criterio, JSON_UNESCAPED_UNICODE), ':rc' => $resultCount, ':ip' => $ip]);
    }

    public function obtenerPorInspector(int $idInspector): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare('SELECT * FROM historial_busquedas WHERE inspector_id = :iid ORDER BY fecha DESC LIMIT 200');
        $stmt->execute([':iid' => $idInspector]);
        return $stmt->fetchAll();
    }
}

