






















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
     * evitar repetir la misma lógica
     * en cada vista.
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
             * GENERALES
             * ==========================================
             */

            'inicio' =>
                $this->baseURL,


            'perfil' =>
                $this->baseURL
                . 'perfil',


            'documentacion' =>
                $this->baseURL
                . 'subida_documentacion',


            /*
             * ==========================================
             * CIUDADANO
             * ==========================================
             *
             * Estas rutas están previstas para
             * las futuras secciones generales
             * del ciudadano.
             */

            'examen' =>
                $this->baseURL
                . 'examen',


            'carnet' =>
                $this->baseURL
                . 'carnet',


            /*
             * ==========================================
             * ADMINISTRACIÓN
             * ==========================================
             */

            'admin' =>
                $this->baseURL
                . 'admin',


            'admin_usuarios' =>
                $this->baseURL
                . 'admin/usuarios',


            'admin_documentos' =>
                $this->baseURL
                . 'admin/documentos',


            'admin_examenes' =>
                $this->baseURL
                . 'admin/examenes',


            'admin_carnets' =>
                $this->baseURL
                . 'admin/carnets',


            'admin_actividad' =>
                $this->baseURL
                . 'admin/actividad',


            'admin_reportes' =>
                $this->baseURL
                . 'admin/reportes',


            /*
             * ==========================================
             * ADMINISTRACIÓN — CARNETS
             * ==========================================
             */

            'cargar_carnet' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/cargar',


            'emitir_carnet' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/emitir',
                
            'carnet' =>
                $this->baseURL
                . 'carnet'
                . (
                    $id !== null
                        ? '?id=' . $id
                        : ''
                ),

            /*
             * ==========================================
             * ADMINISTRACIÓN — DOCUMENTACIÓN
             * ==========================================
             */

            'buscar_documentos' =>
                $this->baseURL
                . 'admin/documentos/buscar',


            'aprobar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/aprobar',


            'rechazar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/rechazar',


            'descargar_documento' =>
                $this->baseURL
                . 'admin/documentos/'
                . $id
                . '/descargar',
            
            /*
            * ==========================================
            * ADMINISTRACIÓN — EXÁMENES
            * ==========================================
            */

            'editar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/editar',


            'administrar_inscripcion' =>
                $this->baseURL
                . 'admin/inscripciones/'
                . $id,
            
            'crear_examen' =>
                $this->baseURL
                . 'admin/examenes/nuevo',


            'guardar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id,

            'detalle_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id,


            'desactivar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/desactivar',


            'activar_examen' =>
                $this->baseURL
                . 'admin/examenes/'
                . $id
                . '/activar',
            /*
             * ==========================================
             * INSPECTOR
             * ==========================================
             */

            'inspector_ciudadanos' =>
                $this->baseURL
                . 'inspector/ciudadanos',


            /*
             * ==========================================
             * AUTENTICACIÓN
             * ==========================================
             */

            'login' =>
                $this->baseURL
                . 'login',


            'logout' =>
                $this->baseURL
                . 'logout',


            /*
             * ==========================================
             * RUTA DESCONOCIDA
             * ==========================================
             */

            'guardar_inscripcion' =>
                $this->baseURL
                . 'admin/inscripciones/'
                . $id,



            'emision_carnet' =>
                $this->baseURL
                . 'admin/inscripciones/'
                . $id
                . '/emitir-carnet',

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
 * - Generar rutas.
 * - Escapar valores para HTML.
 * - Proporcionar la URL base.
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
     * por las vistas.
     *
     * Las rutas que todavía no poseen
     * una implementación definitiva
     * pueden quedar definidas aquí para
     * que la navegación pueda prepararse
     * desde ahora.
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
             * GENERALES
             * ==========================================
             */

            'inicio' =>
                $this->baseURL,


            'perfil' =>
                $this->baseURL
                . 'perfil',


            'documentacion' =>
                $this->baseURL
                . 'subida_documentacion',


            /*
             * ==========================================
             * CIUDADANO — RUTAS PLANIFICADAS
             * ==========================================
             */

            'examen' =>
                $this->baseURL
                . 'examen',


            'carnet' =>
                $this->baseURL
                . 'carnet',


            /*
             * ==========================================
             * ADMINISTRACIÓN
             * ==========================================
             */

            'admin' =>
                $this->baseURL
                . 'admin',


            'admin_usuarios' =>
                $this->baseURL
                . 'admin/usuarios',


            'admin_documentos' =>
                $this->baseURL
                . 'admin/documentos',


            'admin_examenes' =>
                $this->baseURL
                . 'admin/examenes',


            'admin_carnets' =>
                $this->baseURL
                . 'admin/carnets',


            'admin_actividad' =>
                $this->baseURL
                . 'admin/actividad',


            'admin_reportes' =>
                $this->baseURL
                . 'admin/reportes',


            /*
             * ==========================================
             * ADMINISTRACIÓN — CARNETS
             * ==========================================
             */

            'cargar' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/cargar',


            'emitir' =>
                $this->baseURL
                . 'admin/carnets/'
                . $id
                . '/emitir',


            /*
             * ==========================================
             * INSPECTOR — RUTA PLANIFICADA
             * ==========================================
             */

            'inspector_ciudadanos' =>
                $this->baseURL
                . 'inspector/ciudadanos',


            /*
             * ==========================================
             * AUTENTICACIÓN
             * ==========================================
             */

            'login' =>
                $this->baseURL
                . 'login',


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