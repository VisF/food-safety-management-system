<?php
declare(strict_types=1);

/**
 * AdminControlador - Gestión administrativa del sistema
 * 
 * Dependencias esperadas:
 * - Modelos: CursoModelo, FechaCursoModelo, ExamenModelo, InscripcionModelo, 
 *   DocumentoModelo, UsuarioModelo
 * 
 * Vistas esperadas:
 * - vistas/panel_admin.php
 * - vistas/crear_curso.php
 * - vistas/crear_examen.php
 * - vistas/validacion_documentos.php
 * - vistas/crear_respuesta_admin.php
 */

class AdminControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    
    private ?object $cursoModelo = null;
    private ?object $fechaCursoModelo = null;
    private ?object $examenModelo = null;
    private ?object $inscripcionModelo = null;
    private ?object $documentoModelo = null;
    private ?object $usuarioModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar modelos si existen
     */
    private function inicializarModelos(): void
    {
        if (class_exists('CursoModelo')) {
            $this->cursoModelo = new CursoModelo();
        }
        if (class_exists('FechaCursoModelo')) {
            $this->fechaCursoModelo = new FechaCursoModelo();
        }
        if (class_exists('ExamenModelo')) {
            $this->examenModelo = new ExamenModelo();
        }
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('DocumentoModelo')) {
            $this->documentoModelo = new DocumentoModelo();
        }
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
    }

    /**
     * Registrar eventos en log
     */
    private function log(string $event, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = "[$timestamp] [$level] {$event} | {$contextStr}\n";
        error_log($message, 3, self::LOG_FILE);
    }

    // ==================== GESTIÓN DE CURSOS ====================

    // ==================== GESTIÓN DE EXÁMENES ====================


    // ==================== GESTIÓN DE INSCRIPCIONES ====================


    // ==================== GESTIÓN DE SOLICITUDES ====================

    /**
     * Responder una solicitud de revisión
     * 
     * @param int $id_solicitud ID de la solicitud
     * @param array $respuesta Array con datos: [
     *   'contenido' => string,
     *   'archivos' => array|null
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_respuesta' => int|null
     * ]
     */
    public function responderSolicitud(int $id_solicitud, array $respuesta): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'message' => 'Módulo solicitudes no disponible', 'id_respuesta' => null];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM solicitudes WHERE id = :id');
            $stmt->execute([':id' => $id_solicitud]);
            $sol = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$sol) return ['success' => false, 'message' => 'Solicitud no encontrada', 'id_respuesta' => null];

            // Guardar respuesta (si existe tabla de respuestas la usamos, si no actualizamos la solicitud)
            $id_resp = null;
            if ($pdo->query("SHOW TABLES LIKE 'solicitud_respuestas'")->rowCount() > 0) {
                $ins = $pdo->prepare('INSERT INTO solicitud_respuestas (solicitud_id, contenido, creado_por, fecha_creacion) VALUES (:sid, :cont, :por, NOW())');
                $ins->execute([':sid' => $id_solicitud, ':cont' => $respuesta['contenido'] ?? '', ':por' => $respuesta['creador'] ?? 'admin']);
                $id_resp = (int)$pdo->lastInsertId();
                $upd = $pdo->prepare('UPDATE solicitudes SET estado = :estado WHERE id = :id');
                $upd->execute([':estado' => 'respondida', ':id' => $id_solicitud]);
            } else {
                $upd = $pdo->prepare('UPDATE solicitudes SET estado = :estado, respuesta = :resp, fecha_respuesta = NOW() WHERE id = :id');
                $upd->execute([':estado' => 'respondida', ':resp' => $respuesta['contenido'] ?? '', ':id' => $id_solicitud]);
            }

            // Notificar usuario si aplica
            if (!empty($sol['usuario_id']) && class_exists('NotificacionControlador')) {
                try { 
                    $nc = new NotificacionControlador();
                    if (method_exists($nc, 'enviarNotificacion')) 
                        $nc->enviarNotificacion((int)$sol['usuario_id'], 
                    'solicitud_respondida', ['mensaje' => $respuesta['contenido'] ?? '']); 
                    } 
                catch (Exception $e) 
                    {}
            }

            $this->log('Solicitud respondida', 'INFO', ['id_solicitud' => $id_solicitud, 'id_respuesta' => $id_resp]);
            return ['success' => true, 'message' => 'Respuesta registrada correctamente', 'id_respuesta' => $id_resp];
        } catch (Exception $e) {
            $this->log('Error al responder solicitud', 'ERROR', ['id_solicitud' => $id_solicitud, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al responder solicitud: ' . $e->getMessage(),
                'id_respuesta' => null
            ];
        }
    }

    /**
     * Obtener solicitudes pendientes de respuesta
     * 
     * @return array [
     *   'success' => bool,
     *   'solicitudes' => [
     *     ['id' => int, 'id_inscripcion' => int, 'contenido' => string, 'fecha' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerSolicitudesPendientes(): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'solicitudes' => [], 'total' => 0];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE estado IN ('pendiente','nuevo') ORDER BY fecha_creacion DESC");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return ['success' => true, 'solicitudes' => $rows, 'total' => count($rows)];
        } catch (Exception $e) {
            $this->log('Error al obtener solicitudes pendientes', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'solicitudes' => [],
                'total' => 0
            ];
        }
    }

    // ==================== GESTIÓN DE USUARIOS ====================


    // ==================== EXPORTACIÓN DE DATOS ====================

    /**
     * Exportar datos en diferentes formatos
     * 
     * @param string $formato Formato de exportación: 'csv', 'excel', 'json'
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'archivo' => string|null (ruta del archivo generado)
     * ]
     */
    public function exportarDatos(string $formato): array
    {
        try {
            $format = strtolower(trim($formato));
            if (!in_array($format, ['csv', 'json','excel'])) return ['success' => false, 'message' => 'Formato no soportado', 'archivo' => null];
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'message' => 'DB no disponible', 'archivo' => null];
            require_once $pdoFile;
            $pdo = Connection::getPDO();

            // por ahora exportamos usuarios e inscripciones básicas
            $stmt = $pdo->prepare('SELECT u.id as usuario_id, u.nombre, u.apellido, u.email, u.dni, i.id as 
                                    inscripcion_id, i.curso_id, i.estado_tramite_id, i.fecha_inscripcion 
                                    FROM usuarios u LEFT JOIN inscripciones i ON i.usuario_id = u.id');
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $dir = __DIR__ . '/../descargas'; @mkdir($dir, 0755, true);
            $filename = $dir . '/export_' . date('Ymd_His') . '.' . ($format === 'csv' ? 'csv' : 'json');
            if ($format === 'json') {
                file_put_contents($filename, json_encode($rows, JSON_UNESCAPED_UNICODE));
            } else {
                $fp = fopen($filename, 'w');
                if ($fp === false) return ['success' => false, 'message' => 'No se pudo crear archivo', 'archivo' => null];
                if (!empty($rows)) fputcsv($fp, array_keys($rows[0]));
                foreach ($rows as $r) fputcsv($fp, $r);
                fclose($fp);
            }

            $this->log('Datos exportados', 'INFO', ['formato' => $format, 'archivo' => $filename]);
            return ['success' => true, 'message' => 'Datos exportados correctamente', 'archivo' => $filename];
        } catch (Exception $e) {
            $this->log('Error al exportar datos', 'ERROR', ['formato' => $formato, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al exportar datos: ' . $e->getMessage(),
                'archivo' => null
            ];
        }
    }

    // ==================== ESTADÍSTICAS Y REPORTES ====================

   
    
}
