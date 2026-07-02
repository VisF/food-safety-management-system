<?php
declare(strict_types=1);

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
require_once __DIR__ . '/../Repository/ExamenRepository.php';

class AdminExamenControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_examen_controller.log';
    
    private ExamenRepository $examenRepository;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->examenRepository = new ExamenRepository();
    }



    /**
     * Crear una nueva instancia de examen
        * @param array $datos Datos del examen a crear
        * @return array Resultado de la operación
    */
    public function crearExamen(array $datos): array
    {
        try {

            $id = $this->examenRepository
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

    
    public function listarExamenes(): array
    {
        try {

            $examenes = $this->examenRepository
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
    public function obtenerExamen(int $id): array
{
    try {

        $examen = $this->examenRepository
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

    public function actualizarExamen(int $id, array $datos): array
    {
        try {

            $ok = $this->examenRepository
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
    public function activarExamen(int $id): array
    {
        try {

            $this->examenRepository
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
    public function desactivarExamen(int $id): array
    {
        try {

            $this->examenRepository
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
    public function actualizarCupos(int $id, int $cupos): array
    {
        try {

            $this->examenRepository
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
    public function obtenerProximos(int $cantidad = 5): array
    {
        try {

            return [

                'success' => true,

                'examenes' => $this->examenRepository
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