<?php
declare(strict_types=1);

/**
 * UploadService
 *
 * Gestiona la lógica de negocio relacionada
 * con la carga y administración de archivos.
 */
class UploadService
{
    private const LOG_FILE =
        __DIR__ . '/../logs/upload_service.log';

    private const MAX_FILESIZE =
        5242880;

    private const MIN_FILESIZE =
        1;

    private const FORMATOS_PERMITIDOS = [
        'jpg',
        'jpeg',
        'png',
        'pdf'
    ];

    private const MIME_WHITELIST = [

        'jpg' => [
            'image/jpeg'
        ],

        'jpeg' => [
            'image/jpeg'
        ],

        'png' => [
            'image/png'
        ],

        'pdf' => [
            'application/pdf'
        ]
    ];

    private const AV_SOCKET =
        'unix:///var/run/clamav/clamd.ctl';

    private const CARPETA_UPLOADS =
        __DIR__ . '/../uploads';

    private const CARPETA_DOCUMENTOS =
        __DIR__ . '/../uploads/documentos';

    private const CARPETA_CARNETS =
        __DIR__ . '/../uploads/carnets';

    /**
     * Inicializa directorios.
     */
    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        @mkdir(
            self::CARPETA_DOCUMENTOS,
            0755,
            true
        );

        @mkdir(
            self::CARPETA_CARNETS,
            0755,
            true
        );
    }

    /**
     * Registra un evento.
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
            "[$timestamp] " .
            "Usuario: {$usuario} | " .
            "Evento: {$evento} | " .
            "Datos: " .
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            ) .
            PHP_EOL;

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }

    // =====================================================
    // PROCESAMIENTO
    // =====================================================

    /**
     * Procesa una carga completa.
     */
    public function procesarCarga(
        array $archivo,
        string $carpetaDestino,
        string $prefijo
    ): array
    {
        try {

            $validacion =
                $this->validarArchivo(
                    $archivo
                );

            if (!$validacion['success']) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' =>
                        $validacion['mensaje']
                ];
            }

            $nombre =
                $this->generarNombreArchivo(
                    $archivo['name'],
                    $prefijo
                );

            $guardar =
                $this->guardarArchivo(
                    $archivo,
                    $carpetaDestino,
                    $nombre
                );

            if (!$guardar['success']) {
                return $guardar;
            }

            return [
                'success' => true,
                'ruta' =>
                    $guardar['ruta'],
                'nombre' =>
                    $guardar['nombre'],
                'mensaje' =>
                    'Archivo cargado exitosamente'
            ];

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_PROCESAR_CARGA',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'ruta' => null,
                'nombre' => null,
                'mensaje' =>
                    $e->getMessage()
            ];
        }
    }
    // =====================================================
    // VALIDACIONES
    // =====================================================

    /**
     * Valida un archivo antes de almacenarlo.
     */
    public function validarArchivo(
        array $archivo
    ): array
    {
        if (
            !isset($archivo['error']) ||
            !isset($archivo['size']) ||
            !isset($archivo['name'])
        ) {
            return [
                'success' => false,
                'mensaje' =>
                    'Datos del archivo incompletos.'
            ];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {

            $errores = [

                UPLOAD_ERR_INI_SIZE =>
                    'El archivo supera el tamaño permitido.',

                UPLOAD_ERR_FORM_SIZE =>
                    'El archivo supera el tamaño permitido.',

                UPLOAD_ERR_PARTIAL =>
                    'La carga quedó incompleta.',

                UPLOAD_ERR_NO_FILE =>
                    'No se seleccionó ningún archivo.',

                UPLOAD_ERR_NO_TMP_DIR =>
                    'No existe carpeta temporal.',

                UPLOAD_ERR_CANT_WRITE =>
                    'No se pudo guardar el archivo.',

                UPLOAD_ERR_EXTENSION =>
                    'Extensión bloqueada.'
            ];

            return [
                'success' => false,
                'mensaje' =>
                    $errores[
                        $archivo['error']
                    ]
                    ??
                    'Error desconocido.'
            ];
        }

        $tamanio =
            (int)$archivo['size'];

        if (
            !$this->validarTamanoMaximo(
                $tamanio
            )
        ) {

            return [
                'success' => false,
                'mensaje' =>
                    'El archivo supera el tamaño permitido.'
            ];
        }

        if (
            $tamanio <
            self::MIN_FILESIZE
        ) {

            return [
                'success' => false,
                'mensaje' =>
                    'El archivo está vacío.'
            ];
        }

        $extension =
            strtolower(
                pathinfo(
                    $archivo['name'],
                    PATHINFO_EXTENSION
                )
            );

        if (
            !$this
                ->validarFormatosPermitidos(
                    $extension
                )
        ) {

            return [
                'success' => false,
                'mensaje' =>
                    'Formato no permitido.'
            ];
        }

        $tmp =
            $archivo['tmp_name']
            ?? '';

        if (
            empty($tmp)
            ||
            !file_exists($tmp)
        ) {

            return [
                'success' => false,
                'mensaje' =>
                    'Archivo temporal inexistente.'
            ];
        }

        $finfo =
            finfo_open(
                FILEINFO_MIME_TYPE
            );

        if ($finfo !== false) {

            $mime =
                finfo_file(
                    $finfo,
                    $tmp
                );

            finfo_close($finfo);

            if ($mime === false) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'No fue posible determinar el tipo MIME.'
                ];
            }

            $permitidos =
                self::MIME_WHITELIST[
                    $extension
                ]
                ?? [];

            if (
                !in_array(
                    $mime,
                    $permitidos,
                    true
                )
            ) {

                return [
                    'success' => false,
                    'mensaje' =>
                        'El contenido del archivo no coincide con su extensión.'
                ];
            }

            if (
                in_array(
                    $extension,
                    [
                        'jpg',
                        'jpeg',
                        'png'
                    ],
                    true
                )
            ) {

                $imagen =
                    @getimagesize(
                        $tmp
                    );

                if ($imagen === false) {

                    return [
                        'success' => false,
                        'mensaje' =>
                            'La imagen es inválida.'
                    ];
                }
            }
        }

        $antivirus =
            $this
                ->escanearConClamAV(
                    $tmp
                );

        if (
            !$antivirus['success']
        ) {

            return $antivirus;
        }

        return [
            'success' => true,
            'mensaje' =>
                'Archivo válido.'
        ];
    }

    /**
     * Verifica si el formato está permitido.
     */
    public function validarFormatosPermitidos(
        string $extension
    ): bool
    {
        return in_array(
            strtolower($extension),
            self::FORMATOS_PERMITIDOS,
            true
        );
    }

    /**
     * Verifica el tamaño máximo permitido.
     */
    public function validarTamanoMaximo(
        int $tamanio
    ): bool
    {
        return
            $tamanio >= self::MIN_FILESIZE
            &&
            $tamanio <= self::MAX_FILESIZE;
    }


    // =====================================================
    // GESTIÓN DE ARCHIVOS
    // =====================================================

    /**
     * Guarda un archivo en el servidor.
     */
    public function guardarArchivo(
        array $archivo,
        string $carpeta,
        ?string $nombreDeseado = null
    ): array
    {
        try {

            if (!is_dir($carpeta)) {

                if (
                    !mkdir(
                        $carpeta,
                        0755,
                        true
                    )
                ) {

                    return [
                        'success' => false,
                        'ruta' => null,
                        'nombre' => null,
                        'mensaje' =>
                            'No se pudo crear la carpeta.'
                    ];
                }
            }

            if (!is_writable($carpeta)) {

                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' =>
                        'La carpeta no tiene permisos de escritura.'
                ];
            }

            $extension =
                strtolower(
                    pathinfo(
                        $archivo['name'],
                        PATHINFO_EXTENSION
                    )
                );

            if ($nombreDeseado !== null) {

                $nombre =
                    $nombreDeseado;

                if (
                    strtolower(
                        pathinfo(
                            $nombre,
                            PATHINFO_EXTENSION
                        )
                    ) !== $extension
                ) {

                    $nombre .=
                        '.' . $extension;
                }

            } else {

                $nombre =
                    uniqid(
                        'archivo_',
                        true
                    ) .
                    '.' .
                    $extension;
            }

            if (
                str_contains(
                    $nombre,
                    '..'
                )
            ) {

                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' =>
                        'Nombre de archivo inválido.'
                ];
            }

            $ruta =
                $carpeta .
                DIRECTORY_SEPARATOR .
                $nombre;

            if (
                !move_uploaded_file(
                    $archivo['tmp_name'],
                    $ruta
                )
            ) {

                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' =>
                        'No fue posible guardar el archivo.'
                ];
            }

            chmod(
                $ruta,
                0644
            );

            if (
                in_array(
                    $extension,
                    [
                        'jpg',
                        'jpeg',
                        'png'
                    ],
                    true
                )
            ) {

                $this
                    ->limpiarEXIFImagen(
                        $ruta,
                        $extension
                    );
            }

            $this->registrarLog(
                'ARCHIVO_GUARDADO',
                [
                    'archivo' =>
                        $nombre
                ]
            );

            return [
                'success' => true,
                'ruta' => $ruta,
                'nombre' => $nombre,
                'mensaje' =>
                    'Archivo guardado correctamente.'
            ];

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_GUARDAR_ARCHIVO',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'ruta' => null,
                'nombre' => null,
                'mensaje' =>
                    $e->getMessage()
            ];
        }
    }

    /**
     * Genera un nombre único.
     */
    public function generarNombreArchivo(
        string $nombreOriginal,
        string $prefijo
    ): string
    {
        $extension =
            strtolower(
                pathinfo(
                    $nombreOriginal,
                    PATHINFO_EXTENSION
                )
            );

        $nombre =
            pathinfo(
                $nombreOriginal,
                PATHINFO_FILENAME
            );

        $nombre =
            preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '_',
                $nombre
            );

        $nombre =
            substr(
                $nombre,
                0,
                20
            );

        return
            $prefijo .
            '_' .
            $nombre .
            '_' .
            time() .
            '_' .
            uniqid() .
            '.' .
            $extension;
    }

    /**
     * Elimina un archivo.
     */
    public function eliminarArchivo(
        string $ruta
    ): bool
    {
        try {

            if (
                !file_exists(
                    $ruta
                )
            ) {

                return false;
            }

            $rutaReal =
                realpath($ruta);

            $uploads =
                realpath(
                    self::CARPETA_UPLOADS
                );

            if (
                $rutaReal === false ||
                $uploads === false ||
                !str_starts_with(
                    $rutaReal,
                    $uploads
                )
            ) {

                return false;
            }

            if (
                unlink(
                    $ruta
                )
            ) {

                $this->registrarLog(
                    'ARCHIVO_ELIMINADO',
                    [
                        'ruta' =>
                            $ruta
                    ]
                );

                return true;
            }

            return false;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_ELIMINAR_ARCHIVO',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return false;
        }
    }

    /**
     * Obtiene información de un archivo.
     */
    public function obtenerInfoArchivo(
        string $ruta
    ): array
    {
        if (
            !file_exists(
                $ruta
            )
        ) {

            return [
                'existe' => false
            ];
        }

        return [

            'existe' =>
                true,

            'nombre' =>
                basename($ruta),

            'tamanio' =>
                filesize($ruta),

            'mime' =>
                mime_content_type($ruta),

            'fecha_modificacion' =>
                date(
                    'Y-m-d H:i:s',
                    filemtime($ruta)
                ),

            'hash_sha256' =>
                hash_file(
                    'sha256',
                    $ruta
                )
        ];
    }


    // =====================================================
    // SEGURIDAD
    // =====================================================

    /**
     * Escanea un archivo utilizando ClamAV.
     */
    private function escanearConClamAV(
        string $rutaArchivo
    ): array
    {
        if (
            !function_exists(
                'stream_socket_client'
            )
        ) {

            return [
                'success' => true,
                'mensaje' => '',
                'amenaza' => null
            ];
        }

        $socket =
            @stream_socket_client(
                self::AV_SOCKET,
                $errno,
                $errstr,
                2
            );

        if ($socket === false) {

            $this->registrarLog(
                'CLAMAV_NO_DISPONIBLE',
                [
                    'error' =>
                        $errstr
                ]
            );

            return [
                'success' => true,
                'mensaje' => '',
                'amenaza' => null
            ];
        }

        try {

            $comando =
                'SCAN ' .
                escapeshellarg(
                    $rutaArchivo
                ) .
                "\r\n";

            fwrite(
                $socket,
                $comando
            );

            $respuesta =
                fgets(
                    $socket,
                    1024
                );

            fclose($socket);

            if ($respuesta === false) {

                return [
                    'success' => true,
                    'mensaje' => '',
                    'amenaza' => null
                ];
            }

            if (
                stripos(
                    $respuesta,
                    'FOUND'
                ) !== false
            ) {

                preg_match(
                    '/(.+)\s+FOUND/',
                    $respuesta,
                    $coincidencias
                );

                return [

                    'success' => false,

                    'mensaje' =>
                        'Se detectó malware en el archivo.',

                    'amenaza' =>
                        $coincidencias[1]
                        ??
                        'Desconocida'
                ];
            }

            return [

                'success' => true,

                'mensaje' => '',

                'amenaza' => null
            ];

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_CLAMAV',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [

                'success' => true,

                'mensaje' => '',

                'amenaza' => null
            ];
        }
    }

    /**
     * Elimina metadata EXIF de imágenes.
     */
    private function limpiarEXIFImagen(
        string $rutaImagen,
        string $extension
    ): bool
    {
        try {

            if (
                $extension === 'png'
                &&
                extension_loaded(
                    'imagick'
                )
            ) {

                $imagen =
                    new Imagick(
                        $rutaImagen
                    );

                $imagen->stripImage();

                $imagen->writeImage(
                    $rutaImagen
                );

                return true;
            }

            if (

                in_array(
                    $extension,
                    [
                        'jpg',
                        'jpeg'
                    ],
                    true
                )

                &&

                extension_loaded(
                    'gd'
                )

            ) {

                $imagen =
                    imagecreatefromjpeg(
                        $rutaImagen
                    );

                if ($imagen !== false) {

                    imagejpeg(
                        $imagen,
                        $rutaImagen,
                        90
                    );

                    imagedestroy(
                        $imagen
                    );

                    return true;
                }
            }

            return false;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_LIMPIAR_EXIF',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return false;
        }
    }
}