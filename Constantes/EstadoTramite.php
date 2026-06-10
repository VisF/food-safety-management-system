<?php
declare(strict_types=1);

final class EstadoTramite
{
    public const PENDIENTE = 1;
    public const APROBADO = 2;
    public const RECHAZADO = 3;
    public const CURSANDO = 4;
    public const HABILITADO_EXAMEN = 5;
    public const INSCRIPTO_EXAMEN = 6;
    public const EXAMEN_APROBADO = 7;
    public const CARNET_EMITIDO = 8;

    private function __construct()
    {
    }
}