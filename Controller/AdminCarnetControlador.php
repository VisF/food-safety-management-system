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
     * Muestra el panel administrativo de carnets.
     */
    public function mostrarIndex(): void
    {
        try {

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
            * buscamos una inscripción aprobada
            * pendiente de emisión.
            */
            if ($busqueda !== '') {

                $resultado =
                    $this->carnetService
                        ->obtenerPendienteEmisionPorDni(
                            $busqueda
                        );
            }

            $pendientes =
                $this->carnetService
                    ->obtenerPendientesEmision();

            $carnets =
                $this->carnetService
                    ->listarActivosAdministracion();

            $data = [

                'page_title' =>
                    'Gestión de Carnets',

                'pendientes' =>
                    $pendientes,

                'carnets' =>
                    $carnets,

                'resultado' =>
                    $resultado,

                'busqueda' =>
                    $busqueda,

                'modo_carga' =>
                    false,

                'inscripcion' =>
                    null,

                'formulario' => [

                    'numero_carnet' =>
                        '',

                    'fecha_emision' =>
                        '',

                    'fecha_vencimiento' =>
                        ''
                ],

                'errores' =>
                    $errores
            ];

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
     */
    public function mostrarCarga(int $idInscripcion): void
    {
        try {

            $pendientes =
                $this->carnetService
                    ->obtenerPendientesEmision();

            $inscripcion = null;

            foreach (
                $pendientes
                as
                $pendiente
            ) {

                if (
                    (int)(
                        $pendiente['inscripcion_id']
                        ?? 0
                    ) === $idInscripcion
                ) {

                    $inscripcion =
                        $pendiente;

                    break;
                }
            }


            /*
            * Si no encontramos la inscripción entre
            * las pendientes, no permitimos la carga.
            */
            if ($inscripcion === null) {

                header(
                    'Location: /manipulacionDeAlimentos/admin/carnets?toast=inscripcion_no_disponible'
                );

                exit;
            }


            /*
            * Determinamos desde dónde se solicitó
            * la carga del carnet.
            *
            * - busqueda  → vino desde el buscador por DNI.
            * - pendientes → vino desde la lista de pendientes.
            */
            $origenCarga =
                trim(
                    $_GET['origen']
                    ?? 'pendientes'
                );

            if (
                !in_array(
                    $origenCarga,
                    [
                        'busqueda',
                        'pendientes'
                    ],
                    true
                )
            ) {

                $origenCarga =
                    'pendientes';
            }


            /*
            * Conservamos el DNI buscado cuando
            * la carga comenzó desde el buscador.
            */
            $busqueda =
                trim(
                    $_GET['dni']
                    ?? ''
                );


            /*
            * Si venimos desde el buscador, el resultado
            * debe seguir apareciendo en esa sección.
            */
            $resultado =
                null;

            if (
                $origenCarga === 'busqueda'
            ) {

                $resultado =
                    $inscripcion;
            }


            $data = [

                'page_title' =>
                    'Gestión de Carnets',

                'pendientes' =>
                    $pendientes,

                'carnets' =>
                    $this->carnetService
                        ->listarActivosAdministracion(),

                'resultado' =>
                    $resultado,

                'busqueda' =>
                    $busqueda,

                'modo_carga' =>
                    true,

                'origen_carga' =>
                    $origenCarga,

                'inscripcion' =>
                    $inscripcion,

                'formulario' => [

                    'numero_carnet' =>
                        '',

                    'fecha_emision' =>
                        '',

                    'fecha_vencimiento' =>
                        ''
                ],

                'errores' =>
                    []
            ];


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
                'Location: /manipulacionDeAlimentos/admin/carnets?toast=error_cargar_carnet'
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