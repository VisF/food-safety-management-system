<?php
declare(strict_types=1);


/**
 * NotificacionControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * NotificacionControlador
 *
 * Responsabilidad:
 * Coordinar las operaciones de notificaciones.
 * Toda la lógica de negocio y acceso a datos se delega
 * a los Services correspondientes.
 */

require_once __DIR__ . '/../Servicios/NotificacionService.php';
require_once __DIR__ . '/../Servicios/UsuarioService.php';
require_once __DIR__ . '/../Servicios/InscripcionService.php';
require_once __DIR__ . '/../Servicios/DocumentoService.php';
require_once __DIR__ . '/../Servicios/ResultadoExamenService.php';
require_once __DIR__ . '/../Servicios/CarnetService.php';

class NotificacionControlador
{
    private const LOG_FILE =
        __DIR__ .
        '/../logs/notificacion_controller.log';

    private const TIPO_EMAIL = 'email';
    private const TIPO_SMS = 'sms';
    private const TIPO_SISTEMA = 'sistema';

    private const TIPO_CONFIRMACION_INSCRIPCION =
        'confirmacion_inscripcion';

    private const TIPO_CAMBIO_ESTADO =
        'cambio_estado';

    private const TIPO_RECHAZO_DOCUMENTACION =
        'rechazo_documentacion';

    private const TIPO_APROBACION_DOCUMENTACION =
        'aprobacion_documentacion';

    private const TIPO_RESULTADO_EXAMEN =
        'resultado_examen';

    private const TIPO_CARNET_EMITIDO =
        'carnet_emitido';

    private const TIPO_RECUPERACION_PASSWORD =
        'recuperacion_password';

    private const TIPO_COMPROBANTE =
        'comprobante';

    private const EMAIL_CONFIG = [

        'smtp_host' =>
            'localhost',

        'smtp_port' =>
            587,

        'remitente' =>
            'noreply@ManipulacionDeAlimentos.local',

        'nombre_remitente' =>
            'Sistema de Manipulación de Alimentos'
    ];

    private NotificacionService
        $notificacionService;

    private UsuarioService
        $usuarioService;

    private InscripcionService
        $inscripcionService;

    private DocumentoService
        $documentoService;

    private ResultadoExamenService
        $resultadoExamenService;

    private CarnetService
        $carnetService;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(
            dirname(self::LOG_FILE),
            0755,
            true
        );

        $this->notificacionService =
            new NotificacionService();

        $this->usuarioService =
            new UsuarioService();

        $this->inscripcionService =
            new InscripcionService();

        $this->documentoService =
            new DocumentoService();

        $this->resultadoExamenService =
            new ResultadoExamenService();

        $this->carnetService =
            new CarnetService();
    }

    /**
     * Registrar eventos.
     */
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void {

        $timestamp =
            date('Y-m-d H:i:s');

        $usuarioId =
            $_SESSION['usuario_id']
            ?? 'anonimo';

        $mensaje =
            "[{$timestamp}] Usuario: {$usuarioId} | {$evento} | "
            . json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            )
            . PHP_EOL;

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }




    /**
 * Enviar notificación genérica a un usuario.
 *
 * @param int $id_usuario ID del usuario destinatario
 * @param string $tipo Tipo de notificación
 * @param array $datos Datos utilizados para la plantilla
 * @return array Resultado de la operación
 */
public function enviarNotificacion(
    int $id_usuario,
    string $tipo,
    array $datos
): array
{
    try {

        $resultado =
            $this->notificacionService
                ->enviarNotificacion(
                    $id_usuario,
                    $tipo,
                    $datos
                );

        if (!$resultado['success']) {

            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_usuario' => $id_usuario,
                'tipo' => $tipo,
                'mensaje' => $resultado['mensaje']
            ];
        }

        $this->registrarLog(
            'ENVIAR_NOTIFICACION',
            [
                'id_usuario' =>
                    $id_usuario,

                'tipo' =>
                    $tipo,

                'id_notificacion' =>
                    $resultado['notificacion']['id'] ?? 0
            ]
        );

        return [
            'éxito' => true,
            'id_notificacion' =>
                $resultado['notificacion']['id'] ?? 0,
            'id_usuario' => $id_usuario,
            'tipo' => $tipo,
            'mensaje' => $resultado['mensaje']
        ];

    } catch (\Exception $e) {

        $this->registrarLog(
            'ERROR_ENVIAR_NOTIFICACION',
            [
                'error' =>
                    $e->getMessage()
            ]
        );

        return [
            'éxito' => false,
            'id_notificacion' => 0,
            'id_usuario' => $id_usuario,
            'tipo' => $tipo,
            'mensaje' =>
                'Error al enviar notificación: '
                . $e->getMessage()
        ];
    }
}
    /**
     * Enviar alerta de cambio de estado del trámite
     *
     * @param int $id_inscripcion ID de la inscripción
     * @param string $nuevo_estado Nuevo estado del trámite
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'nuevo_estado' => string]
     */
    public function enviarAlertaEstado(int $id_inscripcion, string $nuevo_estado): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener inscripción y usuario asociado
            $stmt = $pdo->prepare('SELECT i.*, u.email, u.nombre, u.apellido FROM inscripciones i LEFT JOIN usuarios u ON u.id = i.usuario_id WHERE i.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $insc = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            if (!$insc) {
                // Si no existe, igualmente registrar pero avisar que no se pudo notificar
                $datos = [
                    'id_inscripcion' => $id_inscripcion,
                    'nuevo_estado' => $nuevo_estado,
                    'fecha_cambio' => date('Y-m-d H:i:s')
                ];
                $resultado = $this->enviarNotificacion(0, self::TIPO_CAMBIO_ESTADO, $datos);
                $this->registrarLog('ENVIAR_ALERTA_ESTADO_NO_ENCONTRADA', ['id_inscripcion' => $id_inscripcion, 'nuevo_estado' => $nuevo_estado]);
                return array_merge($resultado, ['nuevo_estado' => $nuevo_estado]);
            }

            $usuario_id = (int)($insc['usuario_id'] ?? 0);
            $email = $insc['email'] ?? '';

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'nuevo_estado' => $nuevo_estado,
                'fecha_cambio' => date('Y-m-d H:i:s'),
                'asunto' => 'Estado del Trámite Actualizado: ' . $nuevo_estado
            ];

            if ($email) $datos['email'] = $email;

            $resultado = $this->enviarNotificacion($usuario_id > 0 ? $usuario_id : 0, self::TIPO_CAMBIO_ESTADO, $datos);

            $this->registrarLog('ENVIAR_ALERTA_ESTADO', [
                'id_inscripcion' => $id_inscripcion,
                'usuario_id' => $usuario_id,
                'nuevo_estado' => $nuevo_estado
            ]);

            return array_merge($resultado, [
                'nuevo_estado' => $nuevo_estado
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_ALERTA_ESTADO', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_inscripcion' => $id_inscripcion,
                'nuevo_estado' => $nuevo_estado,
                'mensaje' => 'Error al enviar alerta de estado'
            ];
        }
    }

    /**
     * Enviar comprobante de inscripción por email
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'comprobante_id' => string]
     */
    public function enviarComprobante(int $id_inscripcion): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener datos de la inscripción y usuario
            $stmt = $pdo->prepare('SELECT i.*, u.email, u.nombre, u.apellido FROM inscripciones i LEFT JOIN usuarios u ON u.id = i.usuario_id WHERE i.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                throw new \Exception('Inscripción no encontrada');
            }

            $usuario_id = (int)($row['usuario_id'] ?? 0);

            // Generar contenido HTML del comprobante usando la plantilla básica
            $plantilla = $this->plantillaComprobante(['id_inscripcion' => $id_inscripcion, 'fecha_comprobante' => date('Y-m-d H:i:s')]);
            $contenido = $this->aplicarVariablesPlantilla($plantilla, ['id_inscripcion' => $id_inscripcion, 'fecha_comprobante' => date('Y-m-d H:i:s')] );

            // Guardar comprobante en descargas/
            $dir = __DIR__ . '/../descargas';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $comprobante_id = 'COMP-' . $id_inscripcion . '-' . date('YmdHis');
            $filename = $comprobante_id . '.html';
            $filepath = $dir . '/' . $filename;
            file_put_contents($filepath, $contenido);

            // Preparar datos para notificación (link relativo)
            $link = '/descargas/' . $filename;
            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_comprobante' => date('Y-m-d H:i:s'),
                'ruta_comprobante' => $link,
                'comprobante_id' => $comprobante_id
            ];

            // Enviar notificación al usuario si existe, sino registrar notificación sin usuario
            $resultado = $this->enviarNotificacion($usuario_id > 0 ? $usuario_id : 0, self::TIPO_COMPROBANTE, $datos);

            // Registrar referencia en logs y devolver resultado
            $this->registrarLog('ENVIAR_COMPROBANTE', ['id_inscripcion' => $id_inscripcion, 'usuario_id' => $usuario_id, 'ruta' => $filepath]);

            return array_merge($resultado, ['comprobante_id' => $comprobante_id, 'ruta_archivo' => $filepath]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_COMPROBANTE', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_inscripcion' => $id_inscripcion,
                'comprobante_id' => ''
            ];
        }
    }

    /**
     * Enviar link de recuperación de password
     *
     * @param string $email Email del usuario
     * @return array ['éxito' => bool, 'email' => string, 'token_enviado' => bool]
     */
    public function enviarRecuperacionPassword(string $email): array
    {
        try {
            // Validar email
            // Comprobar formato básico del email destino
            if (!$this->validarEmailDestino($email)) {
                throw new \Exception('Email inválido');
            }
            $pdo = $this->pdo();

            // Generar token seguro
            $token = bin2hex(random_bytes(16));

            // Buscar usuario por email (si existe)
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $u = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            $usuario_id = $u ? (int)$u['id'] : 0;

            // Intentar persistir token en tabla recovery_tokens (si existe)
            try {
                $ins = $pdo->prepare('INSERT INTO recovery_tokens (usuario_id, token, expiracion) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
                $ins->execute([':uid' => $usuario_id, ':token' => $token]);
            } catch (\Throwable $t) {
                // Tabla recovery_tokens puede no existir en entornos simples; no detener el flujo
            }

            // Construir link de recuperación (ruta relativa)
            $link = '/reset-password?token=' . $token;

            $datos = [
                'link_recuperacion' => $link,
                'fecha_solicitud' => date('Y-m-d H:i:s')
            ];

            // Enviar notificación por correo si se encontró usuario
            $enviado = false;
            if ($usuario_id > 0) {
                $res = $this->enviarNotificacion($usuario_id, self::TIPO_RECUPERACION_PASSWORD, $datos);
                $enviado = !empty($res['id_notificacion']);
            }

            $this->registrarLog('ENVIAR_RECUPERACION_PASSWORD', ['email' => $email, 'usuario_id' => $usuario_id, 'enviado' => $enviado]);

            return [
                'éxito' => true,
                'email' => $email,
                'token_enviado' => $enviado
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_RECUPERACION_PASSWORD', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'email' => $email,
                'token_enviado' => false,
                'mensaje' => 'Error al enviar link de recuperación'
            ];
        }
    }

    /**
     * Enviar confirmación de inscripción a examen
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'confirmacion' => bool]
     */
    public function enviarConfirmacionInscripcion(int $id_inscripcion): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener datos de la inscripción y usuario
            $stmt = $pdo->prepare('SELECT i.*, u.email, u.nombre, u.apellido FROM inscripciones i LEFT JOIN usuarios u ON u.id = i.usuario_id WHERE i.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                throw new \Exception('Inscripción no encontrada');
            }

            $usuario_id = (int)($row['usuario_id'] ?? 0);

            // Intentar resolver nombre de curso/examen si está disponible
            $curso_nombre = '';
            if (!empty($row['curso_nombre'])) {
                $curso_nombre = $row['curso_nombre'];
            } elseif (!empty($row['curso'])) {
                $curso_nombre = $row['curso'];
            } elseif (!empty($row['curso_id'])) {
                try {
                    $s = $pdo->prepare('SELECT nombre FROM cursos WHERE id = :id LIMIT 1');
                    $s->execute([':id' => (int)$row['curso_id']]);
                    $r = $s->fetch(\PDO::FETCH_ASSOC);
                    if ($r) $curso_nombre = $r['nombre'];
                } catch (\Throwable $t) {
                    // ignorar si tabla no existe
                }
            }

            $lugar = $row['lugar'] ?? $row['sede'] ?? '';
            $hora = $row['hora'] ?? $row['fecha_hora'] ?? '';

            // Generar comprobante y obtener ruta (se envía también como notificación de tipo comprobante)
            $comprobanteInfo = $this->enviarComprobante($id_inscripcion);
            $ruta_comprobante = $comprobanteInfo['ruta_archivo'] ?? ($comprobanteInfo['ruta'] ?? '');

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_confirmacion' => date('Y-m-d H:i:s'),
                'curso' => $curso_nombre,
                'lugar' => $lugar,
                'hora' => $hora,
                'ruta_comprobante' => $ruta_comprobante
            ];

            $resultado = $this->enviarNotificacion($usuario_id > 0 ? $usuario_id : 0, self::TIPO_CONFIRMACION_INSCRIPCION, $datos);

            $this->registrarLog('ENVIAR_CONFIRMACION_INSCRIPCION', ['id_inscripcion' => $id_inscripcion, 'usuario_id' => $usuario_id, 'ruta_comprobante' => $ruta_comprobante]);

            return array_merge($resultado, [
                'confirmacion' => true,
                'ruta_comprobante' => $ruta_comprobante
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_CONFIRMACION_INSCRIPCION', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_inscripcion' => $id_inscripcion,
                'confirmacion' => false
            ];
        }
    }

    /**
     * Enviar notificación de rechazo de documentación
     *
     * @param int $id_documento ID del documento rechazado
     * @param string $motivo Motivo del rechazo
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'documento_id' => int]
     */
    public function enviarRechazoDocs(int $id_documento, string $motivo): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener documento
            $stmt = $pdo->prepare('SELECT * FROM documentos WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_documento]);
            $doc = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$doc) {
                throw new \Exception('Documento no encontrado');
            }

            $inscripcion_id = isset($doc['inscripcion_id']) ? (int)$doc['inscripcion_id'] : 0;
            $ruta_doc = $doc['ruta'] ?? $doc['path'] ?? $doc['filename'] ?? '';
            $nombre_doc = $doc['nombre'] ?? $doc['titulo'] ?? '';

            // Resolver usuario a partir de la inscripción si es posible
            $usuario_id = 0;
            if ($inscripcion_id > 0) {
                $s = $pdo->prepare('SELECT usuario_id FROM inscripciones WHERE id = :id LIMIT 1');
                $s->execute([':id' => $inscripcion_id]);
                $r = $s->fetch(\PDO::FETCH_ASSOC);
                if ($r) $usuario_id = (int)$r['usuario_id'];
            }

            // Si aún no tenemos usuario, intentar si el documento guarda usuario_id
            if ($usuario_id === 0 && !empty($doc['usuario_id'])) {
                $usuario_id = (int)$doc['usuario_id'];
            }

            // Intentar obtener email si no hay usuario resuelto
            $email = '';
            if ($usuario_id > 0) {
                $q = $pdo->prepare('SELECT email FROM usuarios WHERE id = :id LIMIT 1');
                $q->execute([':id' => $usuario_id]);
                $uu = $q->fetch(\PDO::FETCH_ASSOC);
                if ($uu) $email = $uu['email'] ?? '';
            }
            if (!$email && !empty($doc['email'])) {
                $email = $doc['email'];
            }

            $datos = [
                'id_documento' => $id_documento,
                'id_inscripcion' => $inscripcion_id,
                'motivo' => $motivo,
                'fecha_rechazo' => date('Y-m-d H:i:s'),
                'ruta_documento' => $ruta_doc,
                'nombre_documento' => $nombre_doc,
            ];

            if ($email) $datos['email'] = $email;

            // Enviar notificación al usuario si existe, sino usar email si está disponible
            $destino = $usuario_id > 0 ? $usuario_id : 0;
            $resultado = $this->enviarNotificacion($destino, self::TIPO_RECHAZO_DOCUMENTACION, $datos);

            $this->registrarLog('ENVIAR_RECHAZO_DOCS', [
                'id_documento' => $id_documento,
                'motivo' => $motivo,
                'id_inscripcion' => $inscripcion_id,
                'usuario_id' => $usuario_id,
                'email' => $email
            ]);

            return array_merge($resultado, [
                'documento_id' => $id_documento,
                'id_inscripcion' => $inscripcion_id,
                'usuario_id' => $usuario_id
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_RECHAZO_DOCS', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_documento' => $id_documento,
                'mensaje' => 'Error al enviar notificación de rechazo'
            ];
        }
    }

    /**
     * Enviar notificación de aprobación de documentación
     *
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'documentacion_aprobada' => bool]
     */
    public function enviarAprobacionDocs(int $id_inscripcion): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener inscripción y resolver usuario
            $stmt = $pdo->prepare('SELECT i.*, u.email, u.nombre, u.apellido FROM inscripciones i LEFT JOIN usuarios u ON u.id = i.usuario_id WHERE i.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_inscripcion]);
            $ins = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$ins) {
                throw new \Exception('Inscripción no encontrada');
            }

            $usuario_id = (int)($ins['usuario_id'] ?? 0);
            $email = $ins['email'] ?? '';

            // Contar documentos totales y aprobados para la inscripción
            $totalDocsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM documentos WHERE inscripcion_id = :id');
            $totalDocsStmt->execute([':id' => $id_inscripcion]);
            $totalRow = $totalDocsStmt->fetch(\PDO::FETCH_ASSOC);
            $total = $totalRow ? (int)$totalRow['total'] : 0;

            // Aprobados: intentamos soportar campo booleano 'aprobado' o texto 'estado'
            $approvedStmt = $pdo->prepare("SELECT COUNT(*) AS aprobados FROM documentos WHERE inscripcion_id = :id AND (aprobado = 1 OR estado = 'aprobado')");
            $approvedStmt->execute([':id' => $id_inscripcion]);
            $apRow = $approvedStmt->fetch(\PDO::FETCH_ASSOC);
            $aprobados = $apRow ? (int)$apRow['aprobados'] : 0;

            $todosAprobados = ($total > 0 && $aprobados >= $total) || ($total === 0 && $aprobados > 0);

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_aprobacion' => date('Y-m-d H:i:s'),
                'total_documentos' => $total,
                'documentos_aprobados' => $aprobados,
                'todos_aprobados' => $todosAprobados
            ];

            if ($email) $datos['email'] = $email;

            // Enviar notificación al usuario si se resolvió, si no se intentará con email
            $destino = $usuario_id > 0 ? $usuario_id : 0;
            $resultado = $this->enviarNotificacion($destino, self::TIPO_APROBACION_DOCUMENTACION, $datos);

            $this->registrarLog('ENVIAR_APROBACION_DOCS', [
                'id_inscripcion' => $id_inscripcion,
                'usuario_id' => $usuario_id,
                'total_documentos' => $total,
                'documentos_aprobados' => $aprobados,
                'todos_aprobados' => $todosAprobados
            ]);

            return array_merge($resultado, [
                'documentacion_aprobada' => (bool)$todosAprobados,
                'total_documentos' => $total,
                'documentos_aprobados' => $aprobados
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_APROBACION_DOCS', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_inscripcion' => $id_inscripcion,
                'documentacion_aprobada' => false
            ];
        }
    }

    /**
     * Enviar resultado de examen (aprobado o reprobado)
     *
     * @param int $id_resultado ID del resultado del examen
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'resultado' => string, 'nota' => float, 'aprobado' => bool]
     */
    public function enviarResultadoExamen(int $id_resultado): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener resultado del examen con inscripción
            $stmt = $pdo->prepare('SELECT r.*, i.usuario_id, i.id as inscripcion_id FROM resultado_examen r LEFT JOIN inscripciones i ON i.id = r.inscripcion_id WHERE r.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_resultado]);
            $res = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            if (!$res) {
                throw new \Exception('Resultado de examen no encontrado');
            }

            $usuario_id = (int)($res['usuario_id'] ?? 0);
            $nota = (float)($res['nota'] ?? 0.0);
            $aprobado = (bool)($res['aprobado'] ?? false);
            $inscripcion_id = (int)($res['inscripcion_id'] ?? 0);

            // Obtener usuario si existe
            $email = '';
            if ($usuario_id > 0) {
                $q = $pdo->prepare('SELECT email, nombre, apellido FROM usuarios WHERE id = :id LIMIT 1');
                $q->execute([':id' => $usuario_id]);
                $u = $q->fetch(\PDO::FETCH_ASSOC);
                if ($u) $email = $u['email'] ?? '';
            }

            $estado = $aprobado ? 'APROBADO' : 'REPROBADO';
            $asunto = 'Resultado del Examen: ' . $estado;

            $datos = [
                'id_resultado' => $id_resultado,
                'id_inscripcion' => $inscripcion_id,
                'nota' => $nota,
                'aprobado' => $aprobado,
                'estado' => $estado,
                'fecha_resultado' => date('Y-m-d H:i:s'),
                'asunto' => $asunto
            ];

            if ($email) $datos['email'] = $email;

            // Enviar notificación
            $resultado = $this->enviarNotificacion($usuario_id > 0 ? $usuario_id : 0, self::TIPO_RESULTADO_EXAMEN, $datos);

            $this->registrarLog('ENVIAR_RESULTADO_EXAMEN', ['id_resultado' => $id_resultado, 'usuario_id' => $usuario_id, 'nota' => $nota, 'aprobado' => $aprobado]);

            return array_merge($resultado, [
                'resultado' => $estado,
                'nota' => $nota,
                'aprobado' => $aprobado
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_RESULTADO_EXAMEN', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_resultado' => $id_resultado,
                'mensaje' => 'Error al enviar resultado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar notificación de carnet emitido
     *
     * @param int $id_carnet ID del carnet
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'carnet_numero' => string, 'ruta_descarga' => string]
     */
    public function enviarCarnetEmitido(int $id_carnet): array
    {
        try {
            $pdo = $this->pdo();

            // Obtener carnet con inscripción y usuario
            $stmt = $pdo->prepare('SELECT c.*, i.usuario_id, i.id as inscripcion_id FROM carnets c LEFT JOIN inscripciones i ON i.id = c.inscripcion_id WHERE c.id = :id LIMIT 1');
            $stmt->execute([':id' => $id_carnet]);
            $carnet = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            if (!$carnet) {
                throw new \Exception('Carnet no encontrado');
            }

            $usuario_id = (int)($carnet['usuario_id'] ?? 0);
            $inscripcion_id = (int)($carnet['inscripcion_id'] ?? 0);
            $numero_carnet = $carnet['numero_carnet'] ?? ('CARNET-' . $id_carnet . '-' . date('Y'));
            $fecha_emision = $carnet['fecha_emision'] ?? date('Y-m-d');
            $fecha_vencimiento = $carnet['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+2 years'));
            $ruta_pdf = $carnet['ruta_pdf'] ?? '';

            // Obtener usuario si existe
            $email = '';
            $nombre = '';
            if ($usuario_id > 0) {
                $q = $pdo->prepare('SELECT email, nombre, apellido FROM usuarios WHERE id = :id LIMIT 1');
                $q->execute([':id' => $usuario_id]);
                $u = $q->fetch(\PDO::FETCH_ASSOC);
                if ($u) {
                    $email = $u['email'] ?? '';
                    $nombre = ($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '');
                }
            }

            // Construir link de descarga relativo
            $ruta_descarga = '';
            if ($ruta_pdf) {
                // Si la ruta es absoluta o relativa, normalizarla
                $ruta_descarga = strpos($ruta_pdf, '/') === 0 ? substr($ruta_pdf, 1) : $ruta_pdf;
            } else {
                // Generar ruta por defecto si no existe
                $ruta_descarga = 'descargas/carnet_' . $id_carnet . '.pdf';
            }

            $asunto = 'Tu Carnet de Manipulador de Alimentos está disponible';

            $datos = [
                'id_carnet' => $id_carnet,
                'id_inscripcion' => $inscripcion_id,
                'numero_carnet' => $numero_carnet,
                'titular' => $nombre,
                'fecha_emision' => $fecha_emision,
                'fecha_vencimiento' => $fecha_vencimiento,
                'ruta_descarga' => $ruta_descarga,
                'link_descarga' => '/manipulacionDeAlimentos/carnet_emitido&id_carnet=' . $id_carnet,
                'asunto' => $asunto
            ];

            if ($email) $datos['email'] = $email;

            // Enviar notificación
            $resultado = $this->enviarNotificacion($usuario_id > 0 ? $usuario_id : 0, self::TIPO_CARNET_EMITIDO, $datos);

            $this->registrarLog('ENVIAR_CARNET_EMITIDO', ['id_carnet' => $id_carnet, 'usuario_id' => $usuario_id, 'numero' => $numero_carnet]);

            return array_merge($resultado, [
                'carnet_numero' => $numero_carnet,
                'ruta_descarga' => $ruta_descarga,
                'fecha_vencimiento' => $fecha_vencimiento
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_CARNET_EMITIDO', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_carnet' => $id_carnet,
                'mensaje' => 'Error al enviar notificación de carnet: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener notificaciones pendientes de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array ['total' => int, 'notificaciones' => array[]]
     */
    public function obtenerNotificacionesPendientes(int $id_usuario): array
    {
        try {
            // TODO: SELECT * FROM notificacion 
            // WHERE id_usuario = $id_usuario AND enviado = 0
            // ORDER BY fecha_creacion DESC

            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE usuario_id = :uid AND enviado = 0 ORDER BY fecha_creacion DESC');
            $stmt->execute([':uid' => $id_usuario]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $this->registrarLog('OBTENER_NOTIFICACIONES_PENDIENTES', ['id_usuario' => $id_usuario]);

            return [
                'id_usuario' => $id_usuario,
                'total' => count($rows),
                'notificaciones' => $rows
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_NOTIFICACIONES_PENDIENTES', ['error' => $e->getMessage()]);
            return [
                'id_usuario' => $id_usuario,
                'total' => 0,
                'notificaciones' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener historial de notificaciones de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Historial completo de notificaciones
     */
    public function obtenerHistorialNotificaciones(int $id_usuario): array
    {
        try {
            // TODO: SELECT * FROM notificacion 
            // WHERE id_usuario = $id_usuario
            // ORDER BY fecha_creacion DESC
            // LIMIT 100

            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE usuario_id = :uid ORDER BY fecha_creacion DESC LIMIT 100');
            $stmt->execute([':uid' => $id_usuario]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $this->registrarLog('OBTENER_HISTORIAL_NOTIFICACIONES', ['id_usuario' => $id_usuario]);

            return [
                'id_usuario' => $id_usuario,
                'total' => count($rows),
                'notificaciones' => $rows
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_HISTORIAL_NOTIFICACIONES', ['error' => $e->getMessage()]);
            return [
                'id_usuario' => $id_usuario,
                'total' => 0,
                'notificaciones' => []
            ];
        }
    }

    /**
     * Marcar una notificación como enviada
     *
     * @param int $id_notificacion ID de la notificación
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'fecha_envio' => string]
     */
    public function marcarEnviada(int $id_notificacion): array
    {
        try {
            // TODO: UPDATE notificacion SET enviado = 1, fecha_envio = NOW()
            // WHERE id = $id_notificacion

            $pdo = $this->pdo();
            $upd = $pdo->prepare('UPDATE notificaciones SET enviado = 1, fecha_envio = NOW() WHERE id = :id');
            $upd->execute([':id' => $id_notificacion]);
            $ok = $upd->rowCount() > 0;
            $timestamp = date('Y-m-d H:i:s');

            $this->registrarLog('MARCAR_ENVIADA', ['id_notificacion' => $id_notificacion, 'ok' => $ok]);

            return [
                'éxito' => $ok,
                'id_notificacion' => $id_notificacion,
                'fecha_envio' => $timestamp
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_MARCAR_ENVIADA', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => $id_notificacion
            ];
        }
    }

    /**
     * Obtener notificaciones por tipo específico
     *
     * @param string $tipo Tipo de notificación
     * @return array Notificaciones de ese tipo
     */
    public function obtenerNotificacionesPorTipo(string $tipo): array
    {
        try {
            // TODO: Validar que tipo sea válido (usar constantes)
            // TODO: SELECT * FROM notificacion WHERE tipo = $tipo
            // ORDER BY fecha_creacion DESC

            $allowed = [self::TIPO_EMAIL, self::TIPO_SMS, self::TIPO_SISTEMA, self::TIPO_CONFIRMACION_INSCRIPCION, self::TIPO_CAMBIO_ESTADO, self::TIPO_RECHAZO_DOCUMENTACION, self::TIPO_APROBACION_DOCUMENTACION, self::TIPO_RESULTADO_EXAMEN, self::TIPO_CARNET_EMITIDO, self::TIPO_RECUPERACION_PASSWORD, self::TIPO_COMPROBANTE];
            if (!in_array($tipo, $allowed, true)) {
                return ['tipo' => $tipo, 'total' => 0, 'notificaciones' => []];
            }
            $pdo = $this->pdo();
            $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE tipo = :tipo ORDER BY fecha_creacion DESC');
            $stmt->execute([':tipo' => $tipo]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $this->registrarLog('OBTENER_NOTIFICACIONES_POR_TIPO', ['tipo' => $tipo]);

            return ['tipo' => $tipo, 'total' => count($rows), 'notificaciones' => $rows];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_NOTIFICACIONES_POR_TIPO', ['error' => $e->getMessage()]);
            return [
                'tipo' => $tipo,
                'total' => 0,
                'notificaciones' => []
            ];
        }
    }

    /**
     * Eliminar una notificación
     *
     * @param int $id ID de la notificación a eliminar
     * @return array ['éxito' => bool, 'id' => int, 'eliminada' => bool]
     */
    public function eliminarNotificacion(int $id): array
    {
        try {
            // TODO: DELETE FROM notificacion WHERE id = $id
            // TODO: Verificar que se eliminó

            $pdo = $this->pdo();
            $del = $pdo->prepare('DELETE FROM notificaciones WHERE id = :id');
            $del->execute([':id' => $id]);
            $ok = $del->rowCount() > 0;

            $this->registrarLog('ELIMINAR_NOTIFICACION', ['id' => $id, 'ok' => $ok]);

            return ['éxito' => $ok, 'id' => $id, 'eliminada' => $ok];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ELIMINAR_NOTIFICACION', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id' => $id,
                'eliminada' => false
            ];
        }
    }

    /**
     * Procesar cola de notificaciones pendientes (batch job)
     *
     * @return array ['procesadas' => int, 'exitosas' => int, 'errores' => int]
     */
    public function procesarColaNotificaciones(): array
    {
        try {
            $pdo = $this->pdo();

            $cfg = $this->obtenerConfiguracionEmail();
            $maxRetries = (int)(getenv('NOTIF_MAX_RETRIES') ?: 3);

            $procesadas = 0;
            $exitosas = 0;
            $errores = 0;

            $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE enviado = 0 ORDER BY fecha_creacion ASC LIMIT 100');
            $stmt->execute();
            $pendientes = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($pendientes as $n) {
                $procesadas++;
                $id = (int)$n['id'];
                $usuario_id = (int)($n['usuario_id'] ?? 0);
                $tipo = $n['tipo'] ?? '';
                $asunto = $n['asunto'] ?? ($tipo ?: 'Notificación');

                // Resolver email destino
                $email = '';
                if ($usuario_id > 0) {
                    $q = $pdo->prepare('SELECT email FROM usuarios WHERE id = :id LIMIT 1');
                    $q->execute([':id' => $usuario_id]);
                    $u = $q->fetch(\PDO::FETCH_ASSOC);
                    if ($u) $email = $u['email'] ?? '';
                }

                if (!$email && !empty($n['mensaje'])) {
                    // intentar extraer email simple desde el campo mensaje (no siempre presente)
                    if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $n['mensaje'], $m)) {
                        $email = $m[0];
                    }
                }

                if (!$email) {
                    $errores++;
                    $upd = $pdo->prepare('UPDATE notificaciones SET attempts = attempts + 1, last_error = :err WHERE id = :id');
                    $errMsg = 'No se pudo resolver email destino';
                    $upd->execute([':err' => $errMsg, ':id' => $id]);
                    $this->registrarLog('PROCESAR_COLA_NOTIF_NO_EMAIL', ['id' => $id]);
                    continue;
                }

                // Preparar contenido (usar mensaje almacenado si existe, sino generar plantilla)
                $plantilla = $n['mensaje'] ?: $this->generarPlantilla($tipo, []);
                $plantilla = $this->aplicarVariablesPlantilla($plantilla, []);

                $headers = "MIME-Version: 1.0\r\n" .
                           "Content-type: text/html; charset=utf-8\r\n" .
                           "From: " . ($cfg['nombre_remitente'] ?? 'Sistema') . " <" . ($cfg['remitente'] ?? 'noreply@localhost') . ">\r\n";

                try {
                    $mailOk = @mail($email, $asunto, $plantilla, $headers);
                    if ($mailOk) {
                        $upd = $pdo->prepare('UPDATE notificaciones SET enviado = 1, fecha_envio = NOW() WHERE id = :id');
                        $upd->execute([':id' => $id]);
                        $exitosas++;
                        $this->registrarLog('NOTIF_ENVIADA', ['id' => $id, 'email' => $email, 'tipo' => $tipo]);
                    } else {
                        $errores++;
                        $upd = $pdo->prepare('UPDATE notificaciones SET attempts = attempts + 1, last_error = :err WHERE id = :id');
                        $errMsg = 'mail() returned false';
                        $upd->execute([':err' => $errMsg, ':id' => $id]);
                        $this->registrarLog('NOTIF_ERROR_ENVIO', ['id' => $id, 'email' => $email, 'error' => $errMsg]);
                    }
                } catch (\Throwable $t) {
                    $errores++;
                    $upd = $pdo->prepare('UPDATE notificaciones SET attempts = attempts + 1, last_error = :err WHERE id = :id');
                    $errMsg = $t->getMessage();
                    $upd->execute([':err' => $errMsg, ':id' => $id]);
                    $this->registrarLog('NOTIF_EXCEPTION_ENVIO', ['id' => $id, 'email' => $email, 'error' => $errMsg]);
                }
            }

            $this->registrarLog('PROCESAR_COLA_NOTIFICACIONES', [
                'procesadas' => $procesadas,
                'exitosas' => $exitosas,
                'errores' => $errores
            ]);

            return [
                'procesadas' => $procesadas,
                'exitosas' => $exitosas,
                'errores' => $errores,
                'fecha_procesamiento' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_PROCESAR_COLA_NOTIFICACIONES', ['error' => $e->getMessage()]);
            return [
                'procesadas' => 0,
                'exitosas' => 0,
                'errores' => 1,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reemplaza placeholders de plantilla del tipo {key} por valores provistos.
     * Escapa los valores con htmlspecialchars para evitar inyección de HTML.
     *
     * @param string $html
     * @param array $vars
     * @return string
     */
    private function aplicarVariablesPlantilla(string $html, array $vars): string
    {
        if (empty($vars)) return $html;
        foreach ($vars as $k => $v) {
            $val = is_scalar($v) ? (string)$v : json_encode($v);
            $html = str_replace('{' . $k . '}', htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $html);
        }
        return $html;
    }

    /**
     * Generar plantilla HTML de email con variables
     *
     * @param string $tipo Tipo de notificación
     * @param array $variables Variables para reemplazar en la plantilla
     * @return string HTML de la plantilla generada
     */
    public function generarPlantilla(string $tipo, array $variables): string
    {
        try {
            $html = '';

            switch ($tipo) {
                case self::TIPO_CONFIRMACION_INSCRIPCION:
                    $html = $this->plantillaConfirmacionInscripcion($variables);
                    break;
                case self::TIPO_CAMBIO_ESTADO:
                    $html = $this->plantillaCambioEstado($variables);
                    break;
                case self::TIPO_RECHAZO_DOCUMENTACION:
                    $html = $this->plantillaRechazoDocumentacion($variables);
                    break;
                case self::TIPO_APROBACION_DOCUMENTACION:
                    $html = $this->plantillaAprobacionDocumentacion($variables);
                    break;
                case self::TIPO_RESULTADO_EXAMEN:
                    $html = $this->plantillaResultadoExamen($variables);
                    break;
                case self::TIPO_CARNET_EMITIDO:
                    $html = $this->plantillaCarnetEmitido($variables);
                    break;
                case self::TIPO_RECUPERACION_PASSWORD:
                    $html = $this->plantillaRecuperacionPassword($variables);
                    break;
                case self::TIPO_COMPROBANTE:
                    $html = $this->plantillaComprobante($variables);
                    break;
                default:
                    $html = '<p>Notificación del sistema</p>';
            }

            return $html;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_GENERAR_PLANTILLA', ['error' => $e->getMessage()]);
            return '<p>Error al generar la plantilla</p>';
        }
    }

    /**
     * Plantilla de confirmación de inscripción
     * @param array $variables
     * @return string HTML
     */
    private function plantillaConfirmacionInscripcion(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Confirmación de Inscripción</h2>
                    <p>Tu inscripción ha sido confirmada.</p>
                    <p>ID Inscripción: {id_inscripcion}</p>
                    <p>Fecha: {fecha_confirmacion}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de cambio de estado
     * @param array $variables
     * @return string HTML
     */
    private function plantillaCambioEstado(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Cambio de Estado del Trámite</h2>
                    <p>El estado de tu trámite ha cambiado.</p>
                    <p>Nuevo estado: {nuevo_estado}</p>
                    <p>Fecha: {fecha_cambio}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de rechazo de documentación
     * @param array $variables
     * @return string HTML
     */
    private function plantillaRechazoDocumentacion(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Documentación Rechazada</h2>
                    <p>Tu documentación ha sido rechazada.</p>
                    <p>Motivo: {motivo}</p>
                    <p>Fecha: {fecha_rechazo}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de aprobación de documentación
     * @param array $variables
     * @return string HTML
     */
    private function plantillaAprobacionDocumentacion(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Documentación Aprobada</h2>
                    <p>Tu documentación ha sido aprobada correctamente.</p>
                    <p>Fecha: {fecha_aprobacion}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de resultado de examen
     * @param array $variables
     * @return string HTML
     */
    private function plantillaResultadoExamen(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Resultado del Examen</h2>
                    <p>Tu resultado de examen ya está disponible.</p>
                    <p>Fecha: {fecha_resultado}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de carnet emitido
     * @param array $variables
     * @return string HTML
     */
    private function plantillaCarnetEmitido(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>¡Carnet Emitido!</h2>
                    <p>Tu carnet de manipulador de alimentos ha sido emitido.</p>
                    <p>Fecha de emisión: {fecha_emision}</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de recuperación de password
     * @param array $variables
     * @return string HTML
     */
    private function plantillaRecuperacionPassword(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Recuperar Contraseña</h2>
                    <p>Haz click en el siguiente link para restablecer tu contraseña.</p>
                    <p><a href="{link_recuperacion}">Restablecer contraseña</a></p>
                    <p>Este link válido por 1 hora.</p>
                </body>
            </html>
        ';
    }

    /**
     * Plantilla de comprobante
     * @param array $variables
     * @return string HTML
     */
    private function plantillaComprobante(array $variables): string
    {
        return '
            <html>
                <body>
                    <h2>Comprobante de Inscripción</h2>
                    <p>Adjunto encontrarás tu comprobante de inscripción.</p>
                    <p>ID Inscripción: {id_inscripcion}</p>
                    <p>Fecha: {fecha_comprobante}</p>
                </body>
            </html>
        ';
    }

    /**
     * Obtener configuración de email
     *
     * @return array Configuración SMTP y credenciales
     */
    public function obtenerConfiguracionEmail(): array
    {
        // Leer valores desde variables de entorno (.env) cargadas por `config/env.php`
        // Se mantiene `self::EMAIL_CONFIG` como valores por defecto.
        require_once __DIR__ . '/../config/env.php';

        $cfg = self::EMAIL_CONFIG;

        $cfg['smtp_host'] = getenv('SMTP_HOST') ?: $cfg['smtp_host'] ?? 'localhost';
        $cfg['smtp_port'] = (int)(getenv('SMTP_PORT') ?: $cfg['smtp_port'] ?? 587);
        $cfg['smtp_user'] = getenv('SMTP_USER') ?: getenv('MAIL_USERNAME') ?: ($cfg['smtp_user'] ?? '');
        $cfg['smtp_pass'] = getenv('SMTP_PASS') ?: getenv('MAIL_PASSWORD') ?: ($cfg['smtp_pass'] ?? '');
        $cfg['smtp_secure'] = getenv('SMTP_SECURE') ?: $cfg['smtp_secure'] ?? 'tls';
        $cfg['remitente'] = getenv('EMAIL_FROM') ?: getenv('MAIL_FROM') ?: ($cfg['remitente'] ?? 'noreply@localhost');
        $cfg['nombre_remitente'] = getenv('EMAIL_NAME') ?: getenv('MAIL_FROM_NAME') ?: ($cfg['nombre_remitente'] ?? 'Sistema');

        return $cfg;
    }

    /**
     * Validar formato de email destino
     *
     * @param string $email Email a validar
     * @return bool true si es válido, false en caso contrario
     */
    public function validarEmailDestino(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Contar métodos disponibles en el controlador
     *
     * @return int Total de métodos públicos
     */
    public function countMethods(): int
    {
        return 18; // enviarNotificacion, enviarAlertaEstado, enviarComprobante,
                   // enviarRecuperacionPassword, enviarConfirmacionInscripcion,
                   // enviarRechazoDocs, enviarAprobacionDocs, enviarResultadoExamen,
                   // enviarCarnetEmitido, obtenerNotificacionesPendientes,
                   // obtenerHistorialNotificaciones, marcarEnviada,
                   // obtenerNotificacionesPorTipo, eliminarNotificacion,
                   // procesarColaNotificaciones, generarPlantilla,
                   // obtenerConfiguracionEmail, validarEmailDestino
    }
}
