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
require_once __DIR__ . '/../Servicios/ExamenService.php';
require_once __DIR__ . '/../Constant/EstadoTramite.php';

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
     * Muestra el formulario de creación de un examen.
     */
    public function mostrarFormularioCrear(): void
    {
        require_once __DIR__ . '/../Views/admin_examen_form.php';

        $vista = new ExamenFormVista();

        $vista->mostrar([

            'page_title' => 'Nuevo Examen',

            'modo' => 'crear',

            'examen' => [

                'fecha' => '',
                'hora' => '',
                'ubicacion' => '',
                'aula' => '',
                'cupos' => ''

            ],

            'errores' => []

        ]);
    }
    /**
    * Guarda un nuevo examen.
    */
    public function guardarNuevoExamen(): void
    {
        try {

            $datos = [

                'fecha' => trim($_POST['fecha'] ?? ''),

                'hora' => trim($_POST['hora'] ?? ''),

                'ubicacion' => trim($_POST['ubicacion'] ?? ''),

                'aula' => trim($_POST['aula'] ?? ''),

                'cupos' => (int)($_POST['cupos'] ?? 0)

            ];

            $id = $this->examenService
                ->crearExamen($datos);

            $this->log(
                'Examen creado correctamente',
                'INFO',
                [
                    'id_examen' => $id
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=examen_creado'
            );

            exit;

        } catch (Throwable $e) {

            $this->log(
                'Error al crear examen',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            require_once __DIR__ . '/../Views/admin_examen_form.php';

            $vista = new ExamenFormVista();

            $vista->mostrar([

                'page_title' => 'Nuevo Examen',

                'modo' => 'crear',

                'examen' => [

                    'fecha' => $_POST['fecha'] ?? '',

                    'hora' => $_POST['hora'] ?? '',

                    'ubicacion' => $_POST['ubicacion'] ?? '',

                    'aula' => $_POST['aula'] ?? '',

                    'cupos' => $_POST['cupos'] ?? ''

                ],

                'errores' => [

                    $e->getMessage()

                ]

            ]);
        }
    }

    
    // Lista examenes.
    public function listarExamenes(): array
    {
        try {

            $examenes = $this->examenService
                ->listarExamenes();

            $total = $this->examenService
                ->contarExamenes();

            return [
                'success' => true,
                'examenes' => $examenes,
                'total' => $total
            ];

       } catch (Throwable $e) {

            $this->log(
                'Error al listar exámenes',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
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

        } catch (Throwable $e) {

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

    // Obtiene detalle examen.
    public function obtenerDetalleExamen(int $id): array
    {
        try {

            $examen = $this->examenService
                ->obtenerDetalleExamen($id);

            if ($examen === null) {

                return [
                    'success' => false,
                    'examen' => []
                ];

            }

            return [
                'success' => true,
                'examen' => $examen
            ];

        } catch (Throwable $e) {

            $this->log(
                'Error al obtener detalle del examen',
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

        } catch (Throwable $e) {

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
    /**
     * Activa un examen.
     */
    public function activarExamen(
        int $id
    ): void
    {
        $this->cambiarEstadoExamen(
            $id,
            true
        );
    }

    /**
     * Desactiva un examen.
     */
    public function desactivarExamen(
        int $id
    ): void
    {
        $this->cambiarEstadoExamen(
            $id,
            false
        );
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

        } catch (Throwable $e) {

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

        } catch (Throwable $e) {

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
    public function mostrarDetalle(int $id): void
    {
        try {

            $detalle = $this->examenService->obtenerDetalle(
                $id
            );

            if ($detalle === null) {

                header(
                    'Location: /manipulacionDeAlimentos/admin/examenes?toast=examen_no_encontrado'
                );

                exit;
            }

            require_once __DIR__ . '/../Views/admin_examen_detalle.php';

            $vista = new ExamenDetalleVista();

            $vista->mostrar(
                $detalle
            );

        } catch (Throwable $e) {

            error_log(
                $e->getMessage()
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=error_cargar_examen'
            );

            exit;
        }
    }
    /**
     * Muestra el formulario para editar un examen.
     */
    public function mostrarFormularioEditar(int $id): void
    {
        $examen = $this->examenService
            ->obtenerExamen($id);

        if ($examen === null) {

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=examen_no_encontrado'
            );

            exit;
        }

        require_once __DIR__ . '/../Views/admin_examen_form.php';

        $vista = new ExamenFormVista();

        $vista->mostrar([
            'page_title' => 'Editar Examen',
            'modo'       => 'editar',
            'examen'     => $examen,
            'errores'    => []
        ]);
    }

    /**
     * Guarda la edición de un examen.
     */
    public function guardarEdicion(int $id): void
    {
        try {

            $datos = [
                'fecha'      => trim($_POST['fecha'] ?? ''),
                'hora'       => trim($_POST['hora'] ?? ''),
                'ubicacion'  => trim($_POST['ubicacion'] ?? ''),
                'aula'       => trim($_POST['aula'] ?? ''),
                'cupos'      => (int) ($_POST['cupos'] ?? 0)
            ];

            $actualizado = $this->examenService->actualizarExamen(
                $id,
                $datos
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=' .
                ($actualizado
                    ? 'examen_actualizado'
                    : 'examen_sin_cambios')
            );

            exit;

        } catch (InvalidArgumentException $e) {

            require_once __DIR__ . '/../Views/admin_examen_form.php';

            $vista = new ExamenFormVista();

            $datos['id'] = $id;

            $vista->mostrar([
                'page_title' => 'Editar Examen',
                'modo'       => 'editar',
                'examen'     => $datos,
                'errores'    => [
                    $e->getMessage()
                ]
            ]);

        } catch (Throwable $e) {

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=error_actualizar_examen'
            );

            exit;
        }
    }


    public function mostrarListado(): void
    {
        $orden = $_GET['orden'] ?? 'asc';

        if ($orden === 'desc') {

            $examenes = $this->examenService
                ->listarExamenesProgramadosDescendente();

        } else {

            $examenes = $this->examenService
                ->listarExamenesProgramadosAscendente();

        }

        require_once __DIR__ . '/../Views/admin_examenes.php';

        $vista = new ExamenAdminVista();

        $vista->mostrar([
            'page_title' => 'Gestión de Exámenes',
            'examenes' => $examenes,
            'orden' => $orden
        ]);
    }

    public function mostrarAdministracionInscripcion(int $id): void
    {
        $data = $this->examenService
            ->obtenerAdministracionInscripcion($id);

        if ($data === null) {

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=inscripcion_no_encontrada'
            );

            exit;
        }

        require_once __DIR__ .
            '/../Views/admin_inscripcion_examen.php';

        $vista = new AdminInscripcionExamenVista();

        $vista->mostrar($data);
    }
    /**
    * Guarda el resultado de una inscripción a examen.
    */
    public function guardarAdministracionInscripcion(int $id): void
    {
        try {

            $datos = [

                'estado' => trim(
                    $_POST['estado'] ?? ''
                ),

                'observaciones' => trim(
                    $_POST['observaciones'] ?? ''
                )

            ];

            $this->examenService
                ->guardarAdministracionInscripcion(
                    $id,
                    $datos
                );

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=resultado_examen_guardado'
            );

            exit;

        } catch (InvalidArgumentException $e) {

            $data = $this->examenService
                ->obtenerAdministracionInscripcion(
                    $id
                );

            if ($data !== null) {

                $data['errores'] = [
                    $e->getMessage()
                ];

                require_once __DIR__ .
                    '/../Views/admin_inscripcion_examen.php';

                $vista = new AdminInscripcionExamenVista();

                $vista->mostrar(
                    $data
                );

                return;
            }

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=error_guardar_resultado'
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=error_guardar_resultado'
            );

            exit;
        }
    }

    /**
     * Cambia el estado de un examen.
     */
    private function cambiarEstadoExamen(
        int $id,
        bool $activo
    ): void
    {
        try {

            $this->examenService->cambiarEstado(
                $id,
                $activo
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=' .
                ($activo
                    ? 'examen_activado'
                    : 'examen_desactivado')
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: /manipulacionDeAlimentos/admin/examenes?toast=error_estado_examen'
            );

            exit;

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
