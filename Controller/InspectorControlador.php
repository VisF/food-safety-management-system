<?php
declare(strict_types=1);

/**
 * InspectorControlador - Funciones para inspectores de alimentos
 * 
 * Dependencias esperadas:
 * -  UsuarioService, CarnetService, InscripcionService
 * 
 * Vistas esperadas:
 * - vistas/panel_inspector.php
 * - vistas/busqueda_carnet.php
 * - vistas/detalle_carnet.php
 */

class InspectorControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/inspector_controller.log';
    
    private ?object $UsuarioService = null;
    private ?object $CarnetService = null;
    private ?object $InscripcionService = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->UsuarioService = new UsuarioService();
        $this->CarnetService = new CarnetService();
        $this->InscripcionService = new InscripcionService();

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

    // ==================== BÚSQUEDA DE USUARIOS ====================

    /**
     * Buscar usuario por DNI
     * 
     * @param string $dni DNI a buscar
     * @return array [
     *   'success' => bool,
     *   'usuario' => [
     *     'id' => int,
     *     'nombre' => string,
     *     'apellido' => string,
     *     'dni' => string,
     *     'email' => string,
     *     'carnet' => array|null
     *   ]|null,
     *   'message' => string
     * ]
     */
    public function buscarPorDNI(string $dni): array
    {
        try {
            // Validar formato de DNI (Argentina: 7-8 dígitos)
            $dni_limpio = preg_replace('/[^0-9]/', '', $dni);
            if (strlen($dni_limpio) < 7 || strlen($dni_limpio) > 8) {
                return [
                    'success' => false,
                    'usuario' => null,
                    'message' => 'Formato de DNI inválido. Debe contener 7-8 dígitos.'
                ];
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) {
                return ['success' => false, 'usuario' => null, 'message' => 'Conexión a BD no disponible'];
            }
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Obtener usuario por DNI
            $stmt = $pdo->prepare('SELECT id, nombre, apellido, dni, email FROM usuarios WHERE dni = :dni LIMIT 1');
            $stmt->execute([':dni' => $dni_limpio]);
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->log('DNI no encontrado', 'INFO', ['dni' => substr($dni, 0, 2) . '***']);
                return [
                    'success' => true,
                    'usuario' => null,
                    'message' => 'Usuario no encontrado'
                ];
            }

            // Obtener carnet vigente
            $stmt = $pdo->prepare('SELECT * FROM carnets WHERE usuario_id = :uid ORDER BY fecha_emision DESC LIMIT 1');
            $stmt->execute([':uid' => (int)$usuario['id']]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Agregar datos de carnet al usuario
            if ($carnet) {
                $carnet['vigente'] = strtotime($carnet['fecha_vencimiento'] ?? '') > time();
                $usuario['carnet'] = $carnet;
            }

            // Registrar búsqueda en auditoría
            $this->registrarAuditoria('BUSQUEDA_INSPECTOR', [
                'tipo_busqueda' => 'DNI',
                'usuario_id' => $usuario['id'] ?? null,
                'encontrado' => true
            ]);

            $this->log('Usuario encontrado por DNI', 'INFO', ['usuario_id' => $usuario['id']]);
            
            return [
                'success' => true,
                'usuario' => $usuario,
                'message' => 'Usuario encontrado'
            ];
        } catch (Exception $e) {
            $this->log('Error en búsqueda por DNI', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'usuario' => null,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado del carnet de un usuario
     * 
     * @param string $dni DNI del usuario
     * @return array|null Array con estado o null si no existe
     *   [
     *     'id' => int,
     *     'numero_carnet' => string,
     *     'estado' => 'vigente|vencido|cancelado|pendiente',
     *     'fecha_emision' => string,
     *     'fecha_vencimiento' => string,
     *     'vigente' => bool,
     *     'dias_para_vencer' => int|null
     *   ]
     */
    public function obtenerEstadoCarnet(string $dni): ?array
    {
        try {
            // Validar formato de DNI
            $dni_limpio = preg_replace('/[^0-9]/', '', $dni);
            if (strlen($dni_limpio) < 7 || strlen($dni_limpio) > 8) {
                $this->log('DNI inválido al consultar estado', 'WARNING', ['dni' => substr($dni, 0, 2) . '***']);
                return null;
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return null;
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Obtener usuario
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE dni = :dni LIMIT 1');
            $stmt->execute([':dni' => $dni_limpio]);
            $usr = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$usr) return null;

            // Obtener carnet más reciente
            $stmt = $pdo->prepare('SELECT * FROM carnets WHERE usuario_id = :uid ORDER BY fecha_emision DESC LIMIT 1');
            $stmt->execute([':uid' => (int)$usr['id']]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$carnet) return null;

            // Calcular estado basado en fecha de vencimiento
            $hoy = time();
            $fecha_venc = strtotime($carnet['fecha_vencimiento'] ?? '');
            $vigente = $fecha_venc > $hoy;
            $dias_para_vencer = $vigente ? (int)floor(($fecha_venc - $hoy) / 86400) : null;

            if ($vigente) {
                $estado = 'vigente';
            } else {
                $estado = 'vencido';
            }

            return [
                'id' => (int)$carnet['id'],
                'numero_carnet' => $carnet['numero_carnet'] ?? '',
                'estado' => $estado,
                'fecha_emision' => $carnet['fecha_emision'] ?? '',
                'fecha_vencimiento' => $carnet['fecha_vencimiento'] ?? '',
                'vigente' => $vigente,
                'dias_para_vencer' => $dias_para_vencer
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener estado del carnet', 'ERROR', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar si un carnet está vigente
     * 
     * @param string $dni DNI a verificar
     * @return array [
     *   'success' => bool,
     *   'vigente' => bool,
     *   'mensaje' => string,
     *   'carnet' => array|null
     * ]
     */
    public function verificarVigencia(string $dni): array
    {
        try {
            // Obtener carnet del usuario
            $carnet = $this->obtenerEstadoCarnet($dni);
            if (!$carnet) {
                return [
                    'success' => true,
                    'vigente' => false,
                    'mensaje' => 'Carnet no encontrado',
                    'carnet' => null
                ];
            }

            // Comparar fecha de vencimiento con fecha actual (ya hecho en obtenerEstadoCarnet)
            $vigente = (bool)($carnet['vigente'] ?? false);

            // Retornar estado de vigencia
            return [
                'success' => true,
                'vigente' => $vigente,
                'mensaje' => $vigente ? 'Carnet vigente' : 'Carnet vencido',
                'carnet' => $carnet
            ];
        } catch (Exception $e) {
            $this->log('Error al verificar vigencia', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'vigente' => false,
                'mensaje' => 'Error al verificar vigencia: ' . $e->getMessage(),
                'carnet' => null
            ];
        }
    }

    /**
     * Obtener ruta para descargar PDF del carnet
     * 
     * @param string $dni DNI del usuario
     * @return array [
     *   'success' => bool,
     *   'pdf_url' => string|null,
     *   'mensaje' => string,
     *   'archivo' => string|null (nombre del archivo)
     * ]
     */
    public function obtenerCarnetPDF(string $dni): array
    {
        try {
            $dni_limpio = preg_replace('/[^0-9]/', '', $dni);
            if (strlen($dni_limpio) < 7 || strlen($dni_limpio) > 8) {
                return [
                    'success' => false,
                    'pdf_url' => null,
                    'mensaje' => 'DNI inválido',
                    'archivo' => null
                ];
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) {
                return ['success' => false, 'pdf_url' => null, 'mensaje' => 'BD no disponible', 'archivo' => null];
            }
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Validar que el usuario existe
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE dni = :dni LIMIT 1');
            $stmt->execute([':dni' => $dni_limpio]);
            $usr = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$usr) {
                return ['success' => false, 'pdf_url' => null, 'mensaje' => 'Usuario no encontrado', 'archivo' => null];
            }

            // Validar que tiene carnet y obtener ruta PDF
            $stmt = $pdo->prepare('SELECT ruta_pdf FROM carnets WHERE usuario_id = :uid ORDER BY fecha_emision DESC LIMIT 1');
            $stmt->execute([':uid' => (int)$usr['id']]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$carnet || !$carnet['ruta_pdf']) {
                return ['success' => false, 'pdf_url' => null, 'mensaje' => 'Carnet no disponible', 'archivo' => null];
            }

            // Obtener ruta del archivo PDF guardado
            $ruta_pdf = $carnet['ruta_pdf'];
            if (!file_exists($ruta_pdf)) {
                return ['success' => false, 'pdf_url' => null, 'mensaje' => 'Archivo PDF no encontrado en servidor', 'archivo' => null];
            }

            // Retornar URL relativa para descarga (normalizar ruta)
            $url_relativa = strpos($ruta_pdf, '/') === 0 ? substr($ruta_pdf, 1) : $ruta_pdf;
            $nombre_archivo = basename($ruta_pdf);

            $this->log('PDF de carnet solicitado', 'INFO', ['usuario_id' => $usr['id']]);
            
            return [
                'success' => true,
                'pdf_url' => $url_relativa,
                'mensaje' => 'PDF disponible',
                'archivo' => $nombre_archivo
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener PDF', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'pdf_url' => null,
                'mensaje' => 'Error al obtener carnet: ' . $e->getMessage(),
                'archivo' => null
            ];
        }
    }

    /**
     * Buscar usuario por apellido
     * 
     * @param string $apellido Apellido a buscar
     * @return array [
     *   'success' => bool,
     *   'usuarios' => [
     *     ['id' => int, 'nombre' => string, 'apellido' => string, 'dni' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function buscarPorApellido(string $apellido): array
    {
        try {
            if (trim($apellido) === '') {
                return ['success' => false, 'usuarios' => [], 'total' => 0];
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['success' => false, 'usuarios' => [], 'total' => 0];
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Buscar usuarios por apellido (limitar a 50)
            $stmt = $pdo->prepare('SELECT id, nombre, apellido, dni, email FROM usuarios WHERE apellido LIKE :ap ORDER BY apellido, nombre LIMIT 50');
            $stmt->execute([':ap' => '%' . $apellido . '%']);
            $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Incluir estado de carnet para cada usuario
            foreach ($usuarios as &$usr) {
                $stmtC = $pdo->prepare('SELECT id, numero_carnet, fecha_vencimiento FROM carnets WHERE usuario_id = :uid ORDER BY fecha_emision DESC LIMIT 1');
                $stmtC->execute([':uid' => (int)$usr['id']]);
                $carnet = $stmtC->fetch(\PDO::FETCH_ASSOC);
                if ($carnet) {
                    $carnet['vigente'] = strtotime($carnet['fecha_vencimiento'] ?? '') > time();
                    $usr['carnet'] = $carnet;
                } else {
                    $usr['carnet'] = null;
                }
            }

            $this->log('Búsqueda por apellido realizada', 'INFO', ['apellido' => $apellido, 'resultados' => count($usuarios)]);
            
            return [
                'success' => true,
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ];
        } catch (Exception $e) {
            $this->log('Error en búsqueda por apellido', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtener datos públicos de un usuario (información no sensible)
     * 
     * @param string $dni DNI del usuario
     * @return array [
     *   'success' => bool,
     *   'datos' => [
     *     'nombre' => string,
     *     'apellido' => string,
     *     'carnet_vigente' => bool,
     *     'numero_carnet' => string|null,
     *     'fecha_vencimiento' => string|null
     *   ]|null
     * ]
     */
    public function obtenerDatosPublicos(string $dni): array
    {
        try {
            $dni_limpio = preg_replace('/[^0-9]/', '', $dni);
            if (strlen($dni_limpio) < 7 || strlen($dni_limpio) > 8) {
                return ['success' => false, 'datos' => null];
            }

            $connFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($connFile)) return ['success' => false, 'datos' => null];
            require_once $connFile;
            $pdo = Connection::getPDO();

            // Obtener usuario por DNI
            $stmt = $pdo->prepare('SELECT id, nombre, apellido FROM usuarios WHERE dni = :dni LIMIT 1');
            $stmt->execute([':dni' => $dni_limpio]);
            $usr = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$usr) {
                return ['success' => false, 'datos' => null];
            }

            // Obtener estado del carnet
            $stmt = $pdo->prepare('SELECT numero_carnet, fecha_vencimiento FROM carnets WHERE usuario_id = :uid ORDER BY fecha_emision DESC LIMIT 1');
            $stmt->execute([':uid' => (int)$usr['id']]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC);

            $carnet_vigente = false;
            $numero_carnet = null;
            $fecha_vencimiento = null;
            if ($carnet) {
                $carnet_vigente = strtotime($carnet['fecha_vencimiento'] ?? '') > time();
                $numero_carnet = $carnet['numero_carnet'];
                $fecha_vencimiento = $carnet['fecha_vencimiento'];
            }

            // Retornar solo información pública
            return [
                'success' => true,
                'datos' => [
                    'nombre' => $usr['nombre'],
                    'apellido' => $usr['apellido'],
                    'carnet_vigente' => $carnet_vigente,
                    'numero_carnet' => $numero_carnet,
                    'fecha_vencimiento' => $fecha_vencimiento
                ]
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener datos públicos', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'datos' => null
            ];
        }
    }

    // ==================== GESTIÓN DE INSPECCIONES ====================

    /**
     * Registrar detección/inspección de un usuario
     * 
     * @param string $dni DNI del usuario inspeccionado
     * @param array $datos Array con datos: [
     *   'ubicacion' => string,
     *   'notas' => string,
     *   'carnet_presentado' => bool,
     *   'carnet_valido' => bool,
     *   'incidencias' => string|null
     * ]
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_inspeccion' => int|null
     * ]
     */
    public function registrarDeteccion(string $dni, array $datos): array
    {
        try {
            // TODO: Validar DNI
            // TODO: Crear registro de inspección
            // TODO: Guardar ubicación, notas, validez del carnet
            // TODO: Registrar en auditoría
            
            $this->log('Detección registrada', 'INFO', ['dni' => substr($dni, 0, 2) . '***']);
            
            return [
                'success' => true,
                'message' => 'Inspección registrada correctamente',
                'id_inspeccion' => null
            ];
        } catch (Exception $e) {
            $this->log('Error al registrar detección', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al registrar inspección: ' . $e->getMessage(),
                'id_inspeccion' => null
            ];
        }
    }

    /**
     * Obtener inspecciones recientes del inspector actual
     * 
     * @return array [
     *   'success' => bool,
     *   'inspecciones' => [
     *     ['id' => int, 'dni' => string, 'nombre' => string, 'fecha' => string, 'carnet_valido' => bool, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerInspeccionesRecientes(): array
    {
        try {
            // TODO: Obtener ID del inspector actual (de sesión)
            // TODO: Llamar a método para obtener últimas 20 inspecciones del usuario
            // TODO: Ordenar por fecha descendente
            
            return [
                'success' => true,
                'inspecciones' => [],
                'total' => 0
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener inspecciones recientes', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'inspecciones' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Listar carnets vencidos (para administración)
     * 
     * @return array [
     *   'success' => bool,
     *   'carnets' => [
     *     ['id' => int, 'usuario' => array, 'numero_carnet' => string, 'fecha_vencimiento' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function listarCarnetesVencidos(): array
    {
        try {
            // TODO: Llamar a $this->CarnetService->obtenerVencidos()
            // TODO: Incluir datos del usuario
            
            return [
                'success' => true,
                'carnets' => [],
                'total' => 0
            ];
        } catch (Exception $e) {
            $this->log('Error al listar carnets vencidos', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'carnets' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Iniciar proceso de renovación de carnet
     * 
     * @param int $id_carnet ID del carnet
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'carnet' => array
     * ]
     */
    public function renovarCarnet(int $id_carnet): array
    {
        try {
            // TODO: Validar que el carnet existe
            // TODO: Crear inscripción para renovación
            // TODO: Cambiar estado de carnet
            // TODO: Registrar en auditoría
            
            $this->log('Proceso de renovación iniciado', 'INFO', ['id_carnet' => $id_carnet]);
            
            return [
                'success' => true,
                'message' => 'Proceso de renovación iniciado',
                'carnet' => []
            ];
        } catch (Exception $e) {
            $this->log('Error al renovar carnet', 'ERROR', ['id_carnet' => $id_carnet, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al renovar carnet: ' . $e->getMessage(),
                'carnet' => []
            ];
        }
    }

    /**
     * Registrar alerta o irregularidad de un usuario
     * 
     * @param string $dni DNI del usuario
     * @param string $motivo Motivo de la alerta
     * @return array [
     *   'success' => bool,
     *   'message' => string,
     *   'id_alerta' => int|null
     * ]
     */
    public function registrarAlerta(string $dni, string $motivo): array
    {
        try {
            // TODO: Validar DNI
            // TODO: Crear registro de alerta
            // TODO: Guardar motivo y fecha
            // TODO: Notificar a admin si es necesario
            
            $this->log('Alerta registrada', 'WARNING', ['dni' => substr($dni, 0, 2) . '***', 'motivo' => substr($motivo, 0, 50)]);
            
            return [
                'success' => true,
                'message' => 'Alerta registrada, se notificará al administrador',
                'id_alerta' => null
            ];
        } catch (Exception $e) {
            $this->log('Error al registrar alerta', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al registrar alerta: ' . $e->getMessage(),
                'id_alerta' => null
            ];
        }
    }

    /**
     * Obtener historial de búsquedas del inspector
     * 
     * @return array [
     *   'success' => bool,
     *   'historial' => [
     *     ['id' => int, 'dni' => string, 'nombre' => string, 'fecha' => string, 'resultado' => string, ...],
     *     ...
     *   ],
     *   'total' => int
     * ]
     */
    public function obtenerHistorialBusquedas(): array
    {
        try {
            // TODO: Obtener ID del inspector actual
            // TODO: Obtener todas las búsquedas registradas
            // TODO: Limitar a últimas 100 búsquedas
            // TODO: Incluir resultado (carnet encontrado/no encontrado)
            
            return [
                'success' => true,
                'historial' => [],
                'total' => 0
            ];
        } catch (Exception $e) {
            $this->log('Error al obtener historial', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'historial' => [],
                'total' => 0
            ];
        }
    }
}
