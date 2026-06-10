<?php
declare(strict_types=1);

/**
 * HomeControlador - Gestión de la página inicio del sistema
 * 
 * Dependencias esperadas:
 * - Vistas:
 *   - vistas/index.php              (página principal del sitio)
 *   - vistas/dashboard.php          (panel de control para usuarios autenticados)
 *   - vistas/consulta_publica.php   (búsqueda pública por DNI)
 */
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../middleware/CsrfMiddleware.php';

require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../modelo/InscripcionModelo.php';

require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../modelo/DocumentoModelo.php';


class HomeControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/home_controller.log';
    private InscripcionService $inscripcionService;
    private DocumentoService $documentoService;

    public function __construct()
    {
        $this->inscripcionService =
            new InscripcionService(
                new InscripcionModelo()
            );

        $this->documentoService =
            new DocumentoService(
                new DocumentoModelo()
            );
        @mkdir(dirname(self::LOG_FILE), 0755, true);
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

    /**
     * Mostrar página principal del sitio
     * 
     * VISTA A LLAMAR: vistas/index.php
     * 
     * @return array Array con datos para la vista:
     *   [
     *     'title' => 'Sistema de Gestión de Carnets de Manipuladores',
     *     'user_autenticado' => bool,
     *     'user_data' => array|null
     *   ]
     */
    public function mostrarIndex(): array
    {
        $usuario = AuthHelper::usuarioActual();


        $inscripcion = null;

        if (
            $usuario !== null &&
            class_exists('InscripcionModelo')
        ) {
            $inscripcion =
                $this->inscripcionService
                    ->obtenerUltimaPorUsuario(
                        $usuario['id']
                    );
        }
        $documentos = [];

        if ($inscripcion !== null &&
            class_exists('DocumentoService') &&
            class_exists('DocumentoModelo')) 
        {
            $documentos =
                $this->documentoService
                    ->obtenerPorInscripcion(
                        $inscripcion->getId()
                    );
        }
        $this->log(
            'Index page visited',
            'INFO',
            [
                'usuario_id' => $usuario['id'] ?? null
            ]
        );
        $documentosVista = [];

        foreach ($documentos as $doc) {
            $icono = 'description';
            switch (
                strtoupper(
                    $doc->getTipoDocumento()
                )) 
            {
                case 'DNI':
                    $icono = 'badge';
                    break;

                case 'FOTO':
                    $icono = 'add_a_photo';
                    break;
            }

            $documentosVista[] = [
                'label' => $doc->getTipoDocumento(),
                'icon' => $icono,
                'route' => 'subida_documentacion',
                'state' => $doc->getValidado()
            ];
    }

    return [
        'page_title' => 'App Ciudadana - Inicio',

        'welcome_text' => 'Bienvenido de nuevo,',

        'usuario' => $usuario,

        'tramite' => [
            'label' => 'Estado del Trámite',

            'titulo' =>
                    $inscripcion !== null
                    ? $inscripcion->getTipoInscripcion()
                    : 'Carnet de Manipulador',

            'estado' =>
                    $inscripcion !== null
                    ? $inscripcion->getEstadoNombre()
                    : 'SIN INSCRIPCIÓN',

            'fecha_vencimiento' => null,

            'progreso' => null
        ],

        'documentos' => $documentosVista,

        'examenes' => [
            [
                'month' => 'OCT',
                'day' => '24',
                'title' => 'CRESTA',
                'time' => '09:00 AM',
                'place' => 'Aula 3',
                'available' => 1,
                'route' => 'inscripcion_examen',
            ],
        ],

        'carnet' => [
            'descarga_habilitada' => false,
            'ruta_descarga' => 'carnet_emitido',
            'etiqueta_descarga' => 'Descargar Carnet'
        ]
    ];
}

    /**
     * Mostrar dashboard para usuarios autenticados
     * 
     * VISTA A LLAMAR: vistas/dashboard.php
     * 
     * @param int $id_usuario ID del usuario autenticado
     * @return array Array con datos para la vista:
     *   [
     *     'title' => 'Mi Panel de Control',
     *     'usuario' => [...],
     *     'inscripciones_activas' => [...],
     *     'tramites_pendientes' => [...],
     *     'actividad_reciente' => [...]
     *   ]
     */
    public function mostrarDashboard(int $id_usuario): array
    {
        $current = $_SESSION['user_id'] ?? null;
        $isAdmin = $_SESSION['is_admin'] ?? false;
        // Comprobación de permisos: solo el usuario mismo o admin puede ver el dashboard solicitado
        if (!$current || ((int)$current !== (int)$id_usuario && !$isAdmin)) {
            $this->log('Dashboard access denied', 'WARN', ['requested' => $id_usuario, 'current' => $current]);
            return ['title' => 'Mi Panel de Control', 'usuario' => null, 'inscripciones_activas' => [], 'tramites_pendientes' => [], 'actividad_reciente' => []];
        }

        $usuario = null;
        if (class_exists('UsuarioModelo')) { try { $um = new UsuarioModelo(); $usuario = $um->obtenerPorId($id_usuario); } catch (\Exception $e) { $usuario = null; } }

        $inscripciones = [];
        if (class_exists('InscripcionModelo')) { try { $im = new InscripcionModelo(); if (method_exists($im, 'obtenerPorUsuario')) $inscripciones = $im->obtenerPorUsuario($id_usuario); } catch (\Exception $e) { $inscripciones = []; } }

        // Filtrar inscripciones para mostrar solo las que requieren acción (estados 1 y 2)
        $tramitesPendientes = [];
        foreach ($inscripciones as $ins) { if (in_array((int)($ins['estado_tramite_id'] ?? $ins['id_estado'] ?? 0), [1,2], true)) $tramitesPendientes[] = $ins; }

        $actividad = [];
        if (class_exists('AuditoriaAccionesModelo')) { try { $am = new AuditoriaAccionesModelo(); if (method_exists($am, 'obtenerRecientes')) $actividad = $am->obtenerRecientes(10); } catch (\Exception $e) { $actividad = []; } }

        $this->log('Dashboard accessed', 'INFO', ['usuario_id' => $id_usuario]);

        return [
            'title' => 'Mi Panel de Control',
            'usuario' => $usuario,
            'inscripciones_activas' => $inscripciones,
            'tramites_pendientes' => $tramitesPendientes,
            'actividad_reciente' => $actividad
        ];
    }

    /**
     * Mostrar página de consulta pública por DNI
     * 
     * VISTA A LLAMAR: vistas/consulta_publica.php
     * 
     * @return array Array con datos para la vista:
     *   [
     *     'title' => 'Consultar Estado del Carnet',
     *     'resultado' => array|null (si hay búsqueda realizada)
     *   ]
     */
    public function mostrarConsultaPublica(): array
    {
        $result = null;
        $dni = $_REQUEST['dni'] ?? null;
        if ($dni) {
            $dni = trim((string)$dni);
            $consulta = $this->consultarCarnetPorDNI($dni);
            if ($consulta['success']) $result = $consulta['carnet'];
            else $result = ['error' => $consulta['error'] ?? 'No se encontró información'];
        }

        $this->log('Public consultation page accessed', 'INFO', ['query_dni' => isset($dni)]);

        return [
            'title' => 'Consultar Estado del Carnet',
            'resultado' => $result
        ];
    }

    /**
     * Buscar estado de carnet por DNI (consulta pública)
     * 
     * @param string $dni DNI a consultar (formato: XX.XXX.XXX)
     * @return array Array con resultado:
     *   [
     *     'success' => bool,
     *     'carnet' => ['estado' => '...', 'vigencia' => '...', ...] | null,
     *     'error' => string | null
     *   ]
     */
    public function consultarCarnetPorDNI(string $dni): array
    {
        $dni = trim($dni);
        
        // Validación sintáctica del DNI público (formato: XX.XXX.XXX)
        if (!preg_match('/^\d{1,2}\.\d{3}\.\d{3}$/', $dni)) {
            return [
                'success' => false,
                'error' => 'Formato de DNI inválido (XX.XXX.XXX)',
                'carnet' => null
            ];
        }

        // limpiar puntos
        $dni_plain = str_replace('.', '', $dni);
        // intentar modelo CarnetModelo
        $carnet = null;
        if (class_exists('CarnetModelo')) {
            try { $cm = new CarnetModelo(); $carnet = $cm->obtenerPorDNI($dni_plain) ?: $cm->obtenerPorDNI($dni); } catch (\Exception $e) { $carnet = null; }
        }

        // Si no hay modelo, intentar consulta directa a DB (fallback)
        if (!$carnet) {
            $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) { $this->log('Public carnet query failed - no DB', 'WARN'); return ['success' => false, 'error' => 'Servicio no disponible', 'carnet' => null]; }
            require_once $conn; $pdo = Connection::getPDO();
            $sql = 'SELECT c.numero_carnet, c.fecha_emision, c.fecha_vencimiento, c.vigente FROM carnets c JOIN inscripciones i ON c.inscripcion_id = i.id JOIN usuarios u ON i.usuario_id = u.id WHERE u.dni = :dni OR u.dni = :dni_plain LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dni' => $dni, ':dni_plain' => $dni_plain]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        }

        if (!$carnet) {
            $this->log('Public carnet query - not found', 'INFO', ['dni' => $dni]);
            return ['success' => false, 'error' => 'No se encontró información', 'carnet' => null];
        }

        $public = [
            'numero_carnet' => $carnet['numero_carnet'] ?? $carnet['numero'] ?? null,
            'fecha_emision' => $carnet['fecha_emision'] ?? null,
            'fecha_vencimiento' => $carnet['fecha_vencimiento'] ?? null,
            'vigente' => isset($carnet['vigente']) ? ((int)$carnet['vigente'] === 1) : null
        ];

        $this->log('Public carnet query', 'INFO', ['dni' => $dni]);

        return ['success' => true, 'error' => null, 'carnet' => $public];
    }

    /**
     * Obtener información estadística de la plataforma
     * 
     * @return array Array con estadísticas:
     *   [
     *     'total_usuarios' => int,
     *     'carnets_vigentes' => int,
     *     'tramites_en_proceso' => int,
     *     'inscripciones_este_mes' => int
     *   ]
     */
    public function obtenerEstadisticas(): array
    {
        $conn = __DIR__ . '/../db/Connection.php'; if (!file_exists($conn)) return ['total_usuarios' => 0, 'carnets_vigentes' => 0, 'tramites_en_proceso' => 0, 'inscripciones_este_mes' => 0]; require_once $conn; $pdo = Connection::getPDO();
        try {
            $totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn();
            $carnets = (int)$pdo->query('SELECT COUNT(*) FROM carnets WHERE vigente = 1')->fetchColumn();
            $tramites = (int)$pdo->query("SELECT COUNT(*) FROM inscripciones WHERE estado_tramite_id IN (1,2)")->fetchColumn();
            $inicioMes = date('Y-m-01 00:00:00'); $ahora = date('Y-m-d H:i:s');
            $insMes = (int)$pdo->prepare('SELECT COUNT(*) FROM inscripciones WHERE fecha_inscripcion BETWEEN :inicio AND :fin')->execute([':inicio' => $inicioMes, ':fin' => $ahora]) ? $pdo->query("SELECT COUNT(*) FROM inscripciones WHERE fecha_inscripcion BETWEEN '$inicioMes' AND '$ahora'")->fetchColumn() : 0;
            return ['total_usuarios' => $totalUsuarios, 'carnets_vigentes' => $carnets, 'tramites_en_proceso' => $tramites, 'inscripciones_este_mes' => (int)$insMes];
        } catch (\Exception $e) {
            $this->log('Error obtener estadisticas', 'ERROR', ['error' => $e->getMessage()]);
            return ['total_usuarios' => 0, 'carnets_vigentes' => 0, 'tramites_en_proceso' => 0, 'inscripciones_este_mes' => 0];
        }
    }

    /**
     * Obtener información de contacto del sistema
     * 
     * @return array Array con datos de contacto:
     *   [
     *     'email' => 'admin@ManipulacionDeAlimentos.gov.ar',
     *     'telefono' => '...',
     *     'horario_atencion' => 'Lunes a Viernes 8:00 - 17:00',
     *     'dependencia' => 'Área de Manipulacion de Alimentos - Municipalidad'
     *   ]
     */
    public function obtenerInfoContacto(): array
    {
        // intentar cargar desde variables de entorno
        $email = getenv('CONTACT_EMAIL') ?: getenv('ADMIN_EMAIL') ?: 'admin@ManipulacionDeAlimentos.gov.ar';
        $telefono = getenv('CONTACT_PHONE') ?: '+54 XXX XXXX-XXXX';
        $horario = getenv('CONTACT_HORARIO') ?: 'Lunes a Viernes 8:00 - 17:00';
        $dependencia = getenv('CONTACT_DEPENDENCIA') ?: 'Área de Manipulacion de Alimentos - Municipalidad';
        return ['email' => $email, 'telefono' => $telefono, 'horario_atencion' => $horario, 'dependencia' => $dependencia];
    }

    /**
     * Mostrar página de ayuda/FAQ
     * 
     * VISTA A LLAMAR: vistas/ayuda.php
     * 
     * @return array Array con datos para la vista:
     *   [
     *     'title' => 'Centro de Ayuda',
     *     'preguntas_frecuentes' => [...]
     *   ]
     */
    public function mostrarAyuda(): array
    {
        $faqs = [];
        $pathMd = __DIR__ . '/../docs/FAQ.md';
        if (file_exists($pathMd)) {
            $faqs = ['raw' => file_get_contents($pathMd)];
        } else {
            // intentar cargar desde docs/README_PROJECT.md como fallback
            $alt = __DIR__ . '/../docs/README_PROJECT.md';
            if (file_exists($alt)) $faqs = ['raw' => file_get_contents($alt)];
        }
        $this->log('Help page accessed', 'INFO', ['faqs_loaded' => !empty($faqs)]);
        return ['title' => 'Centro de Ayuda', 'preguntas_frecuentes' => $faqs];
    }

    /**
     * Mostrar página de términos y condiciones
     * 
     * VISTA A LLAMAR: vistas/terminos.php
     * 
     * @return array Array con datos para la vista:
     *   [
     *     'title' => 'Términos y Condiciones',
     *     'contenido' => string
     *   ]
     */
    public function mostrarTerminos(): array
    {
        $contenido = '';
        $path = __DIR__ . '/../docs/TERMS.md';
        if (file_exists($path)) $contenido = file_get_contents($path);
        $this->log('Terms page accessed', 'INFO');
        return ['title' => 'Términos y Condiciones', 'contenido' => $contenido];
    }
}
