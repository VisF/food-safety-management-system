<?php
declare(strict_types=1);

/**
 * InscripcionControlador - Gestión de inscripciones a cursos y exámenes
 *
 * Dependencias esperadas:
 * - Modelos: InscripcionModelo, CursoModelo, ExamenModelo, FechaCursoModelo, TipoInscripcionModelo
 *
 * Responsabilidades:
 * - Crear, obtener y cancelar inscripciones
 * - Validar disponibilidad de cursos y exámenes
 * - Obtener detalles completos para vistas
 * - Confirmar inscripciones a exámenes
 * - Registrar eventos de inscripción
 *
 * Flujos soportados:
 * - Curso presencial: inscripción al curso con documentación
 * - Curso virtual: inscripción a examen presencial
 * - Recursante: re-inscripción a examen dentro de plazo
 * - Renovación: inscripción a examen para renovación de carnet
 */

class InscripcionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/inscripcion_controller.log';
    private const VIEW_DETALLE = __DIR__ . '/../vistas/detalle_inscripcion.php';

    private ?InscripcionModelo $inscripcionModelo = null;
    private ?CursoModelo $cursoModelo = null;
    private ?ExamenModelo $examenModelo = null;
    private ?FechaCursoModelo $fechaCursoModelo = null;
    private ?TipoInscripcionModelo $tipoInscripcionModelo = null;

    private function pdo(): \PDO
    {
        require_once __DIR__ . '/../db/Connection.php';
        return Connection::getPDO();
    }

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
        // TODO: Cargar archivos de modelos si no están incluidos
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('CursoModelo')) {
            $this->cursoModelo = new CursoModelo();
        }
        if (class_exists('ExamenModelo')) {
            $this->examenModelo = new ExamenModelo();
        }
        if (class_exists('FechaCursoModelo')) {
            $this->fechaCursoModelo = new FechaCursoModelo();
        }
        if (class_exists('TipoInscripcionModelo')) {
            $this->tipoInscripcionModelo = new TipoInscripcionModelo();
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
     * Crear nueva inscripción
     *
     * @param array $datos Datos: id_usuario, id_curso, id_tipo_inscripcion, id_examen (opcional), observaciones
     * @return array ['success' => bool, 'id' => int|null, 'mensaje' => string, 'inscripcion' => array|null]
     */
    public function crearInscripcion(array $datos): array
    {
        try {
            if (!$this->inscripcionModelo) {
                return [
                    'success' => false,
                    'id' => null,
                    'mensaje' => 'Modelo de inscripción no disponible',
                    'inscripcion' => null
                ];
            }

            $creada = $this->inscripcionModelo->crear($datos);
            if ($creada === false) {
                $this->registrarLog('INSCRIPCION_NO_CREADA', ['datos' => $datos]);
                return [
                    'success' => false,
                    'id' => null,
                    'mensaje' => 'No se pudo crear la inscripción',
                    'inscripcion' => null
                ];
            }

            $inscripcion = is_array($creada) ? $creada : $datos;
            $this->registrarLog('INSCRIPCION_CREADA', $inscripcion);

            return [
                'success' => true,
                'id' => (int)($inscripcion['id'] ?? 0),
                'mensaje' => 'Inscripción creada exitosamente',
                'inscripcion' => $inscripcion
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_CREAR_INSCRIPCION', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'id' => null,
                'mensaje' => 'Error al crear inscripción: ' . $e->getMessage(),
                'inscripcion' => null
            ];
        }
    }

    /**
     * Validar si usuario puede inscribirse
     * Revisa documentación, modalidad y reglas específicas (recursante/renovación)
     */
    public function validarInscripcion(int $id): array
    {
        try {
            $insc = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id) : null;
            if (!$insc) return ['valido' => false, 'motivos_rechazo' => ['Inscripción inexistente'], 'puede_inscribirse' => false];

            $validCtrl = new ValidacionControlador();
            $motivos = [];
            $puede = true;

            $docRes = $validCtrl->validarDocumentacion($id);
            if (!$docRes['valido']) { $puede = false; $motivos[] = 'Documentación incompleta: ' . implode(', ', $docRes['documentos_faltantes']); }

            // modalidad specific checks
            $cursoId = (int)($insc['curso_id'] ?? 0);
            $modalidad = 'presencial';
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id');
            $stmt->execute([':id' => $cursoId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['modalidad'])) $modalidad = $row['modalidad'];

            if ($modalidad === 'presencial') {
                $asis = $validCtrl->validarAsistencia($id);
                if (!$asis['valido']) { $puede = false; $motivos[] = 'Asistencia insuficiente'; }
            }

            if ($modalidad === 'virtual') {
                $m = $validCtrl->validarCursoMoodle($id);
                if (!$m['valido']) { $puede = false; $motivos[] = 'Falta certificado Moodle'; }
            }

            // recursante / renovacion checks
            $usuario_id = (int)($insc['usuario_id'] ?? 0);
            $rec = $validCtrl->validarPlazoRecursante($usuario_id);
            if (!$rec['puede_recursar']) { $puede = false; $motivos[] = 'Plazo recursante no cumplido, faltan ' . ($rec['dias_restantes'] ?? 0) . ' días'; }

            $ren = $validCtrl->validarRenovacion($id);
            if ($ren['carnet_vencido'] === false && $insc['tipo_inscripcion_id'] == 4) { // si es tipo renovación y no tiene carnet vencido
                $puede = false; $motivos[] = 'No existe carnet anterior vencido para renovar';
            }

            return ['valido' => $puede, 'motivos_rechazo' => $motivos, 'puede_inscribirse' => $puede];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]);
            return ['valido' => false, 'motivos_rechazo' => ['Error: ' . $e->getMessage()], 'puede_inscribirse' => false];
        }
    }

    /**
     * Obtener inscripciones por usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Array de inscripciones del usuario
     */
    public function obtenerInscripcionesPorUsuario(int $id_usuario): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT i.*, c.nombre as curso_nombre, e.fecha as examen_fecha FROM inscripciones i LEFT JOIN cursos c ON i.curso_id = c.id LEFT JOIN examenes e ON i.examen_id = e.id WHERE i.usuario_id = :uid ORDER BY i.fecha_inscripcion DESC');
            $stmt->execute([':uid' => $id_usuario]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_INSCRIPCIONES_POR_USUARIO', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener inscripción por ID
     *
     * @param int $id ID de la inscripción
     * @return array|null Datos de la inscripción o null
     */
    public function obtenerInscripcion(int $id): ?array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT * FROM inscripciones WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener inscripciones activas de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Array de inscripciones activas
     */
    public function obtenerInscripcionesActivas(int $id_usuario): array
    {
        try {
            // TODO: SELECT * FROM inscripcion WHERE id_usuario = $id_usuario AND id_estado IN (1,2,3)
            // TODO: (Estados: 1=pendiente, 2=aprobada, 3=en progreso)
            // TODO: ORDER BY fecha_inscripcion DESC

            return [];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_INSCRIPCIONES_ACTIVAS', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]);
            return [];
        }
    }

    

    /**
     * Cancelar inscripción
     *
     * @param int $id ID de la inscripción
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function cancelarInscripcion(int $id): array
    {
        try {
            // TODO: SELECT * FROM inscripcion WHERE id = $id
            // TODO: Validar que inscripción existe
            // TODO: Validar que está en estado permitido para cancelación (no ya finalizado)
            // TODO: UPDATE inscripcion SET id_estado = 5 WHERE id = $id (5=cancelada)
            // TODO: UPDATE fecha_curso SET cupos = cupos + 1 WHERE id = (SELECT id_fecha FROM inscripcion WHERE id = $id)
            // TODO: Registrar en tabla auditoria_acciones

            $this->registrarLog('INSCRIPCION_CANCELADA', ['id' => $id]);

            return [
                'success' => true,
                'mensaje' => 'Inscripción cancelada exitosamente'
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_CANCELAR_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al cancelar inscripción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener cursos disponibles para inscribirse
     *
     * @return array Array de cursos activos con fechas disponibles
     */
    public function obtenerCursosDisponibles(): array
    {
        try {
            // TODO: SELECT DISTINCT c.* FROM curso c
            // TODO: JOIN fecha_curso fc ON c.id = fc.id_curso
            // TODO: WHERE c.activo = 1 AND fc.cupos > 0 AND fc.activo = 1
            // TODO: AND fc.fecha_inicio > NOW()
            // TODO: ORDER BY c.nombre ASC

            $cursos = [];
            // TODO: Llenar array con resultados de BD

            return $cursos;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_CURSOS_DISPONIBLES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener exámenes disponibles para inscribirse
     *
     * @return array Array de exámenes con cupos disponibles
     */
    public function obtenerExamenesDisponibles(): array
    {
        try {
            $stmt = $this->pdo()->prepare('SELECT id, fecha, hora, ubicacion, cupos FROM examenes WHERE activo = 1 AND cupos >= 0 AND fecha >= CURDATE() ORDER BY fecha ASC, hora ASC');
            $stmt->execute();

            $examenes = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $fecha = new \DateTimeImmutable($row['fecha']);
                $hora = $row['hora'] ? substr((string)$row['hora'], 0, 5) : '';
                $examenes[] = [
                    'id' => (int)$row['id'],
                    'month' => strtoupper($fecha->format('M')),
                    'day' => $fecha->format('d'),
                    'title' => $row['ubicacion'] ?: 'Examen',
                    'capacity' => ((int)$row['cupos'] > 0) ? 1 : 0,
                    'capacity_label' => ((int)$row['cupos'] > 0) ? 'CUPOS DISPONIBLES' : 'SIN CUPOS',
                    'time' => $hora !== '' ? date('h:i A', strtotime($hora)) : '',
                    'room' => $row['ubicacion'] ?: '',
                    'route' => 'inscripcion_examen',
                ];
            }

            return $examenes;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_EXAMENES_DISPONIBLES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Confirmar inscripción a examen
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['success' => bool, 'mensaje' => string, 'inscripcion' => array|null]
     */
    public function confirmarInscripcionExamen(int $id_inscripcion): array
    {
        try {
            $insc = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id_inscripcion) : null;
            if (!$insc) return ['success' => false, 'mensaje' => 'Inscripción no encontrada', 'inscripcion' => null];

            $validCtrl = new ValidacionControlador();
            $v = $validCtrl->validarDocumentacion($id_inscripcion);
            if (!$v['valido']) {
                return ['success' => false, 'mensaje' => 'Documentación incompleta: ' . implode(', ', $v['documentos_faltantes']), 'inscripcion' => null];
            }

            $pdo = $this->pdo();
            // actualizar estado de inscripción a aprobada (estado_tramite_id = 2)
            $upd = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = 2 WHERE id = :id');
            $upd->execute([':id' => $id_inscripcion]);

            // disminuir cupos del examen si aplica
            $examen_id = (int)($insc['examen_id'] ?? 0);
            if ($examen_id > 0) {
                $dec = $pdo->prepare('UPDATE examenes SET cupos = GREATEST(cupos - 1, 0) WHERE id = :id');
                $dec->execute([':id' => $examen_id]);
            }

            $this->registrarLog('INSCRIPCION_EXAMEN_CONFIRMADA', ['id_inscripcion' => $id_inscripcion]);
            return ['success' => true, 'mensaje' => 'Inscripción a examen confirmada', 'inscripcion' => $this->obtenerInscripcion($id_inscripcion)];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_CONFIRMAR_INSCRIPCION_EXAMEN', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al confirmar inscripción: ' . $e->getMessage(),
                'inscripcion' => null
            ];
        }
    }

    /**
     * Procesar inscripción a examen desde el formulario de la vista.
     *
     * @param array $datos Datos del formulario
     * @return array Resultado con success/mensaje/inscripcion
     */
    public function procesarInscripcionExamen(array $datos): array
    {
        $idUsuario = (int)($datos['id_usuario'] ?? $_SESSION['user_id'] ?? 0);
        $idExamen = (int)($datos['id_examen'] ?? 0);

        if ($idUsuario <= 0 || $idExamen <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Faltan datos para completar la inscripción',
                'inscripcion' => null,
            ];
        }

        $payload = [
            'usuario_id' => $idUsuario,
            'curso_id' => null,
            'examen_id' => $idExamen,
            'tipo_inscripcion_id' => (int)($datos['id_tipo_inscripcion'] ?? 1),
            'observaciones' => $datos['observaciones'] ?? null,
        ];

        $res = $this->crearInscripcion($payload);
        if ($res['success'] && $res['id']) {
            // intentar confirmar automáticamente
            return $this->confirmarInscripcionExamen((int)$res['id']);
        }
        return $res;
    }

    /**
     * Obtener detalle completo de inscripción para vista
     *
     * @param int $id ID de la inscripción
     * @return array Datos completos: inscripción, usuario, curso, examen, documentos, estado
     */
    public function obtenerDetalleInscripcion(int $id): array
    {
        try {
            // TODO: SELECT i.*, u.nombre, u.apellido, u.email, c.nombre as curso_nombre, e.fecha as examen_fecha
            // TODO: FROM inscripcion i
            // TODO: LEFT JOIN usuario u ON i.id_usuario = u.id
            // TODO: LEFT JOIN curso c ON i.id_curso = c.id
            // TODO: LEFT JOIN examen e ON i.id_examen = e.id
            // TODO: WHERE i.id = $id

            // TODO: SELECT * FROM documento WHERE id_inscripcion = $id ORDER BY fecha_subida DESC

            // TODO: SELECT * FROM asistencia WHERE id_inscripcion = $id ORDER BY fecha ASC

            // TODO: SELECT * FROM resultado_examen WHERE id_inscripcion = $id

            // TODO: SELECT * FROM estado_tramite WHERE id = inscripcion.id_estado

            $detalle = [
                'inscripcion' => [],
                'usuario' => [],
                'curso' => [],
                'examen' => [],
                'documentos' => [],
                'asistencia' => [],
                'resultado_examen' => [],
                'estado_actual' => []
            ];

            return $detalle;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DETALLE_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
