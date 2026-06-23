<?php
declare(strict_types=1);

/**
 * DocumentoControlador - Gestión de documentos y validación
 * 
 * Responsabilidades:
 * - Procesar carga de documentos
 * - Validar documentos administrativamente
 * - Obtener documentos según filtros
 * - Gestionar estado de validación
 * - Registrar observaciones y rechazos
 * 
 * Dependencias:
 * - Modelos: DocumentoModelo, InscripcionModelo, UsuarioModelo
 */

class DocumentoControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/documento_controller.log';
    
    private ?DocumentoModelo $documentoModelo = null;
    private ?InscripcionModelo $inscripcionModelo = null;
    private ?UsuarioModelo $usuarioModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar todas las dependencias de modelos
     */
    private function inicializarModelos(): void
    {
        if (class_exists('DocumentoModelo')) {
            $this->documentoModelo = new DocumentoModelo();
        }
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
    }

    /**
     * Registrar evento en el log
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    /**
     * Procesar subida de documento a partir de inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @param array $archivo Array de $_FILES['archivo']
     * @return array ['success' => bool, 'id' => int|null, 'mensaje' => string, 'documento' => array|null]
     */
    public function subirDocumento(int $id_inscripcion, array $archivo): array
    {
        try {
            // Validación: comprobar que el modelo de inscripción esté disponible
            if (!$this->inscripcionModelo || !method_exists($this->inscripcionModelo, 'obtenerPorId')) 
                return ['success' => false, 'id' => null, 'mensaje' => 'Modelo de inscripción no disponible', 'documento' => null];

            $ins = $this->inscripcionModelo->obtenerPorId($id_inscripcion);
            // Validación: la inscripción debe existir
            if (!$ins) return ['success' => false, 'id' => null, 'mensaje' => 'Inscripción no encontrada', 'documento' => null];

            // Comprobación de autorización: sólo el propietario o admin puede subir en esta inscripción
            $current = $_SESSION['user_id'] ?? null;
            if (!empty($current) && empty($_SESSION['is_admin']) && ((int)$ins['usuario_id'] !== (int)$current)) {
                return ['success' => false, 'id' => null, 'mensaje' => 'No autorizado para subir documento en esta inscripción', 'documento' => null];
            }

            // Validación mínima del array de archivo esperado (estructura de $_FILES)
            $requiredKeys = ['name','type','size','tmp_name','error'];
            foreach ($requiredKeys as $k) 
                if (!array_key_exists($k, $archivo)) 
                    return ['success' => false, 'id' => null, 'mensaje' => 'Archivo inválido', 'documento' => null];
                if ($archivo['error'] !== 0) 
                    return ['success' => false, 'id' => null, 'mensaje' => 'Error en upload: ' . $archivo['error'], 'documento' => null];

            $tipo = $archivo['type'] ?? 'documento';

            // evitar duplicados por tipo: si existe documento no validado del mismo tipo, permitimos pero avisamos
            if ($this->documentoModelo && method_exists($this->documentoModelo, 'obtenerPorInscripcionYTipo')) {
                $exists = $this->documentoModelo->obtenerPorInscripcionYTipo($id_inscripcion, $tipo);
                if ($exists) {
                    // seguimos y dejaremos registro adicional
                }
            }

            // Procesamiento de carga: preferir delegar a UploadControlador si está disponible
            $uploadsDir = __DIR__ . '/../uploads/documentos'; @mkdir($uploadsDir, 0755, true);
            $basename = preg_replace('/[^a-zA-Z0-9_\-\.]/','_',basename($archivo['name']));
            $target = $uploadsDir . '/doc_' . time() . '_' . uniqid() . '_' . $basename;
            $moved = false;
            // Intentar usar controlador dedicado para subir archivos (encapsula validaciones/movimientos)
            if (class_exists('UploadControlador')) {
                try { $up = new UploadControlador(); 
                    if (method_exists($up, 'subirArchivo')) { $res = $up->subirArchivo($archivo, $target); $moved = $res['success'] ?? false; } }
                catch (Exception $e) { $moved = false; }
            }
            if (!$moved) {
                // intentar mover desde tmp_name si existe
                if (!empty($archivo['tmp_name']) && is_uploaded_file($archivo['tmp_name'])) {
                    $moved = move_uploaded_file($archivo['tmp_name'], $target);
                } else {
                    // en entornos de prueba, si archivo temporal no existe, intentar copy
                    if (file_exists($archivo['tmp_name'])) $moved = copy($archivo['tmp_name'], $target);
                }
            }

            if (!$moved && !file_exists($target)) return ['success' => false, 'id' => null, 'mensaje' => 'No se pudo guardar archivo en servidor', 'documento' => null];

            // insertar en BD
            $pdoFile = __DIR__ . '/../db/Connection.php';
            $docId = null;
            if (file_exists($pdoFile)) {
                require_once $pdoFile;
                $pdo = Connection::getPDO();
                $insStmt = $pdo->prepare('INSERT INTO documento (id_inscripcion, tipo_documento, ruta_archivo, estado, fecha_subida, observaciones)
                                            VALUES (:iid, :tipo, :ruta, "pendiente", NOW(), NULL)');
                $insStmt->execute([':iid' => $id_inscripcion, ':tipo' => $tipo, ':ruta' => str_replace(__DIR__ . '/../', '/', $target)]);
                $docId = (int)$pdo->lastInsertId();
                // actualizar inscripcion fecha ultima modificacion
                $upd = $pdo->prepare('UPDATE inscripciones SET fecha_ultima_modificacion = NOW() WHERE id = :id'); $upd->execute([':id' => $id_inscripcion]);
            }

            $documento = ['id' => $docId, 
                        'id_inscripcion' => $id_inscripcion, 
                        'tipo_documento' => $tipo, 
                        'ruta_archivo' => str_replace(__DIR__ . '/../', '/', $target), 
                        'estado' => 'pendiente', 
                        'fecha_subida' => date('Y-m-d H:i:s'),
                         'observaciones' => null];

            $this->registrarLog('DOCUMENTO_SUBIDO', ['id_inscripcion' => $id_inscripcion, 'tipo' => $documento['tipo_documento'], 'id_documento' => $docId]);

            return ['success' => true, 'id' => $docId, 'mensaje' => 'Documento subido exitosamente', 'documento' => $documento];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_SUBIR_DOCUMENTO', [
                'id_inscripcion' => $id_inscripcion,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'id' => null,
                'mensaje' => 'Error al subir documento: ' . $e->getMessage(),
                'documento' => null
            ];
        }
    }
        public function obtenerDocumentosUsuario(int $idUsuario): array
        {
            try {
                $pdoFile = __DIR__ . '/../db/Connection.php';

                if (!file_exists($pdoFile)) {
                    return [];
                }

                require_once $pdoFile;

                $pdo = Connection::getPDO();

                $sql = "
                    SELECT d.*
                    FROM documento d
                    INNER JOIN inscripciones i
                        ON d.id_inscripcion = i.id
                    WHERE i.usuario_id = :id_usuario
                    ORDER BY d.fecha_subida DESC
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':id_usuario' => $idUsuario
                ]);

                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (Exception $e) {

                $this->registrarLog(
                    'ERROR_OBTENER_DOCUMENTOS_USUARIO',
                    ['error' => $e->getMessage()]
                );

                return [];
            }
        }





    /**
     * Obtener todos los documentos de una inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array de documentos con estructura: [id, tipo_documento, validado, fecha_subida, ...]
     */
    public function obtenerDocumentos(int $id_inscripcion): array
    {
        try {
            // si el modelo tiene método, usarlo
            if ($this->documentoModelo && method_exists($this->documentoModelo, 'obtenerPorInscripcion')) {
                $docs = $this->documentoModelo->obtenerPorInscripcion($id_inscripcion);
                $this->registrarLog('DOCUMENTOS_OBTENIDOS', ['id_inscripcion' => $id_inscripcion]);
                return $docs;
            }
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return [];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE id_inscripcion = :id ORDER BY fecha_subida DESC');
            $stmt->execute([':id' => $id_inscripcion]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->registrarLog('DOCUMENTOS_OBTENIDOS', ['id_inscripcion' => $id_inscripcion, 'count' => count($rows)]);
            return $rows;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DOCUMENTOS', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener un documento específico por ID
     * 
     * @param int $id ID del documento
     * @return array|null Datos del documento o null si no existe
     */
    public function obtenerDocumento(int $id): ?array
    {
        try {
            if ($this->documentoModelo && method_exists($this->documentoModelo, 'obtenerPorId')) {
                $doc = $this->documentoModelo->obtenerPorId($id);
                $this->registrarLog('DOCUMENTO_OBTENIDO', ['id' => $id]);
                return $doc;
            }
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return null;
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $doc = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->registrarLog('DOCUMENTO_OBTENIDO', ['id' => $id]);
            return $doc ?: null;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DOCUMENTO', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validar documento (admin)
     * 
     * @param int $id ID del documento a validar
     * @param string $estado 'aprobado' o 'rechazado' o 'pendiente'
     * @return array ['success' => bool, 'mensaje' => string, 'documento' => array|null]
     */
    public function validarDocumento(int $id, string $estado): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'mensaje' => 'DB no disponible', 'documento' => null];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE id = :id'); $stmt->execute([':id' => $id]); $doc = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$doc) return ['success' => false, 'mensaje' => 'Documento no encontrado', 'documento' => null];
            $upd = $pdo->prepare('UPDATE documento SET estado = :v, fecha_validacion = NOW() WHERE id = :id');
            $upd->execute([':v' => $estado, ':id' => $id]);

            // si aprobado, verificar estado de todos los documentos
            if ($estado === 'aprobado') {
                $count = (int)$pdo->prepare('SELECT COUNT(*) FROM documento 
                                            WHERE id_inscripcion = :iid AND estado = "pendiente"')
                                            ->execute([':iid' => $doc['id_inscripcion']]) ? 
                                            $pdo->query("SELECT COUNT(*) FROM documento 
                                                        WHERE id_inscripcion = {$doc['id_inscripcion']} 
                                                        AND estado = 'pendiente'")->fetchColumn() : 0;
                if ((int)$count === 0) {
                    // marcar inscripcion como documentacion completa (estado 2 asumido)
                    $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :estado 
                                    WHERE id = :id')
                                    ->execute([':id' => $doc['id_inscripcion'], ':estado' => EstadoTramite::APROBADO]);
                }
            }

            // auditoría simple si tabla existe
            if ($pdo->query("SHOW TABLES LIKE 'auditoria_acciones'")->rowCount() > 0) {
                $insA = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, accion, detalle, fecha) VALUES (:u, :a, :d, NOW())');
                $insA->execute([':u' => $_SESSION['user_id'] ?? null, ':a' => 'validar_documento', ':d' => json_encode(['id' => $id, 'estado' => $estado])]);
            }

            $this->registrarLog('DOCUMENTO_VALIDADO', ['id' => $id, 'estado' => $estado]);
            return ['success' => true, 'mensaje' => $estado === 'aprobado' ? 'Documento aprobado' : 'Documento rechazado', 
                    'documento' => ['id' => $id, 'estado' => $estado, 'fecha_validacion' => date('Y-m-d H:i:s')]];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_DOCUMENTO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al validar documento: ' . $e->getMessage(),
                'documento' => null
            ];
        }
    }

    /**
     * Rechazar documento con motivo
     * 
     * @param int $id ID del documento
     * @param string $motivo Motivo del rechazo
     * @return array ['success' => bool, 'mensaje' => string, 'documento' => array|null]
     */
    public function rechazarDocumento(int $id, string $motivo): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'mensaje' => 'DB no disponible', 'documento' => null];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE id = :id'); $stmt->execute([':id' => $id]); $doc = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$doc) return ['success' => false, 'mensaje' => 'Documento no encontrado', 'documento' => null];
            $upd = $pdo->prepare('UPDATE documento SET estado = :estado, motivo_rechazo = :mot, fecha_rechazo = NOW() WHERE id = :id'); $upd->execute([':estado' => 'rechazado', ':mot' => $motivo, ':id' => $id]);
            // marcar inscripcion como documentacion rechazada (estado 7 asumido)
            $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :estado 
                            WHERE id = :id')->execute([':id' => $doc['id_inscripcion'], ':estado' => EstadoTramite::DOCUMENTACION_RECHAZADA]);
            // notificar
            if (class_exists('NotificacionControlador')) {
                try { $nc = new NotificacionControlador(); if (method_exists($nc, 'enviarNotificacion')) $nc->enviarNotificacion((int)$doc['usuario_id'] ?? (int)$ins['usuario_id'] ?? null, 'documento_rechazado', ['motivo' => $motivo, 'documento_id' => $id]); } catch (Exception $e) {}
            }
            if ($pdo->query("SHOW TABLES LIKE 'auditoria_acciones'")->rowCount() > 0) {
                $insA = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, accion, detalle, fecha) VALUES (:u, :a, :d, NOW())');
                $insA->execute([':u' => $_SESSION['user_id'] ?? null, ':a' => 'rechazar_documento', ':d' => json_encode(['id' => $id, 'motivo' => $motivo])]);
            }
            $this->registrarLog('DOCUMENTO_RECHAZADO', ['id' => $id, 'motivo' => $motivo]);
            return ['success' => true, 'mensaje' => 'Documento rechazado', 'documento' => ['id' => $id, 'estado' => 'rechazado', 'motivo_rechazo' => $motivo, 'fecha_rechazo' => date('Y-m-d H:i:s')]];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_RECHAZAR_DOCUMENTO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al rechazar documento: ' . $e->getMessage(),
                'documento' => null
            ];
        }
    }

    /**
     * Obtener información para descargar documento
     * 
     * @param int $id ID del documento
     * @return array ['success' => bool, 'ruta' => string|null, 'nombre' => string|null]
     */
    public function descargarDocumento(int $id): array
    {
        try {
            $doc = $this->obtenerDocumento($id);
            if (!$doc) return ['success' => false, 'ruta' => null, 'nombre' => null];
            $ruta = $doc['ruta_archivo'] ?? null;
            // ruta almacenada relativa: convertir a path
            $serverPath = __DIR__ . '/../' . ltrim($ruta, '/');
            if (!file_exists($serverPath)) return ['success' => false, 'ruta' => null, 'nombre' => null];
            // registrar auditoria
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($pdoFile)) 
                { 
                    require_once $pdoFile; 
                    
                    $pdo = Connection::getPDO(); 
                    if ($pdo->query("SHOW TABLES LIKE 'auditoria_acciones'")->rowCount() > 0) 
                        {   
                            $insA = $pdo->prepare
                            ('INSERT INTO auditoria_acciones (usuario_id, accion, detalle, fecha) 
                                VALUES (:u, :a, :d, NOW())'); 
                            $insA->execute([':u' => $_SESSION['user_id'] ?? null, 
                                        ':a' => 'descargar_documento', 
                                        ':d' => json_encode(['id' => $id])]); 
                         } 
            }
            $this->registrarLog('DOCUMENTO_DESCARGADO', ['id' => $id]);
            return ['success' => true, 'ruta' => $ruta, 'nombre' => basename($ruta)];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_DESCARGAR_DOCUMENTO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'ruta' => null,
                'nombre' => null
            ];
        }
    }

    /**
     * Obtener documentos pendientes de validación (admin)
     * 
     * @return array Array de documentos sin validar, ordenados por fecha
     */
    public function obtenerDocumentosPendientes(): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php'; if (!file_exists($pdoFile)) return [];
            require_once $pdoFile; $pdo = Connection::getPDO();
            $sql = 'SELECT d.*, i.usuario_id as id_usuario, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                    FROM documento d 
                    JOIN inscripciones i ON d.id_inscripcion = i.id 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    WHERE d.estado = "pendiente"
                    ORDER BY d.fecha_subida ASC';
            $stmt = $pdo->prepare($sql); $stmt->execute(); $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->registrarLog('DOCUMENTOS_PENDIENTES_OBTENIDOS', ['count' => count($rows)]);
            return $rows;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DOCUMENTOS_PENDIENTES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener documentos filtrados por tipo
     * 
     * @param string $tipo Tipo de documento (DNI, Carnet, Certificado, etc.)
     * @return array Array de documentos del tipo especificado
     */
    public function obtenerDocumentosPorTipo(string $tipo): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php'; if (!file_exists($pdoFile)) return [];
            require_once $pdoFile; $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE tipo_documento = :tipo ORDER BY fecha_subida DESC'); 
            $stmt->execute([':tipo' => $tipo]); 
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->registrarLog('DOCUMENTOS_POR_TIPO_OBTENIDOS', ['tipo' => $tipo, 'count' => count($rows)]);
            return $rows;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DOCUMENTOS_POR_TIPO', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener documentos requeridos según tipo de inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array con documentos requeridos y estado de carga:
     *               [['tipo' => 'DNI', 'requerido' => true, 'cargado' => true, ...], ...]
     */
    public function obtenerDocumentosRequeridos(int $id_inscripcion): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php'; if (!file_exists($pdoFile)) return [];
            require_once $pdoFile; $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT id_tipo_inscripcion, usuario_id FROM inscripciones WHERE id = :id'); 
            $stmt->execute([':id' => $id_inscripcion]); $ins = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ins) return [];
            $tipoIns = (int)($ins['id_tipo_inscripcion'] ?? 0);
            // intentar obtener requeridos desde modelo TipoInscripcionModelo
            $requeridos = [];
            if (class_exists('TipoInscripcionModelo')) {
                try { $t = new TipoInscripcionModelo(); if (method_exists($t, 'obtenerPorId')) $requeridos = $t->obtenerRequeridos($tipoIns); } catch (Exception $e) { $requeridos = []; }
            }
            if (empty($requeridos)) {
                // por defecto
                $requeridos = ['DNI','Certificado Curso'];
            }
            $result = [];
            foreach ($requeridos as $r) {
                $stmt = $pdo->prepare('SELECT * FROM documento WHERE id_inscripcion = :id AND tipo_documento = :t ORDER BY fecha_subida DESC LIMIT 1');
                $stmt->execute([':id' => $id_inscripcion, ':t' => $r]);
                $doc = $stmt->fetch(\PDO::FETCH_ASSOC);
                $result[] = ['tipo' => $r, 'requerido' => true, 'cargado' => (bool)$doc, 'estado' => $doc['estado'] ?? 'pendiente', 'id_documento' => $doc['id'] ?? null];
            }
            $this->registrarLog('DOCUMENTOS_REQUERIDOS_OBTENIDOS', ['id_inscripcion' => $id_inscripcion]);
            return $result;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DOCUMENTOS_REQUERIDOS', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Eliminar documento por ID
     * 
     * @param int $id ID del documento a eliminar
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function eliminarDocumento(int $id): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php'; 
            if (!file_exists($pdoFile)) return ['success' => false, 'mensaje' => 'DB no disponible'];

            require_once $pdoFile; 
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM documento WHERE id = :id'); 
            $stmt->execute([':id' => $id]); 
            $doc = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$doc) return ['success' => false, 'mensaje' => 'Documento no encontrado'];

            if (($doc['estado'] ?? 'pendiente') === 'aprobado' 
                && empty($_SESSION['is_admin'])) 
                return ['success' => false, 'mensaje' => 'No autorizado para eliminar documento aprobado'];

            $serverPath = __DIR__ . '/../' . ltrim($doc['ruta_archivo'] ?? '', '/'); 
            if (file_exists($serverPath)) @unlink($serverPath);

            $del = $pdo->prepare('DELETE FROM documento WHERE id = :id'); 
            $del->execute([':id' => $id]);

            if ($pdo->query("SHOW TABLES LIKE 'auditoria_acciones'")->rowCount() > 0) 
                {
                    $insA = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, accion, detalle, fecha) 
                                            VALUES (:u, :a, :d, NOW())'); 
                    $insA->execute([':u' => $_SESSION['user_id'] ?? null, 
                                    ':a' => 'eliminar_documento', 
                                    ':d' => json_encode(['id' => $id])]); 
                }
            $this->registrarLog('DOCUMENTO_ELIMINADO', ['id' => $id]);
            return ['success' => true, 'mensaje' => 'Documento eliminado'];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ELIMINAR_DOCUMENTO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al eliminar documento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de la documentación de una inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['documentacion_completa' => bool, 'documentos_totales' => int, 'estado' => string, 'pendientes' => int]
     */
    public function obtenerEstadoDocumentacion(int $usuarioId): array
    {
        try {

            $pdoFile =
                __DIR__ . '/../db/Connection.php';

            if (!file_exists($pdoFile)) {

                return [
                    'documentacion_completa' => false,
                    'documentos_totales' => 0,
                    'aprobados' => 0,
                    'pendientes' => 0
                ];
            }

            require_once $pdoFile;

            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(
                        CASE
                            WHEN estado = "aprobado"
                            THEN 1
                            ELSE 0
                        END
                    ) AS aprobados
                FROM documentos
                WHERE usuario_id = :usuario_id'
            );

            $stmt->execute([
                ':usuario_id' => $usuarioId
            ]);

            $row =
                $stmt->fetch(
                    \PDO::FETCH_ASSOC
                );

            $total =
                (int)($row['total'] ?? 0);

            $aprobados =
                (int)($row['aprobados'] ?? 0);

            $pendientes =
                max(
                    0,
                    $total - $aprobados
                );

            return [
                'documentacion_completa' =>
                    $pendientes === 0
                    && $total > 0,

                'documentos_totales' =>
                    $total,

                'aprobados' =>
                    $aprobados,

                'pendientes' =>
                    $pendientes
            ];

        } catch (\Exception $e) {

            return [
                'documentacion_completa' => false,
                'documentos_totales' => 0,
                'aprobados' => 0,
                'pendientes' => 0
            ];
        }
    }
    public function procesarSubida(): void
    {
        try {

            if (empty($_SESSION['usuario_id'])) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/login'
                );

                exit;
            }

            if (
                empty($_FILES['archivo'])
                || empty($_POST['tipo_documento'])
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=error_archivo'
                );

                exit;
            }

            $usuarioId =
                (int)$_SESSION['usuario_id'];

            $tipoDocumento =
                trim(
                    $_POST['tipo_documento']
                );

            $archivo =
                $_FILES['archivo'];

            if (
                $archivo['error']
                !== UPLOAD_ERR_OK
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=error_upload'
                );

                exit;
            }

            $ext =
                strtolower(
                    pathinfo(
                        $archivo['name'],
                        PATHINFO_EXTENSION
                    )
                );

            $permitidas = [
                'pdf',
                'jpg',
                'jpeg',
                'png'
            ];

            if (
                !in_array(
                    $ext,
                    $permitidas,
                    true
                )
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/subida_documentacion?toast=formato_invalido'
                );

                exit;
            }

            $directorio =
                __DIR__ .
                '/../uploads';

            if (
                !is_dir($directorio)
            ) {

                mkdir(
                    $directorio,
                    0775,
                    true
                );
            }

            $nombreArchivo =
                $tipoDocumento .
                '_usuario_' .
                $usuarioId .
                '_' .
                time() .
                '.' .
                $ext;

            $rutaFisica =
                $directorio .
                '/' .
                $nombreArchivo;

            move_uploaded_file(
                $archivo['tmp_name'],
                $rutaFisica
            );

            require_once __DIR__ . '/../db/Connection.php';

            $pdo =
                Connection::getPDO();

            $stmt = $pdo->prepare(
                "SELECT id
                FROM documentos
                WHERE usuario_id = :usuario_id
                AND tipo_documento = :tipo_documento
                LIMIT 1"
            );

            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':tipo_documento' => $tipoDocumento
            ]);

            $documentoExistente =
                $stmt->fetch(PDO::FETCH_ASSOC);

            if ($documentoExistente) {

                $stmt = $pdo->prepare(
                    "UPDATE documentos
                    SET
                        nombre_original = :nombre_original,
                        ruta_archivo = :ruta_archivo,
                        estado = 'pendiente',
                        observaciones = NULL,
                        fecha_revision = NULL,
                        fecha_subida = NOW()
                    WHERE id = :id"
                );

                $stmt->execute([
                    ':id' => $documentoExistente['id'],
                    ':nombre_original' => $archivo['name'],
                    ':ruta_archivo' => '/uploads/' . $nombreArchivo
                ]);

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO documentos
                    (
                        usuario_id,
                        tipo_documento,
                        nombre_original,
                        ruta_archivo,
                        estado,
                        fecha_subida
                    )
                    VALUES
                    (
                        :usuario_id,
                        :tipo_documento,
                        :nombre_original,
                        :ruta_archivo,
                        'pendiente',
                        NOW()
                    )"
                );

                $stmt->execute([
                    ':usuario_id' => $usuarioId,
                    ':tipo_documento' => $tipoDocumento,
                    ':nombre_original' => $archivo['name'],
                    ':ruta_archivo' => '/uploads/' . $nombreArchivo
                ]);
            }


            header(
                'Location: ' .
                BASE_URL .
                '/subida_documentacion?toast=documento_subido'
            );

            exit;

        } catch (\Exception $e) {

            header(
                'Location: ' .
                BASE_URL .
                '/subida_documentacion?toast=error_subida'
            );

            exit;
        }
    }
}
