<?php
declare(strict_types=1);

/**
 * CarnetService
 *
 * Contiene toda la lógica de negocio relacionada
 * con la gestión de carnets.
 */

require_once __DIR__ . '/../Repository/CarnetRepository.php';
require_once __DIR__ . '/../Repository/ExamenRepository.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';

require_once __DIR__ . '/../Constant/EstadoTramite.php';

class CarnetService
{   
    private const DIAS_RENOVACION = 30;
    private CarnetRepository $carnetRepository;
    private ExamenRepository $examenRepository;
    private InscripcionService $inscripcionService;
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->carnetRepository = new CarnetRepository();
        $this->examenRepository = new ExamenRepository();
        $this->inscripcionService = new InscripcionService();

    }

    /**
     * Obtiene un carnet por su ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        return $this->carnetRepository
            ->obtenerPorId($id);
    }

    /**
     * Obtiene un carnet por el ID de inscripción.
     */
    public function obtenerPorInscripcionId(
        int $idInscripcion
    ): ?array
    {
        return $this->carnetRepository
            ->obtenerPorInscripcionId(
                $idInscripcion
            );
    }

    /**
     * Obtiene un carnet por número.
     */
    public function obtenerPorNumero(
        string $numeroCarnet
    ): ?array
    {
        return $this->carnetRepository
            ->obtenerPorNumero(
                $numeroCarnet
            );
    }

    /**
     * Obtiene un carnet por DNI.
     */
    public function obtenerPorDni(
        string $dni
    ): ?array
    {
        return $this->carnetRepository
            ->obtenerPorDni($dni);
    }

    /**
     * Lista todos los carnets activos.
     */
    public function listarActivos(): array
    {
        return $this->carnetRepository
            ->listarActivos();
    }

    /**
     * Obtiene todos los carnets vencidos.
     */
    public function obtenerCarnetsVencidos(): array
    {
        return $this->carnetRepository
            ->obtenerCarnetsVencidos();
    }
        /**
     * Verifica si un carnet está vigente.
     *
     * @param int $idCarnet
     * @return bool
     */
    public function verificarVigencia(int $idCarnet): bool
    {
        $carnet = $this->carnetRepository
            ->obtenerPorId($idCarnet);

        if ($carnet === null) {
            return false;
        }

        if ((int)$carnet['activo'] !== 1) {
            return false;
        }

        return strtotime($carnet['fecha_vencimiento']) > time();
    }

    /**
     * Anula un carnet.
     *
     * @param int $idCarnet
     * @return array
     */
    public function anularCarnet(int $idCarnet): array
    {
        $carnet = $this->carnetRepository
            ->obtenerPorId($idCarnet);

        if ($carnet === null) {
            return [
                'success' => false,
                'mensaje' => 'El carnet no existe.'
            ];
        }

        if ((int)$carnet['activo'] === 0) {
            return [
                'success' => false,
                'mensaje' => 'El carnet ya se encuentra anulado.'
            ];
        }

        $resultado = $this->carnetRepository
            ->anular($idCarnet);

        return [
            'success' => $resultado,
            'mensaje' => $resultado
                ? 'Carnet anulado correctamente.'
                : 'No fue posible anular el carnet.'
        ];
    }
        /**
     * Renueva un carnet.
     *
     * @param int $idCarnet
     * @param string $fechaVencimiento
     * @return array
     */
    public function renovarCarnet(
        int $idCarnet,
        string $fechaVencimiento
    ): array
    {
        $carnet = $this->carnetRepository
            ->obtenerPorId($idCarnet);

        if ($carnet === null) {
            return [
                'success' => false,
                'mensaje' => 'El carnet no existe.'
            ];
        }

        $resultado = $this->carnetRepository
            ->renovar(
                $idCarnet,
                $fechaVencimiento
            );

        return [
            'success' => $resultado,
            'mensaje' => $resultado
                ? 'Carnet renovado correctamente.'
                : 'No fue posible renovar el carnet.'
        ];
    }

    /**
     * Emite un nuevo carnet.
     */
    public function emitirCarnet(int $idInscripcion): array
    {
        $existente = $this->carnetRepository
            ->obtenerPorInscripcionId(
                $idInscripcion
            );

        if ($existente !== null) {

            return [
                'success' => false,
                'mensaje' => 'La inscripción ya posee un carnet emitido.'
            ];
        }

        $fechaEmision = new DateTime();

        $fechaVencimiento = (clone $fechaEmision)
            ->modify('+3 years');

        $datos = [

            'id_inscripcion' => $idInscripcion,

            'numero_carnet' => $this->generarNumeroCarnet(),

            'fecha_emision' => $fechaEmision->format('Y-m-d'),

            'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d')

        ];

        $carnet = $this->carnetRepository
            ->crear($datos);

        if ($carnet === null) {

            return [
                'success' => false,
                'mensaje' => 'No fue posible emitir el carnet.'
            ];
        }

        $this->inscripcionService
            ->actualizarEstadoTramite(
                $idInscripcion,
                EstadoTramite::CARNET_EMITIDO
            );

        return [

            'success' => true,

            'mensaje' => 'Carnet emitido correctamente.',

            'carnet' => $carnet

        ];
    }
        /**
     * Genera un número de carnet único.
     *
     * Formato:
     * CAR-YYYY-XXXXXXXX
     */
    private function generarNumeroCarnet(): string
    {
        do {

            $numero = sprintf(
                'CAR-%s-%08d',
                date('Y'),
                random_int(1, 99999999)
            );

        } while (
            $this->carnetRepository
                ->obtenerPorNumero($numero) !== null
        );

        return $numero;
    }
    /**
     * Obtiene el último carnet de un usuario.
     */
    public function obtenerPorUsuarioId(
        int $usuarioId
    ): ?array
    {
        return $this->carnetRepository
            ->obtenerPorUsuarioId(
                $usuarioId
            );
    }
    /**
     * Obtiene el estado del carnet de un usuario.
     */
    public function obtenerEstadoPorDni(
        string $dni
    ): ?array
    {
        $carnet = $this->obtenerPorDni($dni);

        if ($carnet === null) {
            return null;
        }

        $vigente =
            strtotime($carnet['fecha_vencimiento']) > time();

        return [
            'id' => (int)$carnet['id'],
            'numero_carnet' => $carnet['numero_carnet'],
            'estado' => $vigente ? 'vigente' : 'vencido',
            'fecha_emision' => $carnet['fecha_emision'],
            'fecha_vencimiento' => $carnet['fecha_vencimiento'],
            'vigente' => $vigente,
            'dias_para_vencer' => $vigente
                ? floor(
                    (
                        strtotime($carnet['fecha_vencimiento'])
                        - time()
                    ) / 86400
                )
                : null
        ];
    }
    /**
    * Verifica la vigencia de un carnet mediante DNI.
    */
    public function verificarVigenciaPorDni(
        string $dni
    ): array
    {
        $carnet = $this->obtenerEstadoPorDni($dni);

        if ($carnet === null) {
            return [
                'success' => true,
                'vigente' => false,
                'mensaje' => 'Carnet no encontrado',
                'carnet' => null
            ];
        }

        return [
            'success' => true,
            'vigente' => $carnet['vigente'],
            'mensaje' => $carnet['vigente']
                ? 'Carnet vigente'
                : 'Carnet vencido',
            'carnet' => $carnet
        ];
    }
    /**
     * Obtiene el estado de renovación del carnet de un usuario.
     */
    public function obtenerEstadoRenovacionUsuario(int $usuarioId): ?array
    {
        $carnet = $this->obtenerUltimoCarnetUsuario(
            $usuarioId
        );

        if ($carnet === null) {

            return null;
        }

        $fechaVencimiento = new DateTime(
            $carnet['fecha_vencimiento']
        );

        $hoy = new DateTime();

        $diasRestantes = (int)$hoy->diff(
            $fechaVencimiento
        )->format('%r%a');

        if ($diasRestantes < 0) {

            $estado = 'vencido';

        } elseif ($diasRestantes <= self::DIAS_RENOVACION) {

            $estado = 'proximo_vencimiento';

        } else {

            $estado = 'vigente';
        }

        return [

            'numero_carnet' =>
                $carnet['numero_carnet'],

            'fecha_emision' =>
                $carnet['fecha_emision'],

            'fecha_vencimiento' =>
                $carnet['fecha_vencimiento'],

            'dias_restantes' =>
                $diasRestantes,

            'estado' =>
                $estado,

            'puede_renovar' =>
                $estado !== 'vigente',

            'mensaje' => (
                match ($estado) {

                    'vigente' =>
                        'Su carnet se encuentra vigente. Podrá renovarlo '
                        . self::DIAS_RENOVACION
                        . ' días antes del vencimiento.',

                    'proximo_vencimiento' =>
                        'Su carnet vence en '
                        . $diasRestantes
                        . ' días. Ya puede iniciar la renovación.',

                    'vencido' =>
                        'Su carnet se encuentra vencido. Debe inscribirse nuevamente al examen.'
                }
            )
        ];
    }
    /**
     * Obtiene la ruta del PDF del carnet.
     */
    public function obtenerPdfPorDni(
        string $dni
    ): ?string
    {
        return $this->carnetRepository
            ->obtenerPdfPorDni($dni);
    }
    /**
     * Obtener el último carnet de un usuario.
     *
     * @param int $usuarioId
     * @return array|null
     */
    public function obtenerUltimoCarnetUsuario(
        int $usuarioId
    ): ?array
    {
        return
            $this->carnetRepository
                ->obtenerUltimoCarnetUsuario(
                    $usuarioId
                );
    }
}