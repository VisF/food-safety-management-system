<?php
declare(strict_types=1);


/**
 * InscripcionControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * InscripcionControlador - Gestión de inscripciones a cursos y exámenes
 */
require_once __DIR__ . '/../Constant/EstadoTramite.php';


require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../Servicios/CursoService.php';
require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../Servicios/ExamenService.php';

require_once __DIR__ . '/ValidacionControlador.php';

class InscripcionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/inscripcion_controller.log';

    private ?inscripcionService $inscripcionService = null;
    private ?ExamenService $examenService = null;
    private ValidacionControlador $validacionControlador;
    private ?DocumentoService $documentoService = null;
    private ?CursoService $cursoService = null;

    // Ejecuta pdo.
    private function pdo(): \PDO
    {
        require_once __DIR__ . '/../db/Connection.php';
        return Connection::getPDO();
    }

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inscripcionService = new inscripcionService();
        $this->examenService = new ExamenService();
        $this->validacionControlador = new ValidacionControlador();
        $this->documentoService = new DocumentoService();
        $this->cursoService = new CursoService();
    }



    // Registra log.
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    // Crea inscripcion.
    public function crearInscripcion(array $datos): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->crear($datos);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'id' => null,
                    'mensaje' => 'No se pudo crear la inscripción',
                    'inscripcion' => null
                ];
            }

            $this->registrarLog(
                'INSCRIPCION_CREADA',
                [
                    'id' => $inscripcion->getId(),
                    'usuario_id' => $inscripcion->getUsuarioId()
                ]
            );

            return [
                'success' => true,
                'id' => $inscripcion->getId(),
                'mensaje' => 'Inscripción creada exitosamente',
                'inscripcion' => $inscripcion
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_CREAR_INSCRIPCION',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'id' => null,
                'mensaje' => $e->getMessage(),
                'inscripcion' => null
            ];
        }
    }

    // Valida inscripcion.
    public function validarInscripcion(int $id): array
    {
        try {

            return
                $this->inscripcionService
                    ->validarInscripcion($id);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_VALIDAR_INSCRIPCION',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'valido' => false,
                'motivos_rechazo' => [
                    $e->getMessage()
                ],
                'puede_inscribirse' => false
            ];
        }
    }

    // Obtiene inscripciones por usuario.
    public function obtenerInscripcionesPorUsuario(int $usuarioId): array
    {
        try {

            return $this->inscripcionService
                ->obtenerPorUsuario($usuarioId);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_INSCRIPCIONES_POR_USUARIO',
                [
                    'usuario_id' => $usuarioId,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Obtiene inscripcion.
    public function obtenerInscripcion(int $id): ?InscripcionDTO
    {
        try {

            return $this->inscripcionService
                ->obtenerPorId($id);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_INSCRIPCION',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return null;
        }
    }

    // Obtiene inscripciones activas.
    public function obtenerInscripcionesActivas(int $usuarioId): array
    {
        try {

            return $this->inscripcionService
                ->obtenerActivas($usuarioId);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_INSCRIPCIONES_ACTIVAS',
                [
                    'usuario_id' => $usuarioId,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Ejecuta cancelar inscripcion.
    public function cancelarInscripcion(int $id, string $motivo = ''): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId($id);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'mensaje' => 'Inscripción no encontrada'
                ];
            }

            if (
                $inscripcion->getEstadoId()
                ===
                EstadoTramite::APROBADO
            ) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No puede cancelarse una inscripción finalizada'
                ];
            }

            $ok =
                $this->inscripcionService
                    ->cancelar(
                        $id,
                        $motivo
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No se pudo cancelar la inscripción'
                ];
            }

            $this->registrarLog(
                'INSCRIPCION_CANCELADA',
                [
                    'id' => $id
                ]
            );

            return [
                'success' => true,
                'mensaje' =>
                    'Inscripción cancelada correctamente'
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_CANCELAR_INSCRIPCION',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    // Obtiene cursos disponibles.
    public function obtenerCursosDisponibles(): array
    {
        try {

            return $this->cursoService
                ->obtenerActivos();

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_CURSOS_DISPONIBLES',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Obtiene examenes disponibles.
    public function obtenerExamenesDisponibles(): array
    {
        try {

            $examenes =
                $this->examenService
                    ->obtenerProximos(100);

            $resultado = [];

            foreach ($examenes as $row) {

                $fecha = new \DateTimeImmutable(
                    $row['fecha']
                );

                $hora = $row['hora']
                    ? substr($row['hora'], 0, 5)
                    : '';

                $resultado[] = [

                    'id' => (int)$row['id'],

                    'month' =>
                        strtoupper(
                            $fecha->format('M')
                        ),

                    'day' =>
                        $fecha->format('d'),

                    'title' =>
                        $row['ubicacion']
                        ?: 'Examen',

                    'capacity' =>
                        ((int)$row['cupos'] > 0)
                            ? 1
                            : 0,

                    'capacity_label' =>
                        ((int)$row['cupos'] > 0)
                            ? 'CUPOS DISPONIBLES'
                            : 'SIN CUPOS',

                    'time' =>
                        $hora !== ''
                            ? date(
                                'h:i A',
                                strtotime($hora)
                            )
                            : '',

                    'room' =>
                        $row['ubicacion'] ?: '',

                    'route' =>
                        'inscripcion_examen'
                ];
            }

            return $resultado;

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_EXAMENES_DISPONIBLES',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Ejecuta confirmar inscripcion examen.
    public function confirmarInscripcionExamen(int $idInscripcion): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId($idInscripcion);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'mensaje' => 'Inscripción no encontrada',
                    'inscripcion' => null
                ];
            }

            $validacion =
                $this->usuarioPuedeInscribirseExamen(
                    $inscripcion->getUsuarioId()
                );

            if (!$validacion['puede']) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'Debe completar la documentación requerida',
                    'faltantes' =>
                        $validacion['faltantes']
                ];
            }

            $ok =
                $this->inscripcionService
                    ->confirmarInscripcionExamen(
                        $idInscripcion
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No fue posible confirmar la inscripción'
                ];
            }

            $this->registrarLog(
                'INSCRIPCION_EXAMEN_CONFIRMADA',
                [
                    'id_inscripcion' => $idInscripcion
                ]
            );

            return [
                'success' => true,
                'mensaje' =>
                    'Inscripción a examen confirmada',
                'inscripcion' =>
                    $this->inscripcionService
                        ->obtenerPorId($idInscripcion)
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_CONFIRMAR_INSCRIPCION_EXAMEN',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' =>
                    'Error al confirmar inscripción: ' .
                    $e->getMessage(),
                'inscripcion' => null
            ];
        }
    }

    // Procesa inscripcion examen.
    public function procesarInscripcionExamen(array $datos): array
    {

    $idUsuario =
        (int)($datos['id_usuario']
        ?? $_SESSION['usuario_id']
        ?? 0);

    if ($idUsuario <= 0) {
        return [
                'success' => false,
                'codigo'  => 'login_requerido',
                'mensaje' => 'Debe iniciar sesión para inscribirse a un examen.'
            ];
    }

    if ($this->inscripcionService->tieneExamenActivo($idUsuario)) {

        return [
            'success' => false,
            'mensaje' => 'Ya posee una inscripción activa a un examen.'
        ];
    }

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



    // Obtiene detalle inscripcion.
    public function obtenerDetalleInscripcion(int $id): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerDetalleInscripcion($id);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'inscripcion' => null
                ];
            }

            return [
                'success' => true,
                'inscripcion' => $inscripcion
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_DETALLE_INSCRIPCION',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'inscripcion' => null
            ];
        }
    }

    // Ejecuta usuario puede inscribirse examen.
    private function usuarioPuedeInscribirseExamen(int $idUsuario): array
    {
        try {

            return $this->inscripcionService
                ->usuarioPuedeInscribirseExamen(
                    $idUsuario
                );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_VALIDAR_INSCRIPCION_EXAMEN',
                [
                    'usuario_id' => $idUsuario,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'puede' => false,
                'faltantes' => [
                    'Ocurrió un error al validar la documentación.'
                ]
            ];
        }
    }

    // Ejecuta inscribirse curso.
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

            $usuarioId = (int)$_SESSION['usuario_id'];

            $cursoId = (int)(
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

            $curso =
                $this->cursoService
                    ->obtenerPorId($cursoId);

            if (!$curso) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_invalido'
                );

                exit;
            }

            if (!(bool)$curso['activo']) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_inactivo'
                );

                exit;
            }

            if (
                $this->inscripcionService
                    ->tieneCursoActivo($usuarioId)
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_activo'
                );

                exit;
            }

            if (
                $this->inscripcionService
                    ->verificarDuplicado(
                        $usuarioId,
                        $cursoId
                    )
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=ya_inscripto'
                );

                exit;
            }

            if (
                $this->inscripcionService
                    ->contarInscriptosCurso($cursoId)
                >=
                (int)$curso['cupos']
            ) {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=curso_sin_cupos'
                );

                exit;
            }

            $documentos =
                $this->documentoService
                    ->obtenerPorUsuario($usuarioId);

            $tieneDni = false;
            $tieneFoto = false;
        foreach ($documentos as $doc) {

                    if (!$doc->estaAprobado()) {
                        continue;
                    }

                    switch (
                        strtoupper(
                            $doc->getTipoDocumento()
                        )
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
                    $this->inscripcionService
                        ->crear([
                            'usuario_id' => $usuarioId,
                            'curso_id' => $cursoId,
                            'tipo_inscripcion_id' => 1,
                            'estado_tramite_id' => EstadoTramite::PENDIENTE,
                            'fecha_inscripcion' => date('Y-m-d H:i:s')
                        ]);

                if ($resultado === null) {

                    header(
                        'Location: ' .
                        BASE_URL .
                        '/?toast=error_inscripcion'
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

                $this->registrarLog(
                    'Error al inscribir al curso',
                    [
                        'error' => $e->getMessage()
                    ]
                );

                header(
                    'Location: ' .
                    BASE_URL .
                    '/?toast=error_inscripcion'
                );

                exit;
            }
        }

}
