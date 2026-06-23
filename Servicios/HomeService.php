<?php
require_once __DIR__ . '/../Controller/ValidacionControlador.php';


class HomeService
{
    public function obtenerAccionPrincipal(array $documentos,?object $inscripcion ): array 
    {

        $tieneDni = false;
        $tieneFoto = false;
        $tieneAsistencia = false;
        $tieneMoodle = false;
        $asistenciaValida = false;
        $tituloTramite = 'Carnet de Manipulador';

        foreach ($documentos as $doc) {

            if ($doc->getEstado() !== 'aprobado') {
                continue;
            }

            switch (
                strtoupper(
                    $doc->getTipoDocumento()
                )
            ) {
                case 'DNI':
                    $tieneDni = true;
                    break;

                case 'FOTO':
                case 'FOTO_CARNET':
                    $tieneFoto = true;
                    break;

                case 'MOODLE':
                case 'CERTIFICADO_MOODLE':
                    $tieneMoodle = true;
                    break;
            }
        }


        if ($inscripcion !== null && $inscripcion->getTipoInscripcionId() === 1) 
            {

            $tituloTramite =
                'Curso de Manipulación de Alimentos';
        }


        if ($inscripcion !== null) {

            $validacion = new ValidacionControlador();

            $resultadoAsistencia =
                $validacion->validarAsistencia(
                    $inscripcion->getId()
                );

            $asistenciaValida =
                $resultadoAsistencia['valido'];
        }
        if ($inscripcion !== null
                &&
                $inscripcion->getTipoInscripcionId() === 1
                &&
                $inscripcion->getEstadoNombre() === 'PENDIENTE') 
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
        $documentacionCompleta = $tieneDni
                                && $tieneFoto
                                && (
                                    $tieneAsistencia
                                    || $tieneMoodle
                                );

        if (!$documentacionCompleta) {

            return [
                'titulo' => $tituloTramite,
                'faltantes' => $this->obtenerFaltantes(
                                    $tieneDni,
                                    $tieneFoto,
                                    $tieneAsistencia,
                                    $tieneMoodle
                                ),
                'texto' => 'Completar documentación',
                'ruta' => 'subida_documentacion',
                'completa' => false,
                'porcentaje' => $this->calcularPorcentaje(
                    $tieneDni,
                    $tieneFoto,
                    $tieneAsistencia,
                    $tieneMoodle
                )
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

    private function calcularPorcentaje(
        bool $dni,
        bool $foto,
        bool $asistencia,
        bool $moodle
    ): int {

        $completos = 0;

        if ($dni) {
            $completos++;
        }

        if ($foto) {
            $completos++;
        }

        if ($moodle || $asistencia) {
            $completos++;
        }

        return (int)(($completos / 3) * 100);
    }

    private function obtenerFaltantes(
        bool $dni,
        bool $foto,
        bool $asistencia,
        bool $moodle
    ): array {

        $faltantes = [];

        if (!$dni) {
            $faltantes[] = 'DNI';
        }

        if (!$foto) {
            $faltantes[] = 'Foto Carnet';
        }

        if (!$moodle && !$asistencia) {
            $faltantes[] = 'Certificado Moodle o Constancia de asistencia';
        }

        return $faltantes;
    }
}