<?php

class PlazoRecursanteService
{
    private PlazoRecursanteRepository $repository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->repository = new PlazoRecursanteRepository();
    }

    // Crea plazo recursante.
    public function crearPlazoRecursante(
        int $inscripcionId,
        string $fechaExamenDesaprobado
    ): array {

        $fechaLimite = date(
            'Y-m-d',
            strtotime($fechaExamenDesaprobado . ' +3 months')
        );

        return $this->repository->crear(
            $inscripcionId,
            $fechaExamenDesaprobado,
            $fechaLimite
        );
    }

    // Ejecuta verificar elegibilidad.
    public function verificarElegibilidad(int $usuarioId): bool
    {
        return $this->repository->verificarElegibilidad($usuarioId);
    }

    // Lista recursantes vigentes.
    public function listarRecursantesVigentes(): array
    {
        return $this->repository->listarVigentes();
    }
}  
