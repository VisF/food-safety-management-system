<?php
declare(strict_types=1);

/**
 * TramiteControlador - Gestión integral del trámite y cambios de estado
 */
class TramiteControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/tramite_controller.log';

    private ?InscripcionService $InscripcionService = null;
    private ?EstadoTramiteService $EstadoTramiteService = null;
    private ?CarnetService $CarnetService = null;
    private ?DocumentoService $DocumentoService = null;
    private ?ResultadoExamenService $ResultadoExamenService = null;

    private function pdo(): \PDO
    {
        require_once __DIR__ . '/../db/Connection.php';
        return Connection::getPDO();
    }

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->InscripcionService = new InscripcionService();
        $this->EstadoTramiteService = new EstadoTramiteService();
        $this->CarnetService = new CarnetService();
        $this->DocumentoService = new DocumentoService();
        $this->ResultadoExamenService = new ResultadoExamenService();
    }


    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    public function obtenerDetalleTramite(int $id_inscripcion): array
    {
        try {
            $pdo = $this->pdo();

            $stmt = $pdo->prepare('SELECT * FROM inscripciones WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $insc = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$insc) return [];

            $stmt = $pdo->prepare('SELECT id, nombre, descripcion FROM estados_tramite WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $insc['estado_tramite_id'] ?? $insc['id_estado'] ?? 0]);
            $estado = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            $usuarioId = (int)($insc['usuario_id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT
                    COUNT(*) as total,
                    SUM(
                        CASE
                            WHEN estado = 'aprobado'
                            THEN 1
                            ELSE 0
                        END
                    ) as validados
                FROM documentos
                WHERE usuario_id = :usuario_id"
            );

            $stmt->execute([
                ':usuario_id' => $usuarioId
            ]);

            $docStats =
                $stmt->fetch(\PDO::FETCH_ASSOC)
                ?: [
                    'total' => 0,
                    'validados' => 0
                ];

            $totalDocs =
                (int)($docStats['total'] ?? 0);

            $validados =
                (int)($docStats['validados'] ?? 0);



            $stmt = $pdo->prepare('SELECT * FROM resultado_examen WHERE inscripcion_id = :id ORDER BY fecha_resultado DESC LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            $stmt = $pdo->prepare('SELECT * FROM carnets WHERE inscripcion_id = :id ORDER BY fecha_emision DESC LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $carnetRow = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            $carnet = null;
            if ($carnetRow) {
                $carnet = [
                    'id' => (int)$carnetRow['id'],
                    'numero_carnet' => $carnetRow['numero_carnet'] ?? null,
                    'fecha_emision' => $carnetRow['fecha_emision'] ?? null,
                    'fecha_vencimiento' => $carnetRow['fecha_vencimiento'] ?? null,
                    'ruta_pdf' => $carnetRow['ruta_pdf'] ?? null,
                    'vigente' => isset($carnetRow['vigente']) ? (int)$carnetRow['vigente'] : null
                ];
            }

            $detalle = [
                'inscripcion' => $insc,
                'estado' => $estado,
                'documentacion' => [
                    'documentos_totales' => $totalDocs,
                    'documentos_validados' => $validados,
                    'documentos_pendientes' => max(0, $totalDocs - $validados),
                    'completada' => ($totalDocs > 0 && $validados === $totalDocs)
                ],
                'resultado_examen' => $resultado,
                'carnet' => $carnet,
                'fecha_ultima_modificacion' => $insc['fecha_ultima_modificacion'] ?? $insc['fecha_inscripcion'] ?? null
            ];

            $this->registrarLog('DETALLE_TRAMITE_OBTENIDO', ['id_inscripcion' => $id_inscripcion]);
            return $detalle;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DETALLE_TRAMITE', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function obtenerHistorialTramite(int $id_inscripcion): array
    {
        try {
            $sql = 'SELECT h.id, h.inscripcion_id, h.estado_anterior_id, h.estado_nuevo_id, h.fecha_cambio, h.observaciones,
                           ea.nombre AS estado_anterior, en.nombre AS estado_nuevo, u.nombre AS usuario_admin_nombre, u.apellido AS usuario_admin_apellido
                    FROM historial_tramite h
                    LEFT JOIN estados_tramite ea ON ea.id = h.estado_anterior_id
                    INNER JOIN estados_tramite en ON en.id = h.estado_nuevo_id
                    LEFT JOIN usuarios u ON u.id = h.usuario_admin_id
                    WHERE h.inscripcion_id = :id
                    ORDER BY h.fecha_cambio ASC';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_inscripcion]);

            $historial = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $historial[] = [
                    'id' => (int)$row['id'],
                    'id_inscripcion' => (int)$row['inscripcion_id'],
                    'id_estado_anterior' => $row['estado_anterior_id'] !== null ? (int)$row['estado_anterior_id'] : null,
                    'id_estado_nuevo' => (int)$row['estado_nuevo_id'],
                    'estado_anterior' => $row['estado_anterior'] ?? 'Nuevo',
                    'estado_nuevo' => $row['estado_nuevo'] ?? '',
                    'fecha_cambio' => $row['fecha_cambio'],
                    'usuario_admin' => trim(($row['usuario_admin_nombre'] ?? 'Sistema') . ' ' . ($row['usuario_admin_apellido'] ?? '')),
                    'observaciones' => $row['observaciones'] ?? null,
                ];
            }

            $this->registrarLog('HISTORIAL_TRAMITE_OBTENIDO', ['id_inscripcion' => $id_inscripcion]);
            return $historial;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_HISTORIAL_TRAMITE', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function actualizarEstadoTramite(int $id_inscripcion, int $id_estado_nuevo): array
    {
        try {
            $pdo = $this->pdo();

            $stmt = $pdo->prepare('SELECT estado_tramite_id FROM inscripciones WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return ['success' => false, 'mensaje' => 'Inscripcion no encontrada', 'estado_anterior' => null, 'estado_nuevo' => $id_estado_nuevo];
            $estado_anterior = (int)($row['estado_tramite_id'] ?? $row['id_estado'] ?? 0);

            $stmt = $pdo->prepare('SELECT id FROM estados_tramite WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_estado_nuevo]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'mensaje' => 'Estado destino inválido', 'estado_anterior' => $estado_anterior, 'estado_nuevo' => $id_estado_nuevo];
            }

            $pdo->beginTransaction();
            $upd = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :nid, fecha_ultima_modificacion = NOW() WHERE id = :id');
            $ok = $upd->execute([':nid' => $id_estado_nuevo, ':id' => $id_inscripcion]);

            $hist = $pdo->prepare('INSERT INTO historial_tramite (inscripcion_id, estado_anterior_id, estado_nuevo_id, fecha_cambio, observaciones, usuario_admin_id) VALUES (:iid, :ant, :nuevo, NOW(), :obs, :uid)');
            $usuario_admin = $_SESSION['user_id'] ?? null;
            $hist->execute([':iid' => $id_inscripcion, ':ant' => $estado_anterior, ':nuevo' => $id_estado_nuevo, ':obs' => null, ':uid' => $usuario_admin]);

            try {
                $aud = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, tabla, id_registro, accion, datos_anteriores, datos_nuevos, fecha) VALUES (:uid, :tabla, :idregistro, :accion, :datosant, :datosnew, NOW())');
                $aud->execute([
                    ':uid' => $usuario_admin,
                    ':tabla' => 'inscripciones',
                    ':idregistro' => $id_inscripcion,
                    ':accion' => 'CAMBIO_ESTADO',
                    ':datosant' => json_encode(['estado_anterior' => $estado_anterior]),
                    ':datosnew' => json_encode(['estado_nuevo' => $id_estado_nuevo])
                ]);
            } catch (\Throwable $t) {
                // non critical
            }

            try { $pdo->commit(); } catch (\Throwable $t) { $this->registrarLog('ERROR_COMMIT_CAMBIO_ESTADO', ['error' => $t->getMessage()]); }

            $resultado = ['success' => (bool)$ok, 'mensaje' => $ok ? 'Estado actualizado exitosamente' : 'Error al actualizar estado', 'estado_anterior' => $estado_anterior, 'estado_nuevo' => $id_estado_nuevo];

            $this->registrarLog('ESTADO_TRAMITE_ACTUALIZADO', ['id_inscripcion' => $id_inscripcion, 'estado_anterior' => $estado_anterior, 'estado_nuevo' => $id_estado_nuevo]);
            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ACTUALIZAR_ESTADO_TRAMITE', ['error' => $e->getMessage()]);
            return ['success' => false, 'mensaje' => 'Error al actualizar estado: ' . $e->getMessage(), 'estado_anterior' => 0, 'estado_nuevo' => 0];
        }
    }

    public function obtenerComprobanteDescargable(int $id_inscripcion): array
    {
        try {
            $stmt = $this->pdo()->prepare('SELECT c.id, c.codigo_comprobante, c.fecha_emision, c.ruta_pdf, c.vigente, i.usuario_id, i.estado_tramite_id, u.nombre, u.apellido, u.dni
                                               FROM comprobantes_tramite c
                                               INNER JOIN inscripciones i ON i.id = c.inscripcion_id
                                               INNER JOIN usuarios u ON u.id = i.usuario_id
                                               WHERE c.inscripcion_id = :id
                                               LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'ruta_pdf' => null, 'nombre' => null, 'mensaje' => 'No se encontro comprobante para la inscripcion'];
            }

            $resultado = [
                'success' => true,
                'ruta_pdf' => $row['ruta_pdf'],
                'nombre' => 'comprobante_tramite_' . $id_inscripcion . '.pdf',
                'mensaje' => 'Comprobante generado',
                'comprobante' => [
                    'id' => (int)$row['id'],
                    'codigo_comprobante' => $row['codigo_comprobante'],
                    'fecha_emision' => $row['fecha_emision'],
                    'titular' => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
                    'dni' => $row['dni'] ?? '',
                ]
            ];

            $this->registrarLog('COMPROBANTE_GENERADO', ['id_inscripcion' => $id_inscripcion]);
            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_GENERAR_COMPROBANTE', ['error' => $e->getMessage()]);
            return ['success' => false, 'ruta_pdf' => null, 'nombre' => null, 'mensaje' => 'Error al generar comprobante: ' . $e->getMessage()];
        }
    }

    public function obtenerCarnet(int $id_inscripcion): ?array
    {
        try {
            $sql = 'SELECT c.id, c.numero_carnet, c.fecha_emision, c.fecha_vencimiento, c.ruta_pdf, c.vigente, u.nombre, u.apellido
                    FROM carnets c
                    INNER JOIN inscripciones i ON i.id = c.inscripcion_id
                    INNER JOIN usuarios u ON u.id = i.usuario_id
                    WHERE c.inscripcion_id = :id
                    LIMIT 1';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return null;
            return ['id' => (int)$row['id'], 'id_inscripcion' => $id_inscripcion, 'numero_carnet' => $row['numero_carnet'], 'fecha_emision' => $row['fecha_emision'], 'fecha_vencimiento' => $row['fecha_vencimiento'], 'ruta_pdf' => $row['ruta_pdf'], 'vigente' => (int)$row['vigente'], 'titular' => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''))];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_CARNET', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function verificarVigenciaCarnet(int $id_carnet): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT fecha_vencimiento, vigente FROM carnets WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_carnet]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return ['vigente' => false, 'fecha_vencimiento' => '', 'dias_restantes' => 0, 'mensaje' => 'Carnet no encontrado'];
            $fecha_vencimiento = $row['fecha_vencimiento'];
            $hoy = date('Y-m-d');
            $vigente = strtotime($fecha_vencimiento) >= strtotime($hoy);
            $dias_restantes = max(0, (int)ceil((strtotime($fecha_vencimiento) - strtotime($hoy)) / 86400));
            $vigente_db = (int)($row['vigente'] ?? 0);
            if (($vigente ? 1 : 0) !== $vigente_db) {
                $upd = $pdo->prepare('UPDATE carnets SET vigente = :v WHERE id = :id');
                $upd->execute([':v' => $vigente ? 1 : 0, ':id' => $id_carnet]);
            }
            $resultado = ['vigente' => $vigente, 'fecha_vencimiento' => $fecha_vencimiento, 'dias_restantes' => $dias_restantes, 'mensaje' => $vigente ? "Carnet vigente ($dias_restantes días)" : 'Carnet vencido'];
            $this->registrarLog('VIGENCIA_CARNET_VERIFICADA', ['id_carnet' => $id_carnet, 'vigente' => $vigente]);
            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VERIFICAR_VIGENCIA_CARNET', ['error' => $e->getMessage()]);
            return ['vigente' => false, 'fecha_vencimiento' => '', 'dias_restantes' => 0, 'mensaje' => 'Error al verificar vigencia: ' . $e->getMessage()];
        }
    }

    public function cambiarEstadoTramite(int $id_inscripcion, string $estado): array
    {
        try {
            $mapeo_estados = ['pendiente' => 1, 'documentacion_completa' => 2, 'documentacion_rechazada' => 3, 'apto_examen' => 4, 'examen_rendido' => 5, 'aprobado' => 6, 'rechazado' => 7, 'carnet_emitido' => 8];
            $id_estado = $mapeo_estados[strtolower($estado)] ?? null;
            if ($id_estado === null) {
                $pdo = $this->pdo();
                $stmt = $pdo->prepare('SELECT id FROM estados_tramite WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1');
                $stmt->execute([':nombre' => $estado]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) $id_estado = (int)$row['id'];
            }
            if ($id_estado === null) return ['success' => false, 'mensaje' => 'Estado no válido: ' . $estado];
            return $this->actualizarEstadoTramite($id_inscripcion, $id_estado);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_CAMBIAR_ESTADO_TRAMITE', ['error' => $e->getMessage()]);
            return ['success' => false, 'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()];
        }
    }

    public function obtenerTramitesUsuario(int $id_usuario): array
    {
        try {
            $sql = 'SELECT i.id, i.usuario_id, i.curso_id, i.examen_id, i.tipo_inscripcion_id, i.fecha_inscripcion, i.estado_tramite_id, et.nombre 
                    AS estado_nombre 
                    FROM inscripciones i 
                    LEFT JOIN estados_tramite et ON et.id = i.estado_tramite_id 
                    WHERE i.usuario_id = :id 
                    ORDER BY i.fecha_inscripcion DESC';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);
            $tramites = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tramites[] = ['id' => (int)$row['id'], 
                                'id_usuario' => (int)$row['usuario_id'], 
                                'id_curso' => $row['curso_id'] !== null ? (int)$row['curso_id'] : null, 
                                'id_examen' => $row['examen_id'] !== null ? (int)$row['examen_id'] : null, 
                                'id_tipo_inscripcion' => $row['tipo_inscripcion_id'] !== null ? (int)$row['tipo_inscripcion_id'] : null, 
                                'fecha_inscripcion' => $row['fecha_inscripcion'],
                                'estado_nombre' => $row['estado_nombre'] ?? 'Desconocido', 
                                'estado_id' => $row['estado_tramite_id'] !== null ? (int)$row['estado_tramite_id'] : EstadoTramite::PENDIENTE];
            }
            $this->registrarLog('TRAMITES_USUARIO_OBTENIDOS', ['id_usuario' => $id_usuario]);
            return $tramites;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_TRAMITES_USUARIO', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function obtenerTramitesPendientes(): array
    {
        try {
            $sql = '
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
                    WHERE i.estado_tramite_id IN (
                        ' . EstadoTramite::PENDIENTE . ',
                        ' . EstadoTramite::DOCUMENTACION_PENDIENTE . ',
                        ' . EstadoTramite::DOCUMENTACION_APROBADA . ',
                        ' . EstadoTramite::INSCRIPTO_EXAMEN . '
                    )
                    ORDER BY i.fecha_inscripcion ASC
                ';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute();
            $tramites = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tramites[] = ['id' => (int)$row['id'], 'id_usuario' => (int)$row['usuario_id'], 
                                'usuario_nombre' => $row['nombre'], 
                                'usuario_apellido' => $row['apellido'], 
                                'fecha_inscripcion' => $row['fecha_inscripcion'], 
                                'estado_nombre' => $row['estado_nombre'] ?? 'Pendiente', 
                                'estado_id' => (int)$row['estado_tramite_id']];
            }
            $this->registrarLog('TRAMITES_PENDIENTES_OBTENIDOS', []);
            return $tramites;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_TRAMITES_PENDIENTES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function obtenerEstadisticasTramites(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query('SELECT COUNT(*) as total FROM inscripciones');
            $total = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT estado_tramite_id as estado, COUNT(*) as cantidad FROM inscripciones GROUP BY estado_tramite_id');
            $por_estado_rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $por_estado = [];
            foreach ($por_estado_rows as $r) $por_estado[(string)$r['estado']] = (int)$r['cantidad'];
            
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                FROM inscripciones
                WHERE estado_tramite_id = :id'
            );

            $stmt->execute([
                ':id' => EstadoTramite::APROBADO
            ]);

            $aprobados =
                (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare(
                                    'SELECT COUNT(*)
                                    FROM inscripciones
                                    WHERE estado_tramite_id = :id'
                                );

            $stmt->execute([
                ':id' => EstadoTramite::RECHAZADO
            ]);

            $rechazados = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                FROM inscripciones
                WHERE estado_tramite_id IN (
                    :pendiente,
                    :doc_pendiente,
                    :doc_aprobada,
                    :inscripto_examen
                )'
            );

            $stmt->execute([
                ':pendiente' => EstadoTramite::PENDIENTE,
                ':doc_pendiente' => EstadoTramite::DOCUMENTACION_PENDIENTE,
                ':doc_aprobada' => EstadoTramite::DOCUMENTACION_APROBADA,
                ':inscripto_examen' => EstadoTramite::INSCRIPTO_EXAMEN
            ]);

            $en_tramite =
                (int)$stmt->fetchColumn();

            $tasa_aprobacion = $total > 0 ? round(($aprobados / $total) * 100.0, 2) : 0.0;
            $tasa_rechazo = $total > 0 ? round(($rechazados / $total) * 100.0, 2) : 0.0;
            $stmt = $pdo->query(
                                "SELECT AVG(DATEDIFF(NOW(), fecha_inscripcion))
                                FROM inscripciones
                                WHERE estado_tramite_id IN (
                                    " . EstadoTramite::APROBADO . ",
                                    " . EstadoTramite::RECHAZADO . "
                                )"
                            );
            $dias_promedio = (float)($stmt->fetchColumn() ?: 0.0);
            $estadisticas = ['total_tramites' => $total, 'por_estado' => $por_estado, 'aprobados' => $aprobados, 'rechazados' => $rechazados, 'en_tramite' => $en_tramite, 'tasa_aprobacion' => $tasa_aprobacion, 'tasa_rechazo' => $tasa_rechazo, 'dias_promedio_tramite' => round($dias_promedio, 2)];
            $this->registrarLog('ESTADISTICAS_TRAMITES_OBTENIDAS', []);
            return $estadisticas;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_ESTADISTICAS_TRAMITES', ['error' => $e->getMessage()]);
                return ['total_tramites' => 0, 'por_estado' => [], 
                'aprobados' => 0, 
                'rechazados' => 0, 
                'en_tramite' => 0, 
                'tasa_aprobacion' => 0.0, 
                'tasa_rechazo' => 0.0, 
                'dias_promedio_tramite' => 0.0];
        }
    }

    public function registrarCambioEstado(int $id_inscripcion, int $estado_anterior, int $estado_nuevo): array
    {
        try {
            $pdo = $this->pdo();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO historial_tramite (inscripcion_id, estado_anterior_id, estado_nuevo_id, fecha_cambio, usuario_admin_id) VALUES (:iid, :ant, :nue, NOW(), :uid)');
            $usuario_admin = $_SESSION['user_id'] ?? null;
            $stmt->execute([':iid' => $id_inscripcion, ':ant' => $estado_anterior, ':nue' => $estado_nuevo, ':uid' => $usuario_admin]);
            $id_hist = (int)$pdo->lastInsertId();
            try {
                $aud = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, tabla, id_registro, accion, datos_anteriores, datos_nuevos, fecha) VALUES (:uid, :tabla, :idregistro, :accion, :dant, :dnue, NOW())');
                $aud->execute([':uid' => $usuario_admin, ':tabla' => 'inscripciones', ':idregistro' => $id_inscripcion, ':accion' => 'CAMBIO_ESTADO', ':dant' => json_encode(['estado_anterior' => $estado_anterior]), ':dnue' => json_encode(['estado_nuevo' => $estado_nuevo])]);
            } catch (\Throwable $t) { }
            $pdo->commit();
            $this->registrarLog('CAMBIO_ESTADO_REGISTRADO', ['id_inscripcion' => $id_inscripcion, 'estado_anterior' => $estado_anterior, 'estado_nuevo' => $estado_nuevo, 'id_historial' => $id_hist]);
            return ['success' => true, 'mensaje' => 'Cambio de estado registrado', 'id_registro' => $id_hist];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_REGISTRAR_CAMBIO_ESTADO', ['error' => $e->getMessage()]);
            return ['success' => false, 'mensaje' => 'Error al registrar cambio de estado: ' . $e->getMessage(), 'id_registro' => null];
        }
    }
}
