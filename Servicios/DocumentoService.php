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
require_once __DIR__ . '/HabilitacionExamenService.php';


class DocumentoService
{
    private DocumentoRepository $documentoRepository;
    private HabilitacionExamenService $habilitacionService;
    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->documentoRepository = new DocumentoRepository();
        $this->habilitacionService = new HabilitacionExamenService();
    }

    // Obtiene por usuario.
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
    // Obtiene estado documentacion.
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





    // Obtiene por id.
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
   
    // Obtiene validados.
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

    // Obtiene no validados.
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


    // Lista documentos.
    public function listarDocumentos(): array
    {
        $documentos =
            $this->documentoRepository
                ->listarDocumentos();

        return [
            'documentos' => $documentos,
            'total' => count($documentos)
        ];
    }

    // Obtiene documento.
    public function obtenerDocumento(int $id): ?array
    {
        return
            $this->documentoRepository
                ->obtenerPorId($id);
    }
                    
    // Obtiene pendientes.
    public function obtenerPendientes(): array
    {
        $documentos =
            $this->documentoRepository
                ->obtenerPendientes();

        return [
            'documentos' => $documentos,
            'total' => count($documentos)
        ];
    }

    // Descarga documento.
    public function descargarDocumento(int $id): ?array
    {
        return
            $this->documentoRepository
                ->descargarDocumento($id);
    }

    // Elimina documento.
    public function eliminarDocumento(int $id): array
    {
        $documento =
            $this->documentoRepository
                ->obtenerPorId($id);

        if (!$documento) {

            return [
                'success' => false,
                'codigo' => 'DOCUMENTO_INEXISTENTE'
            ];
        }

        $ok =
            $this->documentoRepository
                ->eliminarDocumento($id);

        if (!$ok) {

            return [
                'success' => false,
                'codigo' => 'ERROR_ELIMINAR'
            ];
        }

        return [
            'success' => true
        ];
    }

    // Valida documento.
    public function validarDocumento(int $id,string $observaciones = ''): array
    {
        $documento =
            $this->documentoRepository
                ->obtenerPorId($id);

        if (!$documento) {

            return [
                'success' => false,
                'codigo' => 'DOCUMENTO_INEXISTENTE'
            ];
        }

        $ok =
            $this->documentoRepository
                ->validarDocumento(
                    $id,
                    $observaciones
                );

        if (!$ok) {

            return [
                'success' => false,
                'codigo' => 'ERROR_VALIDACION'
            ];
        }

        $tipoDocumento =
            strtoupper(
                $documento['tipo_documento'] ?? ''
            );

        if (
            $tipoDocumento === 'MOODLE'
            ||
            $tipoDocumento === 'CERTIFICADO_MOODLE'
        ) {

            $usuarioId =
                (int)$documento['usuario_id'];

            if (
                !$this->habilitacionService
                    ->tieneHabilitacionVigente(
                        $usuarioId
                    )
            ) {

                $this->habilitacionService
                    ->crear([
                        'usuario_id' => $usuarioId,
                        'curso_id' => null,
                        'fecha_habilitacion' => date('Y-m-d'),
                        'fecha_vencimiento' => date(
                            'Y-m-d',
                            strtotime('+6 months')
                        )
                    ]);
            }
        }

        return [
            'success' => true
        ];
    }

    // Rechaza documento.
    public function rechazarDocumento(int $id,string $observaciones = ''): array
    {
        $documento =
            $this->documentoRepository
                ->obtenerPorId($id);

        if (!$documento) {

            return [
                'success' => false,
                'codigo' => 'DOCUMENTO_INEXISTENTE'
            ];
        }

        $ok =
            $this->documentoRepository
                ->rechazarDocumento(
                    $id,
                    $observaciones
                );

        if (!$ok) {

            return [
                'success' => false,
                'codigo' => 'ERROR_RECHAZO'
            ];
        }

        return [
            'success' => true
        ];
    }

}
