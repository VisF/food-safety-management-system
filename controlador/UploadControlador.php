<?php
declare(strict_types=1);

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
 * - Modelos: DocumentoModelo
 */

class UploadControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/upload_controller.log';
    private const MAX_FILESIZE = 5242880; // 5 MB en bytes
    private const FORMATOS_PERMITIDOS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const CARPETA_UPLOADS = __DIR__ . '/../uploads';
    private const CARPETA_DOCUMENTOS = __DIR__ . '/../uploads/documentos';
    private const CARPETA_TEMPORAL = __DIR__ . '/../uploads/temporal';

    private ?DocumentoModelo $documentoModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        @mkdir(self::CARPETA_DOCUMENTOS, 0755, true);
        @mkdir(self::CARPETA_TEMPORAL, 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar dependencias de modelos
     */
    private function inicializarModelos(): void
    {
        if (class_exists('DocumentoModelo')) {
            $this->documentoModelo = new DocumentoModelo();
        }
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

            // Guardar archivo
            $guardar = $this->guardarArchivo($archivo, self::CARPETA_DOCUMENTOS);
            if (!$guardar['success']) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'nombre' => null,
                    'mensaje' => $guardar['mensaje']
                ];
            }

            $resultado = [
                'success' => true,
                'ruta' => '/uploads/documentos/' . $nombre_unico,
                'nombre' => $nombre_unico,
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
        if (!isset($archivo['error']) || !isset($archivo['size']) || !isset($archivo['name'])) {
            return [
                'success' => false,
                'mensaje' => 'Estructura de archivo inválida'
            ];
        }

        // Validar error de upload
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $mensajes_error = [
                UPLOAD_ERR_INI_SIZE => 'Archivo excede tamaño máximo permitido',
                UPLOAD_ERR_FORM_SIZE => 'Archivo excede tamaño máximo permitido',
                UPLOAD_ERR_PARTIAL => 'Archivo subido parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Error temporal del servidor',
                UPLOAD_ERR_CANT_WRITE => 'No se puede escribir el archivo',
                UPLOAD_ERR_EXTENSION => 'Extensión no permitida'
            ];
            $mensaje = $mensajes_error[$archivo['error']] ?? 'Error desconocido en carga';
            return [
                'success' => false,
                'mensaje' => $mensaje
            ];
        }

        // Validar tamaño
        if (!$this->validarTamanoMaximo((int)$archivo['size'])) {
            return [
                'success' => false,
                'mensaje' => 'Archivo excede tamaño máximo de 5 MB'
            ];
        }

        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!$this->validarFormatosPermitidos($extension)) {
            return [
                'success' => false,
                'mensaje' => 'Formato de archivo no permitido. Permitidos: ' . implode(', ', self::FORMATOS_PERMITIDOS)
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Archivo válido'
        ];
    }

    /**
     * Guardar archivo en servidor
     * 
     * @param array $archivo Array de $_FILES['archivo']
     * @param string $carpeta Ruta de destino
     * @return array ['success' => bool, 'ruta' => string|null, 'mensaje' => string]
     */
    public function guardarArchivo(array $archivo, string $carpeta): array
    {
        try {
            // Crear directorio si no existe
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            // Generar nombre único
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $nombre_unico = uniqid('doc_', true) . '.' . $extension;
            $ruta_destino = $carpeta . DIRECTORY_SEPARATOR . $nombre_unico;

            // Mover archivo subido
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'mensaje' => 'Error al guardar archivo en servidor'
                ];
            }

            // Verificar que archivo existe
            if (!file_exists($ruta_destino)) {
                return [
                    'success' => false,
                    'ruta' => null,
                    'mensaje' => 'Archivo no se guardó correctamente'
                ];
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
            // TODO: SELECT * FROM archivo_temporal WHERE id_temporal = $id_temporal AND fecha_expiracion > NOW()
            // TODO: Retornar array con datos del archivo o null
            
            $archivo = [
                'id_temporal' => $id_temporal,
                'nombre' => 'documento_temporal.pdf',
                'ruta' => self::CARPETA_TEMPORAL . '/documento_temporal.pdf',
                'tamanio' => 1024,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'fecha_expiracion' => date('Y-m-d H:i:s', time() + 3600)
            ];

            $this->registrarLog('ARCHIVO_TEMPORAL_OBTENIDO', ['id_temporal' => $id_temporal]);
            
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
