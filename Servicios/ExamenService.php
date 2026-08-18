<?php
/**
 * Métodos:
 * listarExamenes
 * listarExamenesProgramadosAscendente
 * listarExamenesProgramadosDescendente
 * listarHistorialExamenesDescendente
 * contarExamenes
 * obtenerExamen
 * obtenerProximos
 * crearExamen
 * actualizarExamen
 * activarExamen
 * desactivarExamen
 * actualizarCupos
 * descontarCupo
 * obtenerDetalleExamen
 * obtenerDisponibles
 * obtenerProximosPorUsuario
 * obtenerAprobados
 *
 * 
 * 
 */
declare(strict_types=1);


/**
 * ExamenService - Servicio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../Repository/ExamenRepository.php';
require_once __DIR__ . '/../Servicios/CarnetService.php';

require_once __DIR__ . '/../Constant/EstadoTramite.php';

class ExamenService
{
    private ExamenRepository $examenRepository;
    private CarnetService $carnetService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->examenRepository = new ExamenRepository();
        $this->carnetService = new CarnetService();
    }

    /**
     * Obtener todos los exámenes.
     */
    public function listarExamenes(): array
    {
        return $this->examenRepository
            ->listarExamenes();
    }

    /**
     * Obtener exámenes programados ordenados por fecha ascendente.
     */
    public function listarExamenesProgramadosAscendente(): array
    {
        return $this->examenRepository
            ->listarExamenesProgramadosAscendente();
    }

    /**
     * Obtener exámenes programados ordenados por fecha descendente.
     */
    public function listarExamenesProgramadosDescendente(): array
    {
        return $this->examenRepository
            ->listarExamenesProgramadosDescendente();
    }

    /**
     * Obtener historial de exámenes.
     */
    public function listarHistorialExamenesDescendente(): array
    {
        return $this->examenRepository
            ->listarHistorialExamenesDescendente();
    }
    /**
     * Contar exámenes.
     */
    public function contarExamenes(): int
    {
        return $this->examenRepository
            ->contarExamenes();
    }

    /**
     * Obtener examen por ID.
     */
    public function obtenerExamen(int $id): ?array
    {
        return $this->examenRepository
            ->obtenerExamen($id);
    }

    /**
     * Obtener próximos exámenes.
     */
    public function obtenerProximos(
        int $cantidad = 10
    ): array
    {
        return $this->examenRepository
            ->obtenerProximos($cantidad);
    }

    /**
     * Crea un nuevo examen.
     *
     * @param array $datos
     * @return int
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function crearExamen(array $datos): int
    {
        $fecha = trim($datos['fecha'] ?? '');

        $hora = trim($datos['hora'] ?? '');

        $ubicacion = trim($datos['ubicacion'] ?? '');

        $aula = trim($datos['aula'] ?? '');

        $cupos = (int)($datos['cupos'] ?? 0);

        if ($fecha === '') {
            throw new InvalidArgumentException(
                'Debe ingresar una fecha.'
            );
        }

        if ($hora === '') {
            throw new InvalidArgumentException(
                'Debe ingresar una hora.'
            );
        }

        if ($ubicacion === '') {
            throw new InvalidArgumentException(
                'Debe ingresar una ubicación.'
            );
        }

        if ($aula === '') {
            throw new InvalidArgumentException(
                'Debe ingresar un aula.'
            );
        }

        if ($cupos <= 0) {
            throw new InvalidArgumentException(
                'La cantidad de cupos debe ser mayor a cero.'
            );
        }

    $fechaHoraExamen = strtotime($fecha . ' ' . $hora);

    if ($fechaHoraExamen === false) {
        throw new InvalidArgumentException(
            'La fecha u hora ingresada no es válida.'
        );
    }

    if ($fechaHoraExamen < time()) {
        throw new InvalidArgumentException(
            'No puede crear un examen con una fecha y hora anteriores al momento actual.'
        );
    }

        return $this->examenRepository
            ->crearExamen([
                'fecha' => $fecha,
                'hora' => $hora,
                'ubicacion' => $ubicacion,
                'aula' => $aula,
                'cupos' => $cupos
            ]);
    }

    /**
     * Actualizar examen.
     */
    public function actualizarExamen(int $id,array $datos): bool
    {
        $examen = $this->obtenerExamen($id);

        if ($examen === null) {
            throw new InvalidArgumentException(
                'El examen no existe.'
            );
        }

        if (empty($datos['fecha'])) {
            throw new InvalidArgumentException(
                'Debe ingresar una fecha.'
            );
        }

        if (empty($datos['hora'])) {
            throw new InvalidArgumentException(
                'Debe ingresar una hora.'
            );
        }

        if (empty($datos['ubicacion'])) {
            throw new InvalidArgumentException(
                'Debe ingresar una ubicación.'
            );
        }

        if (empty($datos['aula'])) {
            throw new InvalidArgumentException(
                'Debe ingresar un aula.'
            );
        }

        if (($datos['cupos'] ?? 0) <= 0) {
            throw new InvalidArgumentException(
                'La cantidad de cupos debe ser mayor a cero.'
            );
        }

        $fechaHora = strtotime(
            $datos['fecha'] . ' ' . $datos['hora']
        );

        if ($fechaHora < time()) {
            throw new InvalidArgumentException(
                'La fecha y hora del examen no pueden ser anteriores al momento actual.'
            );
        }

        $huboCambios =
            $examen['fecha'] !== $datos['fecha']
            || $examen['hora'] !== $datos['hora']
            || trim($examen['ubicacion']) !== trim($datos['ubicacion'])
            || trim($examen['aula']) !== trim($datos['aula'])
            || (int) $examen['cupos'] !== (int) $datos['cupos'];

        if (!$huboCambios) {
            return true;
        }

        return $this->examenRepository->actualizarExamen(
            $id,
            $datos
        );
    }
    public function obtenerDetalle(int $id): ?array
    {
        $examen = $this->examenRepository->obtenerExamen(
            $id
        );

        if ($examen === null) {

            return null;
        }

        $inscriptos = $this->examenRepository->obtenerInscriptos(
            $id
        );

        $cuposTotales = (int) ($examen['cupos'] ?? 0);

        $cantidadInscriptos = count(
            $inscriptos
        );

        $cuposDisponibles = max(
            0,
            $cuposTotales - $cantidadInscriptos
        );
        foreach ($inscriptos as &$inscripto) {

            $inscripto['estado'] = match ($inscripto['estado']) {

                'PENDIENTE' => 'Pendiente',

                'DOCUMENTACION_PENDIENTE' => 'Documentación pendiente',

                'DOCUMENTACION_APROBADA' => 'Documentación aprobada',

                'INSCRIPTO_EXAMEN' => 'Inscripto',

                'APROBADO' => 'Aprobado',

                'RECHAZADO' => 'Rechazado',

                'CANCELADO' => 'Cancelado',

                'CARNET_EMITIDO' => 'Carnet emitido',

                default => $inscripto['estado']

            };
        }

        return [

            'page_title' => 'Detalle del examen',

            'examen' => [

                'id' => (int) $examen['id'],
                'fecha' => $examen['fecha'],
                'hora' => $examen['hora'],
                'lugar' => $examen['ubicacion'],
                'aula' => $examen['aula'],
                'estado' => ((int) $examen['activo'] === 1)
                    ? 'Activo'
                    : 'Inactivo',
                'cupos_totales' => $cuposTotales,
                'cupos_disponibles' => $cuposDisponibles

            ],

            'inscriptos' => $inscriptos

        ];
    }

    /**
    * Obtiene toda la información necesaria para administrar
    * la inscripción de un alumno a un examen.
    */
    public function obtenerAdministracionInscripcion(int $id): ?array
    {
        $datos = $this->examenRepository
            ->obtenerAdministracionInscripcion(
                $id
            );

        if ($datos === null) {

            return null;
        }

        return [

            'page_title' => 'Administrar inscripción',

            'examen' => [

                'id' => (int) $datos['examen_id'],
                'fecha' => $datos['fecha'],
                'hora' => $datos['hora'],
                'ubicacion' => $datos['ubicacion'],
                'aula' => $datos['aula']

            ],

            'alumno' => [

                'id' => (int) $datos['usuario_id'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'dni' => $datos['dni'],
                'email' => $datos['email']

            ],

            'documentacion' => [

                'dni' => (bool) $datos['dni_aprobado'],
                'foto' => (bool) $datos['foto_aprobada'],
                'curso' => (bool) $datos['curso_aprobado']

            ],

            'inscripcion' => [

                'id' => (int) $datos['inscripcion_id'],

                'estado' => $datos['estado'],

                'observaciones' =>
                    $datos['observaciones'] ?? ''

            ]

        ];
    }
    /**
     * Guarda el resultado de una inscripción de examen.
     *
     * APROBADO y CARNET_EMITIDO son estados diferentes.
     *
     * APROBADO:
     * El alumno aprobó el examen, pero el carnet todavía
     * no fue emitido/cargado.
     */
    public function guardarAdministracionInscripcion(int $id,array $datos): bool
    {
        $inscripcion =
            $this->examenRepository
                ->obtenerAdministracionInscripcion(
                    $id
                );

        if ($inscripcion === null) {

            throw new InvalidArgumentException(
                'La inscripción no existe.'
            );
        }

        if (
            (int)$inscripcion['estado_tramite_id']
            === EstadoTramite::CARNET_EMITIDO
        ) {

            throw new InvalidArgumentException(
                'El carnet ya fue emitido para esta inscripción.'
            );
        }

        $estado = strtoupper(
            trim($datos['estado'] ?? '')
        );

        $estadoTramite = match ($estado) {

            'APROBADO' =>
                EstadoTramite::APROBADO,

            'DESAPROBADO' =>
                EstadoTramite::RECHAZADO,

            default =>
                throw new InvalidArgumentException(
                    'Debe seleccionar un resultado.'
                )
        };

        $observaciones =
            trim(
                $datos['observaciones'] ?? ''
            );

        $resultado =
            $this->examenRepository
                ->guardarAdministracionInscripcion(
                    $id,
                    $estadoTramite,
                    $observaciones
                );

        if (!$resultado) {

            return false;
        }

        return true;
    }
    

    /**
     * Cambia el estado de un examen.
     */
    public function cambiarEstado(
        int $id,
        bool $activo
    ): bool
    {
        $examen = $this->obtenerExamen($id);

        if ($examen === null) {
            throw new InvalidArgumentException(
                'El examen no existe.'
            );
        }

        return $this->examenRepository->actualizarEstado(
            $id,
            $activo
        );
    }
    /**
     * Actualizar cupos.
     */
    public function actualizarCupos(
        int $id,
        int $cupos
    ): bool
    {
        return $this->examenRepository
            ->actualizarCupos(
                $id,
                $cupos
            );
    }

    /**
     * Descontar un cupo disponible.
     */
    public function descontarCupo(int $idExamen): bool
    {
        return $this->examenRepository
            ->descontarCupo($idExamen);
    }

    /**
     * Obtener detalle completo de un examen.
     */
    public function obtenerDetalleExamen(int $id): ?array
    {
        return $this->examenRepository
            ->obtenerDetalleExamen($id);
    }
    // Obtiene disponibles.
    public function obtenerDisponibles(): array
    {
        return
            $this->examenRepository
                ->obtenerDisponibles();
    }
    // Obtiene proximos por usuario.
    public function obtenerProximosPorUsuario(int $usuarioId): array
    {
        return
            $this->examenRepository
                ->obtenerProximosPorUsuario(
                    $usuarioId
                );
    }
    // Obtiene aprobados.
    public function obtenerAprobados(int $idExamen): array
    {
        return
            $this->examenRepository
                ->obtenerAprobados(
                    $idExamen
                );
    }

}
