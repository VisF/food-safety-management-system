<?php
declare(strict_types=1);

/**
 * ExamenControlador - Gestión de exámenes, resultados y asistencia
 *
 * Dependencias esperadas:
 * - Modelos: ExamenModelo, ResultadoExamenModelo, AsistenciaModelo, InscripcionModelo
 *
 * Responsabilidades:
 * - Listar y obtener detalles de exámenes
 * - Registrar y obtener resultados de exámenes
 * - Registrar asistencia a exámenes
 * - Verificar habilitación para rendir examen
 * - Obtener aprobados de un examen
 * - Reportes de exámenes próximos
 *
 * Validaciones:
 * - Verificar habilitación: documentación completa, asistencia mínima (si corresponde)
 * - Nota válida: 0-100
 * - Calificación mínima de aprobación: 60
 */

class ExamenControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/examen_controller.log';
    private const NOTA_MINIMA_APROBACION = 60;
    private const ASISTENCIA_MINIMA_PRESENCIAL = 80.0; // 80%

    private ?ExamenModelo $examenModelo = null;
    private ?ResultadoExamenModelo $resultadoExamenModelo = null;
    private ?AsistenciaModelo $asistenciaModelo = null;
    private ?InscripcionModelo $inscripcionModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar todas las dependencias de modelos
     * @return void
     */
    private function inicializarModelos(): void
    {
        // Instanciar modelos sólo si la clase está definida (evita errores en entornos parciales).
        if (class_exists('ExamenModelo')) {
            $this->examenModelo = new ExamenModelo();
        }
        // Modelo de resultados (opcional en entornos de pruebas).
        if (class_exists('ResultadoExamenModelo')) {
            $this->resultadoExamenModelo = new ResultadoExamenModelo();
        }
        // Modelo de asistencia (registro de sesiones presenciales).
        if (class_exists('AsistenciaModelo')) {
            $this->asistenciaModelo = new AsistenciaModelo();
        }
        // Modelo de inscripciones.
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
    }

    /**
     * Registrar evento en el log
     * @param string $evento Descripción del evento
     * @param array $datos Datos asociados al evento
     * @return void
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    /**
     * Listar todos los exámenes
     *
     * @return array Array de todos los exámenes ordenados por fecha
     */
    public function listarExamenes(): array
    {
        try {
            // Preferir el modelo cuando esté disponible (mejor encapsulación y testabilidad).
            if ($this->examenModelo && method_exists($this->examenModelo, 'obtenerTodos')) {
                return $this->examenModelo->obtenerTodos();
            }
            // Fallback: consulta directa a la base de datos si no hay modelo.
            $conn = __DIR__ . '/../db/Connection.php';
            if (!file_exists($conn)) return [];
            require_once $conn;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM examenes ORDER BY fecha DESC, hora ASC');
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_LISTAR_EXAMENES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener examen por ID
     *
     * @param int $id ID del examen
     * @return array|null Datos del examen o null
     */
    public function obtenerExamen(int $id): ?array
    {
        try {
            // Usar modelo si existe para obtener el examen por id.
            if ($this->examenModelo && method_exists($this->examenModelo, 'obtenerPorId')) {
                return $this->examenModelo->obtenerPorId($id);
            }
            // Fallback: consulta directa a DB
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return null; require_once $conn; $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT * FROM examenes WHERE id = :id'); $stmt->execute([':id' => $id]); $ex = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $ex ?: null;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_EXAMEN', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener detalle completo de examen para vista
     *
     * @param int $id ID del examen
     * @return array Datos: examen, cupos disponibles, horario, ubicación, cantidad inscriptos
     */
    public function obtenerDetalleExamen(int $id): array
    {
        try {
            // Preferir modelo para obtener detalle y cálculo de cupos (modelo encapsula reglas de negocio).
            if ($this->examenModelo && method_exists($this->examenModelo, 'obtenerPorId')) {
                $e = $this->examenModelo->obtenerPorId($id);
                $pdoFile = __DIR__ . '/../db/Connection.php'; if (file_exists($pdoFile)) { require_once $pdoFile; $pdo = Connection::getPDO(); $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM inscripciones WHERE examen_id = :id'); $stmt->execute([':id' => $id]); $c = (int)$stmt->fetchColumn(); } else { $c = 0; }
                $cupos = (int)($e['cupos'] ?? $e['cupos_totales'] ?? 0);
                return ['id' => $id, 'fecha' => $e['fecha'] ?? '', 'hora' => $e['hora'] ?? '', 'ubicacion' => $e['ubicacion'] ?? $e['lugar'] ?? '', 'cupos_totales' => $cupos, 'cupos_disponibles' => max(0, $cupos - $c), 'total_inscriptos' => $c, 'estado' => $e['estado'] ?? ''];
            }
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return []; require_once $conn; $pdo = Connection::getPDO();
            $sql = 'SELECT e.*, COUNT(i.id) as total_inscriptos FROM examenes e LEFT JOIN inscripciones i ON i.examen_id = e.id WHERE e.id = :id GROUP BY e.id';
            $stmt = $pdo->prepare($sql); $stmt->execute([':id' => $id]); $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return [];
            $cupos = (int)($row['cupos'] ?? $row['cupos_totales'] ?? 0);
            return ['id' => $id, 'fecha' => $row['fecha'] ?? '', 'hora' => $row['hora'] ?? '', 'ubicacion' => $row['ubicacion'] ?? $row['lugar'] ?? '', 'cupos_totales' => $cupos, 'cupos_disponibles' => max(0, $cupos - (int)$row['total_inscriptos']), 'total_inscriptos' => (int)$row['total_inscriptos'], 'estado' => $row['estado'] ?? ''];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DETALLE_EXAMEN', ['id' => $id, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener exámenes próximos (siguientes 30 días)
     *
     * @return array Array de exámenes próximos ordenados por fecha
     */
    public function obtenerExamenesProximos(): array
    {
        try {
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return []; require_once $conn; $pdo = Connection::getPDO();
            $sql = "SELECT * FROM examenes WHERE fecha BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) AND (estado IS NULL OR estado != 'finalizado') ORDER BY fecha ASC, hora ASC";
            $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_EXAMENES_PROXIMOS', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener exámenes disponibles (con cupos)
     *
     * @return array Array de exámenes con cupos disponibles
     */
    public function obtenerExamenesDisponibles(): array
    {
        try {
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return []; require_once $conn; $pdo = Connection::getPDO();
            $sql = 'SELECT e.*, (e.cupos - COUNT(i.id)) as cupos_libres FROM examenes e LEFT JOIN inscripciones i ON i.examen_id = e.id WHERE (e.estado IS NULL OR e.estado != "finalizado") AND e.fecha > NOW() GROUP BY e.id HAVING cupos_libres > 0 ORDER BY e.fecha ASC';
            $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_EXAMENES_DISPONIBLES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Registrar resultado de examen
     *
     * @param int $id_inscripcion ID de la inscripción
     * @param array $datos Datos: nota (0-100), observaciones (opcional)
     * @return array ['success' => bool, 'aprobado' => bool, 'mensaje' => string]
     */
    public function registrarResultado(int $id_inscripcion, array $datos): array
    {
        try {
            $nota = (float) ($datos['nota'] ?? 0);
            $observaciones = $datos['observaciones'] ?? null;

            // Validaciones y guardado de resultado (implementado abajo)
            
            // Validaciones básicas
            // Validación: la nota debe estar en el rango permitido
            if ($nota < 0 || $nota > 100) return ['success' => false, 'aprobado' => false, 'mensaje' => 'Nota fuera de rango'];

            // Asegurar que el modelo de resultados está disponible
            if (!$this->resultadoExamenModelo) return ['success' => false, 'aprobado' => false, 'mensaje' => 'Modelo de resultados no disponible'];

            // Verificar existencia previa de resultado para evitar duplicados
            $prev = $this->resultadoExamenModelo->obtenerPorInscripcion($id_inscripcion);
            if ($prev) return ['success' => false, 'aprobado' => (bool)$prev['aprobado'], 'mensaje' => 'Ya existe un resultado registrado para esta inscripción'];

            $examen_id = (int)($datos['id_examen'] ?? 0);
            if ($examen_id <= 0) {
                // intentar obtener examen desde inscripción
                $ins = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id_inscripcion) : null;
                $examen_id = $ins ? (int)($ins['examen_id'] ?? 0) : 0;
            }

            $aprobado = $nota >= self::NOTA_MINIMA_APROBACION ? 1 : 0;
            $create = $this->resultadoExamenModelo->crear(['id_inscripcion' => $id_inscripcion, 'id_examen' => $examen_id, 'nota' => $nota, 'aprobado' => $aprobado, 'observaciones' => $observaciones]);
            if ($create === false) return ['success' => false, 'aprobado' => false, 'mensaje' => 'No se pudo guardar el resultado'];

            // actualizar estado de inscripción: 3=aprobado/en trámite carnet, 4=reprobado
            $pdo = null;
            $connFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
            }
            if ($pdo) { //hardcode de estados, idealmente esto debería estar en un modelo o configuración centralizada
                $nuevoEstado = $aprobado ? 7 : 3; // 7=aprobado, 3=reprobado (en trámite carnet)
                $upd = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :est WHERE id = :id');
                $upd->execute([':est' => $nuevoEstado, ':id' => $id_inscripcion]);
            }

            $this->registrarLog('RESULTADO_EXAMEN_REGISTRADO', ['id_inscripcion' => $id_inscripcion, 'nota' => $nota, 'aprobado' => (bool)$aprobado]);

            return ['success' => true, 'aprobado' => (bool)$aprobado, 'mensaje' => $aprobado ? 'Examen aprobado' : 'Examen reprobado'];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_REGISTRAR_RESULTADO', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'aprobado' => false,
                'mensaje' => 'Error al registrar resultado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener resultado de examen por inscripción
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array|null Datos del resultado o null si no existe
     */
    public function obtenerResultado(int $id_inscripcion): ?array
    {
        try {
            if (!$this->resultadoExamenModelo) return null;
            return $this->resultadoExamenModelo->obtenerPorInscripcion($id_inscripcion);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_RESULTADO', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar si inscripción puede rendir examen
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['habilitado' => bool, 'motivos' => array]
     */
    public function verificarHabilitacion(int $id_inscripcion): array
    {
        try {
            $motivos = [];
            $habilitado = true;

            $validCtrl = new ValidacionControlador();
            $doc = $validCtrl->validarDocumentacion($id_inscripcion);
            if (!$doc['valido']) { $habilitado = false; $motivos[] = 'Documentación incompleta: ' . implode(', ', $doc['documentos_faltantes']); }

            $insc = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id_inscripcion) : null;
            if ($insc) {
                $curso_id = (int)($insc['curso_id'] ?? 0);
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) { require_once $pdoFile; $pdo = Connection::getPDO(); $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id'); $stmt->execute([':id' => $curso_id]); $r = $stmt->fetch(); $modalidad = $r['modalidad'] ?? 'presencial'; }
                if (($modalidad ?? 'presencial') === 'presencial') {
                    $asis = $validCtrl->validarAsistencia($id_inscripcion);
                    if (!$asis['valido']) { $habilitado = false; $motivos[] = 'Asistencia insuficiente'; }
                } else {
                    $m = $validCtrl->validarCursoMoodle($id_inscripcion);
                    if (!$m['valido']) { $habilitado = false; $motivos[] = 'Falta certificado Moodle'; }
                }

                // recursante
                $rec = $validCtrl->validarPlazoRecursante((int)$insc['usuario_id']);
                if (!$rec['puede_recursar']) { $habilitado = false; $motivos[] = 'Plazo recursante no cumplido'; }
            }

            return ['habilitado' => $habilitado, 'motivos' => $motivos];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VERIFICAR_HABILITACION', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'habilitado' => false,
                'motivos' => ['Error en validación: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Obtener asistencia de una inscripción
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array Datos: total_sesiones, sesiones_presentes, porcentaje_asistencia
     */
    public function obtenerAsistencia(int $id_inscripcion): array
    {
        try {
            $asModel = new AsistenciaModelo();
            $tot = $asModel->obtenerTotalAsistencias($id_inscripcion);
            $presentes = (int)($tot['presentes'] ?? 0);
            $total = (int)($tot['total'] ?? 0);
            $porcentaje = $total > 0 ? ($presentes / $total) * 100.0 : 0.0;
            return ['total_sesiones' => $total, 'sesiones_presentes' => $presentes, 'porcentaje_asistencia' => round($porcentaje, 2)];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_ASISTENCIA', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Registrar asistencia
     *
     * @param int $id_inscripcion ID de la inscripción
     * @param bool $presente Indicador de asistencia
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function registrarAsistencia(int $id_inscripcion, bool $presente): array
    {
        try {
            $asModel = new AsistenciaModelo();
            $cre = $asModel->crear(['id_inscripcion' => $id_inscripcion, 'fecha' => date('Y-m-d'), 'presente' => $presente ? 1 : 0]);
            if ($cre === false) return ['success' => false, 'mensaje' => 'No se pudo registrar asistencia'];
            $this->registrarLog('ASISTENCIA_REGISTRADA', ['id_inscripcion' => $id_inscripcion, 'presente' => $presente]);
            return ['success' => true, 'mensaje' => 'Asistencia registrada correctamente'];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_REGISTRAR_ASISTENCIA', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al registrar asistencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener próximos exámenes de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Array de exámenes próximos en los que el usuario está inscrito
     */
    public function obtenerProximosExamenes(int $id_usuario): array
    {
        try {
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return []; require_once $conn; $pdo = Connection::getPDO();
            $sql = 'SELECT DISTINCT e.* FROM examenes e JOIN inscripciones i ON i.examen_id = e.id WHERE i.usuario_id = :uid AND e.fecha > NOW() AND i.estado_tramite_id NOT IN (4,5) ORDER BY e.fecha ASC';
            $stmt = $pdo->prepare($sql); $stmt->execute([':uid' => $id_usuario]); return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_PROXIMOS_EXAMENES', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener aprobados de un examen
     *
     * @param int $id_examen ID del examen
     * @return array Array de usuarios que aprobaron el examen
     */
    public function obtenerAprobados(int $id_examen): array
    {
        try {
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return []; require_once $conn; $pdo = Connection::getPDO();
            $sql = 'SELECT u.*, re.nota, re.fecha AS fecha_resultado FROM usuarios u JOIN inscripciones i ON u.id = i.usuario_id JOIN resultado_examen re ON i.id = re.id_inscripcion WHERE re.id_examen = :eid AND re.aprobado = 1 ORDER BY re.fecha DESC';
            $stmt = $pdo->prepare($sql); $stmt->execute([':eid' => $id_examen]); $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $rows;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_APROBADOS', ['id_examen' => $id_examen, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
