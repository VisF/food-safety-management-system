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
 * - Generar campos CSRF para formularios.
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
     * Genera el campo oculto CSRF
     * para formularios POST.
     *
     * Las vistas no necesitan conocer
     * directamente el middleware CSRF.
     */
    protected function getCsrfInput(): string
    {
        $token =
            \App\Middleware\CsrfMiddleware::generateToken();

        return
            '<input type="hidden" name="csrf_token" value="' .
            $this->e($token) .
            '">';
    }


    /**
     * Escapa un valor para utilizarlo
     * de forma segura dentro de HTML.
     *
     * @param mixed $valor
     */
    protected function e(
        mixed $valor
    ): string {
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
     */
    protected function getRoute(
        string $route,
        ?int $id = null,
        ?int $idSecundario = null
    ): string {
        return match ($route) {

            /* ==================================================
               CIUDADANO
            ================================================== */

            /**
             * Página principal.
             *
             * GET /
             */
            'inicio' =>
                $this->baseURL,


            /**
             * Perfil del ciudadano.
             *
             * GET /perfil
             */
            'perfil' =>
                $this->baseURL .
                'perfil',


            /**
             * Actualizar perfil.
             *
             * POST /perfil/actualizar
             */
            'perfil_actualizar' =>
                $this->baseURL .
                'perfil/actualizar',


            /**
             * Documentación del ciudadano.
             *
             * GET /documentacion
             */
            'documentacion' =>
                $this->baseURL .
                'documentacion',


            /**
             * Descargar documento propio.
             *
             * GET /documentos/{id}/descargar
             */
            'descargar_documento_ciudadano' =>
                $this->baseURL .
                'documentos/' .
                (int)$id .
                '/descargar',


            /**
             * Detalle de un examen.
             *
             * GET /detalle_examen/{id}
             */
            'detalle_examen' =>
                $this->baseURL .
                'detalle_examen' .
                (
                    $id !== null
                        ? '/' . (int)$id
                        : ''
                ),


            /**
             * Procesar inscripción a examen.
             *
             * POST /inscripcion/confirmar
             */
            'confirmar_inscripcion' =>
                $this->baseURL .
                'inscripcion/confirmar',


            /**
             * Inscripciones a cursos.
             *
             * GET /inscripciones
             */
            'inscripciones' =>
                $this->baseURL .
                'inscripciones',


            /**
             * Procesar inscripción a curso.
             *
             * POST /curso/inscribirse
             */
            'guardar_inscripcion_curso' =>
                $this->baseURL .
                'curso/inscribirse',


            /**
             * Subir documentación.
             *
             * POST /documentos/subir
             */
            'guardar_documento' =>
                $this->baseURL .
                'documentos/subir',


            /**
             * Descargar carnet del ciudadano autenticado.
             *
             * GET /carnet/descargar
             */
            'descargar_carnet_ciudadano' =>
                $this->baseURL .
                'carnet/descargar',


            /* ==================================================
               ADMINISTRACIÓN — GENERAL
            ================================================== */

            /**
             * Panel principal de administración.
             *
             * GET /admin
             */
            'admin' =>
                $this->baseURL .
                'admin',


            /**
             * Actividad reciente.
             *
             * GET /admin/actividad
             */
            'admin_actividad' =>
                $this->baseURL .
                'admin/actividad',


            /* ==================================================
               ADMINISTRACIÓN — DOCUMENTACIÓN
            ================================================== */

            /**
             * Panel administrativo de documentación.
             *
             * GET /admin/documentos
             */
            'admin_documentos' =>
                $this->baseURL .
                'admin/documentos',


            /**
             * Buscar documentación por DNI.
             *
             * GET /admin/documentos/buscar
             */
            'buscar_documentos' =>
                $this->baseURL .
                'admin/documentos/buscar',


            /**
             * Aprobar documento.
             *
             * POST /admin/documentos/{id}/aprobar
             */
            'aprobar_documento' =>
                $this->baseURL .
                'admin/documentos/' .
                (int)$id .
                '/aprobar',


            /**
             * Rechazar documento.
             *
             * POST /admin/documentos/{id}/rechazar
             */
            'rechazar_documento' =>
                $this->baseURL .
                'admin/documentos/' .
                (int)$id .
                '/rechazar',


            /**
             * Descargar documento desde administración.
             *
             * GET /admin/documentos/{id}/descargar
             */
            'descargar_documento' =>
                $this->baseURL .
                'admin/documentos/' .
                (int)$id .
                '/descargar',


            /* ==================================================
               ADMINISTRACIÓN — EXÁMENES
            ================================================== */

            /**
             * Listado administrativo de exámenes.
             *
             * GET /admin/examenes
             */
            'admin_examenes' =>
                $this->baseURL .
                'admin/examenes',


            /**
             * Crear un examen.
             *
             * GET /admin/examenes/nuevo
             */
            'crear_examen' =>
                $this->baseURL .
                'admin/examenes/nuevo',


            /**
             * Guardar un examen nuevo.
             *
             * POST /admin/examenes
             */
            'guardar_examen_nuevo' =>
                $this->baseURL .
                'admin/examenes',


            /**
             * Editar un examen.
             *
             * GET /admin/examenes/{id}/editar
             */
            'editar_examen' =>
                $this->baseURL .
                'admin/examenes/' .
                (int)$id .
                '/editar',


            /**
             * Guardar edición de un examen.
             *
             * POST /admin/examenes/{id}
             */
            'guardar_examen' =>
                $this->baseURL .
                'admin/examenes/' .
                (int)$id,


            /**
             * Detalle administrativo de un examen.
             *
             * GET /admin/examenes/{id}
             */
            'detalle_examen_admin' =>
                $this->baseURL .
                'admin/examenes/' .
                (int)$id,


            /**
             * Activar examen.
             *
             * POST /admin/examenes/{id}/activar
             */
            'activar_examen' =>
                $this->baseURL .
                'admin/examenes/' .
                (int)$id .
                '/activar',


            /**
             * Desactivar examen.
             *
             * POST /admin/examenes/{id}/desactivar
             */
            'desactivar_examen' =>
                $this->baseURL .
                'admin/examenes/' .
                (int)$id .
                '/desactivar',


            /**
             * Administrar una inscripción a examen.
             *
             * GET /admin/inscripciones/{id}
             */
            'administrar_inscripcion' =>
                $this->baseURL .
                'admin/inscripciones/' .
                (int)$id,


            /**
             * Guardar administración de inscripción.
             *
             * POST /admin/inscripciones/{id}
             */
            'guardar_inscripcion' =>
                $this->baseURL .
                'admin/inscripciones/' .
                (int)$id,

            /**
             * Guardar configuración del plazo para recursantes.
             *
             * POST /admin/examenes/configuracion/recursante
             */
            'guardar_configuracion_recursante' =>
                $this->baseURL .
                'admin/examenes/configuracion/recursante',


            /* ==================================================
               ADMINISTRACIÓN — CARNETS
            ================================================== */

            /**
             * Panel administrativo de carnets.
             *
             * GET /admin/carnets
             */
            'admin_carnets' =>
                $this->baseURL .
                'admin/carnets',


            /**
             * Cargar carnet.
             *
             * GET /admin/carnets/{id}/cargar
             */
            'cargar_carnet' =>
                $this->baseURL .
                'admin/carnets/' .
                (int)$id .
                '/cargar',


            /**
             * Emitir carnet.
             *
             * POST /admin/carnets/{id}/emitir
             */
            'emitir_carnet' =>
                $this->baseURL .
                'admin/carnets/' .
                (int)$id .
                '/emitir',


            /**
             * Anular carnet.
             *
             * POST /admin/carnets/{id}/anular
             */
            'anular_carnet' =>
                $this->baseURL .
                'admin/carnets/' .
                (int)$id .
                '/anular',


            /**
             * Descargar carnet desde administración.
             *
             * GET /admin/carnets/{id}/descargar
             */
            'descargar_carnet_admin' =>
                $this->baseURL .
                'admin/carnets/' .
                (int)$id .
                '/descargar',

            /* ==================================================
               ADMINISTRACIÓN — USUARIOS
            ================================================== */
            /**
             * Gestión de usuarios.
             *
             * GET /admin/usuarios
             */
            'admin_usuarios' =>
                $this->baseURL .
                'admin/usuarios',
            /* ==================================================
               ADMINISTRACIÓN — CURSOS
            ================================================== */

            /**
             * Listado administrativo de cursos.
             *
             * GET /admin/cursos
             */
            'admin_cursos' =>
                $this->baseURL .
                'admin/cursos',


            /**
             * Crear un curso.
             *
             * GET /admin/cursos/nuevo
             */
            'crear_curso' =>
                $this->baseURL .
                'admin/cursos/nuevo',


            /**
             * Guardar un curso nuevo.
             *
             * POST /admin/cursos
             */
            'guardar_curso_nuevo' =>
                $this->baseURL .
                'admin/cursos',


            /**
             * Editar un curso.
             *
             * GET /admin/cursos/{id}/editar
             */
            'editar_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/editar',


            /**
             * Guardar edición de un curso.
             *
             * POST /admin/cursos/{id}
             */
            'guardar_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id,


            /**
             * Activar curso.
             *
             * POST /admin/cursos/{id}/activar
             */
            'activar_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/activar',


            /**
             * Desactivar curso.
             *
             * POST /admin/cursos/{id}/desactivar
             */
            'desactivar_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/desactivar',


            'inscriptos_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/inscriptos',

            'aprobar_inscripcion_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/inscriptos/' .
                (int)$idSecundario .
                '/aprobar',

            'desaprobar_inscripcion_curso' =>
                $this->baseURL .
                'admin/cursos/' .
                (int)$id .
                '/inscriptos/' .
                (int)$idSecundario .
                '/desaprobar',
            /* ==================================================
               CONSULTA PÚBLICA
            ================================================== */

            /**
             * Consulta pública de carnets.
             *
             * GET /consulta-publica
             */
            'consulta_publica' =>
                $this->baseURL .
                'consulta-publica',


            /**
             * Descargar carnet desde consulta pública.
             *
             * GET /consulta-publica/carnet/{id}/descargar
             */
            'descargar_carnet' =>
                $this->baseURL .
                'consulta-publica/carnet/' .
                (int)$id .
                '/descargar',


            /**
             * Descargar foto del carnet desde
             * consulta pública.
             *
             * GET /consulta-publica/carnet/{id}/foto
             */
            'descargar_foto' =>
                $this->baseURL .
                'consulta-publica/carnet/' .
                (int)$id .
                '/foto',


            /* ==================================================
               AUTENTICACIÓN
            ================================================== */

            /**
             * Registro.
             *
             * GET /registro
             * POST /registro
             */
            'registro' =>
                $this->baseURL .
                'registro',


            /**
             * Iniciar sesión.
             *
             * GET /login
             */
            'login' =>
                $this->baseURL .
                'login',


            /**
             * Cerrar sesión.
             *
             * GET /logout
             */
            'logout' =>
                $this->baseURL .
                'logout',


            /* ==================================================
               RUTA DESCONOCIDA
            ================================================== */

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