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
 * 
 * 
 * 
 * 
*/
require_once __DIR__ . '/../dto/InscripcionDTO.php';
require_once __DIR__ . '/../Repository/InscripcionRepository.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';
require_once __DIR__ . '/../Repository/ExamenRepository.php';


require_once __DIR__ . '/../Servicios/HabilitacionExamenService.php';

        
class InscripcionService
{
    private InscripcionRepository $inscripcionRepository;
    private DocumentoRepository $documentoRepository;
    private ExamenRepository $examenRepository;
    private HabilitacionExamenService $habilitacionService;
    

    public function __construct(){
        $this->inscripcionRepository = new InscripcionRepository();
        $this->documentoRepository = new DocumentoRepository();
        $this->examenRepository = new ExamenRepository();

    }
    public function tieneCursoActivo(int $usuarioId): bool
    {
        return $this->inscripcionRepository
            ->tieneCursoActivo($usuarioId);
    }
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
    public function cancelar(int $id, string $motivo = ''): bool
    {
        return $this->inscripcionRepository
            ->cancelar(
                $id,
                $motivo
            );
    }
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
    public function verificarDuplicado(int $usuarioId, int $cursoId): bool
    {
        return $this->inscripcionRepository
            ->verificarDuplicado(
                $usuarioId,
                $cursoId
            );
    }
    public function contarInscriptosCurso(int $cursoId): int
    {
        return $this->inscripcionRepository
            ->contarInscriptosCurso($cursoId);
    }
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
    public function usuarioPuedeInscribirseExamen(int $usuarioId): array
    {
        $documentos =
            $this->documentoRepository
                ->obtenerPorUsuario($usuarioId);


        $tieneDni = false;
        $tieneFoto = false;

        foreach ($documentos as $documento) {

            if (($documento['estado'] ?? '') !== 'aprobado') {
                continue;
            }

            switch (strtolower($documento['tipo_documento'])) {

                case 'dni':
                    $tieneDni = true;
                    break;

                case 'foto':
                case 'foto_carnet':
                    $tieneFoto = true;
                    break;
            }
        }

        $tieneHabilitacion =
            $habilitacionService
                ->tieneHabilitacionVigente($usuarioId);

        $faltantes = [];

        if (!$tieneDni) {
            $faltantes[] = 'DNI';
        }

        if (!$tieneFoto) {
            $faltantes[] = 'Foto Carnet';
        }

        if (!$tieneHabilitacion) {
            $faltantes[] =
                'No posee una habilitación vigente para rendir el examen';
        }

        return [
            'puede' => empty($faltantes),
            'faltantes' => $faltantes
        ];
    }

    public function confirmarInscripcionExamen(int $idInscripcion): bool
    {
        $inscripcion =
            $this->inscripcionRepository
                ->obtenerPorId($idInscripcion);

        if (!$inscripcion) {
            return false;
        }

        if (
            !$this->inscripcionRepository
                ->confirmarInscripcionExamen($idInscripcion)
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
    public function actualizarEstadoInscripcion(int $id, int $estado): bool
    {
        return
            $this->inscripcionRepository
                ->actualizarEstadoInscripcion(
                    $id,
                    $estado
                );
    }
    public function agregarObservacion(int $id, string $texto): bool
    {
        return
            $this->inscripcionRepository
                ->agregarObservacion(
                    $id,
                    $texto
                );
    }
    public function verificarHabilitacion(int $idInscripcion): ?InscripcionDTO
    {
        return $this->obtenerPorId($idInscripcion);
    }
}