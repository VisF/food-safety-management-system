<?php
declare(strict_types=1);


/**
 * AdminCursoControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * AdminCursoControlador - Gestión administrativa del sistema
 * 
 * Dependencias esperadas:
 * - CursoRepository: Repositorio para operaciones de cursos
 * 
 * Funciones principales:
 * - crearCurso(array $datos): Crear un nuevo curso
 * - listarCursos(): Listar todos los cursos
 * - obtenerCurso(int $id): Obtener detalles de un curso por ID
 * - activarCurso(int $id): Activar un curso
 * - desactivarCurso(int $id): Desactivar un curso
 * - obtenerCursosActivos(): Obtener todos los cursos activos
 * - obtenerCursosPorModalidad(string $modalidad): Obtener cursos por modalidad
 * - actualizarCurso(int $id, array $datos): Actualizar datos de un curso
 * 
 * Vistas esperadas:
 * - vistas/panel_admin.php
 * - vistas/crear_curso.php
 * - vistas/crear_examen.php
 * - vistas/validacion_documentos.php
 * - vistas/crear_respuesta_admin.php
 */

require_once __DIR__ . '/../Repository/CursoRepository.php';

class AdminCursoControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_curso_controller.log';

    private CursoRepository $cursoRepository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        

        $this->cursoRepository = new CursoRepository();
    }

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

            if ($this->cursoRepository->existeNombre($datos['nombre'])) {

                return [
                    'success' => false,
                    'message' => 'Ya existe un curso con ese nombre'
                ];
            }

            $id = $this->cursoRepository->crear($datos);

            return [
                'success' => true,
                'message' => 'Curso creado correctamente',
                'id_curso' => $id
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al crear curso',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    // Lista cursos.
    public function listarCursos(): array
    {
        try {

            $cursos = $this->cursoRepository->listar();

            return [
                'success' => true,
                'cursos' => $cursos,
                'total' => count($cursos)
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al listar cursos',
                'ERROR',
                ['error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'cursos' => [],
                'total' => 0
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
    public function obtenerCurso(int $id): array
    {
        try {

            $curso = $this->cursoRepository->obtenerPorId($id);

            if (!$curso) {

                return [
                    'success' => false,
                    'curso' => []
                ];

            }

            return [
                'success' => true,
                'curso' => $curso
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al obtener curso',
                'ERROR',
                [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'curso' => []
            ];
        }
    }
    // Ejecuta activar curso.
    public function activarCurso(int $id): array
    {
        try {

            $this->cursoRepository->activar($id);

            return [
                'success' => true,
                'message' => 'Curso activado'
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    // Ejecuta desactivar curso.
    public function desactivarCurso(int $id): array
    {
        try {

            $resultado = $this->cursoService->desactivar($id);

            if ($resultado['success']) {
                return [
                    'success' => true,
                    'message' => 'Curso desactivado'
                ];
            }

            switch ($resultado['codigo']) {

                case 'CURSO_CON_INSCRIPCIONES':
                    return [
                        'success' => false,
                        'message' => 'El curso posee inscripciones activas'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'No fue posible desactivar el curso.'
                    ];
            }

        } catch (Throwable $e) {

            $this->log(
                'Error al desactivar curso',
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
    // Obtiene cursos activos.
    public function obtenerCursosActivos(): array
    {
        try {

            return [
                'success' => true,
                'cursos' => $this->cursoRepository->obtenerActivos()
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'cursos' => []
            ];
        }
    }
    // Obtiene cursos por modalidad.
    public function obtenerCursosPorModalidad(string $modalidad): array
    {
        try {

            return [
                'success' => true,
                'cursos' => $this->cursoRepository
                    ->obtenerPorModalidad($modalidad)
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'cursos' => []
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

            $curso = $this->cursoRepository->obtenerPorId($id);

            if (!$curso) {

                return [
                    'success' => false,
                    'message' => 'Curso inexistente'
                ];

            }

            $this->cursoRepository->actualizar($id, $datos);

            return [
                'success' => true,
                'message' => 'Curso actualizado correctamente'
            ];

        } catch (Exception $e) {

            $this->log(
                'Error al actualizar curso',
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

   



}
