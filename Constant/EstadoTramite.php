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
    public static function desdeNombre(string $nombre): ?int
    {
        return [
            'pendiente'               => self::PENDIENTE,
            'documentacion_pendiente' => self::DOCUMENTACION_PENDIENTE,
            'documentacion_aprobada'  => self::DOCUMENTACION_APROBADA,
            'inscripto_examen'        => self::INSCRIPTO_EXAMEN,
            'aprobado'                => self::APROBADO,
            'rechazado'               => self::RECHAZADO,
            'cancelado'               => self::CANCELADO,
            'carnet_emitido'          => self::CARNET_EMITIDO,
        ][strtolower($nombre)] ?? null;
    }
}