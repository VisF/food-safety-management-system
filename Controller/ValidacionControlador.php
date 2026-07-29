<?php
declare(strict_types=1);

/**
 * ValidacionControlador - Controlador del sistema.
 *
 * Define la lógica principal del módulo y sus operaciones públicas.
 */

/**
 * ValidacionControlador - Gestión de validaciones de inscripciones
 *
 * Responsabilidades:
 * - Validar cumplimiento de requisitos.
 * - Verificar documentación.
 * - Verificar asistencia.
 * - Validar cumplimiento de requisitos.
 * - Verificar documentación.
 * - Verificar asistencia.
 * - Procesar la validación integral.
 * - Procesar la validación integral.
 */

require_once __DIR__ . '/../Servicios/CarnetService.php';
require_once __DIR__ . '/../Servicios/asistenciaService.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../Servicios/documentoService.php';
require_once __DIR__ . '/../Servicios/resultadoExamenService.php';


require_once __DIR__ . '/../Constant/EstadoTramite.php';

class ValidacionControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/validacion_controller.log';

    private const ASISTENCIA_MINIMA_PRESENCIAL = 80.0;

    private const PLAZO_RECURSANTE_DIAS = 90;

    private const PORCENTAJE_DOCUMENTACION_REQUERIDA = 100;

    private CarnetService $carnetService;

    private InscripcionService $inscripcionService;

    private DocumentoService $documentoService;

    private AsistenciaService $asistenciaService;

    private ResultadoExamenService $resultadoExamenService;



    /**
     * Inicializa las dependencias.
     */
    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->carnetService =
            new CarnetService();

        $this->inscripcionService =
            new InscripcionService();

        $this->documentoService =
            new DocumentoService();

        $this->asistenciaService =
            new AsistenciaService();

        $this->resultadoExamenService =
            new ResultadoExamenService();


    }

    /**
     * Registrar evento en el log.
     */
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void
    {
        $mensaje = sprintf(
            "[%s] Usuario: %s | Evento: %s | Datos: %s\n",
            date('Y-m-d H:i:s'),
            $_SESSION['user_id'] ?? 'anonimo',
            $evento,
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            )
        );

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }

  /**
     * Validar documentación completa.
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array
     */
    public function validarDocumentacion(int $id_inscripcion): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId(
                        $id_inscripcion
                    );

            if ($inscripcion === null) {

                return [
                    'valido' => false,
                    'documentos_requeridos' => [],
                    'documentos_faltantes' => []
                ];
            }

            $modalidad =
                $this->inscripcionService
                    ->obtenerModalidadCurso(
                        $id_inscripcion
                    )
                ?? 'presencial';

            $usuarioId =
                $inscripcion->getUsuarioId();

            $estado =
                $this->documentoService
                    ->obtenerEstadoDocumentacion(
                        $usuarioId
                    );

            $faltantes = [];

            if (!$estado['dni']) {
                $faltantes[] = 'DNI';
            }

            if (!$estado['foto']) {
                $faltantes[] = 'Foto Carnet';
            }

            if (
                $modalidad === 'virtual'
                && !$estado['moodle']
            ) {

                $faltantes[] =
                    'Certificado Moodle';
            }

            if (
                $modalidad !== 'virtual'
                && !$estado['asistencia']
            ) {

                $faltantes[] =
                    'Constancia de asistencia';
            }

            return [
                'valido' => empty($faltantes),

                'documentos_requeridos' => [
                    'DNI',
                    'Foto Carnet',
                    $modalidad === 'virtual'
                        ? 'Certificado Moodle'
                        : 'Constancia de asistencia'
                ],

                'documentos_faltantes' =>
                    $faltantes
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_VALIDAR_DOCUMENTACION',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'error' => $e->getMessage()
                ]
            );

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
     * @return array
     */
    public function validarPlazoRecursante(
        int $id_usuario
    ): array
    {
        try {

            $resultado =
                $this->resultadoExamenService
                    ->obtenerUltimoExamenReprobadoUsuario(
                        $id_usuario
                    );

            if ($resultado === null) {

                return [
                    'puede_recursar' => true,
                    'dias_restantes' => null,
                    'ultimo_examen_fallido' => null
                ];
            }

            $fecha =
                $resultado['fecha_resultado'] ?? null;

            if ($fecha === null) {

                return [
                    'puede_recursar' => true,
                    'dias_restantes' => null,
                    'ultimo_examen_fallido' => null
                ];
            }

            $fechaExamen =
                new DateTime($fecha);

            $hoy =
                new DateTime();

            $diasTranscurridos =
                $fechaExamen->diff($hoy)->days;

            $diasRestantes =
                max(
                    0,
                    self::PLAZO_RECURSANTE_DIAS -
                    $diasTranscurridos
                );

            return [
                'puede_recursar' =>
                    $diasTranscurridos >=
                    self::PLAZO_RECURSANTE_DIAS,

                'dias_restantes' =>
                    $diasRestantes,

                'ultimo_examen_fallido' =>
                    $fecha
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_VALIDAR_PLAZO_RECURSANTE',
                [
                    'id_usuario' => $id_usuario,
                    'error' => $e->getMessage()
                ]
            );

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
     * @return array
     */
    public function validarRenovacion(
        int $id_inscripcion
    ): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId(
                        $id_inscripcion
                    );

            if ($inscripcion === null) {

                return [
                    'puede_renovar' => false,
                    'carnet_vencido' => false,
                    'fecha_vencimiento' => null
                ];
            }

            $carnet =
                $this->carnetService
                    ->obtenerUltimoCarnetUsuario(
                        $inscripcion->getUsuarioId()
                    );

            if ($carnet === null) {

                return [
                    'puede_renovar' => false,
                    'carnet_vencido' => false,
                    'fecha_vencimiento' => null
                ];
            }

            $fechaVencimiento =
                $carnet['fecha_vencimiento'];

            $vencido =
                new DateTime($fechaVencimiento)
                < new DateTime();

            return [
                'puede_renovar' => $vencido,
                'carnet_vencido' => $vencido,
                'fecha_vencimiento' => $fechaVencimiento
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_VALIDAR_RENOVACION',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'puede_renovar' => false,
                'carnet_vencido' => false,
                'fecha_vencimiento' => null
            ];
        }
    }
    /**
     * Procesar validación integral de inscripción.
     *
     * @param int $id_inscripcion
     * @return array
     */
    public function procesarValidacion(
        int $id_inscripcion
    ): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId(
                        $id_inscripcion
                    );

            if ($inscripcion === null) {

                return [
                    'resultado_general' => false,
                    'validaciones' => [],
                    'pueden_rendir' => false
                ];
            }

            $contexto = [
                'inscripcion' => $inscripcion,
                'curso' =>
                    $this->inscripcionService
                        ->obtenerCurso(
                            $id_inscripcion
                        ),
                'tipo_inscripcion' =>
                    $this->inscripcionService
                        ->obtenerTipoInscripcion(
                            $id_inscripcion
                        )
            ];

            $validaciones = [

                'documentacion' =>
                    $this->validarDocumentacion(
                        $id_inscripcion
                    ),

                
            ];

            $resultadoGeneral = true;

            foreach ($validaciones as $validacion) {

                if (
                    isset($validacion['valido']) &&
                    !$validacion['valido']
                ) {

                    $resultadoGeneral = false;
                    break;
                }
            }

            if ($resultadoGeneral) {

                $this->inscripcionService
                    ->actualizarEstadoTramite(
                        $id_inscripcion,
                        EstadoTramite::DOCUMENTACION_APROBADA
                    );
            }

            $this->registrarLog(
                'VALIDACION_PROCESADA',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'resultado' => $resultadoGeneral
                ]
            );

            return [

                'resultado_general' =>
                    $resultadoGeneral,

                'validaciones' =>
                    $validaciones,

                'pueden_rendir' =>
                    $resultadoGeneral,

                'contexto' =>
                    $contexto
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_PROCESAR_VALIDACION',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'resultado_general' => false,
                'validaciones' => [],
                'pueden_rendir' => false
            ];
        }
    }
    /**
     * Obtener detalle completo de una validación.
     *
     * @param int $id
     * @return array
     */
    public function obtenerDetalleValidacion(
        int $id
    ): array
    {
        try {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerPorId($id);

            if ($inscripcion === null) {
                return [];
            }

            return [

                'inscripcion' =>
                    $inscripcion,

                'curso' =>
                    $this->inscripcionService
                        ->obtenerCurso($id),

                'tipo_inscripcion' =>
                    $this->inscripcionService
                        ->obtenerTipoInscripcion($id),

                'documentos' =>
                    $this->documentoService
                        ->obtenerPorInscripcion($id),

                'asistencia' =>
                    $this->obtenerAsistencia($id),

                'resultado_examen' =>
                    $this->resultadoExamenService
                        ->obtenerPorInscripcion($id),

                'validaciones' =>
                    $this->procesarValidacion($id)
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_DETALLE_VALIDACION',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }
    /**
     * Obtener inscripciones pendientes de validación.
     *
     * @return array
     */
    public function obtenerValidacionesPendientes(): array
    {
        try {

            return
                $this->inscripcionService
                    ->obtenerPendientesValidacion();

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_VALIDACIONES_PENDIENTES',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }
    /**
     * Obtener información de asistencia.
     *
     * @param int $id_inscripcion
     * @return array
     */
    private function obtenerAsistencia(
        int $id_inscripcion
    ): array
    {
        try {

            $datos =
                $this->asistenciaService
                    ->obtenerTotalAsistencias(
                        $id_inscripcion
                    );

            $presentes =
                (int)($datos['presentes'] ?? 0);

            $total =
                (int)($datos['total'] ?? 0);

            return [

                'total_sesiones' =>
                    $total,

                'sesiones_presentes' =>
                    $presentes,

                'porcentaje' =>
                    $total > 0
                        ? round(
                            ($presentes / $total) * 100,
                            2
                        )
                        : 0
            ];

        } catch (\Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_ASISTENCIA',
                [
                    'id_inscripcion' => $id_inscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'total_sesiones' => 0,
                'sesiones_presentes' => 0,
                'porcentaje' => 0
            ];
        }
    }

}