<?php


class HomeService
{
    public function obtenerAccionPrincipal(array $documentos,?object $inscripcion ): array 
    {

        $tieneDni = false;
        $tieneFoto = false;
        $tieneHabilitacion = false;
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

            }
        }


        if ($inscripcion !== null && $inscripcion->getTipoInscripcionId() === 1) 
            {

            $tituloTramite =
                'Curso de Manipulación de Alimentos';
        }


        if ($inscripcion !== null) {

            require_once __DIR__ .
                '/../Service/HabilitacionExamenService.php';

            $habilitacionService =
                new HabilitacionExamenService();

            $tieneHabilitacion =
                $habilitacionService
                    ->tieneHabilitacionVigente(
                        $inscripcion->getUsuarioId()
                    );
        }
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
        $estado = $inscripcion !== null
                ? $inscripcion->getEstadoId()
                : null;

        if ($estado === EstadoTramite::INSCRIPTO_EXAMEN) {

            return [

                'titulo' => $tituloTramite,

                'faltantes' => [],

                'texto' => 'Rendir el examen',

                'ruta' => 'detalle_examen',

                'completa' => true,

                'porcentaje' => 100
            ];
        }

        $documentacionCompleta =
                $tieneDni
                && $tieneFoto
                && $tieneHabilitacion;

        if (!$documentacionCompleta) {

            return [
                'titulo' => $tituloTramite,
                'faltantes' => $this->obtenerFaltantes(
                                    $tieneDni,
                                    $tieneFoto,
                                    $tieneHabilitacion,
                                ),
                'texto' => 'Completar documentación',
                'ruta' => 'subida_documentacion',
                'completa' => false,
                'porcentaje' => $this->calcularPorcentaje(
                    $tieneDni,
                    $tieneFoto,
                    $tieneHabilitacion,
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
        bool $habilitacion
    ): int {

        $completos = 0;

        if ($dni) {
            $completos++;
        }

        if ($foto) {
            $completos++;
        }

        if ($habilitacion) {
            $completos++;
        }

        return (int)(($completos / 3) * 100);
    }

    private function obtenerFaltantes(
        bool $dni,
        bool $foto,
        bool $habilitacion
    ): array {

        $faltantes = [];

        if (!$dni) {
            $faltantes[] = 'DNI';
        }

        if (!$foto) {
            $faltantes[] = 'Foto Carnet';
        }

        if (!$habilitacion) {
            $faltantes[] = 'Falta una habilitación vigente para rendir el examen';
        }

        return $faltantes;
    }
}