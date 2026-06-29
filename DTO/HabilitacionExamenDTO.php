<?php


class HabilitacionExamenDTO
{
    private int $id;

    private int $usuarioId;

    private ?int $cursoId;

    private DateTime $fechaHabilitacion;

    private DateTime $fechaVencimiento;

    private bool $activa;

    public function __construct(
        int $id,
        int $usuarioId,
        ?int $cursoId,
        DateTime $fechaHabilitacion,
        DateTime $fechaVencimiento,
        bool $activa
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->cursoId = $cursoId;
        $this->fechaHabilitacion = $fechaHabilitacion;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->activa = $activa;
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }
    public function getCursoId(): ?int
    {
        return $this->cursoId;
    }
    public function getFechaHabilitacion(): DateTime
    {
        return $this->fechaHabilitacion;
    }
    public function getFechaVencimiento(): DateTime
    {
        return $this->fechaVencimiento;
    }
    public function isActiva(): bool
    {
        return $this->activa;
    }


}
