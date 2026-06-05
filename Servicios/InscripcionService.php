<?php

require_once __DIR__ . '/../dto/InscripcionDTO.php';
require_once __DIR__ . '/../modelo/InscripcionModelo.php';

class InscripcionService
{
    public function __construct(
        private InscripcionModelo $inscripcionModelo
    ) {
    }

    public function obtenerPorId(int $id): ?InscripcionDTO 
    {
        $inscripcion =
            $this->inscripcionModelo
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
            $this->inscripcionModelo
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
            $this->inscripcionModelo
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
        $creada =
            $this->inscripcionModelo
                ->crear($datos);

        if (!$creada) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $creada
        );
    }
    public function cancelar(int $id, string $motivo = ''): bool 
    {
        return
            $this->inscripcionModelo
                ->cancelar(
                    $id,
                    $motivo
                );
    }
    public function obtenerActivas(): array
    {
        $rows =
            $this->inscripcionModelo
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