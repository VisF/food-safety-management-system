<?php

class PlazoRecursanteRepository
{
    private ?PDO $db = null;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->db = Connection::getPDO();
        $this->ensureTable();
    }

    // Ejecuta ensure table.
    private function ensureTable(): void
    {
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

    // Crea la operaci?n correspondiente.
    public function crear(
        int $inscripcionId,
        string $fechaExamen,
        string $fechaLimite
    ): array {

        $stmt = $this->db->prepare("
            INSERT INTO plazos_recursantes
            (inscripcion_id, fecha_examen_desaprobado, fecha_limite, eligible)
            VALUES
            (:iid,:fex,:flim,0)
        ");

        $ok = $stmt->execute([
            ':iid' => $inscripcionId,
            ':fex' => $fechaExamen,
            ':flim' => $fechaLimite
        ]);

        if (!$ok) {
            return [];
        }

        return [
            'id' => (int)$this->db->lastInsertId(),
            'inscripcion_id' => $inscripcionId,
            'fecha_limite' => $fechaLimite
        ];
    }

    // Ejecuta verificar elegibilidad.
    public function verificarElegibilidad(int $usuarioId): bool
    {
        $sql = "
            SELECT 1
            FROM resultado_examen re
            INNER JOIN inscripciones i
                ON re.inscripcion_id = i.id
            WHERE i.usuario_id = :uid
              AND re.aprobado = 0
              AND re.fecha_resultado >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid' => $usuarioId
        ]);

        return (bool)$stmt->fetch();
    }

    // Lista vigentes.
    public function listarVigentes(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM plazos_recursantes
            WHERE fecha_limite >= CURDATE()
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
