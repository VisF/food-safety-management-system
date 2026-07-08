<?php
declare(strict_types=1);

/**
 * Métodos:
 * - crear()
 * - obtenerPorInscripcion()
 * - obtenerPorDNI()
 * - verificarVigencia()
 * - actualizar()
 * - obtenerCarnetsVencidos()
 * - renovar()
 */

require_once __DIR__ . '/../Repository/CarnetRepository.php';

class CarnetService
{
    private CarnetRepository $carnetRepository;

    public function __construct()
    {
        $this->carnetRepository =
            new CarnetRepository();
    }

    /**
     * Crear un carnet.
     */
    public function crear(array $datos): ?array
    {
        return
            $this->carnetRepository
                ->crear($datos);
    }

    /**
     * Obtener carnet por inscripción.
     */
    public function obtenerPorInscripcion(
        int $idInscripcion
    ): ?array
    {
        return
            $this->carnetRepository
                ->obtenerPorInscripcion(
                    $idInscripcion
                );
    }

    /**
     * Obtener carnet por DNI.
     */
    public function obtenerPorDNI(
        string $dni
    ): ?array
    {
        return
            $this->carnetRepository
                ->obtenerPorDNI(
                    $dni
                );
    }

    /**
     * Verificar vigencia.
     */
    public function verificarVigencia(
        int $id
    ): bool
    {
        return
            $this->carnetRepository
                ->verificarVigencia(
                    $id
                );
    }
        /**
     * Actualizar un carnet.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool
    {
        return
            $this->carnetRepository
                ->actualizar(
                    $id,
                    $datos
                );
    }

    /**
     * Obtener carnets vencidos.
     */
    public function obtenerCarnetsVencidos(): array
    {
        return
            $this->carnetRepository
                ->obtenerCarnetsVencidos();
    }

    /**
     * Renovar un carnet.
     */
    public function renovar(
        int $id,
        string $fechaVencimiento
    ): bool
    {
        return
            $this->carnetRepository
                ->renovar(
                    $id,
                    $fechaVencimiento
                );
    }
}