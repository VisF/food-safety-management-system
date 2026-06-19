<?php

require_once __DIR__ . '/../Modelo/DocumentoModelo.php';
require_once __DIR__ . '/../dto/DocumentoDTO.php';

class DocumentoService
{
    public function __construct(
        private DocumentoModelo $documentoModelo
    ) {
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $documentos =
            $this->documentoModelo
                ->obtenerPorUsuario($usuarioId);

        $resultado = [];

        foreach ($documentos as $documento) {

            $resultado[] =
                DocumentoDTO::fromArray(
                    $documento
                );
        }

        return $resultado;
    }
    public function obtenerEstadoDocumentacion(int $usuarioId): array
    {
        $documentos = $this->documentoRepository->obtenerPorUsuario($usuarioId);

        $tieneDni = false;
        $tieneFoto = false;
        $tieneAsistencia = false;
        $tieneMoodle = false;

        foreach ($documentos as $doc) {

            if ($doc['estado'] !== 'aprobado') {
                continue;
            }

            switch ($doc['tipo_documento']) {
                case 'dni':
                    $tieneDni = true;
                    break;

                case 'foto_carnet':
                    $tieneFoto = true;
                    break;

                case 'asistencia':
                    $tieneAsistencia = true;
                    break;

                case 'moodle':
                    $tieneMoodle = true;
                    break;
            }
        }

        $completos = 0;

        if ($tieneDni) $completos++;
        if ($tieneFoto) $completos++;
        if ($tieneAsistencia || $tieneMoodle) $completos++;

        return [
            'completos' => $completos,
            'total' => 3,
            'porcentaje' => ($completos / 3) * 100,
            'completo' => ($completos === 3),

            'dni' => $tieneDni,
            'foto' => $tieneFoto,
            'asistencia' => $tieneAsistencia,
            'moodle' => $tieneMoodle
        ];
    }



    public function rechazarDocumento(int $id, string $observaciones = ''): bool 
    {
        return
            $this->documentoModelo
                ->rechazar(
                    $id,
                    $observaciones
                );
    }
    public function subirDocumento(int $idInscripcion, string $tipoDocumento, string $rutaArchivo): ?DocumentoDTO
    {
        $documento =
        $this->documentoModelo
            ->subirDocumento(
                $idInscripcion,
                $tipoDocumento,
                $rutaArchivo
            );

        if (!$documento) {
            return null;
        }

        return DocumentoDTO::fromArray(
            $documento
        );      
    }



    public function validarDocumento(int $id, string $observaciones = ''): bool 
    {
        return
            $this->documentoModelo
                ->validar(
                    $id,
                    $observaciones
                );
    }
    public function obtenerPorId(int $id): ?DocumentoDTO 
    {
        $documento =
            $this->documentoModelo
                ->obtenerPorId($id);

        if (!$documento) {
            return null;
        }

        return DocumentoDTO::fromArray(
            $documento
        );
    }
    public function obtenerPorInscripcion(int $idInscripcion): array 
    {
        $documentos =
            $this->documentoModelo
                ->obtenerPorInscripcion(
                    $idInscripcion
                );

        $resultado = [];

        foreach ($documentos as $documento) {

            $resultado[] =
                DocumentoDTO::fromArray(
                    $documento
                );
        }

        return $resultado;
    }
    public function obtenerValidados(int $usuarioId): array
    {
        $documentos =
            $this->obtenerPorUsuario(
                $usuarioId
            );

        return array_filter(
            $documentos,
            fn (DocumentoDTO $doc)
                => $doc->estaAprobado()
        );
    }

    public function obtenerNoValidados(int $usuarioId): array
    {
        $documentos =
            $this->obtenerPorUsuario(
                $usuarioId
            );

        return array_filter(
            $documentos,
            fn (DocumentoDTO $doc)
                => !$doc->estaAprobado()
        );
    }



    public function obtenerDocumentos(int $idInscripcion): array 
    {
        $documentos =
            $this->documentoModelo
                ->obtenerPorInscripcion(
                    $idInscripcion
                );

        $resultado = [];

        foreach ($documentos as $documento) {

            $resultado[] =
                DocumentoDTO::fromArray(
                    $documento
                );
        }

        return $resultado;
    }
}