<?php

declare(strict_types=1);

/**
 * ExamenControlador
 *
 * Gestión de exámenes.
 *
 * Responsabilidades:
 * - Crear exámenes.
 * - Obtener un examen por ID.
 * - Obtener el detalle de un examen para el ciudadano.
 * - Obtener exámenes disponibles.
 * - Obtener asistencia de una inscripción.
 */

require_once __DIR__ . '/../Servicios/ExamenService.php';
require_once __DIR__ . '/../Servicios/AsistenciaService.php';

class ExamenControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/examen_controller.log';

    private ?ExamenService $examenService = null;
    private ?AsistenciaService $asistenciaService = null;

    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->examenService =
            new ExamenService();

        $this->asistenciaService =
            new AsistenciaService();
    }

    /**
     * Guarda un nuevo examen.
     *
     * Recibe los datos del formulario y delega la creación
     * al servicio correspondiente.
     */
    public function guardar(): array
    {
        $fecha = trim(
            (string) (
                $_POST['fecha'] ?? ''
            )
        );

        $hora = trim(
            (string) (
                $_POST['hora'] ?? ''
            )
        );

        $cupos = (int) (
            $_POST['cupos'] ?? 0
        );

        $ubicacion = trim(
            (string) (
                $_POST['ubicacion'] ?? ''
            )
        );

        $aula = trim(
            (string) (
                $_POST['aula'] ?? ''
            )
        );

        $datos = [
            'fecha' => $fecha,
            'hora' => $hora,
            'cupos' => $cupos,
            'ubicacion' => $ubicacion,
            'aula' => $aula,
        ];

        if ($fecha === '') {
            return [
                'success' => false,
                'message' => 'Debe indicar una fecha.',
                'data' => $datos,
            ];
        }

        if ($hora === '') {
            return [
                'success' => false,
                'message' => 'Debe indicar un horario.',
                'data' => $datos,
            ];
        }

        if ($ubicacion === '') {
            return [
                'success' => false,
                'message' => 'Debe indicar una ubicación.',
                'data' => $datos,
            ];
        }

        if ($aula === '') {
            return [
                'success' => false,
                'message' => 'Debe indicar un aula.',
                'data' => $datos,
            ];
        }

        if ($cupos <= 0) {
            return [
                'success' => false,
                'message' =>
                    'La cantidad de cupos debe ser mayor a cero.',
                'data' => $datos,
            ];
        }

        try {
            $idExamen =
                $this->examenService
                    ->crearExamen($datos);

            return [
                'success' => true,
                'message' =>
                    'Fecha de examen creada correctamente.',
                'id' => $idExamen,
                'data' => $datos,
            ];

        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $datos,
            ];

        } catch (\Throwable $e) {
            $this->registrarLog(
                'ERROR_GUARDAR_EXAMEN',
                [
                    'error' => $e->getMessage(),
                    'datos' => $datos,
                ]
            );

            return [
                'success' => false,
                'message' =>
                    'Ocurrió un error al crear el examen.',
                'data' => $datos,
            ];
        }
    }

    /**
     * Registra un evento en el log.
     */
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void {
        $timestamp = date('Y-m-d H:i:s');

        $usuario_id =
            $_SESSION['usuario_id']
            ?? 'anonimo';

        $mensaje =
            "[$timestamp] Usuario: $usuario_id | " .
            "Evento: $evento | Datos: " .
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            ) .
            "\n";

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }

    /**
     * Obtiene un examen por ID.
     */
    public function obtenerExamen(
        int $id
    ): ?array {
        try {
            return
                $this->examenService
                    ->obtenerExamen($id);

        } catch (\Exception $e) {
            $this->registrarLog(
                'ERROR_OBTENER_EXAMEN',
                [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * Obtiene el detalle de un examen para el ciudadano.
     */
    public function obtenerDetalleCiudadano(
        int $id
    ): ?array {
        try {
            return
                $this->examenService
                    ->obtenerDetalleCiudadano($id);

        } catch (\Exception $e) {
            $this->registrarLog(
                'ERROR_OBTENER_DETALLE_CIUDADANO',
                [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * Obtiene los exámenes disponibles.
     */
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
                    'error' => $e->getMessage(),
                ]
            );

            return [];
        }
    }

    /**
     * Obtiene la asistencia de una inscripción.
     */
    public function obtenerAsistencia(
        int $idInscripcion
    ): array {
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
                    'error' => $e->getMessage(),
                ]
            );

            return [];
        }
    }
}