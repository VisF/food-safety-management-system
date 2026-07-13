<?php
declare(strict_types=1);


/**
 * CursoService - Servicio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../Repository/CursoRepository.php';

class CursoService
{
    private CursoRepository $cursoRepository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->cursoRepository =
            new CursoRepository();
    }

    // ==========================
    // Consultas
    // ==========================

    /**
     * Listar todos los cursos.
     */
    public function listar(): array
    {
        return
            $this->cursoRepository
                ->listar();
    }

    /**
     * Obtener curso por ID.
     */
    public function obtenerPorId(
        int $id
    ): ?array {

        return
            $this->cursoRepository
                ->obtenerPorId($id);
    }

    /**
     * Obtener cursos activos.
     */
    public function obtenerActivos(): array
    {
        return
            $this->cursoRepository
                ->obtenerActivos();
    }

    /**
     * Obtener cursos por modalidad.
     */
    public function obtenerPorModalidad(
        string $modalidad
    ): array {

        return
            $this->cursoRepository
                ->obtenerPorModalidad(
                    $modalidad
                );
    }

    // ==========================
    // Altas
    // ==========================

    /**
     * Crear curso.
     */
    public function crear(
        array $datos
    ): ?array {

        if (
            $this->cursoRepository
                ->existeNombre(
                    $datos['nombre']
                )
        ) {
            return null;
        }

        $id =
            $this->cursoRepository
                ->crear($datos);

        return
            $this->cursoRepository
                ->obtenerPorId($id);
    }

    // ==========================
    // Actualización
    // ==========================

    /**
     * Actualizar curso.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {

        return
            $this->cursoRepository
                ->actualizar(
                    $id,
                    $datos
                );
    }

    /**
     * Activar curso.
     */
    public function activar(
        int $id
    ): bool {

        return
            $this->cursoRepository
                ->activar($id);
    }

    /**
     * Desactivar curso.
     */
    public function desactivar(
        int $id
    ): bool {

        if (
            $this->cursoRepository
                ->contarInscripciones($id) > 0
        ) {
            return false;
        }

        return
            $this->cursoRepository
                ->desactivar($id);
    }

    // ==========================
    // Validaciones
    // ==========================

    /**
     * Verificar si existe un curso.
     */
    public function existeNombre(
        string $nombre
    ): bool {

        return
            $this->cursoRepository
                ->existeNombre(
                    $nombre
                );
    }

    /**
     * Contar inscripciones.
     */
    public function contarInscripciones(
        int $cursoId
    ): int {

        return
            $this->cursoRepository
                ->contarInscripciones(
                    $cursoId
                );
    }
}
