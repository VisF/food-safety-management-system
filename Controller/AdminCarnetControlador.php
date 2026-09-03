<?php
declare(strict_types=1);

/**
 * AdminCarnetControlador
 *
 * Gestiona las operaciones administrativas
 * relacionadas con la emisión de carnets.
 */

class AdminCarnetControlador
{
    private CarnetService $carnetService;

    private const LOG_FILE =
        __DIR__ . '/../logs/admin_carnet_controller.log';

    /**
     * Constructor.
     */
    public function __construct()
    {
        require_once __DIR__ . '/../db/Connection.php';
        require_once __DIR__ . '/../Servicios/CarnetService.php';

        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->carnetService =
            new CarnetService();
    }

    /**
     * Registra un evento administrativo.
     */
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void
    {
        $timestamp =
            date('Y-m-d H:i:s');

        $usuario =
            $_SESSION['user_id']
            ?? 'anonimo';

        $mensaje =
            sprintf(
                "[%s] Usuario: %s | Evento: %s | Datos: %s\n",
                $timestamp,
                $usuario,
                $evento,
                json_encode(
                    $datos,
                    JSON_UNESCAPED_UNICODE
                )
            );

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }


    /**
     * Construye el rango de páginas que se mostrará
     * en la paginación administrativa.
     *
     * Mantiene siempre:
     * - Primera página.
     * - Última página.
     * - Dos páginas anteriores a la actual.
     * - Página actual.
     * - Dos páginas posteriores a la actual.
     *
     * Cuando existe un salto entre páginas,
     * agrega un separador "...".
     *
     * Ejemplo:
     *
     * 1 ... 11 12 13 14 15 ... 50
     *
     * @param int $pagina
     * @param int $totalPaginas
     * @return array
     */
    private function construirPaginacion(int $pagina,int $totalPaginas): array {

        if ($totalPaginas <= 0) {
            return [];
        }


        /*
        * Si la página solicitada está fuera
        * del rango válido, la ajustamos.
        */
        $pagina =
            max(
                1,
                min(
                    $pagina,
                    $totalPaginas
                )
            );


        /*
        * Si hay pocas páginas,
        * mostramos todas.
        */
        if ($totalPaginas <= 7) {

            return range(
                1,
                $totalPaginas
            );
        }


        /*
        * Dos páginas antes y dos después
        * de la página actual.
        */
        $inicio =
            max(
                2,
                $pagina - 2
            );

        $fin =
            min(
                $totalPaginas - 1,
                $pagina + 2
            );


        $paginas = [];


        /*
        * Primera página.
        */
        $paginas[] = 1;


        /*
        * Separador inicial.
        */
        if ($inicio > 2) {

            $paginas[] = '...';
        }


        /*
        * Páginas cercanas a la actual.
        */
        for (
            $i = $inicio;
            $i <= $fin;
            $i++
        ) {

            $paginas[] = $i;
        }


        /*
        * Separador final.
        */
        if (
            $fin < $totalPaginas - 1
        ) {

            $paginas[] = '...';
        }


        /*
        * Última página.
        */
        $paginas[] =
            $totalPaginas;


        return $paginas;
    }
    
    
    /**
     * Muestra el panel administrativo de carnets.
     *
     * Incluye:
     * - Búsqueda por DNI.
     * - Pendientes de emisión paginados.
     * - Carnets ya emitidos.
     * - Paginación inteligente.
     */
    public function mostrarIndex(): void
    {
        try {

            /*
            * ==============================================
            * BÚSQUEDA POR DNI
            * ==============================================
            */

            $busqueda =
                trim(
                    $_GET['dni'] ?? ''
                );

            $resultado =
                null;

            $errores =
                [];


            /*
            * Si se ingresó un DNI,
            * buscamos independientemente de la
            * paginación de pendientes.
            */
            if ($busqueda !== '') {

                $resultado =
                    $this->carnetService
                        ->obtenerPendienteEmisionPorDni(
                            $busqueda
                        );
            }


            /*
            * ==============================================
            * PAGINACIÓN DE PENDIENTES
            * ==============================================
            */

            $pagina =
                filter_input(
                    INPUT_GET,
                    'pagina',
                    FILTER_VALIDATE_INT
                );

            if (
                $pagina === false
                || $pagina === null
                || $pagina < 1
            ) {

                $pagina = 1;
            }


            $limite =
                filter_input(
                    INPUT_GET,
                    'limite',
                    FILTER_VALIDATE_INT
                );

            if (
                $limite === false
                || $limite === null
                || $limite < 1
            ) {

                $limite = 10;
            }


            /*
            * ==============================================
            * OBTENER PENDIENTES
            * ==============================================
            */

            $paginacion =
                $this->carnetService
                    ->obtenerPendientesEmision(
                        $pagina,
                        $limite
                    );


            /*
            * ==============================================
            * DATOS PRINCIPALES
            * ==============================================
            */

            $pendientes =
                $paginacion['pendientes']
                ?? [];

            $carnets =
                $this->carnetService
                    ->listarActivosAdministracion();


            /*
            * ==============================================
            * DATOS DE PAGINACIÓN
            * ==============================================
            */

            $paginaActual =
                (int)(
                    $paginacion['pagina']
                    ?? $pagina
                );

            $totalPaginas =
                (int)(
                    $paginacion['total_paginas']
                    ?? 1
                );


            /*
            * Construimos solamente las páginas
            * que deben aparecer visualmente.
            */
            $paginas =
                $this->construirPaginacion(
                    $paginaActual,
                    $totalPaginas
                );


            /*
            * ==============================================
            * DATOS PARA LA VISTA
            * ==============================================
            */

            $data = [

                'page_title' =>
                    'Gestión de Carnets',


                /*
                * Pendientes de la página actual.
                */
                'pendientes' =>
                    $pendientes,


                /*
                * Carnets ya emitidos.
                */
                'carnets' =>
                    $carnets,


                /*
                * Resultado de búsqueda por DNI.
                */
                'resultado' =>
                    $resultado,


                /*
                * DNI ingresado.
                */
                'busqueda' =>
                    $busqueda,


                /*
                * Paginación.
                */
                'pagina' =>
                    $paginaActual,

                'limite' =>
                    (int)(
                        $paginacion['limite']
                        ?? $limite
                    ),

                'total_pendientes' =>
                    (int)(
                        $paginacion['total']
                        ?? 0
                    ),

                'total_paginas' =>
                    $totalPaginas,

                'paginas' =>
                    $paginas,

                'tiene_anterior' =>
                    $paginacion['tiene_anterior']
                    ?? false,

                'tiene_siguiente' =>
                    $paginacion['tiene_siguiente']
                    ?? false,


                /*
                * Estado de carga.
                */
                'modo_carga' =>
                    false,

                'inscripcion' =>
                    null,


                /*
                * Formulario vacío.
                */
                'formulario' => [

                    'numero_carnet' =>
                        '',

                    'fecha_emision' =>
                        '',

                    'fecha_vencimiento' =>
                        ''
                ],


                /*
                * Errores.
                */
                'errores' =>
                    $errores
            ];


            /*
            * ==============================================
            * VISTA
            * ==============================================
            */

            require_once __DIR__ .
                '/../Views/admin_carnets.php';


            $vista =
                new AdminCarnetsVista();


            $vista->mostrar(
                $data
            );


        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_MOSTRAR_CARNETS',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );


            header(
                'Location: /manipulacionDeAlimentos/admin?toast=error_carnets'
            );

            exit;
        }
    }

   /**
     * Muestra el formulario para cargar un carnet.
     *
     * Mantiene el contexto desde el que se accedió:
     * - Búsqueda por DNI.
     * - Listado paginado de pendientes.
     */
    public function mostrarCarga(
        int $idInscripcion
    ): void
    {
        try {

            /*
            * ==============================================
            * CONTEXTO DE NAVEGACIÓN
            * ==============================================
            */

            $pagina =
                filter_input(
                    INPUT_GET,
                    'pagina',
                    FILTER_VALIDATE_INT
                );
            $limite =
                filter_input(
                    INPUT_GET,
                    'limite',
                    FILTER_VALIDATE_INT
                );

            if (
                $limite === false
                || $limite === null
                || $limite < 1
            ) {

                $limite = 10;
            }

            if (
                $pagina === false
                || $pagina === null
                || $pagina < 1
            ) {

                $pagina = 1;
            }


            $busqueda =
                trim(
                    $_GET['dni'] ?? ''
                );


            $origen =
                $_GET['origen']
                ?? 'pendientes';


            /*
            * ==============================================
            * BUSCAR LA INSCRIPCIÓN
            * ==============================================
            */

            $inscripcion = null;


            /*
            * Si venimos de una búsqueda por DNI,
            * usamos el resultado de esa búsqueda.
            */
            if (
                $origen === 'busqueda'
                && $busqueda !== ''
            ) {

                $resultado =
                    $this->carnetService
                        ->obtenerPendienteEmisionPorDni(
                            $busqueda
                        );


                if (
                    $resultado !== null
                    && !empty($resultado)
                    && (int)(
                        $resultado['inscripcion_id']
                        ?? $resultado['id']
                        ?? 0
                    ) === $idInscripcion
                ) {

                    $inscripcion =
                        $resultado;
                }
            }


            /*
            * Si no encontramos la inscripción mediante
            * la búsqueda, la obtenemos directamente por ID.
            */
            if ($inscripcion === null) {

                $inscripcion =
                    $this->carnetService
                        ->obtenerPendienteEmisionPorId(
                            $idInscripcion
                        );
            }


            /*
            * La inscripción ya no está disponible.
            */
            if ($inscripcion === null) {

                $url =
                    BASE_URL .
                    '/admin/carnets';


                $parametros = [];


                if ($pagina > 1) {

                    $parametros['pagina'] =
                        $pagina;
                }


                if ($busqueda !== '') {

                    $parametros['dni'] =
                        $busqueda;
                }


                $parametros['toast'] =
                    'inscripcion_no_disponible';


                header(
                    'Location: '
                    . $url
                    . '?'
                    . http_build_query(
                        $parametros
                    )
                );

                exit;
            }


            /*
            * ==============================================
            * RECARGAR PENDIENTES DE LA PÁGINA ACTUAL
            * ==============================================
            *
            * Esto es fundamental.
            *
            * El formulario de carga de los pendientes
            * se muestra dentro del foreach de la vista.
            *
            * Por eso necesitamos volver a cargar los
            * pendientes de la página desde la que hicimos
            * clic.
            */

            $paginacion =
                $this->carnetService
                    ->obtenerPendientesEmision(
                        $pagina,
                        $limite
                    );


            $pendientes =
                $paginacion['pendientes']
                ?? [];


            /*
            * ==============================================
            * DATOS PARA LA VISTA
            * ==============================================
            */

            $data = [

                'page_title' =>
                    'Cargar carnet',


                /*
                * Pendientes de la página actual.
                */
                'pendientes' =>
                    $pendientes,


                /*
                * Carnets ya emitidos.
                */
                'carnets' =>
                    $this->carnetService
                        ->listarActivosAdministracion(),


                /*
                * Resultado de búsqueda.
                */
                'resultado' =>
                    $origen === 'busqueda'
                        ? $inscripcion
                        : null,


                /*
                * DNI conservado.
                */
                'busqueda' =>
                    $busqueda,


                /*
                * Activamos modo carga.
                */
                'modo_carga' =>
                    true,


                /*
                * Inscripción seleccionada.
                */
                'inscripcion' =>
                    $inscripcion,


                /*
                * Datos de paginación.
                */
                'pagina' =>
                    $paginacion['pagina']
                    ?? $pagina,

                'limite' =>
                    $paginacion['limite']
                    ?? 10,

                'total_pendientes' =>
                    $paginacion['total']
                    ?? 0,

                'total_paginas' =>
                    $paginacion['total_paginas']
                    ?? 1,

                'tiene_anterior' =>
                    $paginacion['tiene_anterior']
                    ?? false,

                'tiene_siguiente' =>
                    $paginacion['tiene_siguiente']
                    ?? false,


                /*
                * Origen de la carga.
                *
                * La vista utiliza origen_carga.
                */
                'origen_carga' =>
                    $origen,


                /*
                * Formulario vacío.
                */
                'formulario' => [

                    'numero_carnet' =>
                        '',

                    'fecha_emision' =>
                        '',

                    'fecha_vencimiento' =>
                        ''
                ],


                /*
                * Errores.
                */
                'errores' =>
                    []
            ];


            /*
            * ==============================================
            * VISTA
            * ==============================================
            */

            require_once __DIR__ .
                '/../Views/admin_carnets.php';


            $vista =
                new AdminCarnetsVista();


            $vista->mostrar(
                $data
            );


        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_MOSTRAR_CARGA_CARNET',
                [
                    'id_inscripcion' =>
                        $idInscripcion,

                    'error' =>
                        $e->getMessage()
                ]
            );


            header(
                'Location: '
                . BASE_URL
                . '/admin/carnets'
                . '?toast=error_cargar_carnet'
            );

            exit;
        }
    }
    /**
     * Procesa la carga de un carnet.
     */
    public function emitirCarnet(
        int $idInscripcion
    ): void
    {
        $rutaFisica = null;

        try {

            $datos = [

                'numero_carnet' =>
                    trim(
                        $_POST['numero_carnet']
                        ?? ''
                    ),

                'fecha_emision' =>
                    trim(
                        $_POST['fecha_emision']
                        ?? ''
                    ),

                'fecha_vencimiento' =>
                    trim(
                        $_POST['fecha_vencimiento']
                        ?? ''
                    )
            ];

            /*
             * =====================================================
             * VALIDACIÓN DEL ARCHIVO
             * =====================================================
             */

            if (
                !isset($_FILES['carnet_pdf'])
            ) {

                throw new InvalidArgumentException(
                    'Debe seleccionar el PDF oficial del carnet.'
                );
            }

            $archivo =
                $_FILES['carnet_pdf'];

            if (
                !is_array($archivo)
            ) {

                throw new InvalidArgumentException(
                    'El archivo enviado no es válido.'
                );
            }

            /*
             * Error de subida.
             */
            if (
                $archivo['error']
                !== UPLOAD_ERR_OK
            ) {

                throw new InvalidArgumentException(
                    'No fue posible subir el PDF del carnet.'
                );
            }

            /*
             * Tamaño máximo:
             *
             * 1 MB
             */
            $tamanoMaximo =
                1024 * 1024;

            if (
                (int)$archivo['size']
                > $tamanoMaximo
            ) {

                throw new InvalidArgumentException(
                    'El PDF del carnet no puede superar 1 MB.'
                );
            }

            /*
             * Validar extensión.
             */
            $extension =
                strtolower(
                    pathinfo(
                        $archivo['name'],
                        PATHINFO_EXTENSION
                    )
                );

            if (
                $extension !== 'pdf'
            ) {

                throw new InvalidArgumentException(
                    'El archivo del carnet debe tener formato PDF.'
                );
            }

            /*
             * Validar MIME real.
             */
            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mime =
                $finfo->file(
                    $archivo['tmp_name']
                );

            if (
                $mime !== 'application/pdf'
            ) {

                throw new InvalidArgumentException(
                    'El archivo seleccionado no es un PDF válido.'
                );
            }

            /*
             * =====================================================
             * CREAR DIRECTORIO
             * =====================================================
             */

            $directorio =
                __DIR__
                . '/../uploads/carnets';

            if (
                !is_dir($directorio)
            ) {

                if (
                    !mkdir(
                        $directorio,
                        0755,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'No fue posible crear el directorio de carnets.'
                    );
                }
            }

            /*
             * =====================================================
             * NOMBRE SEGURO DEL ARCHIVO
             * =====================================================
             */

            $nombreArchivo =
                'carnet_'
                . $idInscripcion
                . '_'
                . bin2hex(
                    random_bytes(8)
                )
                . '.pdf';

            $rutaFisica =
                $directorio
                . DIRECTORY_SEPARATOR
                . $nombreArchivo;

            /*
             * Ruta relativa que se almacena
             * en la base de datos.
             */
            $rutaBD =
                'uploads/carnets/'
                . $nombreArchivo;

            /*
             * =====================================================
             * MOVER ARCHIVO AL HOSTING
             * =====================================================
             */

            if (
                !move_uploaded_file(
                    $archivo['tmp_name'],
                    $rutaFisica
                )
            ) {

                throw new RuntimeException(
                    'No fue posible guardar el PDF del carnet.'
                );
            }

            /*
             * Pasamos solamente la ruta al Service.
             */
            $datos['ruta_pdf'] =
                $rutaBD;

            /*
             * =====================================================
             * EMITIR CARNET
             * =====================================================
             */

            $resultado =
                $this->carnetService
                    ->emitirCarnet(
                        $idInscripcion,
                        $datos
                    );

            /*
             * El Service rechazó la operación.
             *
             * Como el archivo ya fue subido,
             * debemos eliminarlo para no dejar
             * archivos huérfanos.
             */
            if (
                empty(
                    $resultado['success']
                )
            ) {

                if (
                    $rutaFisica !== null
                    &&
                    is_file($rutaFisica)
                ) {

                    @unlink(
                        $rutaFisica
                    );
                }

                throw new InvalidArgumentException(
                    $resultado['mensaje']
                    ??
                    'No fue posible emitir el carnet.'
                );
            }

            /*
             * =====================================================
             * ÉXITO
             * =====================================================
             */

            $this->registrarLog(
                'CARNET_CARGADO',
                [
                    'id_inscripcion' =>
                        $idInscripcion,

                    'numero_carnet' =>
                        $datos['numero_carnet']
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/carnets?toast=carnet_emitido'
            );

            exit;

        } catch (
            InvalidArgumentException
            | RuntimeException $e
        ) {

            /*
             * Si hubo error después de subir
             * el archivo, eliminarlo.
             */
            if (
                $rutaFisica !== null
                &&
                is_file($rutaFisica)
            ) {

                @unlink(
                    $rutaFisica
                );
            }

            $this->registrarLog(
                'ERROR_CARGAR_CARNET',
                [
                    'id_inscripcion' =>
                        $idInscripcion,

                    'error' =>
                        $e->getMessage()
                ]
            );

            /*
             * Recuperar inscripción para
             * volver a mostrar el formulario.
             */
            $inscripcion =
                $this->obtenerPendiente(
                    $idInscripcion
                );

            require_once __DIR__ .
                '/../Views/admin_carnets.php';

            $vista =
                new AdminCarnetsVista();

            $vista->mostrar([

                'inscripcion' =>
                    $inscripcion,

                'errores' => [
                    $e->getMessage()
                ],

                'formulario' => [

                    'numero_carnet' =>
                        $_POST['numero_carnet']
                        ?? '',

                    'fecha_emision' =>
                        $_POST['fecha_emision']
                        ?? '',

                    'fecha_vencimiento' =>
                        $_POST['fecha_vencimiento']
                        ?? ''
                ]
            ]);

            return;

        } catch (Throwable $e) {

            /*
             * Limpiar archivo si fue creado.
             */
            if (
                $rutaFisica !== null
                &&
                is_file($rutaFisica)
            ) {

                @unlink(
                    $rutaFisica
                );
            }

            $this->registrarLog(
                'ERROR_CARGAR_CARNET',
                [
                    'id_inscripcion' =>
                        $idInscripcion,

                    'error' =>
                        $e->getMessage()
                ]
            );

            header(
                'Location: /manipulacionDeAlimentos/admin/carnets?toast=error_emitir_carnet'
            );

            exit;
        }
    }

    /**
     * Descarga el PDF oficial de un carnet.
     */
    public function descargarCarnet(int $idCarnet): void
    {
        try {

            if ($idCarnet <= 0) {

                http_response_code(404);

                exit(
                    'Carnet no encontrado.'
                );
            }

            $carnet =
                $this->carnetService
                    ->obtenerPorId(
                        $idCarnet
                    );

            if ($carnet === null) {

                http_response_code(404);

                exit(
                    'Carnet no encontrado.'
                );
            }

            $rutaPdf =
                trim(
                    (string)(
                        $carnet['ruta_pdf']
                        ?? ''
                    )
                );

            if ($rutaPdf === '') {

                http_response_code(404);

                exit(
                    'El carnet no tiene un PDF asociado.'
                );
            }

            /*
            * La ruta almacenada en BD es relativa:
            *
            * uploads/carnets/archivo.pdf
            *
            * La convertimos a una ruta física
            * dentro de la aplicación.
            */
            $rutaFisica =
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . str_replace(
                    [
                        '/',
                        '\\'
                    ],
                    DIRECTORY_SEPARATOR,
                    $rutaPdf
                );

            /*
            * Evitar que una ruta manipulada
            * salga del directorio de la aplicación.
            */
            $baseAplicacion =
                realpath(
                    dirname(__DIR__)
                );

            $archivoReal =
                realpath(
                    $rutaFisica
                );

            if (
                $baseAplicacion === false
                || $archivoReal === false
                || !is_file($archivoReal)
                || strpos(
                    $archivoReal,
                    $baseAplicacion
                        . DIRECTORY_SEPARATOR
                ) !== 0
            ) {

                http_response_code(404);

                exit(
                    'El archivo del carnet no está disponible.'
                );
            }

            /*
            * Nombre con el que se descarga.
            */
            $numeroCarnet =
                trim(
                    (string)(
                        $carnet['numero_carnet']
                        ?? $idCarnet
                    )
                );

            $nombreDescarga =
                'carnet_'
                . preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '_',
                    $numeroCarnet
                )
                . '.pdf';

            header(
                'Content-Type: application/pdf'
            );

            header(
                'Content-Disposition: inline; filename="'
                . $nombreDescarga
                . '"'
            );

            header(
                'Content-Length: '
                . (string)filesize($archivoReal)
            );

            header(
                'X-Content-Type-Options: nosniff'
            );

            readfile(
                $archivoReal
            );

            exit;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_DESCARGAR_CARNET',
                [
                    'id_carnet' =>
                        $idCarnet,

                    'error' =>
                        $e->getMessage()
                ]
            );

            http_response_code(500);

            exit(
                'No fue posible descargar el carnet.'
            );
        }
    }

    /**
     * Obtiene una inscripción pendiente de emisión
     * por su ID interno.
     */
    private function obtenerPendiente(
        int $idInscripcion
    ): ?array
    {
        $pendientes =
            $this->carnetService
                ->obtenerPendientesEmision();

        foreach (
            $pendientes
            as
            $pendiente
        ) {

            if (
                (int)$pendiente['inscripcion_id']
                === $idInscripcion
            ) {

                return $pendiente;
            }
        }

        return null;
    }
}