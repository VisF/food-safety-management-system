<?php

require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../dto/DocumentoDTO.php';


/*
obtenerEstadoDocumentacion()

obtenerValidados()

obtenerNoValidados()

subirDocumento()

validarDocumento()

rechazarDocumento()


*/

require_once __DIR__ . '/../Repository/DocumentoRepository.php';


class DocumentoService
{
    private DocumentoRepository $documentoRepository;
    public function __construct()
    {
        $this->documentoRepository = new DocumentoRepository();
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $documentos =
            $this->documentoRepository
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
            $this->documentoRepository
                ->rechazarDocumento(
                    $id,
                    $observaciones
                );
    }




    public function validarDocumento(int $id, string $observaciones = ''): bool 
    {
        return
            $this->documentoRepository
                ->validarDocumento(
                    $id,
                    $observaciones
                );
    }
    public function obtenerPorId(int $id): ?DocumentoDTO 
    {
        $documento =
            $this->documentoRepository
                ->obtenerPorId($id);

        if (!$documento) {
            return null;
        }

        return DocumentoDTO::fromArray(
            $documento
        );
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



}