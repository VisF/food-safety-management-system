<?php

class AlertaService
{
    private AlertaRepository $alertaRepository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->alertaRepository = new AlertaRepository();
    }

    // Ejecuta programar alerta.
    public function programarAlerta(
        int $usuarioId,
        string $tipo,
        array $payload,
        string $fechaProgramada
    ): int {

        return $this->alertaRepository->programarAlerta(
            $usuarioId,
            $tipo,
            $payload,
            $fechaProgramada
        );
    }

    // Obtiene pendientes.
    public function obtenerPendientes(): array
    {
        return $this->alertaRepository->obtenerPendientes();
    }

    // Marca enviada.
    public function marcarEnviada(int $id): bool
    {
        return $this->alertaRepository->marcarEnviada($id);
    }
}
