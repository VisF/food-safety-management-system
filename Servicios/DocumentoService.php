<?php

/*
obtenerEstadoDocumentacion()

obtenerValidados()

obtenerNoValidados()

subirDocumento()

validarDocumento()

rechazarDocumento()


*/

require_once __DIR__ . '/../dto/DocumentoDTO.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';

class DocumentoService
{
    private DocumentoRepository $documentoRepository;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->documentoRepository = new DocumentoRepository();

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

            switch (strtolower($doc['tipo_documento'])) {
                    case 'dni':
                        $tieneDni = true;
                        break;

                    case 'foto':
                    case 'foto_carnet':
                        $tieneFoto = true;
                        break;

                    case 'asistencia':
                        $tieneAsistencia = true;
                        break;

                    case 'moodle':
                    case 'certificado_moodle':
                        $tieneMoodle = true;
                        break;
                }
        }

        $completos = 0;
        $total = 3;

        if ($tieneDni) $completos++;
        if ($tieneFoto) $completos++;
        if ($tieneAsistencia || $tieneMoodle) $completos++;

        return [
            'completos' => $completos,
            'total' => $total,
            'porcentaje' => (int)(($completos / $total) * 100),
            'completo' => ($completos === $total),

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

    /**
     * Busca la documentación de un ciudadano por DNI.
     */
    public function buscarDocumentacionPorDni(string $dni): array {

        $dni = trim($dni);

        if ($dni === '') {
            return [];
        }

        return $this->obtenerDocumentacionAdministracion(
            $dni
        );
    }

   /**
     * Obtiene la documentación agrupada por ciudadano
     * para el panel administrativo.
     *
     * Sin DNI:
     * - Obtiene ciudadanos con al menos un documento pendiente.
     * - Aplica paginación.
     *
     * Con DNI:
     * - Obtiene únicamente la documentación
     *   del ciudadano buscado.
     * - No aplica paginación.
     *
     * @param string|null $dni
     * @param int $pagina
     * @param int $limite
     * @return array
     */
    public function obtenerDocumentacionAdministracion(?string $dni = null,int $pagina = 1,int $limite = 10): array {

        /*
        * Normalizamos los valores de paginación.
        */
        $pagina =
            max(
                1,
                $pagina
            );

        $limite =
            max(
                1,
                $limite
            );


        /*
        * ==================================================
        * BÚSQUEDA POR DNI
        * ==================================================
        *
        * La búsqueda devuelve un único ciudadano.
        *
        * No necesitamos paginación.
        */
        if (
            $dni !== null
            && trim($dni) !== ''
        ) {

            $documentos =
                $this->documentoRepository
                    ->obtenerDocumentacionAdministracion(
                        trim($dni)
                    );


            $usuarios =
                $this->agruparDocumentacion(
                    $documentos
                );


            return [
                'usuarios' =>
                    $usuarios,

                'total' =>
                    count($usuarios),

                'pagina' =>
                    1,

                'limite' =>
                    $limite,

                'total_paginas' =>
                    count($usuarios) > 0
                        ? 1
                        : 0,

                'tiene_anterior' =>
                    false,

                'tiene_siguiente' =>
                    false,

                'busqueda' =>
                    trim($dni)
            ];
        }


        /*
        * ==================================================
        * LISTADO GENERAL
        * ==================================================
        */

        $total =
            $this->documentoRepository
                ->contarCiudadanosDocumentacionAdministracion();


        /*
        * Calculamos la cantidad de páginas.
        */
        $totalPaginas =
            $total > 0
                ? (int)ceil(
                    $total / $limite
                )
                : 0;


        /*
        * Si la página solicitada supera
        * la cantidad disponible, utilizamos
        * la última página válida.
        */
        if (
            $totalPaginas > 0
            && $pagina > $totalPaginas
        ) {

            $pagina =
                $totalPaginas;
        }


        /*
        * Calculamos el offset.
        */
        $offset =
            ($pagina - 1) * $limite;


        /*
        * Obtenemos los documentos correspondientes
        * a los ciudadanos de esta página.
        */
        $documentos =
            $this->documentoRepository
                ->obtenerDocumentacionAdministracion(
                    null,
                    $limite,
                    $offset
                );


        /*
        * Agrupamos los documentos por ciudadano.
        */
        $usuarios =
            $this->agruparDocumentacion(
                $documentos
            );


        return [

            'usuarios' =>
                $usuarios,

            'total' =>
                $total,

            'pagina' =>
                $pagina,

            'limite' =>
                $limite,

            'total_paginas' =>
                $totalPaginas,

            'tiene_anterior' =>
                $pagina > 1,

            'tiene_siguiente' =>
                $pagina < $totalPaginas,

            'busqueda' =>
                null
        ];
    }

    /**
     * Agrupa los documentos por ciudadano.
     *
     * @param array $documentos
     * @return array
     */
    private function agruparDocumentacion(array $documentos): array {

        $usuarios = [];


        foreach (
            $documentos
            as $documento
        ) {

            $usuarioId =
                (int)$documento['usuario_id'];


            if (
                !isset(
                    $usuarios[$usuarioId]
                )
            ) {

                $usuarios[$usuarioId] = [

                    'usuario_id' =>
                        $usuarioId,

                    'nombre' =>
                        $documento['nombre'],

                    'apellido' =>
                        $documento['apellido'],

                    'dni' =>
                        $documento['dni'],

                    'email' =>
                        $documento['email'],

                    'telefono' =>
                        $documento['telefono'],

                    'domicilio' =>
                        $documento['domicilio'],

                    'documentos' =>
                        []
                ];
            }


            $usuarios[$usuarioId]['documentos'][] = [

                'id' =>
                    (int)$documento['id'],

                'tipo_documento' =>
                    $documento['tipo_documento'],

                'nombre_original' =>
                    $documento['nombre_original'],

                'ruta_archivo' =>
                    $documento['ruta_archivo'],

                'estado' =>
                    $documento['estado'],

                'observaciones' =>
                    $documento['observaciones'],

                'fecha_subida' =>
                    $documento['fecha_subida'],

                'fecha_revision' =>
                    $documento['fecha_revision']
            ];
        }


        return array_values(
            $usuarios
        );
    }

}
