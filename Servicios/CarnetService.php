<?php
declare(strict_types=1);

/**
 * CarnetService
 *
 * Contiene toda la lógica de negocio relacionada
 * con la gestión de carnets.
 */

require_once __DIR__ . '/../db/Connection.php';

require_once __DIR__ . '/../Repository/CarnetRepository.php';

require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../Servicios/DocumentoService.php';

require_once __DIR__ . '/../Constant/EstadoTramite.php';

class CarnetService
{   
    private const DIAS_RENOVACION = 30;
    private CarnetRepository $carnetRepository;
    private InscripcionService $inscripcionService;
    private DocumentoService $documentoService;
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->carnetRepository = new CarnetRepository();
        $this->inscripcionService = new InscripcionService();
        $this->documentoService = new DocumentoService();

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
     * Obtiene las inscripciones aprobadas
     * que todavía no poseen carnet.
     */
    public function obtenerPendientesEmision(): array
    {
        return $this->carnetRepository
            ->obtenerPendientesEmision(
                EstadoTramite::APROBADO
            );
    }
    /**
     * Obtiene una inscripción aprobada pendiente de emisión
     * buscando al ciudadano por DNI.
     */
    public function obtenerPendienteEmisionPorDni(string $dni): ?array
    {
        $dni = trim($dni);

        if ($dni === '') {

            return null;
        }

        return $this->carnetRepository
            ->obtenerPendienteEmisionPorDni(
                $dni,
                EstadoTramite::APROBADO
            );
    }

    /**
     * Lista los carnets activos para administración.
     */
    public function listarActivosAdministracion(): array
    {
        return $this->carnetRepository
            ->listarActivosAdministracion();
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
    public function renovarCarnet(int $idCarnet,string $fechaVencimiento): array
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
     * Carga y emite el carnet oficial correspondiente
     * a una inscripción con examen aprobado.
     *
     * APROBADO y CARNET_EMITIDO son estados diferentes.
     *
     * APROBADO:
     * El alumno aprobó el examen pero todavía
     * no se cargó el carnet oficial.
     *
     * CARNET_EMITIDO:
     * El carnet oficial de DIPA ya fue cargado
     * correctamente.
     */
    public function emitirCarnet(
        int $idInscripcion,
        array $datos
    ): array
    {
        /*
        * Obtener la inscripción.
        */
        $inscripcion =
            $this->inscripcionService
                ->obtenerPorId(
                    $idInscripcion
                );

        if ($inscripcion === null) {

            return [
                'success' => false,

                'mensaje' =>
                    'La inscripción no existe.'
            ];
        }

        /*
        * Solo una inscripción APROBADA puede
        * recibir un carnet.
        */
        if (
            $inscripcion->getEstadoId()
            !== EstadoTramite::APROBADO
        ) {

            return [
                'success' => false,

                'mensaje' =>
                    'Solo se puede cargar un carnet para una inscripción con examen aprobado.'
            ];
        }

        /*
        * Verificar que todavía no exista
        * un carnet para esta inscripción.
        */
        $carnetExistente =
            $this->carnetRepository
                ->obtenerPorInscripcionId(
                    $idInscripcion
                );

        if ($carnetExistente !== null) {

            return [
                'success' => false,

                'mensaje' =>
                    'La inscripción ya posee un carnet emitido.'
            ];
        }

        /*
        * Verificar documentación.
        */
        $documentacion =
            $this->documentoService
                ->obtenerEstadoDocumentacion(
                    $inscripcion->getUsuarioId()
                );

        if (
            empty($documentacion['completo'])
        ) {

            return [
                'success' => false,

                'mensaje' =>
                    'No se puede emitir el carnet porque el alumno no posee toda la documentación requerida.'
            ];
        }

        /*
        * Obtener datos del carnet oficial.
        */
        $numeroCarnet =
            trim(
                $datos['numero_carnet'] ?? ''
            );

        $fechaEmision =
            trim(
                $datos['fecha_emision'] ?? ''
            );

        $fechaVencimiento =
            trim(
                $datos['fecha_vencimiento'] ?? ''
            );

        $rutaPdf =
            trim(
                $datos['ruta_pdf'] ?? ''
            );

        /*
        * Validar número de carnet.
        */
        if ($numeroCarnet === '') {

            return [
                'success' => false,

                'mensaje' =>
                    'Debe ingresar el número de carnet.'
            ];
        }

        /*
        * Verificar que el número no exista.
        */
        $carnetPorNumero =
            $this->carnetRepository
                ->obtenerPorNumero(
                    $numeroCarnet
                );

        if ($carnetPorNumero !== null) {

            return [
                'success' => false,

                'mensaje' =>
                    'El número de carnet ingresado ya existe.'
            ];
        }

        /*
        * Validar que exista el PDF.
        *
        * El controlador será responsable de subir
        * físicamente el archivo.
        *
        * El Service solamente recibe la ruta final.
        */
        if ($rutaPdf === '') {

            return [
                'success' => false,

                'mensaje' =>
                    'Debe cargar el PDF oficial del carnet.'
            ];
        }

        /*
        * Validar fecha de emisión.
        */
        $emision =
            DateTime::createFromFormat(
                'Y-m-d',
                $fechaEmision
            );

        if (
            !$emision
            || $emision->format('Y-m-d')
                !== $fechaEmision
        ) {

            return [
                'success' => false,

                'mensaje' =>
                    'La fecha de emisión no es válida.'
            ];
        }

        /*
        * Validar fecha de vencimiento.
        */
        $vencimiento =
            DateTime::createFromFormat(
                'Y-m-d',
                $fechaVencimiento
            );

        if (
            !$vencimiento
            || $vencimiento->format('Y-m-d')
                !== $fechaVencimiento
        ) {

            return [
                'success' => false,

                'mensaje' =>
                    'La fecha de vencimiento no es válida.'
            ];
        }

        /*
        * El vencimiento debe ser posterior
        * a la emisión.
        */
        if ($vencimiento <= $emision) {

            return [
                'success' => false,

                'mensaje' =>
                    'La fecha de vencimiento debe ser posterior a la fecha de emisión.'
            ];
        }

        /*
        * Preparar los datos para Repository.
        */
        $datosCarnet = [

            'id_inscripcion' =>
                $idInscripcion,

            'numero_carnet' =>
                $numeroCarnet,

            'fecha_emision' =>
                $fechaEmision,

            'fecha_vencimiento' =>
                $fechaVencimiento,

            'ruta_pdf' =>
                $rutaPdf
        ];

        /*
        * Obtener la misma conexión PDO utilizada
        * por los repositorios.
        */
        $conexion =
            Connection::getPDO();

        try {

            /*
            * Comenzar transacción.
            */
            $conexion->beginTransaction();

            /*
            * Crear el carnet.
            */
            $carnet =
                $this->carnetRepository
                    ->crear(
                        $datosCarnet
                    );

            if ($carnet === null) {

                $conexion->rollBack();

                return [
                    'success' => false,

                    'mensaje' =>
                        'No fue posible crear el carnet. Verifique que el número no esté duplicado.'
                ];
            }

            /*
            * Cambiar el estado:
            *
            * APROBADO
            *
            * a:
            *
            * CARNET_EMITIDO
            */
            $estadoActualizado =
                $this->inscripcionService
                    ->actualizarEstadoTramite(
                        $idInscripcion,
                        EstadoTramite::CARNET_EMITIDO
                    );

            if (!$estadoActualizado) {

                $conexion->rollBack();

                return [
                    'success' => false,

                    'mensaje' =>
                        'No fue posible actualizar el estado de la inscripción.'
                ];
            }

            /*
            * Todo salió correctamente.
            */
            $conexion->commit();

            return [

                'success' =>
                    true,

                'mensaje' =>
                    'Carnet cargado y emitido correctamente.',

                'carnet' =>
                    $carnet
            ];

        } catch (Throwable $e) {

            /*
            * Si hubo un error, deshacer tanto
            * la creación del carnet como el
            * cambio de estado.
            */
            if (
                $conexion->inTransaction()
            ) {

                $conexion->rollBack();
            }

            throw $e;
        }
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