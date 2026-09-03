<?php

declare(strict_types=1);

/**
 * Clase base para las vistas.
 *
 * Centraliza funcionalidades comunes
 * utilizadas por las distintas vistas
 * de la aplicación.
 *
 * Responsabilidades:
 * - Proporcionar la URL base.
 * - Generar rutas.
 * - Escapar valores para HTML.
 * - Cargar el footer común.
 */
abstract class BaseVista
{
    /**
     * URL base de la aplicación.
     */
    protected string $baseURL =
        '/manipulacionDeAlimentos/';


    /**
     * Escapa un valor para utilizarlo
     * de forma segura dentro de HTML.
     *
     * @param mixed $valor
     * @return string
     */
    protected function e(
        mixed $valor
    ): string
    {
        return htmlspecialchars(
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }


    /**
     * Obtiene las rutas utilizadas
     * por las vistas de la aplicación.
     *
     * Las rutas se centralizan aquí para
     * evitar repetir URLs y lógica
     * de construcción en cada vista.
     *
     * @param string $route
     * @param int|null $id
     * @return string
     */
    protected function getRoute(
        string $route,
        ?int $id = null
    ): string
    {
        return match ($route) {

            /*
             * ==========================================
             * GENERALES — CIUDADANO
             * ==========================================
             */

            /**
             * Página principal.
             *
             * GET /
             */
            'inicio' =>
                $this->baseURL,


            /**
             * Perfil / datos del ciudadano.
             *
             * GET /perfil
             */
            'perfil' =>
                $this->baseURL
                . 'perfil',


            /**
             * Documentación del ciudadano.
             *
             * GET /subida_documentacion
             */
            'documentacion' =>
                $this->baseURL
                . 'subida_documentacion',


            /**
             * Carnet correspondiente a una inscripción.
             *
             * GET /carnet?id={id}
             */
            'carnet' =>
                $this->baseURL
                . 'carnet'
                . (
                    $id !== null
                        ? '?id=' . $id
                        : ''
                ),


            /**
             * Detalle de un examen para el ciudadano.
             *
             * GET /detalle_examen?id={id}
             */
            'detalle_examen' =>
                $this->baseURL
                . 'detalle_examen'
                . (
                    $id !== null
                        ? '?id=' . $id
                        : ''
                ),


            /**
             * Confirmación de inscripción a examen.
             *
             * GET /confirmar_inscripcion_examen?id={id}
             */
            'confirmar_examen' =>
                $this->baseURL
                . 'confirmar_inscripcion_examen'
                . (
                    $id !== null
                        ? '?id=' . $id
                        : ''
                ),


            /**
             * Procesar inscripción a examen.
             *
             * POST /inscripcion/confirmar
             */
            'confirmar_inscripcion' =>
                $this->baseURL
                . 'inscripcion/confirmar',


            /**
             * Inscripciones a cursos.
             *
             * GET /inscripciones
             */
            'inscripciones' =>
                $this->baseURL
                . 'inscripciones',


            /**
             * Procesar inscripción a curso.
             *
             * POST /curso/inscribirse
             */
            'guardar_inscripcion_curso' =>
                $this->baseURL
                . 'curso/inscribirse',


            /**
             * Subir documentación.
             *
             * POST /documentos/subir
             */
            'guardar_documento' =>
                $this->baseURL
                . 'documentos/subir',


            /*
             * ==========================================
             * ADMINISTRACIÓN — GENERAL
             * ==========================================
             */

            /**
             * Panel principal de administración.
             *
             * GET /admin
             */
            'admin' =>
                $this->baseURL
                . 'admin',


            /**
             * Actividad reciente.
             *
             * GET /admin/actividad
             */
            'admin_actividad' =>
                $this->baseURL
                . 'admin/actividad',


            /**
             * Gestión administrativa de usuarios.
             *
             * Ruta planificada.
             */
            'admin_usuarios' =>
                $this->baseURL
                . 'admin/usuarios',


            /**
             * Reportes administrativos.
             *
             * Ruta planificada.
             */
            'admin_reportes' =>
                $this->baseURL
                . 'admin/reportes',


            /*
             * ==========================================
             * ADMINISTRACIÓN — DOCUMENTACIÓN
             * ==========================================
             */

            /**
             * Panel administrativo de documentación.
             *
             * GET /admin/documentos
             */
            'admin_documentos' =>
                $this->baseURL
                . 'admin/documentos',


            /**
             * Buscar documentación por DNI.
             *
             * GET /admin/documentos/buscar
             */
            'buscar_documentos' =>
                $this->baseURL
                . 'admin/documentos/buscar',


            /**
             * Aprobar documento.
             *
             * POST /admin/documentos/{id}/aprobar
             */
            'aprobar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/aprobar',


            /**
             * Rechazar documento.
             *
             * POST /admin/documentos/{id}/rechazar
             */
            'rechazar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/rechazar',


            /**
             * Descargar documento desde administración.
             *
             * GET /admin/documentos/{id}/descargar
             */
            'descargar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/descargar',


            /**
             * Descargar documento propio del ciudadano.
             *
             * GET /documentos/{id}/descargar
             */
            'descargar_carnet_ciudadano' =>
                $this->baseURL .
                'carnet/descargar',


            /*
             * ==========================================
             * ADMINISTRACIÓN — EXÁMENES
             * ==========================================
             */

            /**
             * Listado administrativo de exámenes.
             *
             * GET /admin/examenes
             */
            'admin_examenes' =>
                $this->baseURL
                . 'admin/examenes',


            /**
             * Crear un examen.
             *
             * GET/POST /admin/examenes/nuevo
             */
            'crear_examen' =>
                $this->baseURL
                . 'admin/examenes/nuevo',

                
            /**
             * Guardar un examen nuevo.
             *
             * POST /admin/examenes
             */
            'guardar_examen_nuevo' =>
                $this->baseURL
                . 'admin/examenes',

            /**
             * Editar un examen.
             *
             * GET /admin/examenes/{id}/editar
             */
            'editar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/editar',


            /**
             * Guardar edición de un examen.
             *
             * POST /admin/examenes/{id}
             */
            'guardar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id,


            /**
             * Detalle administrativo de un examen.
             *
             * GET /admin/examenes/{id}
             */
            'detalle_examen_admin' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id,


            /**
             * Activar examen.
             *
             * POST /admin/examenes/{id}/activar
             */
            'activar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/activar',


            /**
             * Desactivar examen.
             *
             * POST /admin/examenes/{id}/desactivar
             */
            'desactivar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/desactivar',


            /**
             * Administrar una inscripción a examen.
             *
             * GET /admin/inscripciones/{id}
             */
            'administrar_inscripcion' =>
                $this->baseURL
                . 'admin/inscripciones/'
                . $id,


            /**
             * Guardar administración de inscripción.
             *
             * POST /admin/inscripciones/{id}
             */
            'guardar_inscripcion' =>
                $this->baseURL
                . 'admin/inscripciones/'
                . $id,


            /*
             * ==========================================
             * ADMINISTRACIÓN — CARNETS
             * ==========================================
             */

            /**
             * Panel administrativo de carnets.
             *
             * GET /admin/carnets
             */
            'admin_carnets' =>
                $this->baseURL
                . 'admin/carnets',


            /**
             * Cargar carnet.
             *
             * GET /admin/carnets/{id}/cargar
             */
            'cargar_carnet' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/cargar',


            /**
             * Emitir carnet.
             *
             * POST /admin/carnets/{id}/emitir
             */
            'emitir_carnet' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/emitir',


            /**
             * Anular carnet.
             *
             * POST /admin/carnets/{id}/anular
             */
            'anular_carnet' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/anular',

            'descargar_carnet_admin' =>
                $this->baseURL .
                'admin/carnets/' .
                $id .
                '/descargar',
            /*
             * ==========================================
             * CONSULTA PÚBLICA
             * ==========================================
             */

            'consulta_publica' =>
                $this->baseURL .
                'consulta-publica',

            'descargar_carnet' =>
                $this->baseURL .
                'consulta-publica/carnet/' .
                $id .
                '/descargar',

            'descargar_foto' =>
                $this->baseURL .
                'consulta-publica/carnet/' .
                $id .
                '/foto',
            


            /*
             * ==========================================
             * INSPECTOR
             * ==========================================
             *
             * Ruta planificada.
             */

            /**
             * Búsqueda de ciudadanos para inspector.
             *
             * Ruta planificada:
             * GET /inspector/ciudadanos
             */
            'inspector_ciudadanos' =>
                $this->baseURL
                . 'inspector/ciudadanos',


            /*
             * ==========================================
             * AUTENTICACIÓN
             * ==========================================
             */

            /**
             * Iniciar sesión.
             *
             * GET /login
             */
            'login' =>
                $this->baseURL
                . 'login',


            /**
             * Cerrar sesión.
             *
             * GET /logout
             */
            'logout' =>
                $this->baseURL
                . 'logout',


            /*
             * ==========================================
             * RUTA DESCONOCIDA
             * ==========================================
             */

            default =>
                '#',
        };
    }


    /**
     * Genera el pie de página común.
     */
    protected function getFooter(): void
    {
        include __DIR__ .
            '/footer.php';
    }
}



