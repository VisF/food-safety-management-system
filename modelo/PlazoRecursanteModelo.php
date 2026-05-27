<?php
class PlazoRecursanteModelo {
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
        $sql = "CREATE TABLE IF NOT EXISTS plazos_recursantes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            inscripcion_id INT UNSIGNED NOT NULL,
            fecha_examen_desaprobado DATE NOT NULL,
            fecha_limite DATE NOT NULL,
            eligible TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY pr_insc_idx (inscripcion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }

    public function crearPlazoRecursante(int $idInscripcionOriginal, string $fechaExamenDesaprobado): array {
        if (!$this->db) return [];
        $fecha_limite = date('Y-m-d', strtotime($fechaExamenDesaprobado . ' +3 months'));
        $ins = $this->db->prepare('INSERT INTO plazos_recursantes (inscripcion_id, fecha_examen_desaprobado, fecha_limite, eligible) VALUES (:iid, :fex, :flim, 0)');
        $ok = $ins->execute([':iid' => $idInscripcionOriginal, ':fex' => $fechaExamenDesaprobado, ':flim' => $fecha_limite]);
        if ($ok) return ['id' => (int)$this->db->lastInsertId(), 'inscripcion_id' => $idInscripcionOriginal, 'fecha_limite' => $fecha_limite];
        return [];
    }

    public function verificarElegibilidad(int $idUsuario): bool {
        if (!$this->db) return false;

        // Buscar resultados desaprobados en los últimos 3 meses para el usuario
        $sql = 'SELECT re.* FROM resultado_examen re
                JOIN inscripciones i ON re.inscripcion_id = i.id
                WHERE i.usuario_id = :uid AND re.aprobado = 0 AND re.fecha_resultado >= DATE_SUB(NOW(), INTERVAL 3 MONTH) LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $idUsuario]);
        $row = $stmt->fetch();
        return (bool)$row;
    }

    public function listarRecursantesVigentes(): array {
        if (!$this->db) return [];
        $stmt = $this->db->query('SELECT * FROM plazos_recursantes WHERE fecha_limite >= CURDATE()');
        return $stmt->fetchAll();
    }
}

