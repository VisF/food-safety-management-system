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

    /**
     * Crear un nuevo curso
     * 
     * @param array $datos Array con datos: [
     *   'nombre' => string,
     *   'descripcion' => string,
     *   'modalidad' => 'presencial|virtual',
     *   'duracion_horas' => int,
     *   'asistencia_minima' => int
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_curso' => int|null,
     *   'data' => array
     * ]
     */
    public function crearCurso(array $datos): array
    {
        try {
            // Validaciones básicas
            $nombre = trim((string)($datos['nombre'] ?? ''));
            $modalidad = trim((string)($datos['modalidad'] ?? 'presencial'));
            if ($nombre === '' || !in_array($modalidad, ['presencial','virtual'], true)) {
                return ['success' => false, 'message' => 'Datos inválidos: nombre y modalidad requeridos', 'id_curso' => null, 'data' => []];
            }

            if (!$this->cursoModelo || !method_exists($this->cursoModelo, 'crear')) {
                // Si no hay modelo de curso disponible, registrar y devolver error
                $this->log('Intento crear curso sin modelo disponible', 'WARN', $datos);
                return ['success' => false, 'message' => 'Modelo de curso no disponible', 'id_curso' => null, 'data' => []];
            }

            $resultado = $this->cursoModelo->crear($datos);
            if ($resultado === false) {
                $this->log('Error al crear curso en modelo', 'ERROR', $datos);
                return ['success' => false, 'message' => 'No se pudo crear el curso', 'id_curso' => null, 'data' => []];
            }

            $this->log('Curso creado', 'INFO', $resultado);
            return ['success' => true, 'message' => 'Curso creado correctamente', 'id_curso' => (int)($resultado['id'] ?? 0), 'data' => $resultado];
        } catch (Exception $e) {
            $this->log('Error al crear curso', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al crear curso: ' . $e->getMessage(),
                'id_curso' => null,
                'data' => []
            ];
        }
    }

    /**
     * Obtener lista de todos los cursos
     * 
     * @return array [
     *   'success' => bool,
     *   'cursos' => [
     *     ['id' => int, 'nombre' => string, 'modalidad' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerCursos(): array
    {
        try {
            if (!$this->cursoModelo || !method_exists($this->cursoModelo, 'obtenerTodos')) {
                return ['success' => false, 'cursos' => [], 'total' => 0];
            }
            $cursos = $this->cursoModelo->obtenerTodos();
            return ['success' => true, 'cursos' => $cursos, 'total' => is_array($cursos) ? count($cursos) : 0];
        } catch (Exception $e) {
            $this->log('Error al obtener cursos', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'cursos' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Actualizar datos de un curso
     * 
     * @param int $id ID del curso
     * @param array $datos Datos a actualizar
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'data' => array
     * ]
     */
    public function actualizarCurso(int $id, array $datos): array
    {
        try {
            if (!$this->cursoModelo || !method_exists($this->cursoModelo, 'obtenerPorId')) {
                return ['success' => false, 'message' => 'Modelo de curso no disponible', 'data' => []];
            }
            $ex = $this->cursoModelo->obtenerPorId($id);
            if (!$ex) return ['success' => false, 'message' => 'Curso no encontrado', 'data' => []];
            if (method_exists($this->cursoModelo, 'actualizar')) {
                $ok = $this->cursoModelo->actualizar($id, $datos);
            } else {
                $ok = false;
            }
            if (!$ok) return ['success' => false, 'message' => 'No se pudo actualizar curso', 'data' => []];
            $this->log('Curso actualizado', 'INFO', ['id_curso' => $id, 'datos' => $datos]);
            return ['success' => true, 'message' => 'Curso actualizado correctamente', 'data' => $datos];
        } catch (Exception $e) {
            $this->log('Error al actualizar curso', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al actualizar curso: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Eliminar un curso
     * 
     * @param int $id ID del curso
     * @return array [
     *   'success' => bool,
     *   'message' => string
     * ]
     */
    public function eliminarCurso(int $id): array
    {
        try {
            if (!$this->cursoModelo || !method_exists($this->cursoModelo, 'obtenerPorId')) {
                return ['success' => false, 'message' => 'Modelo de curso no disponible'];
            }
            $ex = $this->cursoModelo->obtenerPorId($id);
            if (!$ex) return ['success' => false, 'message' => 'Curso no encontrado'];

            // verificar inscripciones activas
            $pdoFile = __DIR__ . '/../db/Connection.php';
            // Comprobación de integridad: si existe conexión a BD, verificar inscripciones activas
            if (file_exists($pdoFile)) {
                require_once $pdoFile;
                $pdo = Connection::getPDO();
                $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM inscripciones WHERE curso_id = :cid AND estado_tramite_id IN (:estado1, :estado2)');
                $stmt->execute([
                    ':cid' => $id,
                    ':estado1' => EstadoTramite::PENDIENTE,
                    ':estado2' => EstadoTramite::EXAMEN_APROBADO
                ]);
                $row = $stmt->fetch();
                if ($row && (int)$row['c'] > 0) return ['success' => false, 'message' => 'No se puede eliminar: existen inscripciones activas'];
            }

            if (method_exists($this->cursoModelo, 'eliminar')) {
                $ok = $this->cursoModelo->eliminar($id);
            } else {
                $ok = false;
            }
            if (!$ok) return ['success' => false, 'message' => 'No se pudo eliminar curso'];

            $this->log('Curso eliminado', 'INFO', ['id_curso' => $id]);
            return ['success' => true, 'message' => 'Curso eliminado correctamente'];
        } catch (Exception $e) {
            $this->log('Error al eliminar curso', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al eliminar curso: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear una nueva fecha/cohorte de curso
     * 
     * @param int $id_curso ID del curso
     * @param array $datos Array con datos: [
     *   'fecha_inicio' => 'Y-m-d',
     *   'fecha_fin' => 'Y-m-d',
     *   'cupos_totales' => int,
     *   'horario' => string,
     *   'ubicacion' => string
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_fecha' => int|null
     * ]
     */
    public function crearFechaCurso(int $id_curso, array $datos): array
    {
        try {
            if (!$this->fechaCursoModelo || !method_exists($this->fechaCursoModelo, 'crear')) return ['success' => false, 'message' => 'Modelo de fecha de curso no disponible', 'id_fecha' => null];
            // validar fechas simples
            $fi = $datos['fecha_inicio'] ?? null;
            if (!$fi) return ['success' => false, 'message' => 'Fecha inicio requerida', 'id_fecha' => null];
            $res = $this->fechaCursoModelo->crear($id_curso, $datos);
            if ($res === false) return ['success' => false, 'message' => 'No se pudo crear fecha de curso', 'id_fecha' => null];
            $this->log('Fecha de curso creada', 'INFO', ['id_curso' => $id_curso, 'datos' => $res]);
            return ['success' => true, 'message' => 'Fecha de curso creada correctamente', 'id_fecha' => (int)($res['id'] ?? 0)];
        } catch (Exception $e) {
            $this->log('Error al crear fecha de curso', 'ERROR', ['id_curso' => $id_curso, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al crear fecha de curso: ' . $e->getMessage(),
                'id_fecha' => null
            ];
        }
    }

    /**
     * Obtener todas las fechas/cohortes de un curso
     * 
     * @param int $id_curso ID del curso
     * @return array [
     *   'success' => bool,
     *   'fechas' => [
     *     ['id' => int, 'fecha_inicio' => string, 'fecha_fin' => string, 'cupos' => int, ...],
     *     ...
     *   ]
     * ]
     */
    public function obtenerFechasCurso(int $id_curso): array
    {
        try {
            if (!$this->fechaCursoModelo || !method_exists($this->fechaCursoModelo, 'obtenerPorCurso')) return ['success' => false, 'fechas' => []];
            $fechas = $this->fechaCursoModelo->obtenerPorCurso($id_curso);
            return ['success' => true, 'fechas' => $fechas];
        } catch (Exception $e) {
            $this->log('Error al obtener fechas de curso', 'ERROR', ['id_curso' => $id_curso, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'fechas' => []
            ];
        }
    }

    /**
     * Actualizar cupos disponibles para una fecha de curso
     * 
     * @param int $id_fecha ID de la fecha
     * @param int $cupos Nuevos cupos totales
     * @return array [
     *   'success' => bool,
     *   'message' => string
     * ]
     */
    public function actualizarCupos(int $id_fecha, int $cupos): array
    {
        try {
            if (!$this->fechaCursoModelo || !method_exists($this->fechaCursoModelo, 'actualizarCupos')) return ['success' => false, 'message' => 'Modelo de fecha no disponible'];
            $ok = $this->fechaCursoModelo->actualizarCupos($id_fecha, $cupos);
            if (!$ok) return ['success' => false, 'message' => 'No se pudo actualizar cupos'];
            $this->log('Cupos actualizados', 'INFO', ['id_fecha' => $id_fecha, 'cupos' => $cupos]);
            return ['success' => true, 'message' => 'Cupos actualizados correctamente'];
        } catch (Exception $e) {
            $this->log('Error al actualizar cupos', 'ERROR', ['id_fecha' => $id_fecha, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al actualizar cupos: ' . $e->getMessage()
            ];
        }
    }

    // ==================== GESTIÓN DE EXÁMENES ====================

    /**
     * Crear una nueva instancia de examen
     * 
     * @param array $datos Array con datos: [
     *   'nombre' => string,
     *   'fecha' => 'Y-m-d',
     *   'hora_inicio' => 'H:i',
     *   'duracion_minutos' => int,
     *   'ubicacion' => string,
     *   'cupos' => int
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_examen' => int|null
     * ]
     */
    public function crearExamen(array $datos): array
    {
        try {
            $fecha = trim((string) ($datos['fecha'] ?? ''));
            $hora = trim((string) ($datos['hora'] ?? ''));
            $ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
            $aula = trim((string) ($datos['aula'] ?? ''));
            $cupos = (int) ($datos['cupos'] ?? 0);

            if ($fecha === '' || $hora === '' || $ubicacion === '' || $aula === '' || $cupos <= 0) {
                return [
                    'success' => false,
                    'message' => 'Completa fecha, hora, lugar, aula y cupos válidos.',
                    'id_examen' => null,
                    'data' => [],
                ];
            }

            if (!$this->examenModelo) {
                return [
                    'success' => false,
                    'message' => 'El modelo de exámenes no está disponible.',
                    'id_examen' => null,
                    'data' => [],
                ];
            }

            $resultado = $this->examenModelo->crear([
                'fecha' => $fecha,
                'hora' => $hora,
                'ubicacion' => $ubicacion,
                'aula' => $aula,
                'cupos' => $cupos,
            ]);

            if ($resultado === false) {
                return [
                    'success' => false,
                    'message' => 'No se pudo crear el examen.',
                    'id_examen' => null,
                    'data' => [],
                ];
            }
            
            $this->log('Examen creado', 'INFO', $resultado);
            
            return [
                'success' => true,
                'message' => 'Examen creado correctamente',
                'id_examen' => (int) ($resultado['id'] ?? 0),
                'data' => $resultado,
            ];
        } catch (Exception $e) {
            $this->log('Error al crear examen', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al crear examen: ' . $e->getMessage(),
                'id_examen' => null,
                'data' => [],
            ];
        }
    }

    /**
     * Obtener lista de todos los exámenes
     * 
     * @return array [
     *   'success' => bool,
     *   'examenes' => [
     *     ['id' => int, 'nombre' => string, 'fecha' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerExamenes(): array
    {
        try {
            if (!$this->examenModelo || !method_exists($this->examenModelo, 'obtenerTodos')) return ['success' => false, 'examenes' => [], 'total' => 0];
            $ex = $this->examenModelo->obtenerTodos();
            return ['success' => true, 'examenes' => $ex, 'total' => is_array($ex) ? count($ex) : 0];
        } catch (Exception $e) {
            $this->log('Error al obtener exámenes', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'examenes' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Actualizar datos de un examen
     * 
     * @param int $id ID del examen
     * @param array $datos Datos a actualizar
     * @return array [
     *   'success' => bool,
     *   'message' => string
     * ]
     */
    public function actualizarExamen(int $id, array $datos): array
    {
        try {
            if (!$this->examenModelo || !method_exists($this->examenModelo, 'obtenerPorId')) return ['success' => false, 'message' => 'Modelo de examen no disponible'];
            $ex = $this->examenModelo->obtenerPorId($id);
            if (!$ex) return ['success' => false, 'message' => 'Examen no encontrado'];
            $ok = method_exists($this->examenModelo, 'actualizar') ? $this->examenModelo->actualizar($id, $datos) : false;
            if (!$ok) return ['success' => false, 'message' => 'No se pudo actualizar examen'];
            $this->log('Examen actualizado', 'INFO', ['id_examen' => $id, 'datos' => $datos]);
            return ['success' => true, 'message' => 'Examen actualizado correctamente'];
        } catch (Exception $e) {
            $this->log('Error al actualizar examen', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al actualizar examen: ' . $e->getMessage()
            ];
        }
    }

    // ==================== GESTIÓN DE INSCRIPCIONES ====================

    /**
     * Listar inscripciones con filtros
     * 
     * @param array $filtros Array con filtros: [
     *   'estado' => string|null,
     *   'id_usuario' => int|null,
     *   'id_curso' => int|null,
     *   'fecha_desde' => string|null,
     *   'fecha_hasta' => string|null,
     *   'limite' => int (default 50),
     *   'pagina' => int (default 1)
     * ]
     * @return array [
     *   'success' => bool,
     *   'inscripciones' => [
     *     ['id' => int, 'id_usuario' => int, 'id_curso' => int, 'estado' => string, ...],
     *     ...
     *   ],
     *   'total' => int,
     *   'paginas' => int
     * ]
     */
    public function listarInscripciones(array $filtros = []): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'inscripciones' => [], 'total' => 0, 'paginas' => 0];
            require_once $pdoFile;
            $pdo = Connection::getPDO();

            $where = [];
            $params = [];
            if (!empty($filtros['id_usuario'])) { $where[] = 'i.usuario_id = :uid'; $params[':uid'] = (int)$filtros['id_usuario']; }
            if (!empty($filtros['id_curso'])) { $where[] = 'i.curso_id = :cid'; $params[':cid'] = (int)$filtros['id_curso']; }
            if (!empty($filtros['estado'])) { $where[] = 'et.nombre = :estado'; $params[':estado'] = $filtros['estado']; }
            if (!empty($filtros['fecha_desde'])) { $where[] = 'i.fecha_inscripcion >= :fd'; $params[':fd'] = $filtros['fecha_desde']; }
            if (!empty($filtros['fecha_hasta'])) { $where[] = 'i.fecha_inscripcion <= :fh'; $params[':fh'] = $filtros['fecha_hasta']; }

            $limit = (int)($filtros['limite'] ?? 50);
            $pagina = max(1, (int)($filtros['pagina'] ?? 1));
            $offset = ($pagina - 1) * $limit;

            $sql = 'SELECT i.*, u.nombre as usuario_nombre, 
                                u.apellido as usuario_apellido, 
                                c.nombre as curso_nombre, 
                                et.nombre as estado_nombre 
                                FROM inscripciones i 
                                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                                LEFT JOIN cursos c ON i.curso_id = c.id 
                                LEFT JOIN estados_tramite et ON i.estado_tramite_id = et.id';
            if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
            $countSql = 'SELECT COUNT(*) as total FROM (' . $sql . ') t';
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = (int)($stmt->fetchColumn() ?? 0);

            $sql .= ' ORDER BY i.fecha_inscripcion DESC LIMIT :lim OFFSET :off';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $paginas = (int)ceil($total / max(1, $limit));
            return ['success' => true, 'inscripciones' => $rows, 'total' => $total, 'paginas' => $paginas];
        } catch (Exception $e) {
            $this->log('Error al listar inscripciones', 'ERROR', ['filtros' => $filtros, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'inscripciones' => [],
                'total' => 0,
                'paginas' => 0
            ];
        }
    }

    /**
     * Obtener detalle completo de una inscripción
     * 
     * @param int $id ID de la inscripción
     * @return array [
     *   'success' => bool,
     *   'inscripcion' => [
     *     'id' => int,
     *     'usuario' => array,
     *     'curso' => array,
     *     'estado' => string,
     *     'documentos' => array,
     *     'fecha_inscripcion' => string,
     *     'notas' => string
     *   ]
     * ]
     */
    public function obtenerInscripcion(int $id): array
    {
        try {
            $ins = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id) : null;
            if (!$ins) return ['success' => false, 'inscripcion' => []];

            $usuario = [];
            $curso = [];
            if (!empty($ins['usuario_id'])) {
                $uModel = $this->usuarioModelo;
                if ($uModel && method_exists($uModel, 'obtenerPorId')) $usuario = $uModel->obtenerPorId((int)$ins['usuario_id']);
            }
            if (!empty($ins['curso_id'])) {
                $cModel = $this->cursoModelo;
                if ($cModel && method_exists($cModel, 'obtenerPorId')) $curso = $cModel->obtenerPorId((int)$ins['curso_id']);
            }
            $docs = [];
            if ($this->documentoModelo && method_exists($this->documentoModelo, 'obtenerPorInscripcion')) $docs = $this->documentoModelo->obtenerPorInscripcion($id);

            return ['success' => true, 'inscripcion' => ['inscripcion' => $ins, 'usuario' => $usuario, 'curso' => $curso, 'documentos' => $docs]];
        } catch (Exception $e) {
            $this->log('Error al obtener inscripción', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'inscripcion' => []
            ];
        }
    }

    /**
     * Validar documentación de una inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'inscripcion' => array
     * ]
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {
            if (!$this->documentoModelo) return ['success' => false, 'message' => 'Modelo de documentos no disponible', 'inscripcion' => []];
            $docs = $this->documentoModelo->obtenerPorInscripcion($id_inscripcion);
            $faltantes = [];
            foreach ($docs as $d) {
                if (((int)($d['validado'] ?? 0)) !== 1) $faltantes[] = $d['tipo_documento'] ?? 'desconocido';
            }
            if (!empty($faltantes)) {
                return ['success' => false, 'message' => 'Documentación incompleta: ' . implode(', ', $faltantes), 'inscripcion' => $docs];
            }
            // marcar estado de inscripcion a documentacion completa (id 2 asumido)
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($pdoFile)) { require_once $pdoFile; $pdo = Connection::getPDO(); 

            $upd = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :estado WHERE id = :id'); 

            $upd->execute([':id' => $id_inscripcion, ':estado' => EstadoTramite::HABILITADO_EXAMEN]); }

            $this->log('Documentación validada', 'INFO', ['id_inscripcion' => $id_inscripcion]);

            return ['success' => true, 'message' => 'Documentación validada correctamente', 'inscripcion' => $docs];

        } catch (Exception $e) {
            $this->log('Error al validar documentación', 'ERROR', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al validar documentación: ' . $e->getMessage(),
                'inscripcion' => []
            ];
        }
    }

    /**
     * Rechazar documentación con motivo
     * 
     * @param int $id ID de la inscripción
     * @param string $motivo Motivo del rechazo
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'inscripcion' => array
     * ]
     */
    public function rechazarDocumentacion(int $id, string $motivo): array
    {
        try {
            // verificar existencia
            $ins = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id) : null;
            if (!$ins) return ['success' => false, 'message' => 'Inscripción no encontrada', 'inscripcion' => []];
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($pdoFile)) {
                require_once $pdoFile;
                $pdo = Connection::getPDO();
                $sql = "UPDATE inscripciones SET estado_tramite_id = :estado, observaciones = CONCAT(IFNULL(observaciones, ''), :mot) WHERE id = :id";
                $upd = $pdo->prepare($sql);
                $upd->execute([':estado' => EstadoTramite::RECHAZADO, ':mot' => "\nRechazo: " . $motivo, ':id' => $id]);
            }
            $this->log('Documentación rechazada', 'INFO', ['id_inscripcion' => $id, 'motivo' => $motivo]);
            // notificar si existe controlador de notificaciones
            if (class_exists('NotificacionControlador')) {
                try { $nc = new NotificacionControlador(); if (method_exists($nc, 'enviarNotificacion')) $nc->enviarNotificacion((int)$ins['usuario_id'], 'documentacion_rechazada', ['motivo' => $motivo]); } catch (Exception $e) {}
            }
            return ['success' => true, 'message' => 'Documentación rechazada, usuario notificado', 'inscripcion' => $ins];
        } catch (Exception $e) {
            $this->log('Error al rechazar documentación', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al rechazar documentación: ' . $e->getMessage(),
                'inscripcion' => []
            ];
        }
    }

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

    /**
     * Panel de gestión de usuarios
     * 
     * @return array [
     *   'success' => bool,
     *   'usuarios' => [
     *     ['id' => int, 'nombre' => string, 'email' => string, 'dni' => string, 'activo' => bool, ...],
     *     ...
     *   ],
     *   'total' => int,
     *   'activos' => int,
     *   'inactivos' => int
     * ]
     */
    public function gestionarUsuarios(): array
    {
        try {
            $usuarios = [];
            if ($this->usuarioModelo && method_exists($this->usuarioModelo, 'obtenerTodos')) {
                $usuarios = $this->usuarioModelo->obtenerTodos();
            } else {
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) { require_once $pdoFile; $pdo = Connection::getPDO(); $stmt = $pdo->prepare('SELECT * FROM usuarios'); $stmt->execute(); $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC); }
            }
            $total = is_array($usuarios) ? count($usuarios) : 0;
            $activos = 0; $inactivos = 0;
            foreach ($usuarios as $u) { if (!empty($u['activo']) && ((int)$u['activo'] === 1 || $u['activo'] === '1')) $activos++; else $inactivos++; }
            return ['success' => true, 'usuarios' => $usuarios, 'total' => $total, 'activos' => $activos, 'inactivos' => $inactivos];
        } catch (Exception $e) {
            $this->log('Error al gestionar usuarios', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0
            ];
        }
    }

    /**
     * Crear un nuevo usuario
     * 
     * @param array $datos Array con datos: [
     *   'nombre' => string,
     *   'apellido' => string,
     *   'email' => string,
     *   'dni' => string,
     *   'password' => string,
     *   'rol' => string|int
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_usuario' => int|null
     * ]
     */
    public function crearUsuario(array $datos): array
    {
        try {
            $email = trim(strtolower((string)($datos['email'] ?? '')));
            $dni = trim((string)($datos['dni'] ?? ''));
            $password = $datos['password'] ?? null;
            if ($email === '' || $dni === '' || !$password) return ['success' => false, 'message' => 'Email, DNI y password son requeridos', 'id_usuario' => null];

            // verificar email único
            if ($this->usuarioModelo && method_exists($this->usuarioModelo, 'obtenerPorEmail')) {
                $ex = $this->usuarioModelo->obtenerPorEmail($email);
                if ($ex) return ['success' => false, 'message' => 'Email ya registrado', 'id_usuario' => null];
            } else {
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) 
                    { 
                        require_once $pdoFile; 
                        $pdo = Connection::getPDO(); 
                        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :e'); 
                        $stmt->execute([':e' => $email]); 
                        if ($stmt->fetch()) 
                            return ['success' => false, 'message' => 'Email ya registrado', 'id_usuario' => null]; 
                        }
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $datos['password'] = $hashed;
            $datos['email'] = $email;

            if ($this->usuarioModelo && method_exists($this->usuarioModelo, 'crear')) {
                $res = $this->usuarioModelo->crear($datos);
                $id = $res['id'] ?? null;
            } else {
                $id = null;
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) 
                    { 
                        require_once $pdoFile; 
                        $pdo = Connection::getPDO(); 
                        $ins = $pdo->prepare('INSERT INTO usuarios (nombre, apellido, email, dni, password, activo, creado_en) 
                                                VALUES (:n, :a, :e, :d, :p, 1, NOW())'); 
                        $ins->execute([':n' => $datos['nombre'] ?? '', 
                                        ':a' => $datos['apellido'] ?? '', 
                                        ':e' => $email, ':d' => $dni, 
                                        ':p' => $hashed]); 
                        $id = (int)$pdo->lastInsertId(); }
            }

            $this->log('Usuario creado', 'INFO', ['email' => $email, 'dni' => $dni, 'id' => $id]);
            return ['success' => true, 'message' => 'Usuario creado correctamente', 'id_usuario' => $id];
        } catch (Exception $e) {
            $this->log('Error al crear usuario', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
                'id_usuario' => null
            ];
        }
    }

    /**
     * Actualizar datos de un usuario
     * 
     * @param int $id ID del usuario
     * @param array $datos Datos a actualizar
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'usuario' => array
     * ]
     */
    public function actualizarUsuario(int $id, array $datos): array
    {
        try {
            $user = $this->usuarioModelo && method_exists($this->usuarioModelo, 'obtenerPorId') ? $this->usuarioModelo->obtenerPorId($id) : null;
            if (!$user) {
                // intentar DB directa
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) { require_once $pdoFile; $pdo = Connection::getPDO(); $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id'); $stmt->execute([':id' => $id]); $user = $stmt->fetch(\PDO::FETCH_ASSOC); }
            }
            if (!$user) return ['success' => false, 'message' => 'Usuario no encontrado', 'usuario' => []];

            if (isset($datos['password']) && $datos['password'] !== '') {
                $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            } else { unset($datos['password']); }

            if ($this->usuarioModelo && method_exists($this->usuarioModelo, 'actualizar')) {
                $ok = $this->usuarioModelo->actualizar($id, $datos);
            } else {
                $pdoFile = __DIR__ . '/../db/Connection.php';
                if (file_exists($pdoFile)) 
                    { 
                        require_once $pdoFile; 
                        $pdo = Connection::getPDO(); 
                        $sets = []; $params = [':id' => $id]; 
                        foreach ($datos as $k => $v) 
                            { 
                                $sets[] = "$k = :$k"; 
                                $params[":$k"] = $v; 
                            } 
                        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = :id'; 
                        $stmt = $pdo->prepare($sql); 
                        $ok = $stmt->execute($params); 
                        } else 
                            { 
                            $ok = false; 
                            }
            }

            $this->log('Usuario actualizado', 'INFO', ['id_usuario' => $id]);
            return ['success' => true, 'message' => 'Usuario actualizado correctamente', 'usuario' => $this->usuarioModelo && method_exists($this->usuarioModelo, 'obtenerPorId') ? $this->usuarioModelo->obtenerPorId($id) : $user];
        } catch (Exception $e) {
            $this->log('Error al actualizar usuario', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage(),
                'usuario' => []
            ];
        }
    }

    /**
     * Desactivar un usuario
     * 
     * @param int $id ID del usuario
     * @return array [
     *   'success' => bool,
     *   'message' => string
     * ]
     */
    public function desactivarUsuario(int $id): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'message' => 'DB no disponible'];
            require_once $pdoFile;
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id'); $stmt->execute([':id' => $id]); if (!$stmt->fetch()) return ['success' => false, 'message' => 'Usuario no encontrado'];
            $upd = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id = :id'); $upd->execute([':id' => $id]);
            // cancelar inscripciones activas
            $cancel = $pdo->prepare('UPDATE inscripciones SET estado_tramite_id = :estado 
                                        WHERE usuario_id = :id AND estado_tramite_id IN (:pendiente, :aprobado)'); 
            $cancel->execute([':estado' => EstadoTramite::RECHAZADO, 
                                ':id' => $id, 
                                ':pendiente' => EstadoTramite::PENDIENTE, 
                                ':aprobado' => EstadoTramite::EXAMEN_APROBADO    ]);
            $this->log('Usuario desactivado', 'INFO', ['id_usuario' => $id]);
            return ['success' => true, 'message' => 'Usuario desactivado correctamente'];
        } catch (Exception $e) {
            $this->log('Error al desactivar usuario', 'ERROR', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al desactivar usuario: ' . $e->getMessage()
            ];
        }
    }

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

    /**
     * Obtener estadísticas generales del sistema
     * 
     * @return array [
     *   'success' => bool,
     *   'estadisticas' => [
     *     'total_usuarios' => int,
     *     'usuarios_activos' => int,
     *     'total_inscripciones' => int,
     *     'inscripciones_pendientes' => int,
     *     'inscripciones_aprobadas' => int,
     *     'total_exámenes' => int,
     *     'tasa_aprobacion' => float,
     *     'carnets_emitidos' => int
     *   ]
     * ]
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'estadisticas' => []];
            require_once $pdoFile;
            $pdo = Connection::getPDO();

            $totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            $usuariosActivos = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn();
            $totalIns = (int)$pdo->query('SELECT COUNT(*) FROM inscripciones')->fetchColumn();
            $insPend = (int)$pdo->query(
                                            "SELECT COUNT(*) FROM inscripciones
                                            WHERE estado_tramite_id IN (
                                                " . EstadoTramite::PENDIENTE . ",
                                                " . EstadoTramite::CURSANDO . ",
                                                " . EstadoTramite::HABILITADO_EXAMEN . ",
                                                " . EstadoTramite::INSCRIPTO_EXAMEN . "
                                            )"
                                        )->fetchColumn();
            $insAprob = (int)$pdo->query(
                                            "SELECT COUNT(*) FROM inscripciones
                                            WHERE estado_tramite_id IN (
                                                " . EstadoTramite::EXAMEN_APROBADO . ",
                                                " . EstadoTramite::CARNET_EMITIDO . "
                                            )"
                                        )->fetchColumn();
            $totalEx = (int)$pdo->query('SELECT COUNT(*) FROM examenes')->fetchColumn();
            $aprobados = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1")->fetchColumn();
            $reprobados = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0")->fetchColumn();
            $tasa = $aprobados + $reprobados > 0 ? round(($aprobados / max(1, $aprobados + $reprobados)) * 100, 2) : 0.0;
            $carnets = (int)$pdo->query('SELECT COUNT(*) FROM carnets')->fetchColumn();

            return ['success' => true, 'estadisticas' => 
                                                    ['total_usuarios' => $totalUsuarios, 
                                                    'usuarios_activos' => $usuariosActivos, 
                                                    'total_inscripciones' => $totalIns, 
                                                    'inscripciones_pendientes' => $insPend, 
                                                    'inscripciones_aprobadas' => $insAprob, 
                                                    'total_exámenes' => $totalEx, 
                                                    'tasa_aprobacion' => $tasa, 
                                                    'carnets_emitidos' => $carnets]];
        } catch (Exception $e) {
            $this->log('Error al obtener estadísticas', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'estadisticas' => []
            ];
        }
    }

    /**
     * Obtener reporte de actividad por período
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
     *     'detalles' => array
     *   ]
     * ]
     */
    public function obtenerReportePorFecha(string $fecha_inicio, string $fecha_fin): array
    {
        try {
            $f1 = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
            $f2 = DateTime::createFromFormat('Y-m-d', $fecha_fin);
            if (!$f1 || !$f2) return ['success' => false, 'reporte' => [], 'message' => 'Fechas inválidas'];
            $d1 = $f1->format('Y-m-d') . ' 00:00:00';
            $d2 = $f2->format('Y-m-d') . ' 23:59:59';

            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'reporte' => []];
            require_once $pdoFile;
            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                                    'SELECT COUNT(*)
                                    FROM inscripciones
                                    WHERE fecha_inscripcion BETWEEN :d1 AND :d2'
                                );

            $stmt->execute([
                ':d1' => $d1,
                ':d2' => $d2
            ]);

            $nuevas = (int)$stmt->fetchColumn();


            // documentacion validada: asumimos que existe updated fecha o estado 2
            $docVal = (int)$pdo->query(
                                            "SELECT COUNT(*)
                                            FROM documentos
                                            WHERE validado = 1
                                            AND fecha_subida BETWEEN '$d1' AND '$d2'"
                                        )->fetchColumn();
            $exReal = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $ap = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1 AND fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $rep = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0 AND fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $carn = (int)$pdo->query("SELECT COUNT(*) FROM carnets WHERE fecha_emision BETWEEN '$d1' AND '$d2'")->fetchColumn();

            $detalles = ['inscripciones' => [], 'resultados' => []];
            $rows = $pdo->query("SELECT * FROM inscripciones WHERE fecha_inscripcion BETWEEN '$d1' AND '$d2' ORDER BY fecha_inscripcion DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $detalles['inscripciones'] = $rows;
            $detalles['resultados'] = $pdo->query(
                                                    "SELECT *
                                                    FROM resultado_examen
                                                    WHERE fecha_resultado BETWEEN '$d1' AND '$d2'
                                                    ORDER BY fecha_resultado DESC"
                                                )->fetchAll(\PDO::FETCH_ASSOC);

            $this->log('Reporte generado', 'INFO', ['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);
            return ['success' => true, 'reporte' => 
                                        ['periodo' => "{$fecha_inicio} a {$fecha_fin}",
                                        'nuevas_inscripciones' => (int)$nuevas, 
                                        'documentacion_validada' => (int)$docVal, 
                                        'exámenes_realizados' => (int)$exReal, 
                                        'aprobados' => (int)$ap, 
                                        'reprobados' => (int)$rep, 
                                        'carnets_emitidos' => (int)$carn, 
                                        'detalles' => $detalles]];
        } catch (Exception $e) {
            $this->log('Error al generar reporte', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'reporte' => []
            ];
        }
    }
}
