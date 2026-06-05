<?php

require_once __DIR__ . '/../modelo/DocumentoModelo.php';
require_once __DIR__ . '/../dto/DocumentoDTO.php';

class DocumentoService
{
    public function __construct(
        private DocumentoModelo $documentoModelo
    ) {
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
    public function obtenerValidados(int $idInscripcion): array 
    {
        $documentos =
            $this->obtenerPorInscripcion(
                $idInscripcion
            );

        return array_filter(
            $documentos,
            fn (DocumentoDTO $doc)
                => $doc->estaValidado()
        );
    }
    public function obtenerNoValidados(int $idInscripcion): array 
    {
        $documentos =
            $this->obtenerPorInscripcion(
                $idInscripcion
            );

        return array_filter(
            $documentos,
            fn (DocumentoDTO $doc)
                => !$doc->estaValidado()
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