<?php


require_once __DIR__ .'/../Servicios/DocumentoService.php';
require_once __DIR__ .'/../Servicios/InscripcionService.php';

            
class HomeService
{

    private DocumentoService $documentoService;
    private InscripcionService $inscripcionService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->documentoService =
            new DocumentoService();
        $this->inscripcionService =
            new InscripcionService();
    }
    // Obtiene accion principal.
    public function obtenerAccionPrincipal(int $usuarioId,?object $inscripcion ): array 
    {

        $tituloTramite = 'Carnet de Manipulador';



        if ($inscripcion !== null && $inscripcion->getTipoInscripcionId() === 1) 
            {

            $tituloTramite =
                'Curso de Manipulación de Alimentos';
        }

        $estadoDocumentacion =
                 $this->documentoService
                    ->obtenerEstadoDocumentacion($usuarioId);

        if ($inscripcion !== null
                &&
                $inscripcion->getTipoInscripcionId() === 1
                &&
                $inscripcion->getEstadoId() === EstadoTramite::PENDIENTE) 
                {

                return [

                    'titulo' =>
                        'Curso de Manipulación de Alimentos',

                    'faltantes' => [],

                    'texto' =>
                        'Esperando finalización del curso',

                    'ruta' => '#',

                    'completa' => false,

                    'porcentaje' => 100
                ];
            }
        $estadoTramite = $inscripcion !== null
        ? $inscripcion->getEstadoId()
        : null;

        if (
            $estadoTramite === EstadoTramite::CARNET_EMITIDO
        ) {

            return [

                'titulo' =>
                    'Carnet de Manipulador',

                'faltantes' =>
                    [],

                'texto' =>
                    'Trámite finalizado',

                'ruta' =>
                    'carnet',

                'completa' =>
                    true,

                'porcentaje' =>
                    100
            ];
        }

        if ($estadoTramite === EstadoTramite::INSCRIPTO_EXAMEN) {

            return [

                'titulo' =>
                    $tituloTramite,

                'faltantes' =>
                    [],

                'texto' =>
                    'Rendir el examen',

                'ruta' =>
                    'detalle_examen',

                'completa' =>
                    true,

                'porcentaje' =>
                    100
            ];
        }
        $documentacionCompleta =
            $estadoDocumentacion['completo'];

        if (!$documentacionCompleta) {

            return [
                'titulo' => $tituloTramite,
                'faltantes' => $this->obtenerFaltantes(
                        $estadoDocumentacion
                    ),
                'texto' => 'Completar documentación',
                'ruta' => 'subida_documentacion',
                'completa' => false,
                'porcentaje' => $estadoDocumentacion['porcentaje']
            ];
        }

        return [
                'titulo' => $tituloTramite,
                'faltantes' => [],
                'texto' => 'Inscribirme a examen',
                'ruta' => 'inscripciones',
                'completa' => true,
                'porcentaje' => 100
            ];
    }


    // Obtiene faltantes.
    private function obtenerFaltantes(array $estado): array
    {
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

        return $faltantes;
    }
    public function obtenerProximoExamen(int $usuarioId): ?array
    {
        return $this->inscripcionService
            ->obtenerProximoExamenUsuario($usuarioId);
    }
}
