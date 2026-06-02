<?php
declare(strict_types=1);

/**
 * ReporteControlador - Generación de reportes y estadísticas del sistema
 * 
 * Dependencias esperadas:
 * - Modelos: AuditoriaAccionesModelo, InscripcionModelo, ResultadoExamenModelo, 
 *   CarnetModelo, UsuarioModelo
 * 
 * Vistas esperadas:
 * - vistas/actividad_reciente.php
 * - vistas/detalle_actividad.php
 * - vistas/reportes_personalizados.php
 * - vistas/estadisticas_dashboard.php
 */

class ReporteControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/reporte_controller.log';
    private const DESCARGAS_DIR = __DIR__ . '/../descargas';
    
    private ?object $auditoriaModelo = null;
    private ?object $inscripcionModelo = null;
    private ?object $resultadoExamenModelo = null;
    private ?object $carnetModelo = null;
    private ?object $usuarioModelo = null;

    private function pdo(): \PDO
    {
        require_once __DIR__ . '/../db/Connection.php';
        return Connection::getPDO();
    }

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        @mkdir(self::DESCARGAS_DIR, 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar modelos si existen
     */
    private function inicializarModelos(): void
    {
        // Instanciar modelos si están disponibles (fallbacks permiten pruebas sin DB)
        if (class_exists('AuditoriaAccionesModelo')) {
            $this->auditoriaModelo = new AuditoriaAccionesModelo();
        }
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('ResultadoExamenModelo')) {
            $this->resultadoExamenModelo = new ResultadoExamenModelo();
        }
        if (class_exists('CarnetModelo')) {
            $this->carnetModelo = new CarnetModelo();
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

    // ==================== ACTIVIDAD Y AUDITORÍA ====================

    /**
     * Obtener actividad reciente del sistema
     * 
     * VISTA A LLAMAR: vistas/actividad_reciente.php
     * 
     * @param int $limite Número máximo de registros
     * @return array [
     *   'success' => bool,
     *   'actividades' => [
     *     ['id' => int, 'usuario' => string, 'accion' => string, 'tabla' => string, 'fecha' => string, 'detalles' => array, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerActividadReciente(int $limite = 20): array
    {
        try {
            $limite = max(1, min(100, $limite));
            $sql = 'SELECT i.id, u.nombre, u.apellido, u.dni, et.nombre AS estado_nombre, i.fecha_inscripcion
                    FROM inscripciones i
                    JOIN usuarios u ON u.id = i.usuario_id
                    LEFT JOIN estado_tramite et ON et.id = i.estado_tramite_id
                    ORDER BY i.fecha_inscripcion DESC
                    LIMIT :limite';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
            $stmt->execute();

            $actividades = [];
            // Recorrer resultados y normalizar para la vista de actividad
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $estadoNombre = strtolower((string)($row['estado_nombre'] ?? 'pendiente'));
                $actividades[] = [
                    'nombre' => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
                    'dni' => $row['dni'] ?? '',
                    'estado' => strtoupper(str_replace('_', ' ', $estadoNombre)),
                    'estado_class' => $estadoNombre,
                ];
            }

            return [
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener actividad reciente', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'actividades' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtener detalle de una actividad específica
     * 
     * VISTA A LLAMAR: vistas/detalle_actividad.php
     * 
     * @param int $id_auditoria ID del registro de auditoría
     * @return array [
     *   'success' => bool,
     *   'actividad' => [
     *     'id' => int,
     *     'id_usuario' => int,
     *     'usuario' => array,
     *     'accion' => string,
     *     'tabla' => string,
     *     'id_registro' => int,
     *     'valores_anteriores' => array,
     *     'valores_nuevos' => array,
     *     'fecha' => string,
     *     'ip' => string,
     *     'navegador' => string
     *   ]|null
     * ]
     */
    public function obtenerDetalleActividad(int $id_auditoria): array
    {
        try {
            // Preferir modelo si existe
            $row = null;
            if ($this->auditoriaModelo && method_exists($this->auditoriaModelo, 'obtenerPorId')) {
                $row = $this->auditoriaModelo->obtenerPorId($id_auditoria);
            } else {
                $stmt = $this->pdo()->prepare('SELECT a.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido FROM auditoria_acciones a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = :id LIMIT 1');
                $stmt->execute([':id' => $id_auditoria]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            }

            if (!$row) {
                return ['success' => true, 'actividad' => null];
            }

            $valores_anteriores = [];
            $valores_nuevos = [];
            if (!empty($row['datos_anteriores'])) {
                $valores_anteriores = json_decode($row['datos_anteriores'], true) ?? [];
            }
            if (!empty($row['datos_nuevos'])) {
                $valores_nuevos = json_decode($row['datos_nuevos'], true) ?? [];
            }

            $usuario = null;
            if (!empty($row['usuario_id'])) {
                $usuario = [
                    'id' => (int)$row['usuario_id'],
                    'nombre' => trim(($row['usuario_nombre'] ?? '') . ' ' . ($row['usuario_apellido'] ?? ''))
                ];
            }

            $actividad = [
                'id' => (int)$row['id'],
                'id_usuario' => !empty($row['usuario_id']) ? (int)$row['usuario_id'] : null,
                'usuario' => $usuario,
                'accion' => $row['accion'] ?? '',
                'tabla' => $row['tabla'] ?? '',
                'id_registro' => $row['id_registro'] ?? null,
                'valores_anteriores' => $valores_anteriores,
                'valores_nuevos' => $valores_nuevos,
                'fecha' => $row['fecha'] ?? $row['created_at'] ?? null,
                'ip' => $row['ip'] ?? null,
                'navegador' => $row['user_agent'] ?? $row['navegador'] ?? null,
            ];

            return ['success' => true, 'actividad' => $actividad];
        } catch (Exception $e) {
            $this->log('Error al obtener detalle de actividad', 'ERROR', ['id' => $id_auditoria, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'actividad' => null
            ];
        }
    }

    /**
     * Obtener auditoría de todas las acciones de un usuario
     * 
     * @param int $id_usuario ID del usuario
     * @return array [
     *   'success' => bool,
     *   'usuario' => array,
     *   'auditorias' => [
     *     ['id' => int, 'accion' => string, 'tabla' => string, 'fecha' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerAuditoriaUsuario(int $id_usuario): array
    {
        try {
            $usuario = null;
            $auditorias = [];

            // Obtener datos del usuario
            $stmt = $this->pdo()->prepare('SELECT id, nombre, apellido, dni, email FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_usuario]);
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Preferir modelo
            if ($this->auditoriaModelo && method_exists($this->auditoriaModelo, 'obtenerPorUsuario')) {
                $auditorias = $this->auditoriaModelo->obtenerPorUsuario($id_usuario);
            } else {
                $sql = 'SELECT id, accion, tabla, id_registro, datos_anteriores, datos_nuevos, fecha, ip, user_agent FROM auditoria_acciones WHERE usuario_id = :uid ORDER BY fecha DESC LIMIT 1000';
                $stmt = $this->pdo()->prepare($sql);
                $stmt->execute([':uid' => $id_usuario]);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $auditorias[] = [
                        'id' => (int)$r['id'],
                        'accion' => $r['accion'],
                        'tabla' => $r['tabla'],
                        'id_registro' => $r['id_registro'],
                        'datos_anteriores' => $r['datos_anteriores'] ? json_decode($r['datos_anteriores'], true) : null,
                        'datos_nuevos' => $r['datos_nuevos'] ? json_decode($r['datos_nuevos'], true) : null,
                        'fecha' => $r['fecha'],
                        'ip' => $r['ip'] ?? null,
                        'user_agent' => $r['user_agent'] ?? null
                    ];
                }
            }

            return [
                'success' => true,
                'usuario' => $usuario,
                'auditorias' => $auditorias,
                'total' => count($auditorias)
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener auditoría de usuario', 'ERROR', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'usuario' => [],
                'auditorias' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtener auditoría de cambios en una tabla específica
     * 
     * @param string $tabla Nombre de la tabla
     * @return array [
     *   'success' => bool,
     *   'tabla' => string,
     *   'cambios' => [
     *     ['id' => int, 'id_registro' => int, 'accion' => string, 'usuario' => string, 'fecha' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerAuditoriaTabla(string $tabla): array
    {
        try {
            // Validar existencia de tabla en la base de datos
            $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
            $stmt->execute([':t' => $tabla]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) {
                return ['success' => false, 'tabla' => $tabla, 'cambios' => [], 'total' => 0];
            }

            $cambios = [];
            if ($this->auditoriaModelo && method_exists($this->auditoriaModelo, 'obtenerPorTabla')) {
                $cambios = $this->auditoriaModelo->obtenerPorTabla($tabla);
            } else {
                $sql = 'SELECT id, usuario_id, accion, id_registro, datos_anteriores, datos_nuevos, fecha, ip FROM auditoria_acciones WHERE tabla = :tabla ORDER BY fecha DESC LIMIT 2000';
                $stmt = $this->pdo()->prepare($sql);
                $stmt->execute([':tabla' => $tabla]);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $cambios[] = [
                        'id' => (int)$r['id'],
                        'id_registro' => $r['id_registro'],
                        'accion' => $r['accion'],
                        'usuario' => !empty($r['usuario_id']) ? (int)$r['usuario_id'] : null,
                        'fecha' => $r['fecha'],
                        'datos_anteriores' => $r['datos_anteriores'] ? json_decode($r['datos_anteriores'], true) : null,
                        'datos_nuevos' => $r['datos_nuevos'] ? json_decode($r['datos_nuevos'], true) : null,
                        'ip' => $r['ip'] ?? null
                    ];
                }
            }

            return ['success' => true, 'tabla' => $tabla, 'cambios' => $cambios, 'total' => count($cambios)];
        } catch (Exception $e) {
            $this->log('Error al obtener auditoría de tabla', 'ERROR', ['tabla' => $tabla, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'tabla' => $tabla,
                'cambios' => [],
                'total' => 0
            ];
        }
    }

    // ==================== REPORTES PERSONALIZADOS ====================

    /**
     * Generar reporte personalizado con filtros
     * 
     * @param string $tipo Tipo de reporte: 'inscripciones', 'exámenes', 'carnets', 'usuarios', 'completo'
     * @param array $filtros Array con filtros: [
     *   'fecha_desde' => string|null,
     *   'fecha_hasta' => string|null,
     *   'estado' => string|null,
     *   'id_curso' => int|null,
     *   'id_usuario' => int|null,
     *   'rol' => string|null
     * ]
     * @return array [
     *   'success' => bool,
     *   'reporte' => [
     *     'tipo' => string,
     *     'periodo' => string,
     *     'filtros_aplicados' => array,
     *     'datos' => array,
     *     'total_registros' => int,
     *     'fecha_generacion' => string
     *   ]
     * ]
     */
    public function generarReporte(string $tipo, array $filtros): array
    {
        try {
            $allowed = ['inscripciones','examenes','carnets','usuarios','completo'];
            if (!in_array($tipo, $allowed, true)) {
                return ['success' => false, 'error' => 'Tipo de reporte inválido'];
            }

            $fechaDesde = null; $fechaHasta = null;
            if (!empty($filtros['fecha_desde'])) {
                $d = date_create_from_format('Y-m-d', $filtros['fecha_desde']);
                if ($d) $fechaDesde = $d->format('Y-m-d');
            }
            if (!empty($filtros['fecha_hasta'])) {
                $d = date_create_from_format('Y-m-d', $filtros['fecha_hasta']);
                if ($d) $fechaHasta = $d->format('Y-m-d');
            }

            $where = [];
            $params = [];
            if ($fechaDesde) { $where[] = "DATE(i.fecha_inscripcion) >= :fdesde"; $params[':fdesde'] = $fechaDesde; }
            if ($fechaHasta) { $where[] = "DATE(i.fecha_inscripcion) <= :fhasta"; $params[':fhasta'] = $fechaHasta; }
            if (!empty($filtros['id_curso'])) { $where[] = 'i.curso_id = :curso'; $params[':curso'] = (int)$filtros['id_curso']; }
            if (!empty($filtros['id_usuario'])) { $where[] = 'i.usuario_id = :uid'; $params[':uid'] = (int)$filtros['id_usuario']; }
            if (!empty($filtros['estado'])) { $where[] = 'et.nombre = :estado'; $params[':estado'] = $filtros['estado']; }

            $datos = [];
            $periodo = 'N/A';

            switch ($tipo) {
                case 'inscripciones':
                    $sql = 'SELECT i.id, i.usuario_id, u.nombre, u.apellido, i.curso_id, i.fecha_inscripcion, et.nombre AS estado FROM inscripciones i LEFT JOIN usuarios u ON u.id = i.usuario_id LEFT JOIN estado_tramite et ON et.id = i.estado_tramite_id';
                    if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
                    $sql .= ' ORDER BY i.fecha_inscripcion DESC LIMIT 5000';
                    $stmt = $this->pdo()->prepare($sql);
                    $stmt->execute($params);
                    while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $datos[] = $r;
                    }
                    $periodo = ($fechaDesde ?? 'inicio') . ' - ' . ($fechaHasta ?? 'hoy');
                    break;
                case 'usuarios':
                    $sql = 'SELECT id, nombre, apellido, email, dni, creado_en AS fecha_creacion FROM usuarios ORDER BY id DESC LIMIT 5000';
                    $stmt = $this->pdo()->query($sql);
                    $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                case 'carnets':
                    $sql = 'SELECT c.id, c.inscripcion_id, c.numero_carnet, c.fecha_emision, c.fecha_vencimiento, c.vigente FROM carnets c';
                    $stmt = $this->pdo()->query($sql);
                    $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                case 'examenes':
                case 'exámenes':
                    $sql = 'SELECT r.id, r.inscripcion_id, r.nota, r.aprobado, r.fecha_resultado FROM resultado_examen r ORDER BY r.fecha_resultado DESC LIMIT 5000';
                    $stmt = $this->pdo()->query($sql);
                    $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                case 'completo':
                    $datos = [
                        'inscripciones' => $this->generarReporte('inscripciones', $filtros)['reporte']['datos'] ?? [],
                        'usuarios' => $this->generarReporte('usuarios', $filtros)['reporte']['datos'] ?? [],
                        'carnets' => $this->generarReporte('carnets', $filtros)['reporte']['datos'] ?? []
                    ];
                    break;
            }

            $this->log('Reporte generado', 'INFO', ['tipo' => $tipo, 'filtros' => $filtros, 'registros' => count((array)$datos)]);

            return [
                'success' => true,
                'reporte' => [
                    'tipo' => $tipo,
                    'periodo' => $periodo,
                    'filtros_aplicados' => $filtros,
                    'datos' => $datos,
                    'total_registros' => is_array($datos) ? count($datos) : 0,
                    'fecha_generacion' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (Exception $e) {
            $this->log('Error al generar reporte', 'ERROR', ['tipo' => $tipo, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'reporte' => []
            ];
        }
    }

    /**
     * Descargar reporte en diferentes formatos
     * 
     * @param string $id_reporte ID del reporte generado
     * @param string $formato Formato: 'csv', 'excel', 'pdf', 'json'
     * @return array [
     *   'success' => bool,
     *   'archivo' => string|null (ruta relativa para descarga),
     *   'nombre_archivo' => string|null,
     *   'message' => string
     * ]
     */
    public function descargarReporte(string $id_reporte, string $formato): array
    {
        try {
            $allowed = ['csv','excel','pdf','json'];
            $formato = strtolower($formato);
            if (!in_array($formato, $allowed, true)) {
                return ['success' => false, 'archivo' => null, 'nombre_archivo' => null, 'message' => 'Formato no soportado'];
            }

            // Si el archivo ya existe en descargas, devolverlo
            $candidates = glob(self::DESCARGAS_DIR . '/' . $id_reporte . '.*');
            if (!empty($candidates)) {
                $archivo = $candidates[0];
                return ['success' => true, 'archivo' => str_replace(__DIR__ . '/../', '', $archivo), 'nombre_archivo' => basename($archivo), 'message' => 'Archivo disponible'];
            }

            // Intentar generar un reporte bajo el nombre de tipo (id_reporte puede ser tipo)
            $reporteResult = $this->generarReporte($id_reporte, []);
            if (!$reporteResult['success']) {
                return ['success' => false, 'archivo' => null, 'nombre_archivo' => null, 'message' => 'No se pudo generar reporte para: ' . $id_reporte];
            }

            $data = $reporteResult['reporte']['datos'] ?? [];
            $timestamp = date('Ymd_His');
            $baseName = $id_reporte . '_' . $timestamp;

            if ($formato === 'json') {
                $path = self::DESCARGAS_DIR . '/' . $baseName . '.json';
                file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            } else {
                // CSV/Excel fallback to CSV
                $path = self::DESCARGAS_DIR . '/' . $baseName . '.csv';
                $fp = fopen($path, 'w');
                if (is_array($data) && count($data) > 0) {
                    // If associative arrays nested, flatten simple scalar fields
                    $first = $data[0];
                    if (is_array($first)) {
                        $headers = array_keys($first);
                        fputcsv($fp, $headers);
                        foreach ($data as $row) {
                            $out = [];
                            foreach ($headers as $h) {
                                $val = $row[$h] ?? '';
                                if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                $out[] = $val;
                            }
                            fputcsv($fp, $out);
                        }
                    } else {
                        // simple list
                        foreach ($data as $row) fputcsv($fp, [$row]);
                    }
                }
                fclose($fp);
            }

            $rel = str_replace(__DIR__ . '/../', '', $path);
            $this->log('Reporte descargado', 'INFO', ['id_reporte' => $id_reporte, 'formato' => $formato, 'path' => $path]);

            return ['success' => true, 'archivo' => $rel, 'nombre_archivo' => basename($path), 'message' => 'Reporte generado correctamente'];
        } catch (Exception $e) {
            $this->log('Error al descargar reporte', 'ERROR', ['id_reporte' => $id_reporte, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'archivo' => null,
                'nombre_archivo' => null,
                'message' => 'Error al descargar reporte: ' . $e->getMessage()
            ];
        }
    }

    // ==================== ESTADÍSTICAS ====================

    /**
     * Obtener estadísticas generales del sistema
     * 
     * VISTA A LLAMAR: vistas/estadisticas_dashboard.php
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     'usuarios_totales' => int,
     *     'usuarios_activos' => int,
     *     'inscripciones_activas' => int,
     *     'exámenes_pendientes' => int,
     *     'carnets_vigentes' => int,
     *     'tasa_aprobacion' => float,
     *     'tasa_reprobacion' => float,
     *     'promedio_tiempo_tramite' => float (en días)
     *   ]
     * ]
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $pdo = $this->pdo();
            // Usuarios
            $stmt = $pdo->query('SELECT COUNT(*) FROM usuarios');
            $usuarios_totales = (int)$stmt->fetchColumn();
            // Usuarios activos (si existe columna activo)
            $usuarios_activos = $usuarios_totales;
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1');
                $usuarios_activos = (int)$stmt->fetchColumn();
            } catch (\Throwable $t) {
                // columna no existe -> fallback a total
            }

            // Inscripciones activas (no aprobadas ni rechazadas)
            $stmt = $pdo->query('SELECT COUNT(*) FROM inscripciones WHERE estado_tramite_id NOT IN (6,7)');
            $inscripciones_activas = (int)$stmt->fetchColumn();

            // Exámenes pendientes: inscripciones con examen sin resultado
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM inscripciones i LEFT JOIN resultado_examen r ON r.inscripcion_id = i.id WHERE r.id IS NULL');
                $examenes_pendientes = (int)$stmt->fetchColumn();
            } catch (\Throwable $t) {
                $examenes_pendientes = 0;
            }

            // Carnets vigentes
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM carnets WHERE vigente = 1');
                $carnets_vigentes = (int)$stmt->fetchColumn();
            } catch (\Throwable $t) {
                $carnets_vigentes = 0;
            }

            // Tasas de aprobación/reprobación sobre resultados de examen
            $aprobados = 0; $reprobados = 0; $total_examenes = 0;
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen');
                $total_examenes = (int)$stmt->fetchColumn();
                $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1'); $aprobados = (int)$stmt->fetchColumn();
                $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0'); $reprobados = (int)$stmt->fetchColumn();
            } catch (\Throwable $t) {
                $total_examenes = 0; $aprobados = 0; $reprobados = 0;
            }
            $tasa_aprobacion = $total_examenes > 0 ? round(($aprobados / $total_examenes) * 100.0, 2) : 0.0;
            $tasa_reprobacion = $total_examenes > 0 ? round(($reprobados / $total_examenes) * 100.0, 2) : 0.0;

            // Promedio tiempo trámite (días) para inscripciones cerradas
            try {
                $stmt = $pdo->query("SELECT AVG(DATEDIFF(IFNULL(fecha_ultima_modificacion, NOW()), fecha_inscripcion)) FROM inscripciones WHERE estado_tramite_id IN (6,7)");
                $promedio_tiempo = (float)($stmt->fetchColumn() ?: 0.0);
            } catch (\Throwable $t) {
                $promedio_tiempo = 0.0;
            }

            return [
                'success' => true,
                'estadisticas' => [
                    'usuarios_totales' => $usuarios_totales,
                    'usuarios_activos' => $usuarios_activos,
                    'inscripciones_activas' => $inscripciones_activas,
                    'exámenes_pendientes' => $examenes_pendientes,
                    'carnets_vigentes' => $carnets_vigentes,
                    'tasa_aprobacion' => $tasa_aprobacion,
                    'tasa_reprobacion' => $tasa_reprobacion,
                    'promedio_tiempo_tramite' => round($promedio_tiempo, 2)
                ]
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener estadísticas', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Obtener estadísticas desglosadas por rol
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     'inscriptos' => int,
     *     'administradores' => int,
     *     'inspectores' => int,
     *     'total' => int
     *   ]
     * ]
     */
    public function obtenerEstadisticasPorRol(): array
    {
        try {
            $pdo = $this->pdo();
            $sql = 'SELECT r.id, r.nombre, COUNT(ur.usuario_id) AS cantidad FROM roles r LEFT JOIN usuario_roles ur ON ur.rol_id = r.id GROUP BY r.id, r.nombre ORDER BY cantidad DESC';
            $stmt = $pdo->query($sql);
            $byRole = [];
            $total = 0;
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $nombre = strtolower($r['nombre'] ?? '');
                $count = (int)$r['cantidad'];
                $byRole[$nombre] = $count;
                $total += $count;
            }

            // Map common roles to keys
            $estadisticas = [
                'inscriptos' => $byRole['inscripto'] ?? $byRole['inscriptos'] ?? ($byRole['usuario'] ?? 0),
                'administradores' => $byRole['administrador'] ?? $byRole['admin'] ?? 0,
                'inspectores' => $byRole['inspector'] ?? 0,
                'total' => $total
            ];

            return ['success' => true, 'estadisticas' => $estadisticas];
        } catch (Exception $e) {
            $this->log('Error al obtener estadísticas por rol', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Obtener estadísticas de inscripciones por estado
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     'pendiente' => int,
     *     'en_curso' => int,
     *     'documentacion_validada' => int,
     *     'aprobada' => int,
     *     'rechazada' => int,
     *     'total' => int
     *   ]
     * ]
     */
    public function obtenerEstadisticasPorEstado(): array
    {
        try {
            $pdo = $this->pdo();
            $sql = 'SELECT et.nombre AS estado, COUNT(i.id) as cantidad FROM estado_tramite et LEFT JOIN inscripciones i ON i.estado_tramite_id = et.id GROUP BY et.id, et.nombre';
            $stmt = $pdo->query($sql);
            $map = [];
            $total = 0;
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = strtolower(str_replace(' ', '_', $r['estado'] ?? 'pendiente'));
                $count = (int)$r['cantidad'];
                $map[$key] = $count;
                $total += $count;
            }

            $estadisticas = [
                'pendiente' => $map['pendiente'] ?? 0,
                'en_curso' => $map['en curso'] ?? $map['en_curso'] ?? 0,
                'documentacion_validada' => $map['documentacion validada'] ?? $map['documentacion_validada'] ?? 0,
                'aprobada' => $map['aprobada'] ?? 0,
                'rechazada' => $map['rechazada'] ?? 0,
                'total' => $total
            ];

            return ['success' => true, 'estadisticas' => $estadisticas];
        } catch (Exception $e) {
            $this->log('Error al obtener estadísticas por estado', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Obtener estadísticas de inscripciones por curso
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     ['curso' => string, 'total_inscripciones' => int, 'aprobados' => int, 'reprobados' => int],
     *     ...
     *   ]
     * ]
     */
    public function obtenerEstadisticasPorCurso(): array
    {
        try {
            $pdo = $this->pdo();
            $sql = 'SELECT c.id, c.nombre AS curso, COUNT(i.id) AS total_inscripciones,
                           SUM(CASE WHEN r.aprobado = 1 THEN 1 ELSE 0 END) AS aprobados,
                           SUM(CASE WHEN r.aprobado = 0 THEN 1 ELSE 0 END) AS reprobados
                    FROM cursos c
                    LEFT JOIN inscripciones i ON i.curso_id = c.id
                    LEFT JOIN resultado_examen r ON r.inscripcion_id = i.id
                    GROUP BY c.id, c.nombre
                    ORDER BY total_inscripciones DESC';
            $stmt = $pdo->query($sql);
            $out = [];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $out[] = ['curso' => $r['curso'], 'total_inscripciones' => (int)$r['total_inscripciones'], 'aprobados' => (int)$r['aprobados'], 'reprobados' => (int)$r['reprobados']];
            }

            return ['success' => true, 'estadisticas' => $out];
        } catch (Exception $e) {
            $this->log('Error al obtener estadísticas por curso', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Obtener tasa de aprobación del sistema
     * 
     * @return array [
     *   'success' => bool,
     *   'tasa_aprobacion' => float,
     *   'total_exámenes' => int,
     *   'aprobados' => int,
     *   'reprobados' => int
     * ]
     */
    public function obtenerTasaAprobacion(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen');
            $total = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1');
            $aprobados = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0');
            $reprobados = (int)$stmt->fetchColumn();
            $tasa = $total > 0 ? round(($aprobados / $total) * 100.0, 2) : 0.0;
            return ['success' => true, 'tasa_aprobacion' => $tasa, 'total_exámenes' => $total, 'aprobados' => $aprobados, 'reprobados' => $reprobados];
        } catch (Exception $e) {
            $this->log('Error al obtener tasa de aprobación', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'tasa_aprobacion' => 0.0,
                'total_exámenes' => 0,
                'aprobados' => 0,
                'reprobados' => 0
            ];
        }
    }

    /**
     * Obtener tasa de reprobación del sistema
     * 
     * @return array [
     *   'success' => bool,
     *   'tasa_reprobacion' => float,
     *   'total_exámenes' => int,
     *   'aprobados' => int,
     *   'reprobados' => int
     * ]
     */
    public function obtenerTasaReprobacion(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen');
            $total = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0');
            $reprobados = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1');
            $aprobados = (int)$stmt->fetchColumn();
            $tasa = $total > 0 ? round(($reprobados / $total) * 100.0, 2) : 0.0;
            return ['success' => true, 'tasa_reprobacion' => $tasa, 'total_exámenes' => $total, 'aprobados' => $aprobados, 'reprobados' => $reprobados];
        } catch (Exception $e) {
            $this->log('Error al obtener tasa de reprobación', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'tasa_reprobacion' => 0.0,
                'total_exámenes' => 0,
                'aprobados' => 0,
                'reprobados' => 0
            ];
        }
    }

    /**
     * Obtener cantidad de certificados/carnets emitidos
     * 
     * @return array [
     *   'success' => bool,
     *   'carnets_emitidos' => int,
     *   'carnets_vigentes' => int,
     *   'carnets_vencidos' => int,
     *   'en_tramite' => int
     * ]
     */
    public function obtenerCertificadosEmitidos(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query('SELECT COUNT(*) FROM carnets');
            $emitidos = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM carnets WHERE vigente = 1');
            $vigentes = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM carnets WHERE vigente = 0');
            $vencidos = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT COUNT(*) FROM inscripciones i LEFT JOIN carnets c ON c.inscripcion_id = i.id WHERE c.id IS NULL');
            $en_tramite = (int)$stmt->fetchColumn();
            return ['success' => true, 'carnets_emitidos' => $emitidos, 'carnets_vigentes' => $vigentes, 'carnets_vencidos' => $vencidos, 'en_tramite' => $en_tramite];
        } catch (Exception $e) {
            $this->log('Error al obtener certificados emitidos', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'carnets_emitidos' => 0,
                'carnets_vigentes' => 0,
                'carnets_vencidos' => 0,
                'en_tramite' => 0
            ];
        }
    }

    /**
     * Obtener cantidad de inscripciones activas
     * 
     * @return array [
     *   'success' => bool,
     *   'inscripciones_activas' => int,
     *   'detalles' => [
     *     ['estado' => string, 'cantidad' => int],
     *     ...
     *   ]
     * ]
     */
    public function obtenerInscripcionesActivas(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query('SELECT COUNT(*) FROM inscripciones WHERE estado_tramite_id NOT IN (6,7)');
            $count = (int)$stmt->fetchColumn();
            $stmt = $pdo->query('SELECT et.nombre AS estado, COUNT(i.id) as cantidad FROM inscripciones i LEFT JOIN estado_tramite et ON et.id = i.estado_tramite_id WHERE i.estado_tramite_id NOT IN (6,7) GROUP BY i.estado_tramite_id');
            $detalles = [];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $detalles[] = ['estado' => $r['estado'] ?? 'Desconocido', 'cantidad' => (int)$r['cantidad']];
            }
            return ['success' => true, 'inscripciones_activas' => $count, 'detalles' => $detalles];
        } catch (Exception $e) {
            $this->log('Error al obtener inscripciones activas', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'inscripciones_activas' => 0,
                'detalles' => []
            ];
        }
    }

    /**
     * Obtener cantidad de documentos pendientes de validación
     * 
     * @return array [
     *   'success' => bool,
     *   'documentos_pendientes' => int,
     *   'detalles' => [
     *     ['tipo_documento' => string, 'cantidad' => int],
     *     ...
     *   ]
     * ]
     */
    public function obtenerDocumentosPendientes(): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->query("SELECT COUNT(*) FROM documentos WHERE (validado = 0 OR validado IS NULL)");
            $total = (int)$stmt->fetchColumn();
            $stmt = $pdo->query("SELECT tipo_documento, COUNT(*) as cantidad FROM documentos WHERE (validado = 0 OR validado IS NULL) GROUP BY tipo_documento");
            $detalles = [];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $detalles[] = ['tipo_documento' => $r['tipo_documento'] ?? 'desconocido', 'cantidad' => (int)$r['cantidad']];
            }
            return ['success' => true, 'documentos_pendientes' => $total, 'detalles' => $detalles];
        } catch (Exception $e) {
            $this->log('Error al obtener documentos pendientes', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'documentos_pendientes' => 0,
                'detalles' => []
            ];
        }
    }

    // ==================== REPORTES PERIÓDICOS ====================

    /**
     * Generar reporte periódico (diario, semanal, mensual)
     * 
     * @param string $fecha_inicio Fecha en formato Y-m-d
     * @param string $fecha_fin Fecha en formato Y-m-d
     * @return array [
     *   'success' => bool,
     *   'reporte' => [
     *     'periodo' => string,
     *     'nuevas_inscripciones' => int,
     *     'documentacion_validada' => int,
     *     'exámenes_realizados' => int,
     *     'aprobados' => int,
     *     'reprobados' => int,
     *     'carnets_emitidos' => int,
     *     'usuarios_nuevos' => int,
     *     'detalles' => array
     *   ]
     * ]
     */
    public function generarReportePeriodico(string $fecha_inicio, string $fecha_fin): array
    {
        try {
            $pdo = $this->pdo();
            $desde = date('Y-m-d', strtotime($fecha_inicio));
            $hasta = date('Y-m-d', strtotime($fecha_fin));

            // Nuevas inscripciones
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM inscripciones WHERE DATE(fecha_inscripcion) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $nuevas_inscripciones = (int)$stmt->fetchColumn();

            // Documentación validada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE validado = 1 AND DATE(fecha_validacion) BETWEEN :desde AND :hasta");
            $ok = $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $documentacion_validada = $ok ? (int)$stmt->fetchColumn() : 0;

            // Exámenes realizados y resultados
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultado_examen WHERE DATE(fecha_resultado) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $examenes_realizados = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1 AND DATE(fecha_resultado) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $aprobados = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0 AND DATE(fecha_resultado) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $reprobados = (int)$stmt->fetchColumn();

            // Carnets emitidos
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM carnets WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $carnets_emitidos = (int)$stmt->fetchColumn();

            // Usuarios nuevos
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_creacion) BETWEEN :desde AND :hasta');
            $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
            $usuarios_nuevos = (int)$stmt->fetchColumn();

            $this->log('Reporte periódico generado', 'INFO', ['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);

            return [
                'success' => true,
                'reporte' => [
                    'periodo' => "{$fecha_inicio} a {$fecha_fin}",
                    'nuevas_inscripciones' => $nuevas_inscripciones,
                    'documentacion_validada' => $documentacion_validada,
                    'exámenes_realizados' => $examenes_realizados,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'carnets_emitidos' => $carnets_emitidos,
                    'usuarios_nuevos' => $usuarios_nuevos,
                    'detalles' => []
                ]
            ];
        } catch (Exception $e) {
            $this->log('Error al generar reporte periódico', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'reporte' => []
            ];
        }
    }

    // ==================== EXPORTACIÓN PARA DIPA ====================

    /**
     * Generar documentación lista para exportar a DIPA
     * 
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'datos_listos' => int,
     *   'archivo' => string|null,
     *   'detalles' => array
     * ]
     */
    public function generarDocumentacionParaDIPA(): array
    {
        try {
            $pdo = $this->pdo();
            // Obtener inscripciones aprobadas (estado 6 = aprobado) sin carnet asignado
            $sql = 'SELECT i.id, u.dni, u.nombre, u.apellido, u.email, i.fecha_inscripcion FROM inscripciones i JOIN usuarios u ON u.id = i.usuario_id LEFT JOIN carnets c ON c.inscripcion_id = i.id WHERE i.estado_tramite_id = 6 AND c.id IS NULL';
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            if (empty($rows)) {
                $this->log('Documentación para DIPA generada - sin datos', 'INFO');
                return ['success' => true, 'message' => 'No hay datos para exportar', 'datos_listos' => 0, 'archivo' => null, 'detalles' => []];
            }

            $filename = 'dipa_export_' . date('Ymd_His') . '.csv';
            $filepath = self::DESCARGAS_DIR . '/' . $filename;
            $fp = fopen($filepath, 'w');
            // Cabeceras requeridas (ejemplo): dni,nombre,apellido,email,fecha_inscripcion
            fputcsv($fp, ['dni','nombre','apellido','email','fecha_inscripcion']);
            foreach ($rows as $r) {
                fputcsv($fp, [$r['dni'] ?? '', $r['nombre'] ?? '', $r['apellido'] ?? '', $r['email'] ?? '', $r['fecha_inscripcion'] ?? '']);
            }
            fclose($fp);

            $this->log('Documentación para DIPA generada', 'INFO', ['archivo' => $filepath, 'registros' => count($rows)]);
            return ['success' => true, 'message' => 'Documentación generada correctamente', 'datos_listos' => count($rows), 'archivo' => $filepath, 'detalles' => $rows];
        } catch (Exception $e) {
            $this->log('Error al generar documentación para DIPA', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al generar documentación: ' . $e->getMessage(),
                'datos_listos' => 0,
                'archivo' => null,
                'detalles' => []
            ];
        }
    }

    /**
     * Exportar datos de un examen específico para DIPA
     * 
     * @param int $id_examen ID del examen
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'datos' => [
     *     ['dni' => string, 'nombre' => string, 'apellido' => string, 'resultado' => string, ...],
     *     ...
     *   ],
     *   'archivo' => string|null,
     *   'total_registros' => int
     * ]
     */
    public function exportarParaDIPA(int $id_examen): array
    {
        try {
            $pdo = $this->pdo();
            // Validar examen
            $stmt = $pdo->prepare('SELECT id, fecha, hora, ubicacion FROM examenes WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_examen]);
            $ex = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ex) return ['success' => false, 'message' => 'Examen no encontrado', 'datos' => [], 'archivo' => null, 'total_registros' => 0];

            $sql = 'SELECT u.dni, u.nombre, u.apellido, re.nota, re.aprobado, re.fecha_resultado FROM resultado_examen re JOIN inscripciones i ON i.id = re.inscripcion_id JOIN usuarios u ON u.id = i.usuario_id WHERE re.id_examen = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_examen]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $filename = 'dipa_examen_' . $id_examen . '_' . date('Ymd_His') . '.csv';
            $filepath = self::DESCARGAS_DIR . '/' . $filename;
            $fp = fopen($filepath, 'w');
            fputcsv($fp, ['dni','nombre','apellido','nota','aprobado','fecha_resultado']);
            foreach ($rows as $r) {
                fputcsv($fp, [$r['dni'] ?? '', $r['nombre'] ?? '', $r['apellido'] ?? '', $r['nota'] ?? '', $r['aprobado'] ?? '', $r['fecha_resultado'] ?? '']);
            }
            fclose($fp);

            $this->log('Datos de examen exportados para DIPA', 'INFO', ['id_examen' => $id_examen, 'archivo' => $filepath, 'registros' => count($rows)]);
            return ['success' => true, 'message' => 'Datos exportados correctamente', 'datos' => $rows, 'archivo' => $filepath, 'total_registros' => count($rows)];
        } catch (Exception $e) {
            $this->log('Error al exportar para DIPA', 'ERROR', ['id_examen' => $id_examen, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al exportar: ' . $e->getMessage(),
                'datos' => [],
                'archivo' => null,
                'total_registros' => 0
            ];
        }
    }
}
