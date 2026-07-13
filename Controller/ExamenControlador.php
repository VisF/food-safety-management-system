<?php
declare(strict_types=1);


/**
 * ExamenControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * ExamenControlador
 *
 * Gestión administrativa de exámenes.
 *
 * Responsabilidades:
 * - Crear exámenes.
 * - Listar exámenes.
 * - Obtener detalle de un examen.
 * - Obtener exámenes próximos.
 * - Obtener exámenes disponibles.
 * - Registrar resultados.
 * - Consultar resultados.
 * - Registrar asistencia.
 * - Consultar asistencia.
 * - Verificar habilitación para rendir.
 * - Obtener aprobados.
 * - Obtener próximos exámenes de un usuario.
 *
 * Dependencias:
 * - ExamenService
 * - ResultadoExamenService
 * - AsistenciaService
 * - InscripcionService
 *
 * Validaciones:
 * - Nota entre 0 y 100.
 * - Aprobación con nota mínima de 60.
 * - Verificación de habilitación para rendir.
 * - Registro de eventos en log.
 *
 * Métodos:
 * - guardar()
 * - listarExamenes()
 * - obtenerExamen()
 * - obtenerDetalleExamen()
 * - obtenerExamenesProximos()
 * - obtenerExamenesDisponibles()
 * - registrarResultado()
 * - obtenerResultado()
 * - verificarHabilitacion()
 * - obtenerAsistencia()
 * - registrarAsistencia()
 * - obtenerProximosExamenes()
 * - obtenerAprobados()
 */

require_once __DIR__ . '/../Servicios/ExamenService.php';
require_once __DIR__ . '/../Servicios/ResultadoExamenService.php';
require_once __DIR__ . '/../Servicios/AsistenciaService.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';

class ExamenControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/examen_controller.log';
    private const NOTA_MINIMA_APROBACION = 60;
    private const BASE_PATH = '/manipulacionDeAlimentos';


    private ?ExamenService $examenService = null;
    private ?ResultadoExamenService $resultadoExamenService = null;
    private ?AsistenciaService $asistenciaService = null;
    private ?InscripcionService $inscripcionService = null;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->examenService = new ExamenService();
        $this->resultadoExamenService = new ResultadoExamenService();
        $this->asistenciaService = new AsistenciaService();
        $this->inscripcionService = new InscripcionService();

    }
    // Ejecuta guardar.
    public function guardar(): void
    {


        $fecha = trim($_POST['fecha'] ?? '');
        $hora = trim($_POST['hora'] ?? '');
        $cupos = (int)($_POST['cupos'] ?? 0);
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $aula = trim($_POST['aula'] ?? '');

        $errores = [];

        if ($fecha === '') {
            $errores[] = 'Debe indicar una fecha.';
        }

        if ($hora === '') {
            $errores[] = 'Debe indicar una hora.';
        }

        if ($cupos <= 0) {
            $errores[] = 'Los cupos deben ser mayores a cero.';
        }

        if ($ubicacion === '') {
            $errores[] = 'Debe indicar una ubicación.';
        }

        if ($aula === '') {
            $errores[] = 'Debe indicar un aula.';
        }

        if (!empty($errores)) {

            $data = [
                'error' => implode(' ', $errores),
                'fecha_display' => $_POST['fecha_display'] ?? '',
                'hora' => $hora,
                'cupos' => (string)$cupos,
                'ubicacion' => $ubicacion,
                'aula' => $aula
            ];

            header(
                'Location: ' . self::BASE_PATH . '/crear_examen?data=' .
                urlencode(json_encode($data))
            );
            exit;
        }

        $resultado =
            $this->examenService
                ->crearExamen([
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'ubicacion' => $ubicacion,
                    'aula' => $aula,
                    'cupos' => $cupos
                ]);

        if ($resultado <= 0){

            $data = [
                'error' => 'No fue posible crear el examen.',
                'fecha_display' => $_POST['fecha_display'] ?? '',
                'hora' => $hora,
                'cupos' => (string)$cupos,
                'ubicacion' => $ubicacion,
                'aula' => $aula
            ];

            header(
                'Location: ' . self::BASE_PATH . '/crear_examen?data=' .
                urlencode(json_encode($data))
            );
            exit;
        }

        $data = [
            'success' => true,
            'message' => 'Fecha de examen creada correctamente.'
        ];

        header(
            'Location: ' . self::BASE_PATH . '/crear_examen?data=' .
            urlencode(json_encode($data))
        );
        exit;
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

            return
                $this->examenService
                    ->listarExamenes();

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_LISTAR_EXAMENES',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    /**
     * Obtener examen por ID.
     */
    public function obtenerExamen(int $id): ?array
    {
        try {

            return
                $this->examenService
                    ->obtenerExamen($id);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_EXAMEN',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Obtener detalle completo de un examen.
     */
    public function obtenerDetalleExamen(int $id): array
    {
        try {

            $detalle =
                $this->examenService
                    ->obtenerDetalleExamen($id);

            return $detalle ?? [];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_DETALLE_EXAMEN',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    /**
     * Obtener próximos exámenes.
     */
    public function obtenerExamenesProximos(): array
    {
        try {

            return
                $this->examenService
                    ->obtenerProximos(30);

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_EXAMENES_PROXIMOS',
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

            return
                $this->examenService
                    ->obtenerDisponibles();

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

    // Registra resultado.
    public function registrarResultado(int $idInscripcion, array $datos): array {

        try {

            $nota = (float)($datos['nota'] ?? 0);

            if ($nota < 0 || $nota > 100) {

                return [
                    'success' => false,
                    'aprobado' => false,
                    'mensaje' => 'Nota fuera de rango'
                ];
            }

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId($idInscripcion);

            if ($inscripcion === null) {

                return [
                    'success' => false,
                    'aprobado' => false,
                    'mensaje' => 'Inscripción inexistente'
                ];
            }

            $resultado =
                $this->resultadoExamenService
                    ->registrarResultado([
                        'inscripcion_id' => $idInscripcion,
                        'examen_id' => $inscripcion->getExamenId(),
                        'nota' => $nota,
                        'aprobado' => (
                            $nota >= self::NOTA_MINIMA_APROBACION
                        ) ? 1 : 0,
                        'observaciones' =>
                            $datos['observaciones'] ?? null
                    ]);

            if ($resultado === null) {

                return [
                    'success' => false,
                    'aprobado' => false,
                    'mensaje' =>
                        'Ya existe un resultado para esta inscripción'
                ];
            }

            $this->inscripcionService
                ->actualizarEstadoInscripcion(
                    $idInscripcion,
                    $nota >= self::NOTA_MINIMA_APROBACION
                        ? EstadoTramite::APROBADO
                        : EstadoTramite::REPROBADO
                );

            $this->registrarLog(
                'RESULTADO_EXAMEN_REGISTRADO',
                [
                    'id_inscripcion' => $idInscripcion,
                    'nota' => $nota
                ]
            );

            return [
                'success' => true,
                'aprobado' =>
                    $nota >= self::NOTA_MINIMA_APROBACION,
                'mensaje' =>
                    $nota >= self::NOTA_MINIMA_APROBACION
                        ? 'Examen aprobado'
                        : 'Examen reprobado'
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_REGISTRAR_RESULTADO',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'aprobado' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    // Obtiene resultado.
    public function obtenerResultado(int $idInscripcion): ?array {

        try {

            return
                $this->resultadoExamenService
                    ->obtenerPorInscripcion(
                        $idInscripcion
                    );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_RESULTADO',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return null;
        }
    }

    // Ejecuta verificar habilitacion.
    public function verificarHabilitacion(int $idInscripcion): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId($idInscripcion);

            if ($inscripcion === null) {

                return [
                    'habilitado' => false,
                    'motivos' => [
                        'Inscripción inexistente'
                    ]
                ];
            }

            $validacion =
                $this->usuarioPuedeInscribirseExamen(
                    $inscripcion->getUsuarioId()
                );

            return [
                'habilitado' => $validacion['puede'],
                'motivos' => $validacion['faltantes']
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_VERIFICAR_HABILITACION',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'habilitado' => false,
                'motivos' => [
                    'Error en validación'
                ]
            ];
        }
    }

    // Obtiene asistencia.
    public function obtenerAsistencia(int $idInscripcion): array
    {
        try {

            return
                $this->asistenciaService
                    ->obtenerTotalAsistencias(
                        $idInscripcion
                    );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_ASISTENCIA',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Registra asistencia.
    public function registrarAsistencia(int $idInscripcion, bool $presente): array {

        try {

            $ok =
                $this->asistenciaService
                    ->registrarAsistencia(
                        $idInscripcion,
                        $presente
                    );

            if (!$ok) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No se pudo registrar la asistencia'
                ];
            }

            $this->registrarLog(
                'ASISTENCIA_REGISTRADA',
                [
                    'id_inscripcion' => $idInscripcion,
                    'presente' => $presente
                ]
            );

            return [
                'success' => true,
                'mensaje' =>
                    'Asistencia registrada correctamente'
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_REGISTRAR_ASISTENCIA',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

   // Obtiene proximos examenes.
   public function obtenerProximosExamenes(int $idUsuario): array
    {
        try {

            return
                $this->examenService
                    ->obtenerProximosPorUsuario(
                        $idUsuario
                    );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_PROXIMOS_EXAMENES',
                [
                    'id_usuario' => $idUsuario,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }

    // Obtiene aprobados.
    public function obtenerAprobados(int $idExamen): array
    {
        try {

            return
                $this->examenService
                    ->obtenerAprobados(
                        $idExamen
                    );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_APROBADOS',
                [
                    'id_examen' => $idExamen,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }
}
