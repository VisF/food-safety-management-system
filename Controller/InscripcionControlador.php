<?php
declare(strict_types=1);

/**
 * InscripcionControlador - Gestión de inscripciones a cursos y exámenes
 */
require_once __DIR__ . '/../Constant/EstadoTramite.php';
require_once __DIR__ . '/../Modelo/DocumentoModelo.php';

require_once __DIR__ . '/../Modelo/InscripcionModelo.php';
require_once __DIR__ . '/../Modelo/CursoModelo.php';
require_once __DIR__ . '/../Modelo/ExamenModelo.php';
require_once __DIR__ . '/../Modelo/DocumentoModelo.php';

require_once __DIR__ . '/ValidacionControlador.php';

class InscripcionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/inscripcion_controller.log';

    private ?InscripcionModelo $inscripcionModelo = null;
    private ?CursoModelo $cursoModelo = null;
    private ?ExamenModelo $examenModelo = null;
    private ?FechaCursoModelo $fechaCursoModelo = null;
    private ?TipoInscripcionModelo $tipoInscripcionModelo = null;
    private ?DocumentoModelo $documentoModelo = null;

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

    private function inicializarModelos(): void
    {
        if (class_exists('InscripcionModelo')) $this->inscripcionModelo = new InscripcionModelo();
        if (class_exists('CursoModelo')) $this->cursoModelo = new CursoModelo();
        if (class_exists('ExamenModelo')) $this->examenModelo = new ExamenModelo();
        if (class_exists('FechaCursoModelo')) $this->fechaCursoModelo = new FechaCursoModelo();
        if (class_exists('TipoInscripcionModelo')) $this->tipoInscripcionModelo = new TipoInscripcionModelo();
        if (class_exists('DocumentoModelo')) $this->documentoModelo = new DocumentoModelo();
    }

    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    public function crearInscripcion(array $datos): array
    {
        try {
            // Comprobar disponibilidad del modelo para crear la inscripción
            if (!$this->inscripcionModelo) 
                return ['success' => false, 
                        'id' => null, 
                        'mensaje' => 'Modelo de inscripción no disponible', 
                        'inscripcion' => null];

            $creada = $this->inscripcionModelo->crear($datos);
            if ($creada === false)
                { 
                    $this->registrarLog('INSCRIPCION_NO_CREADA', 
                                        ['datos' => $datos]); 
                    return ['success' => false, 
                            'id' => null, 
                            'mensaje' => 'No se pudo crear la inscripción', 
                            'inscripcion' => null]; 
                            }

            $inscripcion = is_array($creada) ? $creada : $datos;

            $this->registrarLog('INSCRIPCION_CREADA', $inscripcion);

            return ['success' => true, 
                    'id' => (int)($inscripcion['id'] ?? 0), 
                    'mensaje' => 'Inscripción creada exitosamente', 
                    'inscripcion' => $inscripcion];
        } catch (\Exception $e) {
            die($e->getMessage());
            $this->registrarLog('ERROR_CREAR_INSCRIPCION', ['error' => $e->getMessage()]);
            return ['success' => false, 
                    'id' => null, 
                    'mensaje' => 'Error al crear inscripción: ' . $e->getMessage(), 
                    'inscripcion' => null];
        }
    }

    public function validarInscripcion(int $id): array
    {
        try {
            // Obtener inscripción y validar existencia
            $insc = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id) : null;
            if (!$insc) return ['valido' => false, 'motivos_rechazo' => ['Inscripción inexistente'], 'puede_inscribirse' => false];
            $validCtrl = new ValidacionControlador();
            $motivos = [];
            $puede = true;
            // Validación documental (delegada a ValidacionControlador)
            $docRes = $validCtrl->validarDocumentacion($id);
            if (!$docRes['valido']) { $puede = false; $motivos[] = 'Documentación incompleta: ' . implode(', ', $docRes['documentos_faltantes']); }
            $cursoId = (int)($insc['curso_id'] ?? 0);
            $modalidad = 'presencial';
            $pdo = $this->pdo();
            // Obtener modalidad del curso para decidir reglas de validación (presencial/virtual)
            $stmt = $pdo->prepare('SELECT modalidad FROM cursos WHERE id = :id');
            $stmt->execute([':id' => $cursoId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['modalidad'])) $modalidad = $row['modalidad'];
            // Reglas por modalidad: presencial requiere asistencia, virtual requiere certificado Moodle
            if ($modalidad === 'presencial') { $asis = $validCtrl->validarAsistencia($id); 
            if (!$asis['valido']) { $puede = false; $motivos[] = 'Asistencia insuficiente'; } }
            if ($modalidad === 'virtual') { $m = $validCtrl->validarCursoMoodle($id); 
            if (!$m['valido']) { $puede = false; $motivos[] = 'Falta certificado Moodle'; } }
            $usuario_id = (int)($insc['usuario_id'] ?? 0);
            $rec = $validCtrl->validarPlazoRecursante($usuario_id);
            // Validación de plazo para recursantes
            if (!$rec['puede_recursar']) { $puede = false; $motivos[] = 'Plazo recursante no cumplido, faltan ' . ($rec['dias_restantes'] ?? 0) . ' días'; }
            $ren = $validCtrl->validarRenovacion($id);
            if (($ren['carnet_vencido'] ?? null) === false && (($insc['tipo_inscripcion_id'] ?? 0) == 4)) { $puede = false; $motivos[] = 'No existe carnet anterior vencido para renovar'; }
            return ['valido' => $puede, 'motivos_rechazo' => $motivos, 'puede_inscribirse' => $puede];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VALIDAR_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]);
            return ['valido' => false, 'motivos_rechazo' => ['Error: ' . $e->getMessage()], 'puede_inscribirse' => false];
        }
    }

    public function obtenerInscripcionesPorUsuario(int $id_usuario): array
    {
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT i.*, c.nombre as curso_nombre, e.fecha as examen_fecha 
                                    FROM inscripciones i 
                                    LEFT JOIN cursos c ON i.curso_id = c.id 
                                    LEFT JOIN examenes e ON i.examen_id = e.id 
                                    WHERE i.usuario_id = :uid 
                                    ORDER BY i.fecha_inscripcion DESC');
            $stmt->execute([':uid' => $id_usuario]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) { $this->registrarLog('ERROR_OBTENER_INSCRIPCIONES_POR_USUARIO', ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]); return []; }
    }

    public function obtenerInscripcion(int $id): ?array
    {
        try { $pdo = $this->pdo(); $stmt = $pdo->prepare('SELECT * 
                                                            FROM inscripciones 
                                                            WHERE id = :id'); 
            $stmt->execute([':id' => $id]); 
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
             return $row ?: null; 
             } catch (\Exception $e) 
             { 
                $this->registrarLog('ERROR_OBTENER_INSCRIPCION', 
                    ['id' => $id, 'error' => $e->getMessage()]); 
                return null; }
    }

    public function obtenerInscripcionesActivas(int $id_usuario): array
    {
        try { 
            $pdo = $this->pdo(); 
            $sql = '
                    SELECT
                        i.*,
                        c.nombre AS curso_nombre,
                        e.fecha AS examen_fecha
                    FROM inscripciones i
                    LEFT JOIN cursos c
                        ON i.curso_id = c.id
                    LEFT JOIN examenes e
                        ON i.examen_id = e.id
                    WHERE i.usuario_id = :uid
                    AND
                    (
                        (
                            i.tipo_inscripcion_id = 1
                            AND i.fecha_fin_curso >= CURDATE()
                        )
                        OR
                        (
                            i.tipo_inscripcion_id = 2
                            AND i.estado_tramite_id IN (
                                ' . EstadoTramite::DOCUMENTACION_APROBADA . ',
                                ' . EstadoTramite::INSCRIPTO_EXAMEN . '
                            )
                        )
                    )
                    ORDER BY i.fecha_inscripcion DESC
                ';

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':uid' => $id_usuario
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC); } 
        catch (\Exception $e) { 
            $this->registrarLog('ERROR_OBTENER_INSCRIPCIONES_ACTIVAS', 
            ['id_usuario' => $id_usuario, 'error' => $e->getMessage()]); 
            return []; 
            }
    }

    public function cancelarInscripcion(int $id): array
    {
        try {
            $pdo = $this->pdo();
            $pdo->beginTransaction();
            // Bloquear fila de inscripción para evitar condiciones de carrera
            $stmt = $pdo->prepare('SELECT * FROM inscripciones WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $ins = $stmt->fetch(\PDO::FETCH_ASSOC);
            // Verificar existencia y estado actual antes de cancelar
            if (!$ins) 
                { 
                    $pdo->rollBack(); return ['success' => false, 'mensaje' => 'Inscripción no encontrada']; 
                }

            $estado = (int)($ins['estado_tramite_id'] ?? $ins['id_estado'] ?? 0);

            if (in_array(
                        $estado,
                        [EstadoTramite::APROBADO,EstadoTramite::CARNET_EMITIDO],
                        true
                    )
                )

            $upd = $pdo->prepare('UPDATE inscripciones 
                                    SET estado_tramite_id = :estado WHERE id = :id'); 
            
            $upd->execute([':id' => $id, ':estado' => EstadoTramite::CANCELADO]);

            $fechaId = (int)($ins['fecha_curso_id'] ?? $ins['id_fecha'] ?? 0);

            if ($fechaId > 0) { 
                $inc = $pdo->prepare('UPDATE fecha_curso SET cupos = cupos + 1 
                WHERE id = :fid'); $inc->execute([':fid' => $fechaId]); 
                }

            if (class_exists('AuditoriaAccionesModelo')) 
                { 
                    try { 
                        $am = new AuditoriaAccionesModelo($pdo); 
                        if (method_exists($am, 'registrar')) 
                            { 
                                $am->registrar(['id_usuario' => $_SESSION['user_id'] ?? null, 
                                                'tabla_afectada' => 'inscripciones', 
                                                'accion' => 'UPDATE', 
                                                'datos_anteriores' => json_encode($ins), 
                                                'datos_nuevos' => json_encode(['estado_tramite_id' => EstadoTramite::CANCELADO]),
                                                 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                                                  'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']); 
                            } 
                        } 
                    catch (\Exception $e) {
                        //TODO: manejar error de logging sin afectar la operación principal
                    } 
                }
            $pdo->commit();
            $this->registrarLog('INSCRIPCION_CANCELADA', ['id' => $id]);
            return ['success' => true, 'mensaje' => 'Inscripción cancelada exitosamente'];

        } catch (\Exception $e) 
            { 
                $this->registrarLog('ERROR_CANCELAR_INSCRIPCION', 
                                    ['id' => $id, 
                                    'error' => $e->getMessage()]); 
                return ['success' => false, 'mensaje' => 'Error al cancelar inscripción: ' . $e->getMessage()]; 
            }
    }

    public function obtenerCursosDisponibles(): array
    {
        try { $pdo = $this->pdo(); $sql = 'SELECT DISTINCT c.* 
                                            FROM cursos c 
                                            JOIN fecha_curso fc ON c.id = fc.curso_id 
                                            WHERE c.activo = 1 AND fc.cupos > 0 AND fc.activo = 1 AND fc.fecha_inicio > NOW() 
                                            ORDER BY c.nombre ASC'; 
            $stmt = $pdo->prepare($sql); 
            $stmt->execute(); 
            return $stmt->fetchAll(\PDO::FETCH_ASSOC); } 
        catch (\Exception $e) 
            { 
                $this->registrarLog('ERROR_OBTENER_CURSOS_DISPONIBLES', ['error' => $e->getMessage()]); 
                return []; }
    }

    public function obtenerExamenesDisponibles(): array
    {
        try {
            $stmt = $this->pdo()->prepare('SELECT id, fecha, hora, ubicacion, cupos 
                                            FROM examenes
                                             WHERE activo = 1 AND cupos >= 0 AND fecha >= CURDATE() 
                                             ORDER BY fecha ASC, hora ASC');
            $stmt->execute();
            $examenes = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $fecha = new \DateTimeImmutable($row['fecha']);
                $hora = $row['hora'] ? substr((string)$row['hora'], 0, 5) : '';
                $examenes[] = ['id' => (int)$row['id'], 
                                'month' => strtoupper($fecha->format('M')), 
                                'day' => $fecha->format('d'), 
                                'title' => $row['ubicacion'] ?: 'Examen', 
                                'capacity' => ((int)$row['cupos'] > 0) ? 1 : 0, 
                                'capacity_label' => ((int)$row['cupos'] > 0) ? 'CUPOS DISPONIBLES' : 'SIN CUPOS', 
                                'time' => $hora !== '' ? date('h:i A', strtotime($hora)) : '', 
                                'room' => $row['ubicacion'] ?: '', 
                                'route' => 'inscripcion_examen'];
            }
            return $examenes;
        } catch (\Exception $e) { $this->registrarLog('ERROR_OBTENER_EXAMENES_DISPONIBLES', ['error' => $e->getMessage()]); return []; }
    }

    public function confirmarInscripcionExamen(int $id_inscripcion): array
    {
        try {

            $insc = $this->inscripcionModelo ? $this->inscripcionModelo->obtenerPorId($id_inscripcion) : null;

            if (!$insc) return ['success' => false, 
                                'mensaje' => 'Inscripción no encontrada', 
                                'inscripcion' => null];
            //
            $validCtrl = new ValidacionControlador(); 
            $v = $validCtrl->validarDocumentacion($id_inscripcion);

            if (!$v['valido']) 
                return ['success' => false, 
                        'mensaje' => 'Documentación incompleta: ' . implode(', ', $v['documentos_faltantes']), 
                        'inscripcion' => null];

            $pdo = $this->pdo(); 
            $upd = $pdo->prepare('UPDATE inscripciones
                                 SET estado_tramite_id = :estado 
                                 WHERE id = :id');  

            $upd->execute([':id' => $id_inscripcion, ':estado' => EstadoTramite::INSCRIPTO_EXAMEN]); 

            $examen_id = (int)($insc['examen_id'] ?? 0);

            if ($examen_id > 0) 
                { 
                    $dec = $pdo->prepare('UPDATE examenes 
                                        SET cupos = GREATEST(cupos - 1, 0) 
                                        WHERE id = :id'); 
                    $dec->execute([':id' => $examen_id]); 
                }

            $this->registrarLog('INSCRIPCION_EXAMEN_CONFIRMADA', ['id_inscripcion' => $id_inscripcion]);

            return ['success' => true, 'mensaje' => 'Inscripción a examen confirmada', 'inscripcion' => $this->obtenerInscripcion($id_inscripcion)];

        } catch (\Exception $e) 
            { 
                $this->registrarLog('ERROR_CONFIRMAR_INSCRIPCION_EXAMEN', ['id_inscripcion' => $id_inscripcion, 'error' => $e->getMessage()]); 
                return ['success' => false, 
                        'mensaje' => 'Error al confirmar inscripción: ' . $e->getMessage(), 
                        'inscripcion' => null]; 
            }
    }

    public function procesarInscripcionExamen(array $datos): array
    {

        $idUsuario =
                (int)($datos['id_usuario']
                ?? $_SESSION['usuario_id']
                ?? 0);

        $validacion =
            $this->usuarioPuedeInscribirseExamen(
                $idUsuario
            );


        if (!$validacion['puede']) {

            return [
                'success' => false,
                'mensaje' =>
                    'Debe completar la documentación requerida',
                'faltantes' =>
                    $validacion['faltantes'] ?? []
            ];
        }

        $idExamen =
            (int)($datos['id_examen'] ?? 0);

        if ($idUsuario <= 0 || $idExamen <= 0) {

            return [
                'success' => false,
                'mensaje' =>
                    'Faltan datos para completar la inscripción',
                'inscripcion' => null
            ];
        }

        $payload = [
            'usuario_id' => $idUsuario,
            'curso_id' => null,
            'examen_id' => $idExamen,
            'tipo_inscripcion_id' =>
                (int)($datos['id_tipo_inscripcion'] ?? 2),
            'estado_tramite_id' =>
                EstadoTramite::INSCRIPTO_EXAMEN,
            'observaciones' =>
                $datos['observaciones'] ?? null
        ];

        $res =
            $this->crearInscripcion(
                $payload
            );

        if (
            $res['success']
            && !empty($res['id'])
        ) {

            return
                $this->confirmarInscripcionExamen(
                    (int)$res['id']
                );
        }

        return $res;
    }




    public function obtenerDetalleInscripcion(int $id): array
    {
        try {
            $pdo = $this->pdo();
            $sql = 'SELECT i.*, u.nombre as usuario_nombre, 
                                u.apellido as usuario_apellido, 
                                u.email as usuario_email, 
                                c.nombre as curso_nombre, 
                                e.fecha as examen_fecha 
                                FROM inscripciones i 
                                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                                LEFT JOIN cursos c ON i.curso_id = c.id 
                                LEFT JOIN examenes e ON i.examen_id = e.id 
                                WHERE i.id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $ins = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ins) return [];
            $dstmt = $pdo->prepare('SELECT * FROM documento WHERE inscripcion_id = :id ORDER BY fecha_subida DESC'); 
            $dstmt->execute([':id' => $id]); $docs = $dstmt->fetchAll(\PDO::FETCH_ASSOC);

            $astmt = $pdo->prepare('SELECT * FROM asistencia WHERE inscripcion_id = :id ORDER BY fecha ASC'); 
            $astmt->execute([':id' => $id]); $asis = $astmt->fetchAll(\PDO::FETCH_ASSOC);

            $rstmt = $pdo->prepare('SELECT * FROM resultado_examen WHERE inscripcion_id = :id'); 
            $rstmt->execute([':id' => $id]); $resEx = $rstmt->fetchAll(\PDO::FETCH_ASSOC);
           
            $estado = [];
            if (isset($ins['estado_tramite_id'])) 
                { 
                    $est = $pdo->prepare('SELECT * FROM estados_tramite WHERE id = :id'); 
                    $est->execute([':id' => $ins['estado_tramite_id']]); 
                    $estado = $est->fetch(\PDO::FETCH_ASSOC) ?: []; 
                    }

            return ['inscripcion' => $ins, 
                    'usuario' => ['nombre' => $ins['usuario_nombre'] ?? '', 
                    'apellido' => $ins['usuario_apellido'] ?? '',
                     'email' => $ins['usuario_email'] ?? ''], 
                     'curso' => ['nombre' => $ins['curso_nombre'] ?? ''],
                      'examen' => ['fecha' => $ins['examen_fecha'] ?? null],
                       'documentos' => $docs, 
                       'asistencia' => $asis, 
                       'resultado_examen' => $resEx, 'estado_actual' => $estado];
        } catch (\Exception $e) { $this->registrarLog('ERROR_OBTENER_DETALLE_INSCRIPCION', ['id' => $id, 'error' => $e->getMessage()]); return []; }
    }

    private function usuarioPuedeInscribirseExamen(int $idUsuario): array
    {
        $documentos =
            $this->documentoModelo
                ->obtenerPorUsuario(
                    $idUsuario
                );


        
        $tieneDni = false;
        $tieneFoto = false;
        $tieneAsistencia = false;
        $tieneMoodle = false;


        foreach ($documentos as $documento) {

            if (
                ($documento['estado'] ?? '')
                !== 'aprobado'
            ) {
                continue;
            }

            switch (
                strtolower(
                    $documento['tipo_documento']
                )
            ) {

                case 'dni':
                    $tieneDni = true;
                    break;

                case 'foto_carnet':
                    $tieneFoto = true;
                    break;

                case 'asistencia':
                    $tieneAsistencia = true;
                    break;

                case 'moodle':
                case 'certificado_moodle':
                    $tieneMoodle = true;
                    break;
            }
        }

        $faltantes = [];

        if (!$tieneDni) {
            $faltantes[] = 'DNI';
        }

        if (!$tieneFoto) {
            $faltantes[] = 'Foto Carnet';
        }

        if (
            !$tieneAsistencia
            && !$tieneMoodle
        ) {
            $faltantes[] =
                'Constancia de asistencia o certificado Moodle';
        }

        return [
            'puede' => empty($faltantes),
            'faltantes' => $faltantes
        ];
    }


    public function inscribirseCurso(): void
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

            $cursoId =
                (int)(
                    $_POST['curso_id']
                    ?? 0
                );

            if ($cursoId <= 0) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_invalido'
                );

                exit;
            }
            if (
                $this->inscripcionModelo
                    ->tieneCursoActivo(
                        (int)$_SESSION['usuario_id']
                    )
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_activo'
                );

                exit;
            }
            $documentos = $this->documentoModelo
                            ->obtenerPorUsuario(
                                (int)$_SESSION['usuario_id']
                            );
            $tieneDni = false;
            $tieneFoto = false;

            foreach ($documentos as $doc) {

                if (($doc['estado'] ?? '') !== 'aprobado') {
                    continue;
                }

                switch (
                    strtoupper($doc['tipo_documento'] ?? '')
                ) {

                    case 'DNI':
                        $tieneDni = true;
                        break;

                    case 'FOTO':
                    case 'FOTO_CARNET':
                        $tieneFoto = true;
                        break;
                }
            }

            if (!$tieneDni || !$tieneFoto) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=documentacion_incompleta'
                );

                exit;
            }
            $resultado =
                $this->inscripcionModelo->crear([
                    'usuario_id' =>
                        (int)$_SESSION['usuario_id'],

                    'curso_id' =>
                        $cursoId,

                    'tipo_inscripcion_id' =>
                        1,

                    'estado_tramite_id' =>
                        1
                ]);

            if (!$resultado) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=ya_inscripto'
                );

                exit;
            }

            header(
                'Location: ' .
                BASE_URL .
                '/?toast=curso_inscripto'
            );

            exit;

        } catch (\Exception $e) {

            header(
                'Location: ' .
                BASE_URL .
                '/?toast=error_inscripcion'
            );

            exit;
        }
    }


}
