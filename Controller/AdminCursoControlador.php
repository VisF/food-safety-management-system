<?php

declare(strict_types=1);

require_once __DIR__ . '/../Servicios/CursoService.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';

class AdminCursoControlador
{
    private CursoService $cursoService;
    private InscripcionService $inscripcionService;

    public function __construct()
    {
        $this->cursoService = new CursoService();
        $this->inscripcionService = new InscripcionService();
    }

    // =========================================================
    // LISTADO
    // =========================================================

    /**
     * Muestra el listado administrativo de cursos.
     */
    public function mostrarListado(): void
    {
        try {

            $cursos = $this->cursoService->listar();

            require_once __DIR__ . '/../Views/admin_cursos.php';

            $vista = new CursosAdminVista();

            $vista->mostrar([
                'page_title' => 'Gestión de Cursos',
                'cursos' => $cursos
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo 'Error al cargar los cursos.';
        }
    }
    // =========================================================
    // INSCRIPTOS
    // =========================================================

    /**
     * Muestra los usuarios inscriptos en un curso.
     */
    public function mostrarInscriptos(int $id): void
    {
        try {
            $curso = $this->cursoService->obtenerPorId($id);

            if ($curso === null) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_no_encontrado'
                );
                exit;
            }

            $inscriptos = $this->inscripcionService
                ->obtenerPorCurso($id);

            require_once __DIR__ . '/../Views/admin_curso_inscriptos.php';

            $vista = new CursoInscriptosAdminVista();

            $vista->mostrar([
                'page_title' => 'Inscriptos - ' . $curso['nombre'],
                'curso' => $curso,
                'inscriptos' => $inscriptos
            ]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al cargar los inscriptos del curso.';
        }
    }
    /**
     * Aprueba una inscripción a un curso.
     */
    public function aprobarInscripcion(int $cursoId, int $inscripcionId): void
    {
        try {

            $curso = $this->cursoService->obtenerPorId($cursoId);

            if ($curso === null) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_no_encontrado'
                );
                exit;
            }

            $inscripcion = $this->inscripcionService
                ->obtenerInscripcion($inscripcionId);

            if ($inscripcion === null) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos/' .
                    $cursoId .
                    '/inscriptos?toast=inscripcion_no_encontrada'
                );
                exit;
            }

            if ((int)($inscripcion['curso_id'] ?? 0) !== $cursoId) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos/' .
                    $cursoId .
                    '/inscriptos?toast=inscripcion_no_pertenece_curso'
                );
                exit;
            }

            $resultado = $this->inscripcionService
                ->aprobarInscripcionCurso($inscripcionId);

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos/' .
                $cursoId .
                '/inscriptos?toast=' .
                ($resultado
                    ? 'inscripcion_aprobada'
                    : 'error_aprobar_inscripcion')
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos/' .
                $cursoId .
                '/inscriptos?toast=error_aprobar_inscripcion'
            );

            exit;
        }
    }


    /**
     * Desaprueba una inscripción a un curso.
     */
    public function desaprobarInscripcion(int $cursoId, int $inscripcionId): void
    {
        try {

            $curso = $this->cursoService->obtenerPorId($cursoId);

            if ($curso === null) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_no_encontrado'
                );
                exit;
            }

            $inscripcion = $this->inscripcionService
                ->obtenerInscripcion($inscripcionId);

            if ($inscripcion === null) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos/' .
                    $cursoId .
                    '/inscriptos?toast=inscripcion_no_encontrada'
                );
                exit;
            }

            if ((int)($inscripcion['curso_id'] ?? 0) !== $cursoId) {
                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos/' .
                    $cursoId .
                    '/inscriptos?toast=inscripcion_no_pertenece_curso'
                );
                exit;
            }

            $resultado = $this->inscripcionService
                ->desaprobarInscripcionCurso($inscripcionId);

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos/' .
                $cursoId .
                '/inscriptos?toast=' .
                ($resultado
                    ? 'inscripcion_desaprobada'
                    : 'error_desaprobar_inscripcion')
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos/' .
                $cursoId .
                '/inscriptos?toast=error_desaprobar_inscripcion'
            );

            exit;
        }
    }
    // =========================================================
    // CREAR
    // =========================================================

    /**
     * Muestra el formulario para crear un curso.
     */
    public function mostrarFormularioCrear(): void
    {
        require_once __DIR__ . '/../Views/admin_curso_form.php';

        $vista = new CursoFormVista();

        $vista->mostrar([
            'page_title' => 'Nuevo Curso',
            'modo' => 'crear',

            'curso' => [
                'nombre' => '',
                'modalidad' => 'presencial',
                'descripcion' => '',
                'fecha_inicio' => '',
                'hora_inicio' => '',
                'ubicacion' => '',
                'cupos' => '',
                'inscriptos' => 0,
            ],

            'errores' => []
        ]);
    }


    /**
     * Guarda un nuevo curso.
     */
    public function guardarNuevoCurso(): void
    {
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'modalidad' => trim($_POST['modalidad'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? ''),
            'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'cupos' => (int)($_POST['cupos'] ?? 0)
        ];

        $errores = $this->validarDatos($datos);

        if (!empty($errores)) {

            $this->mostrarFormularioConErrores(
                'Nuevo Curso',
                'crear',
                $datos,
                $errores
            );

            return;
        }

        try {

            $curso = $this->cursoService->crear($datos);

            if ($curso === null) {

                $this->mostrarFormularioConErrores(
                    'Nuevo Curso',
                    'crear',
                    $datos,
                    [
                        'Ya existe un curso con ese nombre.'
                    ]
                );

                return;
            }

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=curso_creado'
            );

            exit;

        } catch (Throwable $e) {

            $this->mostrarFormularioConErrores(
                'Nuevo Curso',
                'crear',
                $datos,
                [
                    'No fue posible crear el curso.'
                ]
            );
        }
    }


    // =========================================================
    // EDITAR
    // =========================================================

    /**
     * Muestra el formulario para editar un curso.
     */
    public function mostrarFormularioEditar(int $id): void
    {
        $curso = $this->cursoService->obtenerPorId($id);

        if ($curso === null) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=curso_no_encontrado'
            );

            exit;
        }

        require_once __DIR__ . '/../Views/admin_curso_form.php';

        $vista = new CursoFormVista();

        $curso['inscriptos'] =
            $this->cursoService
                ->contarInscripciones((int)$curso['id']);

        $vista->mostrar([
            'page_title' => 'Editar Curso',
            'modo' => 'editar',
            'curso' => $curso,
            'errores' => []
        ]);
    }


    /**
     * Guarda la edición de un curso.
     */
    public function guardarEdicion(int $id): void
    {
        $cursoActual = $this->cursoService->obtenerPorId($id);

        if ($cursoActual === null) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=curso_no_encontrado'
            );

            exit;
        }

        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'modalidad' => trim($_POST['modalidad'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? ''),
            'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'cupos' => (int)($_POST['cupos'] ?? 0)
        ];

        $errores = $this->validarDatos($datos);

        if (!empty($errores)) {

            $datos['id'] = $id;
            $datos['activo'] = $cursoActual['activo'];

            $this->mostrarFormularioConErrores(
                'Editar Curso',
                'editar',
                $datos,
                $errores
            );

            return;
        }

        try {

            $actualizado = $this->cursoService->actualizar(
                $id,
                $datos
            );

            if (!$actualizado) {

                $this->mostrarFormularioConErrores(
                    'Editar Curso',
                    'editar',
                    array_merge(
                        $datos,
                        [
                            'id' => $id,
                            'activo' => $cursoActual['activo']
                        ]
                    ),
                    [
                        'No fue posible actualizar el curso.'
                    ]
                );

                return;
            }

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=curso_actualizado'
            );

            exit;

        } catch (Throwable $e) {

            $datos['id'] = $id;
            $datos['activo'] = $cursoActual['activo'];

            $this->mostrarFormularioConErrores(
                'Editar Curso',
                'editar',
                $datos,
                [
                    'No fue posible actualizar el curso.'
                ]
            );
        }
    }


    // =========================================================
    // ACTIVAR
    // =========================================================

    /**
     * Activa un curso.
     */
    public function activarCurso(int $id): void
    {
        try {

            $curso = $this->cursoService->obtenerPorId($id);

            if ($curso === null) {

                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_no_encontrado'
                );

                exit;
            }

            $resultado = $this->cursoService->activar($id);

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=' .
                ($resultado
                    ? 'curso_activado'
                    : 'error_activar_curso')
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=error_activar_curso'
            );

            exit;
        }
    }


    // =========================================================
    // DESACTIVAR
    // =========================================================

    /**
     * Desactiva un curso.
     *
     * El CursoService impide desactivar cursos
     * que tengan inscripciones activas.
     */
    public function desactivarCurso(int $id): void
    {
        try {

            $curso = $this->cursoService->obtenerPorId($id);

            if ($curso === null) {

                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_no_encontrado'
                );

                exit;
            }

            $resultado = $this->cursoService->desactivar($id);

            if (!empty($resultado['success'])) {

                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_desactivado'
                );

                exit;
            }

            if (
                ($resultado['codigo'] ?? null)
                === 'CURSO_CON_INSCRIPCIONES'
            ) {

                header(
                    'Location: ' .
                    rtrim(BASE_URL, '/') .
                    '/admin/cursos?toast=curso_con_inscripciones'
                );

                exit;
            }

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=error_desactivar_curso'
            );

            exit;

        } catch (Throwable $e) {

            header(
                'Location: ' .
                rtrim(BASE_URL, '/') .
                '/admin/cursos?toast=error_desactivar_curso'
            );

            exit;
        }
    }


    // =========================================================
    // VALIDACIONES
    // =========================================================

    /**
     * Valida los datos recibidos desde los formularios.
     */
    private function validarDatos(array $datos): array
    {
        $errores = [];

        if ($datos['nombre'] === '') {

            $errores[] =
                'El nombre del curso es obligatorio.';
        }

        if (
            !in_array(
                $datos['modalidad'],
                ['presencial', 'virtual'],
                true
            )
        ) {

            $errores[] =
                'La modalidad seleccionada no es válida.';
        }

        if ($datos['fecha_inicio'] === '') {

            $errores[] =
                'La fecha de inicio es obligatoria.';
        }

        if ($datos['hora_inicio'] === '') {

            $errores[] =
                'La hora de inicio es obligatoria.';
        }

        if ($datos['ubicacion'] === '') {

            $errores[] =
                'La ubicación es obligatoria.';
        }

        if ($datos['cupos'] <= 0) {

            $errores[] =
                'La cantidad de cupos debe ser mayor a cero.';
        }

        return $errores;
    }


    /**
     * Renderiza nuevamente el formulario conservando
     * los datos introducidos y mostrando errores.
     */
    private function mostrarFormularioConErrores(
        string $titulo,
        string $modo,
        array $curso,
        array $errores
    ): void
    {
        require_once __DIR__ . '/../Views/admin_curso_form.php';

        $vista = new CursoFormVista();

        $vista->mostrar([
            'page_title' => $titulo,
            'modo' => $modo,
            'curso' => $curso,
            'errores' => $errores
        ]);
    }
}