<?php
declare(strict_types=1);

final class EstadoTramite
{
    public const PENDIENTE = 1;

    public const DOCUMENTACION_PENDIENTE = 2;

    public const DOCUMENTACION_APROBADA = 3;

    public const INSCRIPTO_EXAMEN = 4;

    public const APROBADO = 5;

    public const RECHAZADO = 6;

    public const CANCELADO = 7;

    public const CARNET_EMITIDO = 8;

    private function __construct()
    {
    }
}