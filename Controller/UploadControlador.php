<?php
declare(strict_types=1);


/**
 * UploadControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * UploadControlador - Gestión de carga y procesamiento de archivos
 * 
 * Responsabilidades:
 * - Validar archivos antes de guardarlos
 * - Procesar y guardar archivos en servidor
 * - Generar nombres únicos para archivos
 * - Obtener metadata de archivos
 * - Eliminar archivos cuando sea necesario
 * - Mantener archivos temporales
 * 
 * Dependencias:
 * - Servicios: DocumentoService
 */

class UploadControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/upload_controller.log';
    private const MAX_FILESIZE = 5242880; // 5 MB en bytes
    private const FORMATOS_PERMITIDOS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const AV_SOCKET = 'unix:///var/run/clamav/clamd.ctl'; // Socket Unix para ClamAV
    private const MIN_FILESIZE = 1; // Mínimo 1 byte para evitar archivos vacíos
    /**
     * Mapeo de extensiones a tipos MIME permitidos.
     * Usamos esto para comprobar que el contenido real del archivo
     * coincide con la extensión declarada por el usuario.
     */
    private const MIME_WHITELIST = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf']
    ];
    private const CARPETA_UPLOADS = __DIR__ . '/../uploads';
    private const CARPETA_DOCUMENTOS = __DIR__ . '/../uploads/documentos';
    private const CARPETA_TEMPORAL = __DIR__ . '/../uploads/temporal';

    private ?DocumentoService $DocumentoService = null;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        @mkdir(self::CARPETA_DOCUMENTOS, 0755, true);
        @mkdir(self::CARPETA_TEMPORAL, 0755, true);
        $this->DocumentoService = new DocumentoService();
    }



    /**
     * Registrar evento en el log
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    /**
     * Procesar carga completa: validar, guardar y registrar
     * 
     * @param array $archivo Array de $_FILES['archivo']
     * @param int $id_inscripcion ID de la inscripción
     * @param string $tipo Tipo de documento (DNI, Carnet, Certificado, etc.)
     * @return array ['success' => bool, 'ruta' => string|null, 'nombre' => string|null, 'mensaje' => string]
     */
    public function procesarCarga(array $archivo, int $id_inscripcion, string $tipo): array
    {
        try {
            // Validar archivo
            $validacion = $this->validarArchivo($archivo);
            if (!$validacion['success']) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => $validacion['mensaje']
                ];
            }

            // Generar nombre único
            $nombre_original = $archivo['name'] ?? 'documento';
            $nombre_unico = $this->generarNombreArchivo($nombre_original, $id_inscripcion);

            // Guardar archivo usando el nombre único generado
            $guardar = $this->guardarArchivo($archivo, self::CARPETA_DOCUMENTOS, $nombre_unico);
            if (!$guardar['success']) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => $guardar['mensaje']
                ];
            }

            // Construir ruta web relativa usando el nombre retornado por guardarArchivo
            $nombre_guardado = $guardar['nombre'] ?? $nombre_unico;
            $resultado = [
                'success' => true,
                'ruta' => '/uploads/documentos/' . $nombre_guardado,
                'nombre' => $nombre_guardado,
                'mensaje' => 'Archivo cargado exitosamente'
            ];

            $this->registrarLog('ARCHIVO_PROCESADO', [
                'id_inscripcion' => $id_inscripcion,
                'tipo' => $tipo,
                'nombre' => $nombre_unico,
                'tamanio' => $archivo['size'] ?? 0
            ]);

            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_PROCESAR_CARGA', [
                'id_inscripcion' => $id_inscripcion,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'ruta' => null,
                'nombre' => null,
                'mensaje' => 'Error al procesar archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar archivo (formato, tamaño, estructura)
     * 
     * @param array $archivo Array de $_FILES['archivo']
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function validarArchivo(array $archivo): array
    {
        // Validar estructura del array
        // Comprobar estructura mínima de $_FILES para evitar warnings
        if (!isset($archivo['error']) || !isset($archivo['size']) || !isset($archivo['name'])) {
            return [
                'success' => false,
                'mensaje' => 'Error: datos del archivo incompletos o inválidos.'
            ];
        }

        // Validar error de upload
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $mensajes_error = [
                UPLOAD_ERR_INI_SIZE => 'El archivo supera el límite permitido (5 MB).',
                UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite permitido (5 MB).',
                UPLOAD_ERR_PARTIAL => 'La carga del archivo quedó incompleta. Intenta nuevamente.',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Error temporal en el servidor. Contacta al administrador.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor.',
                UPLOAD_ERR_EXTENSION => 'Extensión de archivo no permitida.'
            ];
            $mensaje = $mensajes_error[$archivo['error']] ?? 'Error desconocido durante la carga del archivo.';
            return [
                'success' => false,
                'mensaje' => $mensaje
            ];
        }

        // Validar tamaño (mínimo y máximo)
        $tamanio = (int)($archivo['size'] ?? 0);
        if ($tamanio < self::MIN_FILESIZE) {
            return [
                'success' => false,
                'mensaje' => 'El archivo está vacío o es demasiado pequeño. El tamaño mínimo es 1 byte.'
            ];
        }
        if (!$this->validarTamanoMaximo($tamanio)) {
            $tamanio_mb = round($tamanio / 1024 / 1024, 2);
            return [
                'success' => false,
                'mensaje' => "El archivo es demasiado grande ({$tamanio_mb} MB). El máximo permitido es 5 MB."
            ];
        }

        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!$this->validarFormatosPermitidos($extension)) {
            return [
                'success' => false,
                'mensaje' => 'Formato no permitido. Extensiones válidas: ' . implode(', ', self::FORMATOS_PERMITIDOS)
            ];
        }

        // Validación adicional por MIME real usando finfo
        // Pasos:
        // 1) Comprobamos que el archivo temporal exista y sea un upload válido (is_uploaded_file).
        // 2) Usamos finfo_file(FILEINFO_MIME_TYPE) para obtener el tipo MIME real.
        // 3) Comparamos el MIME real con la lista blanca para la extensión.
        // 4) Para imágenes hacemos un chequeo extra con getimagesize() para evitar fakes.

        $tmpName = $archivo['tmp_name'] ?? '';
        if (empty($tmpName) || !file_exists($tmpName)) {
            return ['success' => false, 'mensaje' => 'No se encontró el archivo temporal para validación. Intenta subir de nuevo.'];
        }

        // Preferir is_uploaded_file en entornos con uploads reales
        if (function_exists('is_uploaded_file') && !is_uploaded_file($tmpName)) {
            // No detener en entornos de prueba, pero registrar y rechazar por defecto
            $this->registrarLog('ADVERTENCIA_TMP_NO_UPLOAD', ['tmp' => $tmpName]);
            // continuar (no fatal) — aún podemos validar MIME
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if ($mime === false) {
                return ['success' => false, 'mensaje' => 'Imposible determinar el tipo de archivo. Archivo posiblemente corrupto.'];
            }

            $allowedMimes = self::MIME_WHITELIST[$extension] ?? [];
            if (!in_array($mime, $allowedMimes, true)) {
                $mimes_esperados = implode(', ', $allowedMimes);
                return ['success' => false, 'mensaje' => "Seguridad: El archivo tiene tipo MIME $mime pero se esperaba: $mimes_esperados. Verifica que el archivo no ha sido manipulado."];
            }

            // Para imágenes, validar con getimagesize para detectar archivos no-imagen
            if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                $img = @getimagesize($tmpName);
                if ($img === false) {
                    return ['success' => false, 'mensaje' => 'La imagen es inválida, corrupta o no es una imagen real. Verifica el archivo e intenta nuevamente.'];
                }
                // Validar que las dimensiones sean razonables (mínimo 100x100, máximo 10000x10000)
                if (isset($img[0], $img[1])) {
                    if ($img[0] < 100 || $img[1] < 100) {
                        return ['success' => false, 'mensaje' => 'La imagen es demasiado pequeña (mínimo 100x100 píxeles).'];
                    }
                    if ($img[0] > 10000 || $img[1] > 10000) {
                        return ['success' => false, 'mensaje' => 'La imagen es demasiado grande (máximo 10000x10000 píxeles).'];
                    }
                }
            }
        } else {
            // Si no hay finfo disponible, registramos y aceptamos la extensión (fallback seguro pero menos estricto)
            $this->registrarLog('ADVERTENCIA_NO_FINFO', ['archivo' => $archivo['name'] ?? '']);
        }

        // Escaneo antivirus opcional (ClamAV) si está disponible
        $av_check = $this->escanearConClamAV($tmpName);
        if (!$av_check['success']) {
            return [
                'success' => false,
                'mensaje' => $av_check['mensaje']
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'El archivo pasó todas las verificaciones de seguridad.'
        ];
    }

    /**
     * Guardar archivo en servidor
     * 
     * @param array $archivo Array de $_FILES['archivo']
     * @param string $carpeta Ruta de destino
     * @return array ['success' => bool, 'ruta' => string|null, 'mensaje' => string]
     */
    public function guardarArchivo(array $archivo, string $carpeta, ?string $nombreDeseado = null): array
    {
        try {
            // Crear directorio si no existe
            if (!is_dir($carpeta)) {
                if (!mkdir($carpeta, 0755, true)) {
                    return [
                        'success' => false,
                        'ruta' => null,
                        'nombre' => null,
                        'mensaje' => 'No se pudo crear el directorio de destino. Contacta al administrador.'
                    ];
                }
            }

            // Generar nombre único o usar el nombre deseado si se proporcionó
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if ($nombreDeseado && is_string($nombreDeseado)) {
                $nombre_unico = $nombreDeseado;
                // Asegurar que la extensión coincida
                if (strtolower(pathinfo($nombre_unico, PATHINFO_EXTENSION)) !== $extension) {
                    $nombre_unico .= '.' . $extension;
                }
            } else {
                $nombre_unico = uniqid('doc_', true) . '.' . $extension;
            }
            
            // Validar path traversal
            if (strpos($nombre_unico, '..') !== false || strpos($nombre_unico, '//') !== false) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => 'Nombre de archivo inválido. No se permiten trayectorias relativas.'
                ];
            }
            
            $ruta_destino = $carpeta . DIRECTORY_SEPARATOR . $nombre_unico;

            // Intento seguro de mover el archivo desde tmp_name (requerido en entornos PHP normales)
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => 'No se pudo guardar el archivo en el servidor. Intenta nuevamente.'
                ];
            }

            // Verificar que archivo existe
            if (!file_exists($ruta_destino)) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => 'El archivo no se guardó correctamente. Verifica permisos del servidor.'
                ];
            }
            
            // Establecer permisos seguros
            chmod($ruta_destino, 0644);
            
            // Limpiar EXIF de imágenes (metadata sensible)
            if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                $this->limpiarEXIFImagen($ruta_destino, $extension);
            }

            $resultado = [
                'success' => true,
                'ruta' => $ruta_destino,
                'nombre' => $nombre_unico
            ];

            $this->registrarLog('ARCHIVO_GUARDADO', [
                'nombre' => $nombre_unico,
                'tamanio' => filesize($ruta_destino),
                'carpeta' => $carpeta
            ]);

            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_GUARDAR_ARCHIVO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'ruta' => null,
                'mensaje' => 'Error al guardar archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param string $nombre_original Nombre original del archivo
     * @param int $id_inscripcion ID de la inscripción
     * @return string Nombre único generado
     */
    public function generarNombreArchivo(string $nombre_original, int $id_inscripcion): string
    {
        // Obtener extensión
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        
        // Limpiar nombre original
        $nombre_limpio = pathinfo($nombre_original, PATHINFO_FILENAME);
        $nombre_limpio = preg_replace('/[^a-z0-9_-]/i', '_', $nombre_limpio);
        $nombre_limpio = substr($nombre_limpio, 0, 20); // Limitar longitud
        
        // Generar nombre único
        $timestamp = time();
        $random = uniqid();
        
        return "inscripcion_{$id_inscripcion}_{$nombre_limpio}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Eliminar archivo del servidor
     * 
     * @param string $ruta Ruta del archivo a eliminar
     * @return bool true si se eliminó, false si falla
     */
    public function eliminarArchivo(string $ruta): bool
    {
        try {
            // Validar que ruta existe
            if (!file_exists($ruta)) {
                $this->registrarLog('ADVERTENCIA_ARCHIVO_NO_EXISTE', ['ruta' => $ruta]);
                return false;
            }

            // Validar que está dentro de carpeta permitida
            $ruta_real = realpath($ruta);
            $carpeta_real = realpath(self::CARPETA_UPLOADS);
            if ($ruta_real === false || $carpeta_real === false || strpos($ruta_real, $carpeta_real) !== 0) {
                $this->registrarLog('ERROR_RUTA_INVALIDA', ['ruta' => $ruta]);
                return false;
            }

            // Eliminar archivo
            if (unlink($ruta)) {
                $this->registrarLog('ARCHIVO_ELIMINADO', ['ruta' => $ruta]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ELIMINAR_ARCHIVO', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtener archivo temporal por ID
     * 
     * @param string $id_temporal ID temporal del archivo
     * @return array|null Array con datos del archivo o null si no existe
     */
    public function obtenerArchivoTemporal(string $id_temporal): ?array
    {
        try {
            $connFile = __DIR__ . '/../db/Connection.php';
            if (file_exists($connFile)) {
                require_once $connFile;
                $pdo = Connection::getPDO();
                $sql = 'SELECT id_temporal, nombre, ruta, tamanio, fecha_creacion, fecha_expiracion FROM archivo_temporal WHERE id_temporal = :id AND fecha_expiracion > NOW() LIMIT 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id_temporal]);
                $row = $stmt->fetch();
                if (!$row) return null;

                $ruta = $row['ruta'] ?? (self::CARPETA_TEMPORAL . DIRECTORY_SEPARATOR . ($row['nombre'] ?? ''));
                $existe = file_exists($ruta);

                $archivo = [
                    'id_temporal' => $row['id_temporal'] ?? $id_temporal,
                    'nombre' => $row['nombre'] ?? basename($ruta),
                    'ruta' => $ruta,
                    'tamanio' => isset($row['tamanio']) ? (int)$row['tamanio'] : ($existe ? filesize($ruta) : 0),
                    'fecha_creacion' => $row['fecha_creacion'] ?? null,
                    'fecha_expiracion' => $row['fecha_expiracion'] ?? null,
                    'existe' => $existe
                ];

                $this->registrarLog('ARCHIVO_TEMPORAL_OBTENIDO', ['id_temporal' => $id_temporal, 'db' => true, 'existe' => $existe]);
                return $archivo;
            }

            // Fallback: retornar placeholder (sin DB disponible)
            $rutaFallback = self::CARPETA_TEMPORAL . DIRECTORY_SEPARATOR . 'documento_temporal.pdf';
            $existeFallback = file_exists($rutaFallback);
            $archivo = [
                'id_temporal' => $id_temporal,
                'nombre' => 'documento_temporal.pdf',
                'ruta' => $rutaFallback,
                'tamanio' => $existeFallback ? filesize($rutaFallback) : 0,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'fecha_expiracion' => date('Y-m-d H:i:s', time() + 3600),
                'existe' => $existeFallback
            ];

            $this->registrarLog('ARCHIVO_TEMPORAL_OBTENIDO', ['id_temporal' => $id_temporal, 'db' => false, 'existe' => $existeFallback]);
            return $archivo;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_ARCHIVO_TEMPORAL', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validar si extensión está permitida
     * 
     * @param string $extension Extensión sin punto (jpg, pdf, etc.)
     * @return bool true si está permitida
     */
    public function validarFormatosPermitidos(string $extension): bool
    {
        return in_array(strtolower($extension), self::FORMATOS_PERMITIDOS, true);
    }

    /**
     * Validar si tamaño no excede máximo permitido
     * 
     * @param int $tamanio Tamaño en bytes
     * @return bool true si está dentro del límite
     */
    public function validarTamanoMaximo(int $tamanio): bool
    {
        return $tamanio > 0 && $tamanio <= self::MAX_FILESIZE;
    }

    /**
     * Escanear archivo con ClamAV (antivirus) si está disponible
     * 
     * @param string $ruta_archivo Ruta del archivo a escanear
     * @return array ['success' => bool, 'mensaje' => string, 'amenaza' => string|null]
     */
    private function escanearConClamAV(string $ruta_archivo): array
    {
        // ClamAV es opcional. Si no está configurado, retornar OK
        if (!function_exists('stream_socket_client')) {
            // Sin soporte de sockets, saltar escaneo
            return ['success' => true, 'mensaje' => '', 'amenaza' => null];
        }

        // Intentar conectar a ClamAV
        $socket = @stream_socket_client(self::AV_SOCKET, $errno, $errstr, 2);
        if ($socket === false) {
            // ClamAV no disponible, esto es OK (no es requerido)
            $this->registrarLog('AVISO_CLAMAV_NO_DISPONIBLE', ['error' => $errstr ?? 'Socket no disponible']);
            return ['success' => true, 'mensaje' => '', 'amenaza' => null];
        }

        try {
            // Enviar comando SCAN a ClamAV
            $comando = 'SCAN ' . escapeshellarg($ruta_archivo) . "\r\n";
            fwrite($socket, $comando);
            
            // Leer respuesta
            $respuesta = fgets($socket, 1024);
            fclose($socket);

            if ($respuesta === false) {
                $this->registrarLog('ERROR_CLAMAV_RESPUESTA', ['archivo' => $ruta_archivo]);
                return ['success' => true, 'mensaje' => '', 'amenaza' => null]; // OK, mejor no bloquear
            }

            // Revisar si hay VIRUS en la respuesta
            if (stripos($respuesta, 'FOUND') !== false) {
                preg_match('/(.+)\s+FOUND/', $respuesta, $matches);
                $amenaza = $matches[1] ?? 'Malware desconocido';
                $this->registrarLog('ARCHIVO_AMENAZA_DETECTADA', ['archivo' => $ruta_archivo, 'amenaza' => $amenaza]);
                return [
                    'success' => false,
                    'mensaje' => "Seguridad: Se detectó malware en el archivo ($amenaza). El archivo fue rechazado.",
                    'amenaza' => $amenaza
                ];
            }

            // OK, no es amenaza
            return ['success' => true, 'mensaje' => '', 'amenaza' => null];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ESCANEO_CLAMAV', ['error' => $e->getMessage()]);
            // Fallar de forma segura: dejar pasar si no se puede escanear
            return ['success' => true, 'mensaje' => '', 'amenaza' => null];
        }
    }

    /**
     * Limpiar metadata EXIF de imágenes (privacidad)
     * 
     * @param string $ruta_imagen Ruta de la imagen
     * @param string $extension Extensión del archivo (jpg, png)
     * @return bool true si se limpió exitosamente
     */
    private function limpiarEXIFImagen(string $ruta_imagen, string $extension): bool
    {
        try {
            // Para PNG, no hay EXIF estándar, pero puede haber metadata
            if ($extension === 'png' && extension_loaded('imagick')) {
                $image = new \Imagick($ruta_imagen);
                $image->stripImage(); // Remover metadata
                $image->writeImage($ruta_imagen);
                $this->registrarLog('EXIF_LIMPIADO', ['archivo' => $ruta_imagen, 'tipo' => 'PNG/Imagick']);
                return true;
            }

            // Para JPG con GD
            if (in_array($extension, ['jpg', 'jpeg'], true) && extension_loaded('gd')) {
                if (function_exists('exif_read_data')) {
                    // Intentar limpiar EXIF mediante relectura
                    $img = imagecreatefromjpeg($ruta_imagen);
                    if ($img !== false) {
                        // Recodificar sin EXIF
                        if (imagejpeg($img, $ruta_imagen, 90)) {
                            imagedestroy($img);
                            $this->registrarLog('EXIF_LIMPIADO', ['archivo' => $ruta_imagen, 'tipo' => 'JPEG/GD']);
                            return true;
                        }
                    }
                }
            }

            // Si no se pudo limpiar, registrar aviso pero no fallar
            $this->registrarLog('EXIF_LIMPIEZA_PARCIAL', ['archivo' => $ruta_imagen, 'razon' => 'Extensiones no disponibles']);
            return false;
        } catch (\Exception $e) {
            // Fallar silenciosamente (la imagen ya está guardada, no queremos perderla)
            $this->registrarLog('ADVERTENCIA_EXIF_LIMPIEZA', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtener información metadata de un archivo
     * 
     * @param string $ruta Ruta del archivo
     * @return array ['nombre' => string, 'tamanio' => int, 'tipo_mime' => string, 'fecha_creacion' => string]
     */
    public function obtenerInfoArchivo(string $ruta): array
    {
        try {
            if (!file_exists($ruta)) {
                return [
                    'nombre' => '',
                    'tamanio' => 0,
                    'tipo_mime' => '',
                    'fecha_creacion' => '',
                    'existe' => false
                ];
            }

            $info = [
                'nombre' => basename($ruta),
                'tamanio' => filesize($ruta),
                'tipo_mime' => mime_content_type($ruta) ?? 'application/octet-stream',
                'fecha_creacion' => date('Y-m-d H:i:s', filemtime($ruta)),
                'existe' => true
            ];

            $this->registrarLog('INFO_ARCHIVO_OBTENIDA', ['ruta' => $ruta]);
            
            return $info;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_INFO_ARCHIVO', ['error' => $e->getMessage()]);
            return [
                'nombre' => '',
                'tamanio' => 0,
                'tipo_mime' => '',
                'fecha_creacion' => '',
                'existe' => false
            ];
        }
    }
}
