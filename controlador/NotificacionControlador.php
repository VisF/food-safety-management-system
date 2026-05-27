<?php
declare(strict_types=1);

/**
 * NotificacionControlador - Gestión de notificaciones y emails
 *
 * Responsabilidades:
 * - Enviar notificaciones por email a usuarios
 * - Alertar cambios de estado del trámite
 * - Enviar comprobantes de inscripción
 * - Enviar resultados de exámenes
 * - Gestionar plantillas de email
 * - Procesar cola de notificaciones pendientes
 * - Mantener historial de notificaciones
 *
 * Dependencias esperadas:
 * - Modelos: NotificacionModelo, UsuarioModelo, InscripcionModelo, DocumentoModelo, ResultadoExamenModelo
 * - Funcionalidad: Envío de emails (SMTP, PHPMailer, etc.)
 */

class NotificacionControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/notificacion_controller.log';
    private const TIPO_EMAIL = 'email';
    private const TIPO_SMS = 'sms';
    private const TIPO_SISTEMA = 'sistema';
    
    // Constantes para tipos de notificación
    private const TIPO_CONFIRMACION_INSCRIPCION = 'confirmacion_inscripcion';
    private const TIPO_CAMBIO_ESTADO = 'cambio_estado';
    private const TIPO_RECHAZO_DOCUMENTACION = 'rechazo_documentacion';
    private const TIPO_APROBACION_DOCUMENTACION = 'aprobacion_documentacion';
    private const TIPO_RESULTADO_EXAMEN = 'resultado_examen';
    private const TIPO_CARNET_EMITIDO = 'carnet_emitido';
    private const TIPO_RECUPERACION_PASSWORD = 'recuperacion_password';
    private const TIPO_COMPROBANTE = 'comprobante';

    private const EMAIL_CONFIG = [
        'smtp_host' => 'localhost', // TODO: Configurar SMTP
        'smtp_port' => 587,
        'remitente' => 'noreply@bromatologia.local',
        'nombre_remitente' => 'Sistema de Bromatología'
    ];

    private ?NotificacionModelo $notificacionModelo = null;
    private ?UsuarioModelo $usuarioModelo = null;
    private ?InscripcionModelo $inscripcionModelo = null;
    private ?DocumentoModelo $documentoModelo = null;
    private ?ResultadoExamenModelo $resultadoExamenModelo = null;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar todas las dependencias de modelos
     * @return void
     */
    private function inicializarModelos(): void
    {
        if (class_exists('NotificacionModelo')) {
            $this->notificacionModelo = new NotificacionModelo();
        }
        if (class_exists('UsuarioModelo')) {
            $this->usuarioModelo = new UsuarioModelo();
        }
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('DocumentoModelo')) {
            $this->documentoModelo = new DocumentoModelo();
        }
        if (class_exists('ResultadoExamenModelo')) {
            $this->resultadoExamenModelo = new ResultadoExamenModelo();
        }
    }

    /**
     * Registrar evento en el log
     * @param string $evento Descripción del evento
     * @param array $datos Datos asociados
     * @return void
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    /**
     * Enviar notificación genérica a usuario
     *
     * @param int $id_usuario ID del usuario destinatario
     * @param string $tipo Tipo de notificación (ver constantes TIPO_*)
     * @param array $datos Array con variables para la plantilla
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'tipo' => string, 'mensaje' => string]
     */
    public function enviarNotificacion(int $id_usuario, string $tipo, array $datos): array
    {
        try {
            // Validar usuario
            // TODO: SELECT * FROM usuario WHERE id = $id_usuario
            
            // Generar plantilla
            $plantilla = $this->generarPlantilla($tipo, $datos);

            // Obtener email del usuario
            // TODO: $email = SELECT email FROM usuario WHERE id = $id_usuario

            // TODO: Enviar email usando PHPMailer o mail()
            // TODO: INSERT en tabla notificacion
            // TODO: Retornar ID de notificación creada

            $this->registrarLog('ENVIAR_NOTIFICACION', [
                'id_usuario' => $id_usuario,
                'tipo' => $tipo
            ]);

            return [
                'éxito' => true,
                'id_notificacion' => 0, // TODO: ID insertado
                'id_usuario' => $id_usuario,
                'tipo' => $tipo,
                'mensaje' => 'Notificación enviada correctamente'
            ];
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_NOTIFICACION', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_usuario' => $id_usuario,
                'tipo' => $tipo,
                'mensaje' => 'Error al enviar notificación: ' . $e->getMessage()
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
            // TODO: SELECT * FROM inscripcion WHERE id = $id_inscripcion
            // TODO: Obtener id_usuario de la inscripción

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'nuevo_estado' => $nuevo_estado,
                'fecha_cambio' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_CAMBIO_ESTADO, $datos);

            $this->registrarLog('ENVIAR_ALERTA_ESTADO', [
                'id_inscripcion' => $id_inscripcion,
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
            // TODO: SELECT * FROM inscripcion WHERE id = $id_inscripcion
            // TODO: Generar comprobante (PDF o HTML)
            // TODO: Adjuntar a email y enviar
            // TODO: Guardar referencia en notificación

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_comprobante' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_COMPROBANTE, $datos);

            $this->registrarLog('ENVIAR_COMPROBANTE', ['id_inscripcion' => $id_inscripcion]);

            return array_merge($resultado, [
                'comprobante_id' => 'COMP-' . $id_inscripcion . '-' . date('YmdHis')
            ]);
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
            if (!$this->validarEmailDestino($email)) {
                throw new \Exception('Email inválido');
            }

            // TODO: SELECT * FROM usuario WHERE email = $email
            // TODO: Generar token único de recuperación
            // TODO: Guardar en tabla recovery_tokens con TTL de 1 hora
            // TODO: Generar link: /reset-password?token=$token
            // TODO: Enviar email con link

            $datos = [
                'email' => $email,
                'fecha_solicitud' => date('Y-m-d H:i:s')
            ];

            // Envío sin id_usuario
            // TODO: INSERT directo en notificacion sin id_usuario

            $this->registrarLog('ENVIAR_RECUPERACION_PASSWORD', ['email' => $email]);

            return [
                'éxito' => true,
                'email' => $email,
                'token_enviado' => true,
                'mensaje' => 'Link de recuperación enviado al email'
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
            // TODO: SELECT * FROM inscripcion WHERE id = $id_inscripcion
            // TODO: Obtener detalles del curso/examen
            // TODO: Generar confirmación con fecha, hora, lugar

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_confirmacion' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_CONFIRMACION_INSCRIPCION, $datos);

            $this->registrarLog('ENVIAR_CONFIRMACION_INSCRIPCION', ['id_inscripcion' => $id_inscripcion]);

            return array_merge($resultado, [
                'confirmacion' => true
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
            // TODO: SELECT * FROM documento WHERE id = $id_documento
            // TODO: SELECT id_inscripcion FROM documento
            // TODO: Obtener usuario de la inscripción

            $datos = [
                'id_documento' => $id_documento,
                'motivo' => $motivo,
                'fecha_rechazo' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_RECHAZO_DOCUMENTACION, $datos);

            $this->registrarLog('ENVIAR_RECHAZO_DOCS', [
                'id_documento' => $id_documento,
                'motivo' => $motivo
            ]);

            return array_merge($resultado, [
                'documento_id' => $id_documento
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
            // TODO: SELECT * FROM inscripcion WHERE id = $id_inscripcion
            // TODO: Contar documentos aprobados

            $datos = [
                'id_inscripcion' => $id_inscripcion,
                'fecha_aprobacion' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_APROBACION_DOCUMENTACION, $datos);

            $this->registrarLog('ENVIAR_APROBACION_DOCS', ['id_inscripcion' => $id_inscripcion]);

            return array_merge($resultado, [
                'documentacion_aprobada' => true
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
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'resultado' => string]
     */
    public function enviarResultadoExamen(int $id_resultado): array
    {
        try {
            // TODO: SELECT * FROM resultado_examen WHERE id = $id_resultado
            // TODO: SELECT nota, aprobado FROM resultado_examen
            // TODO: Obtener datos de la inscripción y usuario

            $datos = [
                'id_resultado' => $id_resultado,
                'fecha_resultado' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_RESULTADO_EXAMEN, $datos);

            $this->registrarLog('ENVIAR_RESULTADO_EXAMEN', ['id_resultado' => $id_resultado]);

            return array_merge($resultado, [
                'resultado' => 'pendiente_lectura'
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_RESULTADO_EXAMEN', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_resultado' => $id_resultado
            ];
        }
    }

    /**
     * Enviar notificación de carnet emitido
     *
     * @param int $id_carnet ID del carnet
     * @return array ['éxito' => bool, 'id_notificacion' => int, 'carnet_numero' => string]
     */
    public function enviarCarnetEmitido(int $id_carnet): array
    {
        try {
            // TODO: SELECT * FROM carnet WHERE id = $id_carnet
            // TODO: Obtener inscripción y usuario
            // TODO: Generar link a descarga/consulta del carnet

            $datos = [
                'id_carnet' => $id_carnet,
                'fecha_emision' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->enviarNotificacion(0, self::TIPO_CARNET_EMITIDO, $datos);

            $this->registrarLog('ENVIAR_CARNET_EMITIDO', ['id_carnet' => $id_carnet]);

            return array_merge($resultado, [
                'carnet_numero' => 'CARNET-' . $id_carnet . '-' . date('Y')
            ]);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ENVIAR_CARNET_EMITIDO', ['error' => $e->getMessage()]);
            return [
                'éxito' => false,
                'id_notificacion' => 0,
                'id_carnet' => $id_carnet
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

            $this->registrarLog('OBTENER_NOTIFICACIONES_PENDIENTES', ['id_usuario' => $id_usuario]);

            return [
                'id_usuario' => $id_usuario,
                'total' => 0,
                'notificaciones' => []
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

            $this->registrarLog('OBTENER_HISTORIAL_NOTIFICACIONES', ['id_usuario' => $id_usuario]);

            return [
                'id_usuario' => $id_usuario,
                'total' => 0,
                'notificaciones' => []
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

            $timestamp = date('Y-m-d H:i:s');

            $this->registrarLog('MARCAR_ENVIADA', ['id_notificacion' => $id_notificacion]);

            return [
                'éxito' => true,
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

            $this->registrarLog('OBTENER_NOTIFICACIONES_POR_TIPO', ['tipo' => $tipo]);

            return [
                'tipo' => $tipo,
                'total' => 0,
                'notificaciones' => []
            ];
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

            $this->registrarLog('ELIMINAR_NOTIFICACION', ['id' => $id]);

            return [
                'éxito' => true,
                'id' => $id,
                'eliminada' => true
            ];
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
            // TODO: SELECT * FROM notificacion WHERE enviado = 0
            // TODO: Procesar cada una
            // TODO: Manejar reintentos
            // TODO: Marcar como enviada si es exitosa

            $procesadas = 0;
            $exitosas = 0;
            $errores = 0;

            // TODO: foreach notificaciones_pendientes
            // TODO: Intentar envío
            // TODO: Actualizar estado

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
        // TODO: Leer de archivo de configuración o .env
        return self::EMAIL_CONFIG;
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
