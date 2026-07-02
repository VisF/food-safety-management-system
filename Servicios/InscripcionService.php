<?php

require_once __DIR__ . '/../dto/InscripcionDTO.php';
require_once __DIR__ . '/../Repository/InscripcionRepository.php';

class InscripcionService
{
    private InscripcionRepository $inscripcionRepository;
    

    public function __construct(){
        $this->inscripcionRepository = new InscripcionRepository();
    }
    public function tieneCursoActivo(int $usuarioId): bool
    {
        return $this->inscripcionRepository
            ->tieneCursoActivo($usuarioId);
    }
    public function obtenerPorId(int $id): ?InscripcionDTO 
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($id);

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $inscripcion
        );
    }
    public function obtenerPorUsuario(int $usuarioId    ): array 
    {
        $inscripciones =
            $this->inscripcionRepository
                ->obtenerPorUsuario(
                    $usuarioId
                );

        $resultado = [];

        foreach ($inscripciones as $inscripcion) {

            $resultado[] =
                InscripcionDTO::fromArray(
                    $inscripcion
                );
        }

        return $resultado;
    }
    public function obtenerUltimaPorUsuario(int $usuarioId): ?InscripcionDTO 
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerUltimaInscripcionPorUsuario(
                    $usuarioId
                );

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $inscripcion
        );
    }
    public function crear(array $datos): ?InscripcionDTO
    {
        $id = $this->inscripcionRepository->crear($datos);

        if (!$id) {
            return null;
        }

        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($id);

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray($inscripcion);
    }
    public function cancelar(int $id, string $motivo = ''): bool 
    {
        return
            $this->inscripcionRepository
                ->cancelar(
                    $id,
                    $motivo
                );
    }
    public function obtenerActivas(): array
    {
        $rows =
            $this->inscripcionRepository
                ->obtenerInscripcionesActivas();

        $resultado = [];

        foreach ($rows as $row) {

            $resultado[] =
                InscripcionDTO::fromArray(
                    $row
                );
        }

        return $resultado;
    }
}