<?php
class ReservaModelo {
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
        $sql = "CREATE TABLE IF NOT EXISTS reservas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            fecha_curso_id INT UNSIGNED NOT NULL,
            usuario_id INT UNSIGNED NOT NULL,
            estado ENUM('reservado','cancelado','expirado') NOT NULL DEFAULT 'reservado',
            fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion DATETIME NOT NULL,
            token VARCHAR(128) NOT NULL,
            PRIMARY KEY (id),
            KEY res_fecha_idx (fecha_curso_id),
            KEY res_usuario_idx (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }

    public function crearReserva(int $idFechaCurso, int $idUsuario): array {
        if (!$this->db) return [];

        // Contar reservas activas
        $act = $this->contarReservasActivas($idFechaCurso);
        // Obtener cupos de fecha_cursos
        $stmt = $this->db->prepare('SELECT cupos FROM fecha_cursos WHERE id = :id AND activo = 1');
        $stmt->execute([':id' => $idFechaCurso]);
        $row = $stmt->fetch();
        $cupos = $row ? (int)$row['cupos'] : 0;
        if ($cupos <= 0 || $act >= $cupos) {
            return ['success' => false, 'message' => 'No hay cupos disponibles'];
        }

        $token = bin2hex(random_bytes(16));
        $expiracion = date('Y-m-d H:i:s', time() + 900); // 15 minutos

        $ins = $this->db->prepare('INSERT INTO reservas (fecha_curso_id, usuario_id, estado, fecha_expiracion, token) VALUES (:fc, :uid, "reservado", :exp, :token)');
        $ok = $ins->execute([':fc' => $idFechaCurso, ':uid' => $idUsuario, ':exp' => $expiracion, ':token' => $token]);
        if ($ok) {
            return ['success' => true, 'id' => (int)$this->db->lastInsertId(), 'token' => $token, 'fecha_expiracion' => $expiracion];
        }
        return ['success' => false, 'message' => 'Error al crear reserva'];
    }

    public function cancelarReserva(int $idReserva): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare('UPDATE reservas SET estado = "cancelado" WHERE id = :id');
        return (bool)$stmt->execute([':id' => $idReserva]);
    }

    public function contarReservasActivas(int $idFechaCurso): int {
        if (!$this->db) return 0;
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM reservas WHERE fecha_curso_id = :fc AND estado = "reservado" AND fecha_expiracion > NOW()');
        $stmt->execute([':fc' => $idFechaCurso]);
        $row = $stmt->fetch();
        return $row ? (int)$row['c'] : 0;
    }

    public function liberarReservaExpirada(): int {
        if (!$this->db) return 0;
        $upd = $this->db->prepare('UPDATE reservas SET estado = "expirado" WHERE estado = "reservado" AND fecha_expiracion <= NOW()');
        $upd->execute();
        return $upd->rowCount();
    }
}

