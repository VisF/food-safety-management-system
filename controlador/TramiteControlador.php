<?php
declare(strict_types=1);

/**
 * TramiteControlador - Gestión integral del trámite y cambios de estado
 * 
 * Responsabilidades:
 * - Obtener estado actual completo del trámite
 * - Gestionar historial de cambios de estado
 * - Cambiar estado del trámite
 * - Generar comprobantes descargables
 * - Verificar asociación de carnets
 * - Validar vigencia de carnets
 * - Registrar cambios de estado en auditoría
 * - Generar estadísticas para reportes
 * 
 * Dependencias:
 * - Modelos: InscripcionModelo, EstadoTramiteModelo, CarnetModelo, DocumentoModelo, ResultadoExamenModelo
 */

class TramiteControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/tramite_controller.log';
    
    private ?InscripcionModelo $inscripcionModelo = null;
    private ?EstadoTramiteModelo $estadoTramiteModelo = null;
    private ?CarnetModelo $carnetModelo = null;
    private ?DocumentoModelo $documentoModelo = null;
    private ?ResultadoExamenModelo $resultadoExamenModelo = null;
        private function pdo(): \PDO
        {
            require_once __DIR__ . '/../db/Connection.php';
            return Connection::getPDO();
        }

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->inicializarModelos();
    }

    /**
     * Inicializar todas las dependencias de modelos
     */
    private function inicializarModelos(): void
    {
        if (class_exists('InscripcionModelo')) {
            $this->inscripcionModelo = new InscripcionModelo();
        }
        if (class_exists('EstadoTramiteModelo')) {
            $this->estadoTramiteModelo = new EstadoTramiteModelo();
        }
        if (class_exists('CarnetModelo')) {
            $this->carnetModelo = new CarnetModelo();
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
     */
    private function registrarLog(string $evento, array $datos = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';
        $mensaje = "[$timestamp] Usuario: $usuario_id | Evento: $evento | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::LOG_FILE, $mensaje, FILE_APPEND);
    }

    /**
     * Obtener detalle completo del trámite (estado actual)
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array Detalles completos: [inscripcion, estado, documentacion, resultado_examen, carnet, ...]
     */
    public function obtenerDetalleTramite(int $id_inscripcion): array
    {
        try {
            // TODO: SELECT * FROM inscripcion WHERE id = $id_inscripcion
            // TODO: SELECT * FROM estado_tramite WHERE id = inscripcion.id_estado
            // TODO: SELECT COUNT(*) as total, SUM(validado) as validados FROM documento WHERE id_inscripcion = $id_inscripcion
            // TODO: SELECT * FROM resultado_examen WHERE id_inscripcion = $id_inscripcion (si existe)
            // TODO: SELECT * FROM carnet WHERE id_inscripcion = $id_inscripcion (si existe)
            
            $detalle = [
                'inscripcion' => [
                    'id' => $id_inscripcion,
                    'id_usuario' => 1,
                    'id_curso' => 1,
                    'id_tipo_inscripcion' => 1,
                    'fecha_inscripcion' => date('Y-m-d H:i:s'),
                    'id_estado' => 1
                ],
                'estado' => [
                    'id' => 1,
                    'nombre' => 'Pendiente',
                    'descripcion' => 'Inscripción creada, pendiente documentación'
                ],
                'documentacion' => [
                    'documentos_totales' => 2,
                    'documentos_validados' => 1,
                    'documentos_pendientes' => 1,
                    'completada' => false
                ],
                'resultado_examen' => null,
                'carnet' => null,
                'fecha_ultima_modificacion' => date('Y-m-d H:i:s')
            ];

            $this->registrarLog('DETALLE_TRAMITE_OBTENIDO', ['id_inscripcion' => $id_inscripcion]);
            
            return $detalle;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_DETALLE_TRAMITE', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener historial de cambios de estado del trámite
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array de cambios de estado ordenados cronológicamente
     *               [['id_estado_anterior' => int, 'id_estado_nuevo' => int, 'fecha_cambio' => string, 'usuario' => string], ...]
     */
    public function obtenerHistorialTramite(int $id_inscripcion): array
    {
        try {
            $sql = 'SELECT h.id, h.inscripcion_id, h.estado_anterior_id, h.estado_nuevo_id, h.fecha_cambio, h.observaciones,
                           ea.nombre AS estado_anterior, en.nombre AS estado_nuevo, u.nombre AS usuario_admin_nombre, u.apellido AS usuario_admin_apellido
                    FROM historial_tramite h
                    LEFT JOIN estado_tramite ea ON ea.id = h.estado_anterior_id
                    INNER JOIN estado_tramite en ON en.id = h.estado_nuevo_id
                    LEFT JOIN usuarios u ON u.id = h.usuario_admin_id
                    WHERE h.inscripcion_id = :id
                    ORDER BY h.fecha_cambio ASC';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_inscripcion]);

            $historial = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $historial[] = [
                    'id' => (int)$row['id'],
                    'id_inscripcion' => (int)$row['inscripcion_id'],
                    'id_estado_anterior' => $row['estado_anterior_id'] !== null ? (int)$row['estado_anterior_id'] : null,
                    'id_estado_nuevo' => (int)$row['estado_nuevo_id'],
                    'estado_anterior' => $row['estado_anterior'] ?? 'Nuevo',
                    'estado_nuevo' => $row['estado_nuevo'] ?? '',
                    'fecha_cambio' => $row['fecha_cambio'],
                    'usuario_admin' => trim(($row['usuario_admin_nombre'] ?? 'Sistema') . ' ' . ($row['usuario_admin_apellido'] ?? '')),
                    'observaciones' => $row['observaciones'] ?? null,
                ];
            }

            $this->registrarLog('HISTORIAL_TRAMITE_OBTENIDO', ['id_inscripcion' => $id_inscripcion]);
            
            return $historial;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_HISTORIAL_TRAMITE', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Actualizar estado del trámite
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @param int $id_estado_nuevo ID del nuevo estado
     * @return array ['success' => bool, 'mensaje' => string, 'estado_anterior' => int, 'estado_nuevo' => int]
     */
    public function actualizarEstadoTramite(int $id_inscripcion, int $id_estado_nuevo): array
    {
        try {
            // TODO: SELECT id_estado FROM inscripcion WHERE id = $id_inscripcion
            // TODO: Validar que id_estado_nuevo sea válido
            // TODO: UPDATE inscripcion SET id_estado = $id_estado_nuevo, fecha_ultima_modificacion = NOW()
            // TODO: INSERT en historial_tramite (id_inscripcion, id_estado_anterior, id_estado_nuevo, fecha_cambio)
            // TODO: Registrar en auditoria_acciones
            // TODO: Enviar notificación al usuario si corresponde
            
            $resultado = [
                'success' => true,
                'mensaje' => 'Estado actualizado exitosamente',
                'estado_anterior' => 1,
                'estado_nuevo' => $id_estado_nuevo
            ];

            $this->registrarLog('ESTADO_TRAMITE_ACTUALIZADO', [
                'id_inscripcion' => $id_inscripcion,
                'estado_nuevo' => $id_estado_nuevo
            ]);

            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_ACTUALIZAR_ESTADO_TRAMITE', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al actualizar estado: ' . $e->getMessage(),
                'estado_anterior' => 0,
                'estado_nuevo' => 0
            ];
        }
    }

    /**
     * Obtener comprobante descargable (PDF)
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array ['success' => bool, 'ruta_pdf' => string|null, 'nombre' => string|null, 'mensaje' => string]
     */
    public function obtenerComprobanteDescargable(int $id_inscripcion): array
    {
        try {
            // TODO: Obtener datos de inscripción
                $stmt = $this->pdo()->prepare('SELECT c.id, c.codigo_comprobante, c.fecha_emision, c.ruta_pdf, c.vigente, i.usuario_id, i.estado_tramite_id, u.nombre, u.apellido, u.dni
                                               FROM comprobantes_tramite c
                                               INNER JOIN inscripciones i ON i.id = c.inscripcion_id
                                               INNER JOIN usuarios u ON u.id = i.usuario_id
                                               WHERE c.inscripcion_id = :id
                                               LIMIT 1');
                $stmt->execute([':id' => $id_inscripcion]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$row) {
                    return [
                        'success' => false,
                        'ruta_pdf' => null,
                        'nombre' => null,
                        'mensaje' => 'No se encontro comprobante para la inscripcion'
                    ];
                }

                $resultado = [
                    'success' => true,
                    'ruta_pdf' => $row['ruta_pdf'],
                    'nombre' => 'comprobante_tramite_' . $id_inscripcion . '.pdf',
                    'mensaje' => 'Comprobante generado',
                    'comprobante' => [
                        'id' => (int)$row['id'],
                        'codigo_comprobante' => $row['codigo_comprobante'],
                        'fecha_emision' => $row['fecha_emision'],
                        'titular' => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
                        'dni' => $row['dni'] ?? '',
                    ]
                ];

            $this->registrarLog('COMPROBANTE_GENERADO', ['id_inscripcion' => $id_inscripcion]);
            
            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_GENERAR_COMPROBANTE', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'ruta_pdf' => null,
                'nombre' => null,
                'mensaje' => 'Error al generar comprobante: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener carnet asociado a una inscripción
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @return array|null Datos del carnet o null si no existe
     */
    public function obtenerCarnet(int $id_inscripcion): ?array
    {
        try {
            $sql = 'SELECT c.id, c.numero_carnet, c.fecha_emision, c.fecha_vencimiento, c.ruta_pdf, c.vigente,
                           u.nombre, u.apellido
                    FROM carnets c
                    INNER JOIN inscripciones i ON i.id = c.inscripcion_id
                    INNER JOIN usuarios u ON u.id = i.usuario_id
                    WHERE c.inscripcion_id = :id
                    LIMIT 1';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_inscripcion]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            $carnet = [
                'id' => (int)$row['id'],
                'id_inscripcion' => $id_inscripcion,
                'numero_carnet' => $row['numero_carnet'],
                'fecha_emision' => $row['fecha_emision'],
                'fecha_vencimiento' => $row['fecha_vencimiento'],
                'ruta_pdf' => $row['ruta_pdf'],
                'vigente' => (int)$row['vigente'],
                'titular' => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
            ];

            $this->registrarLog('CARNET_OBTENIDO', ['id_inscripcion' => $id_inscripcion]);
            
            return $carnet;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_CARNET', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar vigencia del carnet
     * 
     * @param int $id_carnet ID del carnet
     * @return array ['vigente' => bool, 'fecha_vencimiento' => string, 'dias_restantes' => int, 'mensaje' => string]
     */
    public function verificarVigenciaCarnet(int $id_carnet): array
    {
        try {
            // TODO: SELECT fecha_vencimiento FROM carnet WHERE id = $id_carnet
            // TODO: Comparar fecha_vencimiento con hoy
            // TODO: Calcular días restantes
            // TODO: UPDATE carnet SET vigente = (fecha_vencimiento >= HOY) si cambió
            
            $fecha_vencimiento = date('Y-m-d', strtotime('+1 year'));
            $hoy = date('Y-m-d');
            $vigente = strtotime($fecha_vencimiento) >= strtotime($hoy);
            $dias_restantes = ceil((strtotime($fecha_vencimiento) - strtotime($hoy)) / 86400);

            $resultado = [
                'vigente' => $vigente,
                'fecha_vencimiento' => $fecha_vencimiento,
                'dias_restantes' => $dias_restantes,
                'mensaje' => $vigente ? "Carnet vigente ($dias_restantes días)" : 'Carnet vencido'
            ];

            $this->registrarLog('VIGENCIA_CARNET_VERIFICADA', ['id_carnet' => $id_carnet]);
            
            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_VERIFICAR_VIGENCIA_CARNET', ['error' => $e->getMessage()]);
            return [
                'vigente' => false,
                'fecha_vencimiento' => '',
                'dias_restantes' => 0,
                'mensaje' => 'Error al verificar vigencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cambiar estado del trámite (helper)
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @param string $estado Nombre del estado (ej: 'aprobado', 'rechazado', 'pendiente')
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function cambiarEstadoTramite(int $id_inscripcion, string $estado): array
    {
        try {
            // TODO: Mapear nombre de estado a id_estado
            // TODO: SELECT id FROM estado_tramite WHERE nombre = $estado
            // TODO: Llamar a actualizarEstadoTramite con id_estado obtenido
            
            $mapeo_estados = [
                'pendiente' => 1,
                'documentacion_completa' => 2,
                'documentacion_rechazada' => 3,
                'apto_examen' => 4,
                'examen_rendido' => 5,
                'aprobado' => 6,
                'rechazado' => 7,
                'carnet_emitido' => 8
            ];

            $id_estado = $mapeo_estados[strtolower($estado)] ?? null;
            if ($id_estado === null) {
                return [
                    'success' => false,
                    'mensaje' => 'Estado no válido: ' . $estado
                ];
            }

            return $this->actualizarEstadoTramite($id_inscripcion, $id_estado);
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_CAMBIAR_ESTADO_TRAMITE', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todos los trámites de un usuario
     * 
     * @param int $id_usuario ID del usuario
     * @return array Array de trámites del usuario con estado actual
     */
    public function obtenerTramitesUsuario(int $id_usuario): array
    {
        try {
            $sql = 'SELECT i.id, i.usuario_id, i.curso_id, i.examen_id, i.tipo_inscripcion_id, i.fecha_inscripcion, i.estado_tramite_id,
                           et.nombre AS estado_nombre
                    FROM inscripciones i
                    LEFT JOIN estado_tramite et ON et.id = i.estado_tramite_id
                    WHERE i.usuario_id = :id
                    ORDER BY i.fecha_inscripcion DESC';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);

            $tramites = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tramites[] = [
                    'id' => (int)$row['id'],
                    'id_usuario' => (int)$row['usuario_id'],
                    'id_curso' => $row['curso_id'] !== null ? (int)$row['curso_id'] : null,
                    'id_examen' => $row['examen_id'] !== null ? (int)$row['examen_id'] : null,
                    'id_tipo_inscripcion' => $row['tipo_inscripcion_id'] !== null ? (int)$row['tipo_inscripcion_id'] : null,
                    'fecha_inscripcion' => $row['fecha_inscripcion'],
                    'estado_nombre' => $row['estado_nombre'] ?? 'Pendiente',
                    'estado_id' => $row['estado_tramite_id'] !== null ? (int)$row['estado_tramite_id'] : 1,
                ];
            }

            $this->registrarLog('TRAMITES_USUARIO_OBTENIDOS', ['id_usuario' => $id_usuario]);
            
            return $tramites;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_TRAMITES_USUARIO', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener trámites pendientes (admin)
     * 
     * @return array Array de trámites pendientes de acción administrativa
     */
    public function obtenerTramitesPendientes(): array
    {
        try {
            $sql = 'SELECT i.id, i.usuario_id, i.fecha_inscripcion, i.estado_tramite_id, u.nombre, u.apellido, et.nombre AS estado_nombre
                    FROM inscripciones i
                    INNER JOIN usuarios u ON u.id = i.usuario_id
                    LEFT JOIN estado_tramite et ON et.id = i.estado_tramite_id
                    WHERE i.estado_tramite_id IN (1,2,3,4,5)
                    ORDER BY i.fecha_inscripcion ASC';
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute();

            $tramites = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tramites[] = [
                    'id' => (int)$row['id'],
                    'id_usuario' => (int)$row['usuario_id'],
                    'usuario_nombre' => $row['nombre'],
                    'usuario_apellido' => $row['apellido'],
                    'fecha_inscripcion' => $row['fecha_inscripcion'],
                    'estado_nombre' => $row['estado_nombre'] ?? 'Pendiente',
                    'estado_id' => (int)$row['estado_tramite_id'],
                ];
            }

            $this->registrarLog('TRAMITES_PENDIENTES_OBTENIDOS', []);
            
            return $tramites;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_TRAMITES_PENDIENTES', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener estadísticas de trámites para reportes
     * 
     * @return array ['total_tramites' => int, 'por_estado' => array, 'tasa_aprobacion' => float, ...]
     */
    public function obtenerEstadisticasTramites(): array
    {
        try {
            // TODO: SELECT COUNT(*) as total FROM inscripcion
            // TODO: SELECT id_estado, COUNT(*) as cantidad FROM inscripcion GROUP BY id_estado
            // TODO: SELECT COUNT(*) as aprobados FROM inscripcion WHERE id_estado = 6 (aprobado)
            // TODO: SELECT COUNT(*) as rechazados FROM inscripcion WHERE id_estado = 7 (rechazado)
            // TODO: Calcular tasas de aprobación, promedio de días en trámite, etc.
            
            $estadisticas = [
                'total_tramites' => 100,
                'por_estado' => [
                    'pendiente' => 10,
                    'documentacion_completa' => 20,
                    'aprobado' => 50,
                    'rechazado' => 20
                ],
                'aprobados' => 50,
                'rechazados' => 20,
                'en_tramite' => 30,
                'tasa_aprobacion' => 71.4,
                'tasa_rechazo' => 28.6,
                'dias_promedio_tramite' => 15.2
            ];

            $this->registrarLog('ESTADISTICAS_TRAMITES_OBTENIDAS', []);
            
            return $estadisticas;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_OBTENER_ESTADISTICAS_TRAMITES', ['error' => $e->getMessage()]);
            return [
                'total_tramites' => 0,
                'por_estado' => [],
                'aprobados' => 0,
                'rechazados' => 0,
                'en_tramite' => 0,
                'tasa_aprobacion' => 0.0,
                'tasa_rechazo' => 0.0,
                'dias_promedio_tramite' => 0.0
            ];
        }
    }

    /**
     * Registrar cambio de estado en auditoría
     * 
     * @param int $id_inscripcion ID de la inscripción
     * @param int $estado_anterior ID del estado anterior
     * @param int $estado_nuevo ID del nuevo estado
     * @return array ['success' => bool, 'mensaje' => string, 'id_registro' => int|null]
     */
    public function registrarCambioEstado(int $id_inscripcion, int $estado_anterior, int $estado_nuevo): array
    {
        try {
            // TODO: INSERT en tabla historial_tramite (id_inscripcion, id_estado_anterior, id_estado_nuevo, fecha_cambio, usuario_admin)
            // TODO: INSERT en tabla auditoria_acciones (id_usuario, tabla, id_registro, accion, datos_anteriores, datos_nuevos)
            
            $resultado = [
                'success' => true,
                'mensaje' => 'Cambio de estado registrado',
                'id_registro' => 1
            ];

            $this->registrarLog('CAMBIO_ESTADO_REGISTRADO', [
                'id_inscripcion' => $id_inscripcion,
                'estado_anterior' => $estado_anterior,
                'estado_nuevo' => $estado_nuevo
            ]);

            return $resultado;
        } catch (\Exception $e) {
            $this->registrarLog('ERROR_REGISTRAR_CAMBIO_ESTADO', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error al registrar cambio de estado: ' . $e->getMessage(),
                'id_registro' => null
            ];
        }
    }
}
