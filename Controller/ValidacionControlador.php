<?php
declare(strict_types=1);


/**
 * ValidacionControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * ValidacionControlador - Gestión de validaciones de inscripciones
 *
 * Dependencias esperadas:
 * -  inscripcionService, DocumentoService, AsistenciaService, ResultadoExamenService, EstadoTramiteService
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

require_once __DIR__ . '/../Servicios/AsistenciaService.php';
require_once __DIR__ . '/../Servicios/inscripcionService.php';
require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../Servicios/ResultadoExamenService.php';
require_once __DIR__ . '/../Servicios/HabilitacionExamenService.php';

require_once __DIR__ . '/../Constant/EstadoTramite.php';


class ValidacionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/validacion_controller.log';
    private const ASISTENCIA_MINIMA_PRESENCIAL = 80.0; // 80%
    private const PLAZO_RECURSANTE_DIAS = 90; // 3 meses
    private const PORCENTAJE_DOCUMENTACION_REQUERIDA = 100; // 100% de documentos

    private ?inscripcionService $inscripcionService = null;
    private ?DocumentoService $DocumentoService = null;
    private ?AsistenciaService $AsistenciaService = null;
    private ?ResultadoExamenService $ResultadoExamenService = null;
    private ?HabilitacionExamenService $HabilitacionExamenService = null;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inscripcionService = new inscripcionService();
        $this->DocumentoService = new DocumentoService();
        $this->AsistenciaService = new AsistenciaService();
        $this->ResultadoExamenService = new ResultadoExamenService();
        $this->HabilitacionExamenService = new HabilitacionExamenService();
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
     * Validar documentación completa
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['valido' => bool, 'documentos_requeridos' => array, 'documentos_faltantes' => array]
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {
            // obtener inscripcion y modalidad
            if (!$this->inscripcionService) return ['valido' => false, 'documentos_requeridos' => [], 'documentos_faltantes' => []];
            $insc = $this->inscripcionService->obtenerPorId($id_inscripcion);
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

            $requeridos = [
                'dni',
                'foto_carnet'
            ];


            $docService = new DocumentoService();
            $insc =
                $this->inscripcionService
                    ->obtenerPorId(
                        $id_inscripcion
                    );

            $usuarioId =
                (int)$insc['usuario_id'];

            $docs =
                $docService->obtenerPorUsuario(
                    $usuarioId
                );
            $presentes = [];

            foreach ($docs as $d) {

                if (($d['estado'] ?? '') === 'aprobado') {

                    $presentes[] =
                        strtolower(
                            $d['tipo_documento']
                        );
                }
            }

            $faltantes = [];

            if (!in_array('dni', $presentes)) {

                $faltantes[] = 'DNI';
            }

            if (!in_array('foto_carnet', $presentes)) {

                $faltantes[] = 'Foto Carnet';
            }

            $tieneMoodle =
                in_array(
                    'moodle',
                    $presentes
                );

            $tieneAsistencia =
                in_array(
                    'asistencia',
                    $presentes
                );

            if (
                !$tieneMoodle
                &&
                !$tieneAsistencia
            ) {

                $faltantes[] =
                    'Certificado Moodle o Constancia de asistencia';
            }

            $valido =
                empty(
                    $faltantes
                );

            return [
                'valido' => $valido,
                'documentos_requeridos' => [
                    'DNI',
                    'Foto Carnet',
                    'Moodle o Asistencia'
                ],
                'documentos_faltantes' => $faltantes
            ];
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
            if (!$this->inscripcionService) return ['puede_renovar' => false, 'carnet_vencido' => false, 'fecha_vencimiento' => null];
            $insc = $this->inscripcionService->obtenerPorId($id_inscripcion);
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
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return [];
            require_once $connFile;
            $pdo = Connection::getPDO();
            $sql = 'SELECT id, id_resultado_examen, motivo, fecha_registro FROM motivo_rechazo WHERE id_resultado_examen = :rid ORDER BY fecha_registro DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':rid' => $id_resultado]);
            $rows = $stmt->fetchAll();
            return $rows ?: [];
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
            // Obtener información básica de la inscripción y contexto (curso, tipo)
            $insc = $this->inscripcionService ? $this->inscripcionService->obtenerPorId($id_inscripcion) : null;
            $contexto = [];
            if ($insc) {
                $contexto['inscripcion'] = $insc;
                $connFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($connFile)) {
                    require_once $connFile;
                    $pdo = Connection::getPDO();
                    // curso
                    $stmt = $pdo->prepare('SELECT id, nombre, modalidad 
                                            FROM cursos 
                                            WHERE id = :id');

                    $stmt->execute([':id' => (int)($insc['curso_id'] ?? $insc['id_curso'] ?? 0)]);

                    $contexto['curso'] = $stmt->fetch() ?: [];
                    // tipo inscripcion
                    $stmt = $pdo->prepare('SELECT id, nombre 
                                            FROM tipo_inscripcion 
                                            WHERE id = :id');

                    $stmt->execute([':id' => (int)($insc['tipo_inscripcion_id'] ?? $insc['id_tipo_inscripcion'] ?? 0)]);

                    $contexto['tipo_inscripcion'] = $stmt->fetch() ?: [];
                }
            }

            $validaciones = [
                'documentacion' => $this->validarDocumentacion($id_inscripcion),
                'habilitacion' => [
                    'valido' => $this->HabilitacionExamenService
                        ->tieneHabilitacionVigente(
                            (int)$insc['usuario_id']
                        )
                ]
            ];

            $resultado_general = true;
            foreach ($validaciones as $validacion) {
                if (isset($validacion['valido']) && !$validacion['valido']) {
                    $resultado_general = false;
                }
            }

            /*
            * Si todas las validaciones fueron aprobadas,
            * la inscripción queda habilitada para examen.
            */
            if ($resultado_general && isset($pdo)) {

                $stmt = $pdo->prepare("
                    UPDATE inscripciones
                    SET estado_tramite_id = :estado
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':estado' => EstadoTramite::DOCUMENTACION_APROBADA,
                    ':id' => $id_inscripcion
                ]);
            }

            $this->registrarLog('VALIDACION_PROCESADA', [
                'id_inscripcion' => $id_inscripcion,
                'resultado' => $resultado_general
            ]);

            return [
                'resultado_general' => $resultado_general,
                'validaciones' => $validaciones,
                'pueden_rendir' => $resultado_general,
                'contexto' => $contexto
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_PROCESAR_VALIDACION', [
                'id_inscripcion' => $id_inscripcion,
                'error' => $e->getMessage()
            ]);

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
            
            $inscripcion = $this->inscripcionService ? $this->inscripcionService->obtenerPorId($id) : null;
            $usuario = [];
            $curso = [];
            $tipo_inscripcion = [];

            $connFile = __DIR__ . '/../db/Connection.php';
            if ($inscripcion && file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
                // usuario
                $stmt = $pdo->prepare('SELECT id, nombre, apellido, email FROM usuario WHERE id = :uid');
                $stmt->execute([':uid' => (int)($inscripcion['usuario_id'] ?? $inscripcion['id_usuario'] ?? 0)]);
                $usuario = $stmt->fetch() ?: [];

                // curso
                $stmt = $pdo->prepare('SELECT id, nombre, modalidad FROM cursos WHERE id = :cid');
                $stmt->execute([':cid' => (int)($inscripcion['curso_id'] ?? $inscripcion['id_curso'] ?? 0)]);
                $curso = $stmt->fetch() ?: [];

                // tipo_inscripcion
                $stmt = $pdo->prepare('SELECT id, nombre FROM tipo_inscripcion WHERE id = :tid');
                $stmt->execute([':tid' => (int)($inscripcion['tipo_inscripcion_id'] ?? $inscripcion['id_tipo_inscripcion'] ?? 0)]);
                $tipo_inscripcion = $stmt->fetch() ?: [];
            }

            $documentos = $this->DocumentoService ? $this->DocumentoService->obtenerPorInscripcion($id) : [];
            $asistencia = $this->obtenerAsistencia($id);
            $resultadoExamen = $this->ResultadoExamenService ? $this->ResultadoExamenService->obtenerPorInscripcion($id) : null;
            $motivos = [];
            if ($resultadoExamen && isset($resultadoExamen['id'])) {
                $motivos = $this->obtenerMotivosRechazo((int)$resultadoExamen['id']);
            }

            $detalle = [
                'inscripcion' => $inscripcion ?: [],
                'usuario' => $usuario,
                'curso' => $curso,
                'tipo_inscripcion' => $tipo_inscripcion,
                'validaciones' => $this->procesarValidacion($id),
                'documentos' => $documentos,
                'asistencia' => $asistencia,
                'resultado_examen' => $resultadoExamen ?: [],
                'motivos_rechazo' => $motivos
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
            if (trim($motivo) === '') {
                return ['success' => false, 'mensaje' => 'Motivo vacío'];
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) {
                return ['success' => false, 'mensaje' => 'Conexión a BD no disponible'];
            }
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Verificar que resultado exista
            $stmt = $pdo->prepare('SELECT * FROM resultado_examen WHERE id = :id');
            $stmt->execute([':id' => $id_resultado]);
            $row = $stmt->fetch();
            if (!$row) return ['success' => false, 'mensaje' => 'Resultado de examen no encontrado'];

            // Insertar motivo_rechazo
            $ins = $pdo->prepare('INSERT INTO motivo_rechazo (id_resultado_examen, motivo, fecha_registro) VALUES (:rid, :motivo, NOW())');
            $ok = $ins->execute([':rid' => $id_resultado, ':motivo' => $motivo]);
            if (!$ok) return ['success' => false, 'mensaje' => 'Error al insertar motivo de rechazo'];

            // Actualizar estado de inscripcion (marcar como reprobado) si existe campo
            $insc_id = (int)($row['inscripcion_id'] ?? $row['insc_id'] ?? 0);
            if ($insc_id > 0) {
                // Intentar actualizar inscripcion a estado 'reprobado' = 4 (según convención del proyecto)
                $upd = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :estado WHERE id = :id');
                $upd->execute([':id' => $insc_id, ':estado' => EstadoTramite::RECHAZADO]);
            }

            // Registrar auditoría si existe tabla
            try {
                $aud = $pdo->prepare('INSERT INTO auditoria_acciones (usuario_id, accion, detalles, fecha) VALUES (:uid, :accion, :detalles, NOW())');
                $usuario_id = $_SESSION['user_id'] ?? null;
                $aud->execute([':uid' => $usuario_id, ':accion' => 'MOTIVO_RECHAZO', ':detalles' => json_encode(['id_resultado' => $id_resultado, 'motivo' => $motivo])]);
            } catch (\Throwable $t) {
                // No crítico
            }

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
            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return [];
            require_once $connFile;
            $pdo = Connection::getPDO();

            $sql = 'SELECT i.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, c.nombre as curso_nombre, c.modalidad 
                    FROM inscripciones i   
                    JOIN usuario u ON i.usuario_id = u.id 
                    LEFT JOIN cursos c ON i.curso_id = c.id 
                    WHERE i.estado_tramite_id = :estado 
                    ORDER BY i.fecha_inscripcion ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':estado' => EstadoTramite::PENDIENTE]);
            $rows = $stmt->fetchAll();
            return $rows ?: [];
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
        try {
            $asistencia = new AsistenciaService();
            $tot = $asistencia->obtenerTotalAsistencias($id_inscripcion);
            $presentes = (int)($tot['presentes'] ?? 0);
            $total = (int)($tot['total'] ?? 0);
            $porcentaje = $total > 0 ? round(($presentes / $total) * 100.0, 2) : 0.0;
            return [
                'total_sesiones' => $total,
                'sesiones_presentes' => $presentes,
                'porcentaje' => $porcentaje
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_ASISTENCIA', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'total_sesiones' => 0,
                'sesiones_presentes' => 0,
                'porcentaje' => 0.0
            ];
        }
    }
}
