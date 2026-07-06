<?php
declare(strict_types=1);

/**
 * metodos:
 * crear()
 * obtenerPorId()
 * obtenerPorInscripcion()
 * obtenerPorExamen()
 * obtenerPorUsuario()
 * listarResultados()
 * registrarResultado()
 * actualizar()
 * eliminar()
 * contarAprobados()
 * contarReprobados()
 * obtenerPromedioExamen()
 */


require_once __DIR__ . '/../Repository/ResultadoExamenRepository.php';

class ResultadoExamenService
{
    private ResultadoExamenRepository $resultadoRepository;

    public function __construct()
    {
        $this->resultadoRepository =
            new ResultadoExamenRepository();
    }

    /**
     * Crear resultado.
     */
    public function crear(array $datos): ?array
    {
        $id =
            $this->resultadoRepository
                ->crear($datos);

        if (!$id) {
            return null;
        }

        return
            $this->resultadoRepository
                ->obtenerPorId($id);
    }

    /**
     * Obtener resultado por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        return
            $this->resultadoRepository
                ->obtenerPorId($id);
    }

    /**
     * Obtener resultado de una inscripción.
     */
    public function obtenerPorInscripcion(
        int $inscripcionId
    ): ?array {

        return
            $this->resultadoRepository
                ->obtenerPorInscripcion(
                    $inscripcionId
                );
    }

    /**
     * Obtener resultados de un examen.
     */
    public function obtenerPorExamen(
        int $examenId
    ): array {

        return
            $this->resultadoRepository
                ->obtenerPorExamen(
                    $examenId
                );
    }

    /**
     * Obtener resultados de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array {

        return
            $this->resultadoRepository
                ->obtenerPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Listar resultados.
     */
    public function listarResultados(): array
    {
        return
            $this->resultadoRepository
                ->listarResultados();
    }

    /**
     * Registrar resultado.
     */
    public function registrarResultado(array $datos): ?array
    {
        $existente =
            $this->resultadoRepository
                ->obtenerPorInscripcion(
                    $datos['inscripcion_id']
                );

        if ($existente) {
            return null;
        }

        return
            $this->crear($datos);
    }

    /**
     * Actualizar resultado.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {

        return
            $this->resultadoRepository
                ->actualizar(
                    $id,
                    $datos
                );
    }

    /**
     * Eliminar resultado.
     */
    public function eliminar(int $id): bool
    {
        return
            $this->resultadoRepository
                ->eliminar($id);
    }

    /**
     * Cantidad de aprobados.
     */
    public function contarAprobados(
        int $examenId
    ): int {

        return
            $this->resultadoRepository
                ->contarAprobados(
                    $examenId
                );
    }

    /**
     * Cantidad de reprobados.
     */
    public function contarReprobados(
        int $examenId
    ): int {

        return
            $this->resultadoRepository
                ->contarReprobados(
                    $examenId
                );
    }

    /**
     * Promedio de notas.
     */
    public function obtenerPromedioExamen(
        int $examenId
    ): float {

        return
            $this->resultadoRepository
                ->obtenerPromedioExamen(
                    $examenId
                );
    }
}