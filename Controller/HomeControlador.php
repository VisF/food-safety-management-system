<?php
declare(strict_types=1);


/**
 * HomeControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * HomeControlador - Gestión de la página inicio del sistema
 * 
 * Dependencias esperadas:
 * - Vistas:
 *   - vistas/index.php              (página principal del sitio)
 */
require_once __DIR__ . '/../helpers/AuthHelper.php';

require_once __DIR__ . '/../Servicios/HomeService.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../Servicios/CursoService.php';
require_once __DIR__ . '/../Servicios/ExamenService.php';
require_once __DIR__ . '/../Servicios/CarnetService.php';



require_once __DIR__ . '/../Constant/EstadoTramite.php';

class HomeControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/home_controller.log';

    private HomeService $homeService;

    private InscripcionService $inscripcionService;

    private DocumentoService $documentoService;

    private CursoService $cursoService;

    private ExamenService $examenService;

    private CarnetService $carnetService;



    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->homeService =
            new HomeService();

        $this->inscripcionService =
            new InscripcionService();

        $this->documentoService =
            new DocumentoService();

        $this->cursoService =
            new CursoService();

        $this->examenService =
            new ExamenService();

        $this->carnetService =
            new CarnetService();

    }

    /**
     * Registrar eventos en log
     */
    private function log(string $event, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = "[$timestamp] [$level] {$event} | {$contextStr}\n";
        error_log($message, 3, self::LOG_FILE);
    }

    public function mostrarIndex(): array
    {
        $usuario =
            AuthHelper::usuarioActual();

        $inscripcion = null;

        if ($usuario !== null) {

            $inscripcion =
                $this->inscripcionService
                    ->obtenerUltimaPorUsuario(
                        $usuario['id']
                    );
        }

        $estadoRecursante = [
            'es_recursante' => false,
            'puede_inscribirse' => false,
            'fecha_examen' => null,
            'fecha_limite' => null,
            'dias_plazo' => null
        ];

        if ($usuario !== null) {

            $estadoRecursante =
                $this->inscripcionService
                    ->obtenerEstadoRecursante(
                        $usuario['id']
                    );
        }

        $documentos = [];

        if ($usuario !== null) {

            $documentos =
                $this->documentoService
                    ->obtenerPorUsuario(
                        $usuario['id']
                    );
        }

        $this->log(
            'Index page visited',
            'INFO',
            [
                'usuario_id' =>
                    $usuario['id'] ?? null
            ]
        );

        $tieneMoodleAprobado =
            $this->tieneMoodleAprobado(
                $documentos
            );

      $carnetVigente = null;

        $mostrarExamenes = true;

        $puedeInscribirse =
            true;

        if ($usuario !== null) {

            $carnetVigente =
                $this->carnetService
                    ->obtenerEstadoRenovacionUsuario(
                        $usuario['id']
                    );

            if (
                $carnetVigente !== null
                && $carnetVigente['estado'] === 'vigente'
            ) {

                $puedeInscribirse = false;

                $mostrarExamenes = false;
            }
        }

        $cursosVista =
            $this->obtenerCursosVista(
                $tieneMoodleAprobado,
                $inscripcion,
                $puedeInscribirse
            );

        $documentosVista =
            $this->obtenerDocumentosVista(
                $documentos
            );

        $examenesVista =
            $this->obtenerExamenesVista(
                $puedeInscribirse
            );

        $accionPrincipal =
            $this->homeService
                ->obtenerAccionPrincipal(
                    $usuario['id'] ?? 0,
                    $inscripcion
                );
        $proximoExamen = null;

        if ($usuario !== null) {

            $proximoExamen =
                $this->homeService
                    ->obtenerProximoExamen(
                        $usuario['id']
                    );
        }

        if (
            $carnetVigente !== null
            && $inscripcion !== null
            && $inscripcion->getEstadoId() === EstadoTramite::CARNET_EMITIDO
        ) {

            $accionPrincipal = [

                'titulo' => 'Carnet de Manipulador de Alimentos',

                'texto' => 'Trámite finalizado.',

                'ruta' => '/manipulacionDeAlimentos/carnet',

                'porcentaje' => 100
            ];
        }
        return [

            'page_title' =>
                'App Ciudadana - Inicio',

            'welcome_text' =>
                'Bienvenido de nuevo,',

            'usuario' =>
                $usuario,

            'tramite' => [

                'label' =>
                    'Estado del Trámite',

                'titulo' =>
                    $accionPrincipal['titulo'],

                'estado' =>
                    $inscripcion !== null
                        ? $this->obtenerTextoEstado(
                            $inscripcion->getEstadoId()
                        )
                        : 'Sin inscripción',

                'fecha_vencimiento' =>
                    null,

                'progreso' =>
                    $accionPrincipal['porcentaje'] . '%',

                'porcentaje' =>
                    $accionPrincipal['porcentaje'],

                'accion_principal' => [

                    'texto' =>
                        $accionPrincipal['texto'],

                    'ruta' =>
                        $accionPrincipal['ruta']
                ]
            ],

            'documentos' =>
                $documentosVista,

            'cursos' =>
                $cursosVista,

            'examenes' =>
                $examenesVista,

            'proximo_examen' =>
                $proximoExamen,
            'carnet_vigente' =>
                $carnetVigente,

            'mostrar_examenes' =>
                $mostrarExamenes,

            'recursante' => $estadoRecursante,

            'documentos_faltantes' =>
                $accionPrincipal['faltantes'] ?? [],

            'carnet' => [

                'descarga_habilitada' =>
                    false,

                'ruta_descarga' =>
                    'carnet_emitido',

                'etiqueta_descarga' =>
                    'Descargar Carnet'
            ]
        ];
    }
    /**
     * Determina si el usuario posee un certificado Moodle aprobado.
     */
    private function tieneMoodleAprobado(
        array $documentos
    ): bool
    {
        foreach ($documentos as $doc) {

            $tipoDocumento =
                strtoupper(
                    $doc->getTipoDocumento()
                );

            if (
                (
                    $tipoDocumento === 'MOODLE'
                    ||
                    $tipoDocumento === 'CERTIFICADO_MOODLE'
                )
                &&
                $doc->getEstado() === 'aprobado'
            ) {
                return true;
            }
        }

        return false;
    }
    /**
     * Genera la información de cursos para la vista.
     */
    private function obtenerCursosVista(
        bool $tieneMoodleAprobado,
        $inscripcion,
        bool $puedeInscribirse
    ): array
    {
        if ($tieneMoodleAprobado) {
            return [];
        }

        $cursosBD =
            $this->cursoService
                ->obtenerActivos();

        $cursosVista = [];

        foreach ($cursosBD as $curso) {

            $yaInscripto = false;

            if ($inscripcion !== null) {

                $yaInscripto =
                    $inscripcion->getCursoId() === (int)$curso['id']
                    &&
                    $inscripcion->getTipoInscripcionId() === 1;
            }

            $inscriptos =
                $this->inscripcionService
                    ->contarInscriptosCurso(
                        (int)$curso['id']
                    );

            $cupos =
                (int)$curso['cupos'];

            $cuposDisponibles =
                max(
                    0,
                    $cupos - $inscriptos
                );

            $cursosVista[] = [

                'id' => (int)$curso['id'],

                'nombre' => $curso['nombre'],

                'descripcion' => $curso['descripcion'],

                'modalidad' =>
                    ucfirst($curso['modalidad']),

                'fecha_inicio' =>
                    $curso['fecha_inicio'],

                'hora_inicio' =>
                    substr(
                        (string)$curso['hora_inicio'],
                        0,
                        5
                    ),

                'ubicacion' =>
                    $curso['ubicacion'],

                'cupos' =>
                    $cupos,

                'inscripto' =>
                    $yaInscripto,

                'cupos_totales' =>
                    $cupos,

                'inscriptos' =>
                    $inscriptos,

                'cupos_disponibles' =>
                    $cuposDisponibles,

                'puede_inscribirse' =>
                    $puedeInscribirse
            ];
        }

        return $cursosVista;
    }

    /**
     * Genera la información de documentos para la vista.
     */
    private function obtenerDocumentosVista(
        array $documentos
    ): array
    {
        $documentosVista = [];

        foreach ($documentos as $doc) {

            $tipo =
                strtoupper(
                    $doc->getTipoDocumento()
                );

            switch ($tipo) {

                case 'DNI':

                    $label = 'DNI';
                    $descripcion =
                        'Documento de identidad';
                    $icono = 'badge';
                    break;

                case 'FOTO_CARNET':
                case 'FOTO':

                    $label = 'Foto carnet';
                    $descripcion =
                        'Fotografía actual';
                    $icono =
                        'account_circle';
                    break;

                case 'CERTIFICADO_MOODLE':
                case 'MOODLE':

                    $label =
                        'Certificado Moodle';

                    if (
                        $doc->getEstado()
                        === 'aprobado'
                    ) {

                        $descripcion =
                            'Curso aprobado';

                    } elseif (
                        $doc->getEstado()
                        === 'rechazado'
                    ) {

                        $descripcion =
                            'Requiere corrección';

                    } else {

                        $descripcion =
                            'Pendiente de revisión';
                    }

                    $icono = 'school';
                    break;

                default:

                    $label =
                        ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $doc->getTipoDocumento()
                            )
                        );

                    $descripcion =
                        'Documento cargado';

                    $icono =
                        'description';
            }

            $documentosVista[] = [

                'label' => $label,

                'descripcion' =>
                    $descripcion,

                'icon' =>
                    $icono,

                'route' =>
                    'documentacion',

                'state' =>
                    $doc->getEstado()
            ];
        }

        return $documentosVista;
    }
    
        /**
     * Genera la información de exámenes para la vista.
     */
    private function obtenerExamenesVista(bool $puedeInscribirse): array
    {
        $examenesBD =
            $this->examenService
                ->obtenerProximos(10);

        $examenesVista = [];

        foreach ($examenesBD as $examen) {

            $fecha =
                new DateTime(
                    $examen['fecha']
                );

            $examenesVista[] = [

                'month' =>
                    strtoupper(
                        $fecha->format('M')
                    ),

                'day' =>
                    $fecha->format('d'),

                'title' =>
                    'Examen de Manipulación de Alimentos',

                'time' =>
                    substr(
                        $examen['hora'],
                        0,
                        5
                    ),

                'place' =>
                    $examen['ubicacion']
                    .
                    (
                        !empty($examen['aula'])
                        ? ' - ' . $examen['aula']
                        : ''
                    ),

                'available' =>
                    ((int)$examen['cupos'] > 0)
                        ? 1
                        : 0,

                'route' =>
                    'inscripcion_examen?id='
                    . $examen['id'],

                'id' =>
                    (int)$examen['id'],

                'puede_inscribirse' =>
                    $puedeInscribirse
            ];
        }

        return $examenesVista;
    }




    // Obtiene texto estado.
    private function obtenerTextoEstado(int $estado): string
    {
        return match ($estado) {

            EstadoTramite::PENDIENTE =>
                'Pendiente',

            EstadoTramite::DOCUMENTACION_PENDIENTE =>
                'Documentación pendiente',

            EstadoTramite::DOCUMENTACION_APROBADA =>
                'Documentación aprobada',

            EstadoTramite::INSCRIPTO_EXAMEN =>
                'Inscripto a examen',

            EstadoTramite::APROBADO =>
                'Examen aprobado',

            EstadoTramite::CARNET_EMITIDO =>
                'Carnet emitido',

            EstadoTramite::RECHAZADO =>
                'Rechazado',

            EstadoTramite::CANCELADO =>
                'Cancelado',

            default =>
                'Estado desconocido'
        };
    }
}
