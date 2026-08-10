<?php
/**
 * InscripcionService.php
 *
 * Servicio para manejar la lógica de negocio relacionada con las inscripciones.
 *
 * Métodos:
 * tieneCursoActivo() ok
 * obtenerPorId() ok
 * obtenerPorUsuario() ok
 * obtenerUltimaPorUsuario() ok
 * crear() ok
 * cancelar() ok
 * obtenerActivas() ok
 * verificarDuplicado() ok
 * contarInscriptosCurso() ok 
 * obtenerDetalleInscripcion() ok
 * usuarioPuedeInscribirseExamen() ok
 * confirmarInscripcionExamen() ok
 * actualizarEstadoInscripcion() ok
 * agregarObservacion() ok
 * verificarHabilitacion() ok
 * listarInscripciones() ok
 * obtenerInscripcion() ok
 * validarDocumentacion() ok
 * rechazarDocumentacion() ok
 * 
 * 
 * 
 * 
*/
require_once __DIR__ . '/../dto/InscripcionDTO.php';

require_once __DIR__ . '/../Repository/InscripcionRepository.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';
require_once __DIR__ . '/../Repository/ExamenRepository.php';
require_once __DIR__ . '/../Repository/CarnetRepository.php';

require_once __DIR__ . '/../Servicios/DocumentoService.php';

require_once __DIR__ . '/../Constant/EstadoTramite.php';
        
class InscripcionService
{
    private InscripcionRepository $inscripcionRepository;
    private DocumentoRepository $documentoRepository;
    private ExamenRepository $examenRepository;
    private DocumentoService $documentoService;
    private CarnetRepository $carnetRepository;

    

    // Inicializa las dependencias de la clase.
    public function __construct(){
        $this->inscripcionRepository = new InscripcionRepository();
        $this->documentoRepository = new DocumentoRepository();
        $this->examenRepository = new ExamenRepository();
        $this->documentoService = new DocumentoService();
        $this->carnetRepository = new CarnetRepository();

    }
    // Ejecuta puede iniciar nueva inscripción.
    public function puedeIniciarNuevaInscripcion(int $usuarioId): bool
    {
        $carnet =
            $this->carnetRepository
                ->obtenerCarnetVigentePorUsuario(
                    $usuarioId
                );

        return $carnet === null;
    }
    // Ejecuta tiene curso activo.
    public function tieneCursoActivo(int $usuarioId): bool
    {
        return $this->inscripcionRepository
            ->tieneCursoActivo($usuarioId);
    }
    // Obtiene por id.
    public function obtenerPorId(int $id): ?InscripcionDTO 
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($id);

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $inscripcion
        );
    }
    // Obtiene por usuario.
    public function obtenerPorUsuario(int $usuarioId    ): array 
    {
        $inscripciones =
            $this->inscripcionRepository
                ->obtenerPorUsuario(
                    $usuarioId
                );

        $resultado = [];

        foreach ($inscripciones as $inscripcion) {

            $resultado[] =
                InscripcionDTO::fromArray(
                    $inscripcion
                );
        }

        return $resultado;
    }
    // Obtiene ultima por usuario.
    public function obtenerUltimaPorUsuario(int $usuarioId): ?InscripcionDTO 
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerUltimaInscripcionPorUsuario(
                    $usuarioId
                );

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $inscripcion
        );
    }
    // Crea la operaci?n correspondiente.
    public function crear(array $datos): ?InscripcionDTO
    {
        $id = $this->inscripcionRepository->crear($datos);

        if (!$id) {
            return null;
        }

        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($id);

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray($inscripcion);
    }
    // Ejecuta cancelar.
    public function cancelar(int $id, string $motivo = ''): bool
    {
        return $this->inscripcionRepository
            ->cancelar(
                $id,
                $motivo
            );
    }
    // Obtiene activas.
    public function obtenerActivas(int $usuarioId): array
    {
        $rows =
            $this->inscripcionRepository
                ->obtenerInscripcionesActivas($usuarioId);

        $resultado = [];

        foreach ($rows as $row) {

            $resultado[] =
                InscripcionDTO::fromArray(
                    $row
                );
        }

        return $resultado;
    }
    // Ejecuta verificar duplicado.
    public function verificarDuplicado(int $usuarioId, int $cursoId): bool
    {
        return $this->inscripcionRepository
            ->verificarDuplicado(
                $usuarioId,
                $cursoId
            );
    }
    // Ejecuta contar inscriptos curso.
    public function contarInscriptosCurso(int $cursoId): int
    {
        return $this->inscripcionRepository
            ->contarInscriptosCurso($cursoId);
    }
    // Obtiene detalle inscripcion.
    public function obtenerDetalleInscripcion(int $id): ?InscripcionDTO
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($id);

        if (!$inscripcion) {
            return null;
        }

        return InscripcionDTO::fromArray(
            $inscripcion
        );
    }
    // Ejecuta usuario puede inscribirse examen.
    public function usuarioPuedeInscribirseExamen(int $usuarioId): array
    {
        $estado = $this->documentoService
            ->obtenerEstadoDocumentacion($usuarioId);

        $faltantes = [];

        if (!$estado['dni']) {
            $faltantes[] = 'DNI';
        }

        if (!$estado['foto']) {
            $faltantes[] = 'Foto Carnet';
        }

        if (
            !$estado['asistencia']
            &&
            !$estado['moodle']
        ) {
            $faltantes[] = 'Curso aprobado';
        }

        return [
            'puede' => $estado['completo'],
            'faltantes' => $faltantes
        ];
    }

    public function confirmarInscripcionExamen(int $idInscripcion): bool
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId(
                    $idInscripcion
                );

        if (!$inscripcion) {

            return false;
        }

        $usuarioId =
            (int)$inscripcion['usuario_id'];

        if (
            !$this->puedeIniciarNuevaInscripcion(
                $usuarioId
            )
        ) {

            return false;
        }

        if (
            !$this->inscripcionRepository
                ->confirmarInscripcionExamen(
                    $idInscripcion
                )
        ) {

            return false;
        }

        if (
            !empty($inscripcion['examen_id'])
        ) {

            $this->examenRepository
                ->descontarCupo(
                    (int)$inscripcion['examen_id']
                );
        }

        return true;
    }
    // Actualiza estado inscripcion.
    public function actualizarEstadoInscripcion(int $id, int $estado): bool
    {
        return
            $this->inscripcionRepository
                ->actualizarEstadoInscripcion(
                    $id,
                    $estado
                );
    }
    // Ejecuta agregar observacion.
    public function agregarObservacion(int $id, string $texto): bool
    {
        return
            $this->inscripcionRepository
                ->agregarObservacion(
                    $id,
                    $texto
                );
    }
    // Ejecuta verificar habilitacion.
    public function verificarHabilitacion(int $idInscripcion): ?InscripcionDTO
    {
        return $this->obtenerPorId($idInscripcion);
    }

    /**
     * Listar inscripciones.
     */
    public function listarInscripciones(array $filtros = []): array
    {
        return [
            'inscripciones' => $this->inscripcionRepository
                ->listarInscripciones($filtros),

            'total' => $this->inscripcionRepository
                ->contarInscripciones($filtros)
        ];
    }
    /**
     * Obtener inscripción.
     */
    public function obtenerInscripcion(int $id): ?array
    {
        return $this->inscripcionRepository
            ->obtenerInscripcion($id);
    }
    /**
     * Validar documentación.
     */
    public function validarDocumentacion(int $idInscripcion): array
    {
        $inscripcion = $this->inscripcionRepository
            ->obtenerInscripcion($idInscripcion);

        if (!$inscripcion) {
            return [
                'success' => false,
                'codigo' => 'INSCRIPCION_INEXISTENTE'
            ];
        }

        $docs = $this->documentoRepository
            ->obtenerPorUsuario(
                (int)$inscripcion['usuario_id']
            );

        $faltantes = [];

        $estado =
            $this->documentoService
                ->obtenerEstadoDocumentacion(
                    (int)$inscripcion['usuario_id']
                );

        if (!$estado['completo']) {

            return [
                'success' => false,
                'codigo' => 'DOCUMENTACION_INCOMPLETA',
                'faltantes' => $this->obtenerFaltantesDocumentacion($estado)
            ];
        }

        $this->inscripcionRepository
            ->actualizarEstadoInscripcion(
                $idInscripcion,
                EstadoTramite::DOCUMENTACION_APROBADA
            );

        return [
            'success' => true
        ];
    }
    /**
     * Rechazar documentación.
     */
    public function rechazarDocumentacion(
        int $id,
        string $motivo
    ): array {

        $inscripcion = $this->inscripcionRepository
            ->obtenerInscripcion($id);

        if (!$inscripcion) {
            return [
                'success' => false,
                'codigo' => 'INSCRIPCION_INEXISTENTE'
            ];
        }

        $this->inscripcionRepository
            ->actualizarEstadoInscripcion(
                $id,
                EstadoTramite::RECHAZADO
            );

        $this->inscripcionRepository
            ->agregarObservacion(
                $id,
                "\nRechazo: " . $motivo
            );

        if (class_exists('NotificacionControlador')) {

            try {

                $nc = new NotificacionControlador();

                if (method_exists($nc, 'enviarNotificacion')) {

                    $nc->enviarNotificacion(
                        (int)$inscripcion['usuario_id'],
                        'documentacion_rechazada',
                        [
                            'motivo' => $motivo
                        ]
                    );
                }

            } catch (Throwable $e) {
                // Ignorar error de notificación.
            }
        }

        return [
            'success' => true
        ];
    }
    public function obtenerModalidadCurso(int $idInscripcion): ?string
    {
        return $this->inscripcionRepository
            ->obtenerModalidadCurso(
                $idInscripcion
            );
    }
    /**
     * Obtener el curso asociado a una inscripción.
     *
     * @param int $idInscripcion
     * @return array|null
     */
    public function obtenerCurso(
        int $idInscripcion
    ): ?array
    {
        return
            $this->inscripcionRepository
                ->obtenerCurso(
                    $idInscripcion
                );
    }

    /**
     * Obtener el tipo de inscripción.
     *
     * @param int $idInscripcion
     * @return array|null
     */
    public function obtenerTipoInscripcion(
        int $idInscripcion
    ): ?array
    {
        return
            $this->inscripcionRepository
                ->obtenerTipoInscripcion(
                    $idInscripcion
                );
    }

    /**
     * Actualizar estado del trámite.
     *
     * @param int $idInscripcion
     * @param int $estado
     * @return bool
     */
    public function actualizarEstadoTramite(
        int $idInscripcion,
        int $estado
    ): bool
    {
        return
            $this->inscripcionRepository
                ->actualizarEstadoTramite(
                    $idInscripcion,
                    $estado
                );
    }
    /**
     * Obtener inscripciones pendientes de validación.
     *
     * @return array
     */
    public function obtenerPendientesValidacion(): array
    {
        return
            $this->inscripcionRepository
                ->obtenerPendientesValidacion();
    }

    /**
     * Verificar si un usuario tiene un examen activo.
     *
     * @param int $usuarioId
     * @return bool
     */
    public function tieneExamenActivo(int $usuarioId): bool
    {
        return $this->inscripcionRepository
            ->tieneExamenActivo($usuarioId);
    }
    
    public function obtenerProximoExamenUsuario(int $usuarioId): ?array
    {
        return $this->inscripcionRepository
            ->obtenerProximoExamenUsuario($usuarioId);
    }
    
}
