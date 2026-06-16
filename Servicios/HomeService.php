<?php

class HomeService
{
    public function obtenerAccionPrincipal(
        array $documentos,
        ?object $inscripcion
    ): array {

        $tieneDni = false;
        $tieneFoto = false;
        $tieneAsistencia = false;
        $tieneMoodle = false;

        foreach ($documentos as $doc) {

            if (!$doc->getValidado()) {
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

                case 'ASISTENCIA':
                    $tieneAsistencia = true;
                    break;

                case 'MOODLE':
                    $tieneMoodle = true;
                    break;
            }
        }

        $documentacionCompleta =
            $tieneDni
            && $tieneFoto
            && ($tieneAsistencia || $tieneMoodle);

        if (!$documentacionCompleta) {

            return [
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

        if ($asistencia || $moodle) {
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

        if (!$asistencia && !$moodle) {
            $faltantes[] =
                'Constancia de asistencia o certificado Moodle';
        }

        return $faltantes;
    }
}