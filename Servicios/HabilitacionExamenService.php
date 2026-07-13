<?php
declare(strict_types=1);

require_once __DIR__ . '/../Repository/HabilitacionExamenRepository.php';

class HabilitacionExamenService
{
    private HabilitacionExamenRepository $habilitacionRepository;

    public function __construct()
    {
        $this->habilitacionRepository =
            new HabilitacionExamenRepository();
    }

    /**
     * Crear una habilitación.
     */
    public function crear(
        array $datos
    ): bool {

        return
            $this->habilitacionRepository
                ->crear($datos);
    }

    /**
     * Obtener la habilitación activa
     * de un usuario.
     */
    public function obtenerActivaPorUsuario(
        int $usuarioId
    ): ?HabilitacionExamenDTO {

        return
            $this->habilitacionRepository
                ->obtenerActivaPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Verificar si un usuario posee
     * una habilitación vigente.
     */
    public function tieneHabilitacionVigente(
        int $usuarioId
    ): bool {

        return
            $this->habilitacionRepository
                ->tieneHabilitacionVigente(
                    $usuarioId
                );
    }

    /**
     * Desactivar habilitaciones vencidas.
     */
    public function vencer(): bool
    {
        return
            $this->habilitacionRepository
                ->vencer();
    }

    /**
     * Desactivar una habilitación.
     */
    public function desactivar(
        int $id
    ): bool {

        return
            $this->habilitacionRepository
                ->desactivar($id);
    }
}