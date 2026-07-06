<?php
declare(strict_types=1);
/**
 * Servicio para gestionar las asistencias.
 * 
 * Metodos:
 * crear() 
 * registrarAsistencia()
 * obtenerPorId()
 * obtenerPorInscripcion()
 * obtenerPorUsuario()
 * obtenerPorCurso()
 * obtenerTotalAsistencias()
 * listar()
 * actualizar()
 * eliminar()
 */
require_once __DIR__ . '/../Repository/AsistenciaRepository.php';

class AsistenciaService
{
    private AsistenciaRepository $asistenciaRepository;

    public function __construct()
    {
        $this->asistenciaRepository =
            new AsistenciaRepository();
    }

    /**
     * Crear asistencia.
     */
    public function crear(array $datos): ?array
    {
        $id =
            $this->asistenciaRepository
                ->crear($datos);

        if (!$id) {
            return null;
        }

        return
            $this->asistenciaRepository
                ->obtenerPorId($id);
    }

    /**
     * Registrar asistencia.
     */
    public function registrarAsistencia(
        int $inscripcionId,
        bool $presente,
        ?string $observaciones = null
    ): bool {

        $resultado =
            $this->crear([
                'inscripcion_id' => $inscripcionId,
                'fecha' => date('Y-m-d'),
                'presente' => $presente ? 1 : 0,
                'observaciones' => $observaciones
            ]);

        return $resultado !== null;
    }

    /**
     * Obtener asistencia por ID.
     */
    public function obtenerPorId(
        int $id
    ): ?array {

        return
            $this->asistenciaRepository
                ->obtenerPorId($id);
    }

    /**
     * Obtener asistencias de una inscripción.
     */
    public function obtenerPorInscripcion(
        int $inscripcionId
    ): array {

        return
            $this->asistenciaRepository
                ->obtenerPorInscripcion(
                    $inscripcionId
                );
    }

    /**
     * Obtener asistencias de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array {

        return
            $this->asistenciaRepository
                ->obtenerPorUsuario(
                    $usuarioId
                );
    }

    /**
     * Obtener asistencias de un curso.
     */
    public function obtenerPorCurso(
        int $cursoId
    ): array {

        return
            $this->asistenciaRepository
                ->obtenerPorCurso(
                    $cursoId
                );
    }

        /**
     * Obtener resumen de asistencia.
     */
    public function obtenerTotalAsistencias(
        int $inscripcionId
    ): array {

        $totales =
            $this->asistenciaRepository
                ->obtenerTotalAsistencias(
                    $inscripcionId
                );

        $presentes =
            (int)$totales['presentes'];

        $total =
            (int)$totales['total'];

        $porcentaje =
            $total > 0
                ? round(($presentes / $total) * 100, 2)
                : 0;

        return [
            'total_sesiones' => $total,
            'sesiones_presentes' => $presentes,
            'porcentaje_asistencia' => $porcentaje
        ];
    }

    /**
     * Listar asistencias.
     */
    public function listar(): array
    {
        return
            $this->asistenciaRepository
                ->listar();
    }

    /**
     * Actualizar asistencia.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {

        return
            $this->asistenciaRepository
                ->actualizar(
                    $id,
                    $datos
                );
    }

    /**
     * Eliminar asistencia.
     */
    public function eliminar(
        int $id
    ): bool {

        return
            $this->asistenciaRepository
                ->eliminar($id);
    }
}