<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

class ReporteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    // =====================================================
    // ACTIVIDAD Y AUDITORÍA
    // =====================================================

    /**
     * Obtiene la actividad reciente.
     */
    public function obtenerActividadReciente(
        int $limite = 50
    ): array
    {
        $sql = "
            SELECT
                a.*,
                u.nombre,
                u.apellido
            FROM auditoria_acciones a
            LEFT JOIN usuarios u
                ON u.id = a.usuario_id
            ORDER BY a.fecha DESC
            LIMIT :limite
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(
            ':limite',
            $limite,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una actividad por ID.
     */
    public function obtenerDetalleActividad(
        int $idAuditoria
    ): ?array
    {
        $sql = "
            SELECT
                a.*,
                u.nombre,
                u.apellido
            FROM auditoria_acciones a
            LEFT JOIN usuarios u
                ON u.id = a.usuario_id
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idAuditoria
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene la auditoría de un usuario.
     */
    public function obtenerAuditoriaUsuario(
        int $usuarioId
    ): array
    {
        $sql = "
            SELECT
                *
            FROM auditoria_acciones
            WHERE usuario_id = :usuario
            ORDER BY fecha DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la auditoría de una tabla.
     */
    public function obtenerAuditoriaTabla(
        string $tabla
    ): array
    {
        $sql = "
            SELECT
                *
            FROM auditoria_acciones
            WHERE tabla = :tabla
            ORDER BY fecha DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':tabla' => $tabla
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un usuario.
     */
    public function obtenerUsuario(
        int $id
    ): ?array
    {
        $sql = "
            SELECT
                id,
                nombre,
                apellido,
                dni,
                email
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }
        // =====================================================
    // REPORTES PERSONALIZADOS
    // =====================================================

    /**
     * Obtiene las inscripciones según los filtros.
     */
    public function obtenerReporteInscripciones(
        array $filtros = []
    ): array
    {
        $sql = "
            SELECT
                i.id,
                i.usuario_id,
                u.nombre,
                u.apellido,
                i.curso_id,
                i.fecha_inscripcion,
                et.nombre AS estado
            FROM inscripciones i
            LEFT JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
        ";

        $where = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(i.fecha_inscripcion) >= :desde";
            $params[':desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(i.fecha_inscripcion) <= :hasta";
            $params[':hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = "et.nombre = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['id_curso'])) {
            $where[] = "i.curso_id = :curso";
            $params[':curso'] = $filtros['id_curso'];
        }

        if (!empty($filtros['id_usuario'])) {
            $where[] = "i.usuario_id = :usuario";
            $params[':usuario'] = $filtros['id_usuario'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= "
            ORDER BY
                i.fecha_inscripcion DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los usuarios.
     */
    public function obtenerReporteUsuarios(): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                apellido,
                email,
                dni,
                creado_en AS fecha_creacion
            FROM usuarios
            ORDER BY id DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los carnets.
     */
    public function obtenerReporteCarnets(): array
    {
        $sql = "
            SELECT
                id,
                inscripcion_id,
                numero_carnet,
                fecha_emision,
                fecha_vencimiento,
                vigente
            FROM carnets
            ORDER BY fecha_emision DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los resultados de examen.
     */
    public function obtenerReporteExamenes(): array
    {
        $sql = "
            SELECT
                id,
                inscripcion_id,
                nota,
                aprobado,
                fecha_resultado
            FROM resultado_examen
            ORDER BY fecha_resultado DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
        // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Cantidad total de usuarios.
     */
    public function contarUsuarios(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM usuarios")
            ->fetchColumn();
    }

    /**
     * Cantidad de usuarios activos.
     */
    public function contarUsuariosActivos(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM usuarios
            WHERE activo = 1
        ";

        try {

            return (int) $this->pdo
                ->query($sql)
                ->fetchColumn();

        } catch (Throwable $e) {

            return $this->contarUsuarios();
        }
    }

    /**
     * Cantidad de inscripciones activas.
     */
    public function contarInscripcionesActivas(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM inscripciones
            WHERE estado_tramite_id
            NOT IN (:estado1,:estado2)
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':estado1' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':estado2' => EstadoTramite::APROBADO
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cantidad de exámenes pendientes.
     */
    public function contarExamenesPendientes(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM inscripciones i
            LEFT JOIN resultado_examen r
                ON r.inscripcion_id = i.id
            WHERE r.id IS NULL
        ";

        return (int) $this->pdo
            ->query($sql)
            ->fetchColumn();
    }

    /**
     * Cantidad de carnets vigentes.
     */
    public function contarCarnetsVigentes(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM carnets
                WHERE vigente = 1
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de exámenes aprobados.
     */
    public function contarExamenesAprobados(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM resultado_examen
                WHERE aprobado = 1
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de exámenes reprobados.
     */
    public function contarExamenesReprobados(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM resultado_examen
                WHERE aprobado = 0
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad total de exámenes.
     */
    public function contarExamenes(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM resultado_examen
            ")
            ->fetchColumn();
    }

    /**
     * Promedio de duración de los trámites.
     */
    public function obtenerPromedioTiempoTramite(): float
    {
        $sql = "
            SELECT AVG(
                DATEDIFF(
                    IFNULL(
                        fecha_ultima_modificacion,
                        NOW()
                    ),
                    fecha_inscripcion
                )
            )
            FROM inscripciones
            WHERE estado_tramite_id
            IN (:estado1,:estado2)
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':estado1' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':estado2' => EstadoTramite::APROBADO
        ]);

        return (float) (
            $stmt->fetchColumn() ?: 0
        );
    }
        // =====================================================
    // ESTADÍSTICAS ESPECÍFICAS
    // =====================================================

    /**
     * Estadísticas por rol.
     */
    public function obtenerEstadisticasPorRol(): array
    {
        $sql = "
            SELECT
                r.nombre,
                COUNT(ur.usuario_id) AS cantidad
            FROM roles r
            LEFT JOIN usuario_roles ur
                ON ur.rol_id = r.id
            GROUP BY
                r.id,
                r.nombre
            ORDER BY
                cantidad DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estadísticas por estado del trámite.
     */
    public function obtenerEstadisticasPorEstado(): array
    {
        $sql = "
            SELECT
                et.nombre,
                COUNT(i.id) AS cantidad
            FROM estados_tramite et
            LEFT JOIN inscripciones i
                ON i.estado_tramite_id = et.id
            GROUP BY
                et.id,
                et.nombre
            ORDER BY
                et.id
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estadísticas por curso.
     */
    public function obtenerEstadisticasPorCurso(): array
    {
        $sql = "
            SELECT
                c.nombre AS curso,
                COUNT(i.id) AS total_inscripciones,
                SUM(CASE WHEN r.aprobado = 1 THEN 1 ELSE 0 END) AS aprobados,
                SUM(CASE WHEN r.aprobado = 0 THEN 1 ELSE 0 END) AS reprobados
            FROM cursos c
            LEFT JOIN inscripciones i
                ON i.curso_id = c.id
            LEFT JOIN resultado_examen r
                ON r.inscripcion_id = i.id
            GROUP BY
                c.id,
                c.nombre
            ORDER BY
                total_inscripciones DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cantidad de carnets emitidos.
     */
    public function contarCarnetsEmitidos(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM carnets
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de carnets vencidos.
     */
    public function contarCarnetsVencidos(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM carnets
                WHERE vigente = 0
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de trámites sin carnet.
     */
    public function contarCarnetsEnTramite(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM inscripciones i
            LEFT JOIN carnets c
                ON c.inscripcion_id = i.id
            WHERE c.id IS NULL
        ";

        return (int) $this->pdo
            ->query($sql)
            ->fetchColumn();
    }

    /**
     * Documentos pendientes.
     */
    public function contarDocumentosPendientes(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM documentos
                WHERE estado = 'pendiente'
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de documentos pendientes por tipo.
     */
    public function obtenerDocumentosPendientesPorTipo(): array
    {
        $sql = "
            SELECT
                tipo_documento,
                COUNT(*) AS cantidad
            FROM documentos
            WHERE estado = 'pendiente'
            GROUP BY
                tipo_documento
            ORDER BY
                tipo_documento
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inscripciones activas agrupadas por estado.
     */
    public function obtenerInscripcionesActivas(): array
    {
        $sql = "
            SELECT
                et.nombre AS estado,
                COUNT(i.id) AS cantidad
            FROM inscripciones i
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            WHERE i.estado_tramite_id
                NOT IN (:cancelado,:rechazado)
            GROUP BY
                et.nombre
            ORDER BY
                et.nombre
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':cancelado' => EstadoTramite::CANCELADO,
            ':rechazado' => EstadoTramite::RECHAZADO
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        // =====================================================
    // EXPORTACIONES
    // =====================================================

    /**
     * Obtiene los registros listos para exportar a DIPA.
     */
    public function obtenerDatosParaDIPA(): array
    {
        $sql = "
            SELECT
                i.id,
                u.dni,
                u.nombre,
                u.apellido,
                u.email,
                i.fecha_inscripcion
            FROM inscripciones i
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN carnets c
                ON c.inscripcion_id = i.id
            WHERE i.estado_tramite_id = :estado
            AND c.id IS NULL
            ORDER BY
                i.fecha_inscripcion
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':estado' => EstadoTramite::APROBADO
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un examen.
     */
    public function obtenerExamen(
        int $idExamen
    ): ?array
    {
        $sql = "
            SELECT
                *
            FROM examenes
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idExamen
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene los resultados de un examen.
     */
    public function obtenerResultadosExamen(
        int $idExamen
    ): array
    {
        $sql = "
            SELECT
                u.dni,
                u.nombre,
                u.apellido,
                r.nota,
                r.aprobado,
                r.fecha_resultado
            FROM resultado_examen r
            INNER JOIN inscripciones i
                ON i.id = r.inscripcion_id
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            WHERE r.id_examen = :id
            ORDER BY
                u.apellido,
                u.nombre
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idExamen
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un reporte por tipo.
     */
    public function obtenerReporte(
        string $tipo,
        array $filtros = []
    ): array
    {
        return match ($tipo) {

            'inscripciones' =>
                $this->obtenerReporteInscripciones(
                    $filtros
                ),

            'usuarios' =>
                $this->obtenerReporteUsuarios(),

            'carnets' =>
                $this->obtenerReporteCarnets(),

            'examenes' =>
                $this->obtenerReporteExamenes(),

            default => []
        };
    }

    /**
     * Verifica si existe un examen.
     */
    public function existeExamen(
        int $idExamen
    ): bool
    {
        $sql = "
            SELECT COUNT(*)
            FROM examenes
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idExamen
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }
}