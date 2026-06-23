<?php

declare(strict_types=1);

class ToastHelper
{
    public static function obtenerToast(
        ?string $codigo
    ): ?array {

        if ($codigo === null) {
            return null;
        }

        return match ($codigo) {

            'inscripcion_exitosa' => [
                'mensaje' =>
                    'Inscripción realizada correctamente',
                'tipo' =>
                    'success'
            ],

            'curso_inscripto' => [
                'mensaje' =>
                    'Te inscribiste correctamente al curso',
                'tipo' =>
                    'success'
            ],

            'curso_activo' => [
                'mensaje' =>
                    'Ya tienes un curso activo',
                'tipo' =>
                    'warning'
            ],

            'documentacion_incompleta' => [
                'mensaje' =>
                    'Debes tener aprobados el DNI y la foto carnet',
                'tipo' =>
                    'warning'
            ],

            'curso_invalido' => [
                'mensaje' =>
                    'El curso seleccionado no es válido',
                'tipo' =>
                    'error'
            ],

            'ya_inscripto' => [
                'mensaje' =>
                    'Ya te encuentras inscripto',
                'tipo' =>
                    'warning'
            ],

            'error_inscripcion' => [
                'mensaje' =>
                    'Ocurrió un error al procesar la inscripción',
                'tipo' =>
                    'error'
            ],

            'documento_subido' => [
                'mensaje' =>
                    'Documento subido correctamente',
                'tipo' =>
                    'success'
            ],

            'error_subida' => [
                'mensaje' =>
                    'Ocurrió un error al subir el documento',
                'tipo' =>
                    'error'
            ],

            'formato_invalido' => [
                'mensaje' =>
                    'Solo se permiten archivos PDF, JPG o PNG',
                'tipo' =>
                    'error'
            ],

            'error_upload' => [
                'mensaje' =>
                    'El archivo no pudo procesarse',
                'tipo' =>
                    'error'
            ],

            'error_archivo' => [
                'mensaje' =>
                    'Archivo inválido',
                'tipo' =>
                    'error'
            ],

            default => null
        };
    }
}