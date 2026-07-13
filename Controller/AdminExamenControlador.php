<?php
declare(strict_types=1);


/**
 * AdminExamenControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * Responsabilidades:
 * - Listar exámenes.
 * - Obtener detalle de un examen.
 * - Crear examen.
 * - Actualizar examen.
 * - Activar examen.
 * - Desactivar examen.
 * - Actualizar cupos.
 * - Obtener próximos exámenes.
 */
require_once __DIR__ . '/../Service/ExamenService.php';

class AdminExamenControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_examen_controller.log';
    
    private ExamenService $examenService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->examenService = new ExamenService();
    }



    /**
     * Crear una nueva instancia de examen
        * @param array $datos Datos del examen a crear
        * @return array Resultado de la operación
    */
    public function crearExamen(array $datos): array
    {
        try {

            $id = $this->examenService
                ->crearExamen($datos);

            return [

                'success' => true,

                'message' => 'Examen creado correctamente',

                'id_examen' => $id

            ];

        } catch (Exception $e) {

            $this->log(
                'Error al crear examen',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [

                'success' => false,

                'message' => $e->getMessage(),

                'id_examen' => null

            ];
        }
    }

    
    // Lista examenes.
    public function listarExamenes(): array
    {
        try {

            $examenes = $this->examenService
                ->listarExamenes();

            return [
                'success' => true,
                'examenes' => $examenes,
                'total' => count($examenes)
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al listar exámenes',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'examenes' => [],
                'total' => 0
            ];
        }
    } 
    // Obtiene examen.
    public function obtenerExamen(int $id): array
{
    try {

        $examen = $this->examenService
            ->obtenerExamen($id);

        if (!$examen) {

            return [
                'success' => false,
                'examen' => []
            ];

        }

        return [
            'success' => true,
            'examen' => $examen
        ];

    } catch (Exception $e) {

        $this->log(
            'Error al obtener examen',
            'ERROR',
            [
                'id' => $id,
                'error' => $e->getMessage()
            ]
        );

        return [
            'success' => false,
            'examen' => []
        ];
    }
}

    // Actualiza examen.
    public function actualizarExamen(int $id, array $datos): array
    {
        try {

            $ok = $this->examenService
                ->actualizarExamen($id, $datos);

            return [

                'success' => $ok,

                'message' => $ok
                    ? 'Examen actualizado correctamente'
                    : 'No se pudo actualizar'

            ];

        } catch (Exception $e) {

            $this->log(
                'Error al actualizar examen',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [

                'success' => false,

                'message' => $e->getMessage()

            ];
        }
    }
    // Ejecuta activar examen.
    public function activarExamen(int $id): array
    {
        try {

            $this->examenService
                ->activarExamen($id);

            return [

                'success' => true,

                'message' => 'Examen activado correctamente'

            ];

        } catch (Exception $e) {

            $this->log(
                'Error al activar examen',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
}
    }
    // Ejecuta desactivar examen.
    public function desactivarExamen(int $id): array
    {
        try {

            $this->examenService
                ->desactivarExamen($id);

            return [

                'success' => true,

                'message' => 'Examen desactivado correctamente'

            ];

        } catch (Exception $e) {
    
            $this->log(
                'Error al desactivar examen',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [

                'success' => false,

                'message' => $e->getMessage()

            ];
        }
    }
    // Actualiza cupos.
    public function actualizarCupos(int $id, int $cupos): array
    {
        try {

            $this->examenService
                ->actualizarCupos($id, $cupos);

            return [

                'success' => true,

                'message' => 'Cupos actualizados'

            ];

        } catch (Exception $e) {

            $this->log(
                'Error al actualizar cupos',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [

                'success' => false,

                'message' => $e->getMessage()

            ];
        }
    }
    // Obtiene proximos.
    public function obtenerProximos(int $cantidad = 5): array
    {
        try {

            return [

                'success' => true,

                'examenes' => $this->examenService
                    ->obtenerProximos($cantidad)

            ];

        } catch (Exception $e) {

            $this->log(
                'Error al obtener próximos exámenes',
                'ERROR',
                [
                    'cantidad' => $cantidad,
                    'error' => $e->getMessage()
                ]
            );

            return [

                'success' => false,

                'examenes' => []

            ];
        }
    }
    // Ejecuta log.
    private function log(string $mensaje, string $nivel = 'INFO', array $contexto = []): void
    {
        $linea = sprintf(
            "[%s] [%s] %s %s%s",
            date('Y-m-d H:i:s'),
            $nivel,
            $mensaje,
            !empty($contexto)
                ? json_encode($contexto, JSON_UNESCAPED_UNICODE)
                : '',
            PHP_EOL
        );

        file_put_contents(
            self::LOG_FILE,
            $linea,
            FILE_APPEND
        );
    }
 
}
