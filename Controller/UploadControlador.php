<?php
declare(strict_types=1);

/**
 * UploadControlador
 *
 * Gestiona las solicitudes HTTP relacionadas
 * con la carga de archivos.
 */
class UploadControlador
{
    private UploadService $uploadService;

    /**
     * Inicializa las dependencias.
     */
    public function __construct()
    {
        $this->uploadService =
            new UploadService();
    }

    /**
     * Procesa la carga de un archivo.
     */
    public function procesarCarga(
        array $archivo,
        string $carpetaDestino,
        string $prefijo
    ): array
    {
        return $this->uploadService
            ->procesarCarga(
                $archivo,
                $carpetaDestino,
                $prefijo
            );
    }

        /**
     * Valida un archivo.
     */
    public function validarArchivo(
        array $archivo
    ): array
    {
        return $this->uploadService
            ->validarArchivo(
                $archivo
            );
    }

    /**
     * Guarda un archivo.
     */
    public function guardarArchivo(
        array $archivo,
        string $carpeta,
        ?string $nombreDeseado = null
    ): array
    {
        return $this->uploadService
            ->guardarArchivo(
                $archivo,
                $carpeta,
                $nombreDeseado
            );
    }

    /**
     * Genera un nombre único.
     */
    public function generarNombreArchivo(
        string $nombreOriginal,
        string $prefijo
    ): string
    {
        return $this->uploadService
            ->generarNombreArchivo(
                $nombreOriginal,
                $prefijo
            );
    }

    /**
     * Elimina un archivo.
     */
    public function eliminarArchivo(
        string $ruta
    ): bool
    {
        return $this->uploadService
            ->eliminarArchivo(
                $ruta
            );
    }

    /**
     * Obtiene información de un archivo.
     */
    public function obtenerInfoArchivo(
        string $ruta
    ): array
    {
        return $this->uploadService
            ->obtenerInfoArchivo(
                $ruta
            );
    }
}