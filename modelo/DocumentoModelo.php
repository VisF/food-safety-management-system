<?php
declare(strict_types=1);

/**
 * DocumentoModelo - Gestión de documentos
 * 
 * Propiedades:
 * - id: identificador único
 * - id_inscripcion: ID de la inscripción
 * - tipo_documento: tipo de documento (DNI, certificado, etc.)
 * - ruta_archivo: ruta del archivo guardado
 * - estado: pendiente, aprobado o rechazado
 * - fecha_subida: timestamp de subida
 * - observaciones: notas de validación
 */
require_once __DIR__ . '/../Config/Configuracion.php';

class DocumentoModelo
{
    private int $id;
    private string $tipo_documento;
    private string $ruta_archivo;
    private string $fecha_subida;
    private ?string $observaciones;
    private int $usuarioId;
    private string $estado;

    // Conexión a BD (PDO)
    private ?\PDO $conexion = null;

    public function __construct(?\PDO $conexion = null)
    {
        if ($conexion instanceof \PDO) {
            $this->conexion = $conexion;
            return;
        }

        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->conexion = Connection::getPDO();
        }
    }

    /**
     * Crear nuevo documento
     * @param array $data Datos: id_inscripcion, tipo_documento, ruta_archivo
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;

        $usuario_id = (int)($data['usuario_id'] ?? 0);
        $tipo = $data['tipo_documento'] ?? '';
        $ruta = $data['ruta_archivo'] ?? '';
        $nombre_original = $data['nombre_original'] ?? '';
        $estado = 'pendiente';
        $fecha_subida = date('d-m-Y H:i:s');

        if ($usuario_id <= 0 || !$tipo || !$ruta) return false;

        $sql = 'INSERT INTO documentos (usuario_id, tipo_documento,nombre_original, ruta_archivo, estado, fecha_subida) VALUES (:uid, :tipo, :nombre_original, :ruta, "pendiente", NOW())';
        $stmt = $this->conexion->prepare($sql);
        $params = [':uid' => $usuario_id, ':tipo' => $tipo, ':nombre_original' => $nombre_original, ':ruta' => $ruta];
        if ($stmt->execute($params)) {
            $id = (int)$this->conexion->lastInsertId();
            return ['id' => $id, 'usuario_id' => $usuario_id, 'tipo_documento' => $tipo, 'nombre_original' => $nombre_original, 'ruta_archivo' => $ruta, 'estado' => 'pendiente', 'fecha_subida' => date('Y-m-d H:i:s')];
        }
        return false;
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        if (!$this->conexion) {
            return [];
        }

        $stmt = $this->conexion->prepare(
            'SELECT * 
            FROM documentos
            WHERE usuario_id = :usuario_id'
        );

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener documentos de una inscripción
     * @param int $id_inscripcion ID de la inscripción
     * @return array Array de documentos de esa inscripción
     */
    public function obtenerPorInscripcion(int $id_inscripcion): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM documentos WHERE usuario_id = :iid ORDER BY fecha_subida DESC');
        $stmt->execute([':iid' => $id_inscripcion]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener documentos pendientes de validación
     * @return array Array de documentos no validados
     */
    public function obtenerPendientes(): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->query('SELECT * FROM documentos WHERE estado = "pendiente" ORDER BY fecha_subida ASC');
        return $stmt->fetchAll();
    }

    /**
     * Validar documento
     * @param int $id ID del documento
     * @param string $observaciones Notas de validación
     * @return bool true si fue exitoso, false si falla
     */
    public function validar(int $id, string $observaciones = ''): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('UPDATE documentos 
                                        SET estado = "aprobado", 
                                        observaciones = :obs, 
                                        fecha_revision = NOW() 
                                        WHERE id = :id');
        

        if (!$stmt->execute([':obs' => $observaciones,':id' => $id])) 
            {
            return false;
        }

        $stmt = $this->conexion->prepare(
                            'SELECT usuario_id, tipo_documento
                            FROM documentos
                            WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $doc = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (
            $doc
            && strtolower($doc['tipo_documento']) === 'moodle'
        ) {

            require_once __DIR__ . '/HabilitacionExamenModelo.php';

            $habilitacionModelo = new HabilitacionExamenModelo();

            $habilitacionModelo->crear([
                'usuario_id' => $doc['usuario_id'],
                'curso_id' => null,
                'fecha_habilitacion' => date('Y-m-d'),
                'fecha_vencimiento' => date(
                    'Y-m-d',
                    strtotime(
                        '+' . TramitesConfig::VIGENCIA_HABILITACION_DIAS . ' days'
                    )
                )
            ]);
        }
        return true;
    }

    /**
     * Rechazar documento
     * @param int $id ID del documento
     * @param string $motivo Motivo del rechazo
     * @return bool true si fue exitoso, false si falla
     */
    public function rechazar(int $id, string $motivo = ''): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('UPDATE documentos SET estado = "rechazado", observaciones = :motivo, fecha_revision = NOW() WHERE id = :id');
        return (bool)$stmt->execute([':motivo' => $motivo, ':id' => $id]);
    }

    /**
     * Descargar documento
     * @param int $id ID del documento
     * @return string|false Ruta del archivo o false si no existe
     */
    public function descargar(int $id)
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('SELECT ruta_archivo FROM documentos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return false;
        $path = $row['ruta_archivo'];
        return file_exists($path) ? $path : false;
    }

    /**
     * Obtener documentos por tipo
     * @param string $tipo_documento Tipo de documento
     * @return array Array de documentos del tipo especificado
     */
    public function obtenerPorTipo(string $tipo_documento): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->prepare('SELECT * FROM documentos WHERE tipo_documento = :tipo ORDER BY fecha_subida DESC');
        $stmt->execute([':tipo' => $tipo_documento]);
        return $stmt->fetchAll();
    }
    public function subirDocumento(int $id_inscripcion, string $tipo_documento, string $ruta_archivo): array|false
    {
        return $this->crear([
            'id_inscripcion' => $id_inscripcion,
            'tipo_documento' => $tipo_documento,
            'ruta_archivo' => $ruta_archivo
        ]);
    }
    



    /**
     * Eliminar documento
     * @param int $id ID del documento
     * @return bool true si fue exitoso, false si falla
     */
    public function eliminar(int $id): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('SELECT ruta_archivo FROM documentos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['ruta_archivo']) && file_exists($row['ruta_archivo'])) {
            @unlink($row['ruta_archivo']);
        }

        $del = $this->conexion->prepare('DELETE FROM documentos WHERE id = :id');
        return (bool)$del->execute([':id' => $id]);
    }

    // Getters
    /**
     * Obtener ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtener ID de inscripción
     * @return int
     */
    public function getIdInscripcion(): int
    {
        return $this->id_inscripcion;
    }

    /**
     * Obtener tipo de documento
     * @return string
     */
    public function getTipoDocumento(): string
    {
        return $this->tipo_documento;
    }

    /**
     * Obtener ruta del archivo
     * @return string
     */
    public function getRutaArchivo(): string
    {
        return $this->ruta_archivo;
    }

    /**
     * Obtener estado validado
     * @return int
     */
    public function getEstado(): int
    {
        return $this->estado;
    }

    /**
     * Obtener fecha de subida
     * @return string
     */
    public function getFechaSubida(): string
    {
        return $this->fecha_subida;
    }

    /**
     * Obtener observaciones
     * @return string|null
     */
    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    // Setters
    /**
     * Establecer ID
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Establecer ID de inscripción
     * @param int $id_inscripcion
     * @return void
     */
    public function setIdInscripcion(int $id_inscripcion): void
    {
        $this->id_inscripcion = $id_inscripcion;
    }

    /**
     * Establecer tipo de documento
     * @param string $tipo_documento
     * @return void
     */
    public function setTipoDocumento(string $tipo_documento): void
    {
        $this->tipo_documento = $tipo_documento;
    }

    /**
     * Establecer ruta del archivo
     * @param string $ruta_archivo
     * @return void
     */
    public function setRutaArchivo(string $ruta_archivo): void
    {
        $this->ruta_archivo = $ruta_archivo;
    }

    /**
     * Establecer estado estado de validación
     * @param string $estado
     * @return void
     */
    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    /**
     * Establecer fecha de subida
     * @param string $fecha_subida
     * @return void
     */
    public function setFechaSubida(string $fecha_subida): void
    {
        $this->fecha_subida = $fecha_subida;
    }

    /**
     * Establecer observaciones
     * @param string|null $observaciones
     * @return void
     */
    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }
}
?>
