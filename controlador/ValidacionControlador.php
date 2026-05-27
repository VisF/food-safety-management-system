<?php
declare(strict_types=1);

/**
 * ValidacionControlador - Gestión de validaciones de inscripciones
 *
 * Dependencias esperadas:
 * - Modelos: InscripcionModelo, DocumentoModelo, AsistenciaModelo, ResultadoExamenModelo, EstadoTramiteModelo
 *
 * Responsabilidades:
 * - Validar cumplimiento de requisitos por flujo (presencial, virtual, recursante, renovación)
 * - Verificar documentación, asistencia, curso virtual y plazos
 * - Gestionar motivos de rechazo
 * - Procesar validación integral
 * - Generar reportes de validaciones
 *
 * Validaciones por flujo:
 * - Presencial: documentación + asistencia mínima 80% + examen
 * - Virtual: documentación + certificado Moodle + examen
 * - Recursante: documentación + examen dentro de 3 meses
 * - Renovación: documentación + examen
 */

class ValidacionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/validacion_controller.log';
    private const ASISTENCIA_MINIMA_PRESENCIAL = 80.0; // 80%
    private const PLAZO_RECURSANTE_DIAS = 90; // 3 meses
    private const PORCENTAJE_DOCUMENTACION_REQUERIDA = 100; // 100% de documentos

    private ?InscripcionModelo $inscripcionModelo = null;
    private ?DocumentoModelo $documentoModelo = null;
    private ?AsistenciaModelo $asistenciaModelo = null;
    private ?ResultadoExamenModelo $resultadoExamenModelo = null;
    private ?EstadoTramiteModelo $estadoTramiteModelo = null;

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
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('DocumentoModelo')) {
            $this->documentoModelo = new DocumentoModelo();
        }
        if (class_exists('AsistenciaModelo')) {
            $this->asistenciaModelo = new AsistenciaModelo();
        }
        if (class_exists('ResultadoExamenModelo')) {
            $this->resultadoExamenModelo = new ResultadoExamenModelo();
        }
        if (class_exists('EstadoTramiteModelo')) {
            $this->estadoTramiteModelo = new EstadoTramiteModelo();
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
     * Validar asistencia (solo para curso presencial)
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['valido' => bool, 'porcentaje_asistencia' => float, 'sesiones_presentes' => int, 'total_sesiones' => int]
     */
    public function validarAsistencia(int $id_inscripcion): array
    {
        try {
            if (!$this->inscripcionModelo) return ['valido' => false, 'porcentaje_asistencia' => 0.0, 'sesiones_presentes' => 0, 'total_sesiones' => 0];
            $insc = $this->inscripcionModelo->obtenerPorId($id_inscripcion);
            if (!$insc) return ['valido' => false, 'porcentaje_asistencia' => 0.0, 'sesiones_presentes' => 0, 'total_sesiones' => 0];

            $curso_id = (int)($insc['curso_id'] ?? 0);
            $modalidad = 'presencial';
            $connFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
                $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id');
                $stmt->execute([':id' => $curso_id]);
                $row = $stmt->fetch();
                if ($row && isset($row['modalidad'])) $modalidad = $row['modalidad'];
            }

            if ($modalidad !== 'presencial') {
                return ['valido' => true, 'porcentaje_asistencia' => 100.0, 'sesiones_presentes' => 0, 'total_sesiones' => 0];
            }

            $asistencia = new AsistenciaModelo();
            $tot = $asistencia->obtenerTotalAsistencias($id_inscripcion);
            $presentes = (int)($tot['presentes'] ?? 0);
            $total = (int)($tot['total'] ?? 0);
            $porcentaje = $total > 0 ? ($presentes / $total) * 100.0 : 0.0;

            $valido = $porcentaje >= self::ASISTENCIA_MINIMA_PRESENCIAL;
            return ['valido' => $valido, 'porcentaje_asistencia' => round($porcentaje, 2), 'sesiones_presentes' => $presentes, 'total_sesiones' => $total];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_ASISTENCIA', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return ['valido' => false, 'porcentaje_asistencia' => 0.0, 'sesiones_presentes' => 0, 'total_sesiones' => 0];
        }
    }

    /**
     * Validar documentación completa
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['valido' => bool, 'documentos_requeridos' => array, 'documentos_faltantes' => array]
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {
            // obtener inscripcion y modalidad
            if (!$this->inscripcionModelo) return ['valido' => false, 'documentos_requeridos' => [], 'documentos_faltantes' => []];
            $insc = $this->inscripcionModelo->obtenerPorId($id_inscripcion);
            if (!$insc) return ['valido' => false, 'documentos_requeridos' => [], 'documentos_faltantes' => []];

            $curso_id = (int)($insc['curso_id'] ?? 0);
            $modalidad = 'presencial';
            $connFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
                $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id');
                $stmt->execute([':id' => $curso_id]);
                $row = $stmt->fetch();
                if ($row && isset($row['modalidad'])) $modalidad = $row['modalidad'];
            }

            $requeridos = ['DNI', 'Foto carnet'];
            if ($modalidad === 'virtual') $requeridos[] = 'certificado_moodle';

            $docModel = new DocumentoModelo();
            $docs = $docModel->obtenerPorInscripcion($id_inscripcion);
            $presentes = [];
            foreach ($docs as $d) {
                if ((int)($d['validado'] ?? 0) === 1) {
                    $presentes[] = $d['tipo_documento'];
                }
            }

            $faltantes = array_values(array_diff($requeridos, $presentes));
            $valido = empty($faltantes);
            return ['valido' => $valido, 'documentos_requeridos' => $requeridos, 'documentos_faltantes' => $faltantes];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_DOCUMENTACION', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'valido' => false,
                'documentos_requeridos' => [],
                'documentos_faltantes' => []
            ];
        }
    }

    /**
     * Validar certificado de curso virtual (Moodle)
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['valido' => bool, 'certificado_presente' => bool, 'fecha_certificado' => string|null]
     */
    public function validarCursoMoodle(int $id_inscripcion): array
    {
        try {
            if (!$this->inscripcionModelo) return ['valido' => false, 'certificado_presente' => false, 'fecha_certificado' => null];
            $insc = $this->inscripcionModelo->obtenerPorId($id_inscripcion);
            if (!$insc) return ['valido' => false, 'certificado_presente' => false, 'fecha_certificado' => null];

            $curso_id = (int)($insc['curso_id'] ?? 0);
            $modalidad = 'presencial';
            $connFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
                $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id');
                $stmt->execute([':id' => $curso_id]);
                $row = $stmt->fetch();
                if ($row && isset($row['modalidad'])) $modalidad = $row['modalidad'];
            }

            if ($modalidad !== 'virtual') {
                return ['valido' => true, 'certificado_presente' => true, 'fecha_certificado' => null];
            }

            $docModel = new DocumentoModelo();
            $docs = $docModel->obtenerPorInscripcion($id_inscripcion);
            foreach ($docs as $d) {
                $tipo = $d['tipo_documento'] ?? '';
                if (((int)($d['validado'] ?? 0) === 1) && (strtolower($tipo) === 'certificado_moodle' || strtolower($tipo) === 'certificado moodle')) {
                    return ['valido' => true, 'certificado_presente' => true, 'fecha_certificado' => $d['fecha_subida'] ?? null];
                }
            }

            // fallback: preguntar a MoodleModelo si hay certificado validado por usuario/curso
            $moodle = new MoodleModelo();
            $puede = $moodle->validarCursoCompletado((int)$insc['usuario_id'], $curso_id);
            return ['valido' => (bool)$puede, 'certificado_presente' => (bool)$puede, 'fecha_certificado' => null];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_CURSO_MOODLE', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'valido' => false,
                'certificado_presente' => false,
                'fecha_certificado' => null
            ];
        }
    }

    /**
     * Validar plazo de recursante (3 meses desde último examen fallido)
     *
     * @param int $id_usuario ID del usuario
     * @return array ['puede_recursar' => bool, 'dias_restantes' => int|null, 'ultimo_examen_fallido' => string|null]
     */
    public function validarPlazoRecursante(int $id_usuario): array
    {
        try {
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['puede_recursar' => true, 'dias_restantes' => null, 'ultimo_examen_fallido' => null];
            require_once $connFile;
            $pdo = Connection::getPDO();
            $sql = 'SELECT re.* FROM resultado_examen re JOIN inscripciones i ON re.inscripcion_id = i.id WHERE i.usuario_id = :uid AND re.aprobado = 0 ORDER BY re.fecha_resultado DESC LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $id_usuario]);
            $row = $stmt->fetch();
            if (!$row) return ['puede_recursar' => true, 'dias_restantes' => null, 'ultimo_examen_fallido' => null];

            $fecha = $row['fecha_resultado'] ?? null;
            if (!$fecha) return ['puede_recursar' => true, 'dias_restantes' => null, 'ultimo_examen_fallido' => null];
            $ts = strtotime($fecha);
            $hoy = time();
            $dias_transcurridos = (int)floor(($hoy - $ts) / 86400);
            $dias_restantes = max(0, self::PLAZO_RECURSANTE_DIAS - $dias_transcurridos);
            $puede = $dias_transcurridos >= self::PLAZO_RECURSANTE_DIAS;
            return ['puede_recursar' => $puede, 'dias_restantes' => $dias_restantes, 'ultimo_examen_fallido' => $fecha];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_PLAZO_RECURSANTE', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]);
            return [
                'puede_recursar' => false,
                'dias_restantes' => null,
                'ultimo_examen_fallido' => null
            ];
        }
    }

    /**
     * Validar si puede renovar carnet
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['puede_renovar' => bool, 'carnet_vencido' => bool, 'fecha_vencimiento' => string|null]
     */
    public function validarRenovacion(int $id_inscripcion): array
    {
        try {
            if (!$this->inscripcionModelo) return ['puede_renovar' => false, 'carnet_vencido' => false, 'fecha_vencimiento' => null];
            $insc = $this->inscripcionModelo->obtenerPorId($id_inscripcion);
            if (!$insc) return ['puede_renovar' => false, 'carnet_vencido' => false, 'fecha_vencimiento' => null];

            $usuario_id = (int)($insc['usuario_id'] ?? 0);
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['puede_renovar' => false, 'carnet_vencido' => false, 'fecha_vencimiento' => null];
            require_once $connFile;
            $pdo = Connection::getPDO();
            $sql = 'SELECT c.* FROM carnets c JOIN inscripciones i ON c.inscripcion_id = i.id WHERE i.usuario_id = :uid ORDER BY c.fecha_vencimiento DESC LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $usuario_id]);
            $row = $stmt->fetch();
            if (!$row) return ['puede_renovar' => false, 'carnet_vencido' => false, 'fecha_vencimiento' => null];

            $fecha_venc = $row['fecha_vencimiento'] ?? null;
            $vencido = $fecha_venc ? (strtotime($fecha_venc) < time()) : false;
            return ['puede_renovar' => $vencido, 'carnet_vencido' => $vencido, 'fecha_vencimiento' => $fecha_venc];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_RENOVACION', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'puede_renovar' => false,
                'carnet_vencido' => false,
                'fecha_vencimiento' => null
            ];
        }
    }

    /**
     * Obtener motivos de rechazo de un resultado de examen
     *
     * @param int $id_resultado ID del resultado de examen
     * @return array Array de motivos de rechazo registrados
     */
    public function obtenerMotivosRechazo(int $id_resultado): array
    {
        try {
            // TODO: SELECT * FROM motivo_rechazo WHERE id_resultado_examen = $id_resultado ORDER BY fecha_registro DESC

            return [];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_MOTIVOS_RECHAZO', ['id_resultado' => $id_resultado, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Procesar validación integral de inscripción
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['resultado_general' => bool, 'validaciones' => array, 'pueden_rendir' => bool]
     */
    public function procesarValidacion(int $id_inscripcion): array
    {
        try {
            // TODO: SELECT i.*, c.modalidad, ti.nombre as tipo FROM inscripcion i
            // TODO: LEFT JOIN curso c ON i.id_curso = c.id
            // TODO: LEFT JOIN tipo_inscripcion ti ON i.id_tipo_inscripcion = ti.id
            // TODO: WHERE i.id = $id_inscripcion

            $validaciones = [
                'documentacion' => $this->validarDocumentacion($id_inscripcion),
                'asistencia' => $this->validarAsistencia($id_inscripcion),
                'curso_moodle' => $this->validarCursoMoodle($id_inscripcion)
                // TODO: Agregar validaciones según flujo (recursante, renovación)
            ];

            $resultado_general = true;
            foreach ($validaciones as $validacion) {
                if (isset($validacion['valido']) && !$validacion['valido']) {
                    $resultado_general = false;
                }
            }

            $this->registrarLog('VALIDACION_PROCESADA', [
                'id_inscripcion' => $id_inscripcion,
                'resultado' => $resultado_general
            ]);

            return [
                'resultado_general' => $resultado_general,
                'validaciones' => $validaciones,
                'pueden_rendir' => $resultado_general
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_PROCESAR_VALIDACION', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'resultado_general' => false,
                'validaciones' => [],
                'pueden_rendir' => false
            ];
        }
    }

    /**
     * Obtener detalle de validación para vista detalle_validacion.php
     *
     * @param int $id ID de la inscripción
     * @return array Datos completos de validación con todos los detalles
     */
    public function obtenerDetalleValidacion(int $id): array
    {
        try {
            // TODO: SELECT i.*, u.nombre, u.apellido, c.nombre as curso_nombre, c.modalidad, ti.nombre as tipo_inscripcion
            // TODO: FROM inscripcion i
            // TODO: JOIN usuario u ON i.id_usuario = u.id
            // TODO: LEFT JOIN curso c ON i.id_curso = c.id
            // TODO: LEFT JOIN tipo_inscripcion ti ON i.id_tipo_inscripcion = ti.id
            // TODO: WHERE i.id = $id

            $detalle = [
                'inscripcion' => [],
                'usuario' => [],
                'curso' => [],
                'tipo_inscripcion' => [],
                'validaciones' => $this->procesarValidacion($id),
                'documentos' => [],
                'asistencia' => $this->obtenerAsistencia($id),
                'resultado_examen' => [],
                'motivos_rechazo' => []
            ];

            return $detalle;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DETALLE_VALIDACION', ['id' => $id, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generar motivo de rechazo
     *
     * @param int $id_resultado ID del resultado de examen
     * @param string $motivo Descripción del motivo de rechazo
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function generarMotivo(int $id_resultado, string $motivo): array
    {
        try {
            // TODO: Validar que id_resultado existe en tabla resultado_examen
            // TODO: Validar que motivo no sea vacío
            // TODO: INSERT en tabla motivo_rechazo (id_resultado_examen, motivo, fecha_registro)
            // TODO: UPDATE inscripcion SET id_estado = 4 (reprobado) WHERE id = resultado_examen.id_inscripcion
            // TODO: Registrar en tabla auditoria_acciones

            $this->registrarLog('MOTIVO_RECHAZO_GENERADO', [
                'id_resultado' => $id_resultado,
                'motivo' => $motivo
            ]);

            return [
                'success' => true,
                'mensaje' => 'Motivo de rechazo registrado correctamente'
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_GENERAR_MOTIVO', ['id_resultado' => $id_resultado, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al registrar motivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener validaciones pendientes (inscripciones sin validar)
     *
     * @return array Array de inscripciones pendientes de validación
     */
    public function obtenerValidacionesPendientes(): array
    {
        try {
            // TODO: SELECT i.*, u.nombre, u.apellido, c.nombre as curso_nombre
            // TODO: FROM inscripcion i
            // TODO: JOIN usuario u ON i.id_usuario = u.id
            // TODO: LEFT JOIN curso c ON i.id_curso = c.id
            // TODO: WHERE i.id_estado = 1 (pendiente)
            // TODO: ORDER BY i.fecha_inscripcion ASC

            return [];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_VALIDACIONES_PENDIENTES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Método auxiliar privado para obtener asistencia
     * @param int $id_inscripcion ID de la inscripción
     * @return array Datos de asistencia
     */
    private function obtenerAsistencia(int $id_inscripcion): array
    {
        // TODO: SELECT COUNT(*) as total FROM asistencia WHERE id_inscripcion = $id_inscripcion
        // TODO: SELECT COUNT(*) as presentes FROM asistencia WHERE id_inscripcion = $id_inscripcion AND presente = 1

        return [
            'total_sesiones' => 0,
            'sesiones_presentes' => 0,
            'porcentaje' => 0.0
        ];
    }
}
