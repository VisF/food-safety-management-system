<?php

declare(strict_types=1);

require_once __DIR__ . '/../Repository/ConfiguracionRepository.php';

class ConfiguracionService
{
    private ConfiguracionRepository $configuracionRepository;

    public function __construct()
    {
        $this->configuracionRepository =
            new ConfiguracionRepository();
    }

    /**
     * Obtiene una configuración completa.
     */
    public function obtenerPorClave(string $clave): ?array
    {
        $clave = trim($clave);

        if ($clave === '') {
            return null;
        }

        return $this->configuracionRepository
            ->obtenerPorClave($clave);
    }

    /**
     * Obtiene el valor de una configuración.
     */
    public function obtenerValor(string $clave): ?string
    {
        $clave = trim($clave);

        if ($clave === '') {
            return null;
        }

        return $this->configuracionRepository
            ->obtenerValor($clave);
    }

    /**
     * Obtiene el plazo configurado para recursantes.
     */
    public function obtenerPlazoRecursanteDias(): int
    {
        $valor = $this->obtenerValor(
            'plazo_recursante_dias'
        );

        if ($valor === null || !ctype_digit($valor)) {
            return 90;
        }

        $dias = (int)$valor;

        return $dias > 0 ? $dias : 90;
    }

    /**
     * Actualiza el plazo para reinscripción como recursante.
     */
    public function actualizarPlazoRecursanteDias(
        int $dias
    ): bool {
        if ($dias <= 0) {
            throw new InvalidArgumentException(
                'El plazo para recursantes debe ser mayor a cero.'
            );
        }

        return $this->configuracionRepository
            ->actualizarValor(
                'plazo_recursante_dias',
                (string)$dias
            );
    }
}