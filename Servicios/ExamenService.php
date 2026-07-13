<?php
/**
 * Metodos:
 * listarExamenes
 * contarExamenes
 * obtenerExamen
 * obtenerProximos
 * crearExamen
 * actualizarExamen
 * activarExamen
 * desactivarExamen
 * actualizarCupos
 * descontarCupo
 * obtenerDetalleExamen
 * obtenerDisponibles
 * obtenerProximosPorUsuario
 * obtenerAprobados
 * 
 * 
 */
declare(strict_types=1);


/**
 * ExamenService - Servicio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../Repository/ExamenRepository.php';

class ExamenService
{
    private ExamenRepository $examenRepository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->examenRepository = new ExamenRepository();
    }

    /**
     * Obtener todos los exámenes.
     */
    public function listarExamenes(): array
    {
        return $this->examenRepository
            ->listarExamenes();
    }

    /**
     * Contar exámenes.
     */
    public function contarExamenes(): int
    {
        return $this->examenRepository
            ->contarExamenes();
    }

    /**
     * Obtener examen por ID.
     */
    public function obtenerExamen(int $id): ?array
    {
        return $this->examenRepository
            ->obtenerExamen($id);
    }

    /**
     * Obtener próximos exámenes.
     */
    public function obtenerProximos(
        int $cantidad = 5
    ): array
    {
        return $this->examenRepository
            ->obtenerProximos($cantidad);
    }
     /**
     * Crear examen.
     */
    public function crearExamen(array $datos): int
    {
        return $this->examenRepository
            ->crearExamen($datos);
    }

    /**
     * Actualizar examen.
     */
    public function actualizarExamen(
        int $id,
        array $datos
    ): bool
    {
        return $this->examenRepository
            ->actualizarExamen(
                $id,
                $datos
            );
    }

    /**
     * Activar examen.
     */
    public function activarExamen(int $id): bool
    {
        return $this->examenRepository
            ->activarExamen($id);
    }

    /**
     * Desactivar examen.
     */
    public function desactivarExamen(int $id): bool
    {
        return $this->examenRepository
            ->desactivarExamen($id);
    }

    /**
     * Actualizar cupos.
     */
    public function actualizarCupos(
        int $id,
        int $cupos
    ): bool
    {
        return $this->examenRepository
            ->actualizarCupos(
                $id,
                $cupos
            );
    }

    /**
     * Descontar un cupo disponible.
     */
    public function descontarCupo(int $idExamen): bool
    {
        return $this->examenRepository
            ->descontarCupo($idExamen);
    }

    /**
     * Obtener detalle completo de un examen.
     */
    public function obtenerDetalleExamen(int $id): ?array
    {
        return $this->examenRepository
            ->obtenerDetalleExamen($id);
    }
    // Obtiene disponibles.
    public function obtenerDisponibles(): array
    {
        return
            $this->examenRepository
                ->obtenerDisponibles();
    }
    // Obtiene proximos por usuario.
    public function obtenerProximosPorUsuario(int $usuarioId): array
    {
        return
            $this->examenRepository
                ->obtenerProximosPorUsuario(
                    $usuarioId
                );
    }
    // Obtiene aprobados.
    public function obtenerAprobados(int $idExamen): array
    {
        return
            $this->examenRepository
                ->obtenerAprobados(
                    $idExamen
                );
    }
}
