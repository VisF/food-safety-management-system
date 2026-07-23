<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

class TramiteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    // =====================================================
    // DETALLE DEL TRÁMITE
    // =====================================================

    /**
     * Obtiene una inscripción.
     */
    public function obtenerInscripcion(
        int $idInscripcion
    ): ?array
    {
        $sql = "
            SELECT *
            FROM inscripciones
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene un estado.
     */
    public function obtenerEstado(
        int $idEstado
    ): ?array
    {
        $sql = "
            SELECT
                id,
                nombre,
                descripcion
            FROM estados_tramite
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idEstado
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene estadísticas de documentación.
     */
    public function obtenerEstadisticasDocumentacion(
        int $usuarioId
    ): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(
                    CASE
                        WHEN estado = 'aprobado'
                        THEN 1
                        ELSE 0
                    END
                ) AS validados
            FROM documentos
            WHERE usuario_id = :usuario
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC)
            ?: [
                'total' => 0,
                'validados' => 0
            ];
    }

    /**
     * Obtiene el último resultado de examen.
     */
    public function obtenerResultadoExamen(
        int $idInscripcion
    ): ?array
    {
        $sql = "
            SELECT *
            FROM resultado_examen
            WHERE inscripcion_id = :id
            ORDER BY fecha_resultado DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene el último carnet emitido.
     */
    public function obtenerCarnet(
        int $idInscripcion
    ): ?array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE inscripcion_id = :id
            ORDER BY fecha_emision DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }
    // =====================================================
    // HISTORIAL
    // =====================================================

    /**
     * Obtiene el historial de un trámite.
     */
    public function obtenerHistorialTramite(
        int $idInscripcion
    ): array
    {
        $sql = "
            SELECT
                h.id,
                h.inscripcion_id,
                h.estado_anterior_id,
                h.estado_nuevo_id,
                h.fecha_cambio,
                h.observaciones,
                ea.nombre AS estado_anterior,
                en.nombre AS estado_nuevo,
                u.nombre AS usuario_admin_nombre,
                u.apellido AS usuario_admin_apellido
            FROM historial_tramite h

            LEFT JOIN estados_tramite ea
                ON ea.id = h.estado_anterior_id

            INNER JOIN estados_tramite en
                ON en.id = h.estado_nuevo_id

            LEFT JOIN usuarios u
                ON u.id = h.usuario_admin_id

            WHERE h.inscripcion_id = :id

            ORDER BY h.fecha_cambio ASC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================================
    // COMPROBANTES
    // =====================================================

    /**
     * Obtiene un comprobante descargable.
     */
    public function obtenerComprobante(
        int $idInscripcion
    ): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.codigo_comprobante,
                c.fecha_emision,
                c.ruta_pdf,
                c.vigente,
                i.usuario_id,
                i.estado_tramite_id,
                u.nombre,
                u.apellido,
                u.dni

            FROM comprobantes_tramite c

            INNER JOIN inscripciones i
                ON i.id = c.inscripcion_id

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            WHERE c.inscripcion_id = :id

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    // =====================================================
    // CARNETS
    // =====================================================

    /**
     * Obtiene un carnet por inscripción.
     */
    public function obtenerCarnetPorInscripcion(
        int $idInscripcion
    ): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.numero_carnet,
                c.fecha_emision,
                c.fecha_vencimiento,
                c.ruta_pdf,
                c.vigente,
                u.nombre,
                u.apellido

            FROM carnets c

            INNER JOIN inscripciones i
                ON i.id = c.inscripcion_id

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            WHERE c.inscripcion_id = :id

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene un carnet por ID.
     */
    public function obtenerCarnetPorId(
        int $idCarnet
    ): ?array
    {
        $sql = "
            SELECT
                *
            FROM carnets
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idCarnet
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Actualiza la vigencia de un carnet.
     */
    public function actualizarVigenciaCarnet(
        int $idCarnet,
        bool $vigente
    ): bool
    {
        $sql = "
            UPDATE carnets
            SET vigente = :vigente
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':vigente' => $vigente ? 1 : 0,
            ':id' => $idCarnet
        ]);
    }
        // =====================================================
    // CAMBIO DE ESTADO
    // =====================================================

    /**
     * Inicia una transacción.
     */
    public function iniciarTransaccion(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    /**
     * Confirma una transacción.
     */
    public function confirmarTransaccion(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    /**
     * Revierte una transacción.
     */
    public function cancelarTransaccion(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Actualiza el estado de una inscripción.
     */
    public function actualizarEstadoInscripcion(
        int $idInscripcion,
        int $idEstado
    ): bool
    {
        $sql = "
            UPDATE inscripciones
            SET
                estado_tramite_id = :estado,
                fecha_ultima_modificacion = NOW()
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':estado' => $idEstado,
            ':id'     => $idInscripcion
        ]);
    }

    /**
     * Registra un cambio en el historial.
     */
    public function registrarHistorial(
        int $idInscripcion,
        int $estadoAnterior,
        int $estadoNuevo,
        ?string $observaciones,
        ?int $usuarioAdmin
    ): bool
    {
        $sql = "
            INSERT INTO historial_tramite
            (
                inscripcion_id,
                estado_anterior_id,
                estado_nuevo_id,
                fecha_cambio,
                observaciones,
                usuario_admin_id
            )
            VALUES
            (
                :inscripcion,
                :anterior,
                :nuevo,
                NOW(),
                :observaciones,
                :usuario
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':inscripcion'  => $idInscripcion,
            ':anterior'     => $estadoAnterior,
            ':nuevo'        => $estadoNuevo,
            ':observaciones'=> $observaciones,
            ':usuario'      => $usuarioAdmin
        ]);
    }

    /**
     * Registra una acción en auditoría.
     */
    public function registrarAuditoria(
        ?int $usuarioId,
        string $tabla,
        int $idRegistro,
        string $accion,
        array $datosAnteriores,
        array $datosNuevos
    ): bool
    {
        $sql = "
            INSERT INTO auditoria_acciones
            (
                usuario_id,
                tabla,
                id_registro,
                accion,
                datos_anteriores,
                datos_nuevos,
                fecha
            )
            VALUES
            (
                :usuario,
                :tabla,
                :registro,
                :accion,
                :anteriores,
                :nuevos,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario'     => $usuarioId,
            ':tabla'       => $tabla,
            ':registro'    => $idRegistro,
            ':accion'      => $accion,
            ':anteriores'  => json_encode($datosAnteriores),
            ':nuevos'      => json_encode($datosNuevos)
        ]);
    }

    /**
     * Obtiene el ID del último registro insertado.
     */
    public function obtenerUltimoId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
        // =====================================================
    // CONSULTAS
    // =====================================================

    /**
     * Obtiene los trámites de un usuario.
     */
    public function obtenerTramitesUsuario(
        int $idUsuario
    ): array
    {
        $sql = "
            SELECT
                i.id,
                i.usuario_id,
                i.curso_id,
                i.examen_id,
                i.tipo_inscripcion_id,
                i.fecha_inscripcion,
                i.estado_tramite_id,
                et.nombre AS estado_nombre
            FROM inscripciones i

            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id

            WHERE i.usuario_id = :usuario

            ORDER BY
                i.fecha_inscripcion DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':usuario' => $idUsuario
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los trámites pendientes.
     */
    public function obtenerTramitesPendientes(): array
    {
        $sql = "
            SELECT
                i.id,
                i.usuario_id,
                i.fecha_inscripcion,
                i.estado_tramite_id,
                u.nombre,
                u.apellido,
                et.nombre AS estado_nombre

            FROM inscripciones i

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id

            WHERE i.estado_tramite_id IN
            (
                :pendiente,
                :docPendiente,
                :docAprobada,
                :inscriptoExamen
            )

            ORDER BY
                i.fecha_inscripcion ASC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':pendiente'       => EstadoTramite::PENDIENTE,
            ':docPendiente'    => EstadoTramite::DOCUMENTACION_PENDIENTE,
            ':docAprobada'     => EstadoTramite::DOCUMENTACION_APROBADA,
            ':inscriptoExamen' => EstadoTramite::INSCRIPTO_EXAMEN
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Cantidad total de trámites.
     */
    public function contarTramites(): int
    {
        return (int) $this->pdo
            ->query("
                SELECT COUNT(*)
                FROM inscripciones
            ")
            ->fetchColumn();
    }

    /**
     * Cantidad de trámites por estado.
     */
    public function obtenerCantidadPorEstado(): array
    {
        $sql = "
            SELECT
                estado_tramite_id AS estado,
                COUNT(*) AS cantidad
            FROM inscripciones
            GROUP BY
                estado_tramite_id
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta trámites por estado.
     */
    public function contarPorEstado(
        int $estado
    ): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM inscripciones
            WHERE estado_tramite_id = :estado
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':estado' => $estado
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta trámites por varios estados.
     */
    public function contarPorEstados(
        array $estados
    ): int
    {
        $placeholders = [];
        $params = [];

        foreach ($estados as $i => $estado) {
            $placeholder = ":estado{$i}";
            $placeholders[] = $placeholder;
            $params[$placeholder] = $estado;
        }

        $sql = "
            SELECT COUNT(*)
            FROM inscripciones
            WHERE estado_tramite_id IN (
                " . implode(',', $placeholders) . "
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Promedio de duración de los trámites.
     */
    public function obtenerPromedioDiasTramite(): float
    {
        $sql = "
            SELECT AVG(
                DATEDIFF(
                    NOW(),
                    fecha_inscripcion
                )
            )
            FROM inscripciones
            WHERE estado_tramite_id IN
            (
                :aprobado,
                :rechazado
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':aprobado' => EstadoTramite::APROBADO,
            ':rechazado' => EstadoTramite::RECHAZADO
        ]);

        return (float) (
            $stmt->fetchColumn() ?: 0
        );
    }
    /**
     * Verifica si existe un estado.
     */
    public function existeEstado(
        int $idEstado
    ): bool
    {
        $sql = "
            SELECT 1
            FROM estados_tramite
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $idEstado
        ]);

        return (bool)$stmt->fetchColumn();
    }
}