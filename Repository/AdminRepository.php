<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

require_once __DIR__.'/../Constant/EstadoTramite.php';

class AdminRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }


    // ============================
    // Dashboard
    // ============================

    public function obtenerEstadisticas(): array
    {
        $estadisticas = [];

        // Usuarios
        $estadisticas['total_usuarios'] = $this->contar('usuarios');
        $estadisticas['usuarios_activos'] = $this->contar('usuarios', 'activo = 1');

        // Inscripciones
        $estadisticas['total_inscripciones'] = $this->contar('inscripciones');

        $estadisticas['inscripciones_pendientes'] = (int) $this->conexion
            ->query("
                SELECT COUNT(*)
                FROM inscripciones
                WHERE estado_tramite_id IN (
                    " . EstadoTramite::PENDIENTE . ",
                    " . EstadoTramite::DOCUMENTACION_APROBADA . ",
                    " . EstadoTramite::INSCRIPTO_EXAMEN . "
                )
            ")
            ->fetchColumn();

        $estadisticas['inscripciones_aprobadas'] = $this->contar(
            'inscripciones',
            'estado_tramite_id = ' . EstadoTramite::APROBADO
        );

        $estadisticas['cursos_activos'] = (int) $this->conexion
            ->query("
                SELECT COUNT(*)
                FROM inscripciones
                WHERE tipo_inscripcion_id = 1
                AND fecha_fin_curso >= CURDATE()
            ")
            ->fetchColumn();

        // Exámenes
        $estadisticas['total_examenes'] = $this->contar('examenes');

        $aprobados = $this->contar('resultado_examen', 'aprobado = 1');
        $reprobados = $this->contar('resultado_examen', 'aprobado = 0');

        $estadisticas['tasa_aprobacion'] =
            ($aprobados + $reprobados) > 0
                ? round(($aprobados / ($aprobados + $reprobados)) * 100, 2)
                : 0.0;

        // Carnets
        $estadisticas['carnets_emitidos'] = $this->contar('carnets');

        return $estadisticas;
    }

    public function obtenerActividadReciente(int $limite = 20): array
    {
        $limite = max(1, min(100, $limite));

        $sql = "
            SELECT
                i.id,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre,
                u.dni,
                LOWER(et.nombre) AS estado_class,
                UPPER(REPLACE(et.nombre, '_', ' ')) AS estado
            FROM inscripciones i
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            ORDER BY i.fecha_inscripcion DESC
            LIMIT :limite
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

//Metodos privados
    private function contar(string $tabla, string $where = ''): int
        {
            $sql = "SELECT COUNT(*) FROM {$tabla}";

            if ($where !== '') {
                $sql .= " WHERE {$where}";
            }

            return (int) $this->conexion
                ->query($sql)
                ->fetchColumn();
        }
  
}   