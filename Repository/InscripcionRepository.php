<?php

declare(strict_types=1);

/**
 * InscripcionRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/** faltan? 
 * obtenerUltimaInscripcionPorUsuario() ok

 * obtenerPorEstado() ok

 * actualizar() ok

 * obtenerInscripcionesActivas() ok

 * verificarDuplicado() ok

 * contarInscriptosCurso() ok

 * tieneCursoActivo() ok
 * 
 * listarInscripciones() ok
 * 
*   contarInscripciones() ok

*   obtenerPorId() ok

*   obtenerPorUsuario() ok

*   crear() ok

*   actualizarEstadoInscripcion() ok

*   agregarObservacion() ok

*   cancelar() ok
 * obtenerUsuarioIdPorInscripcion() ok  
 * 
 * 
 */
require_once __DIR__ . '/../db/Connection.php';


class InscripcionRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();

    }

   
   
   
   // Lista inscripciones.
   public function listarInscripciones(array $filtros = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filtros['id_usuario'])) {
            $where[] = 'i.usuario_id = :uid';
            $params[':uid'] = (int)$filtros['id_usuario'];
        }

        if (!empty($filtros['id_curso'])) {
            $where[] = 'i.curso_id = :cid';
            $params[':cid'] = (int)$filtros['id_curso'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = 'et.nombre = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = 'i.fecha_inscripcion >= :fd';
            $params[':fd'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = 'i.fecha_inscripcion <= :fh';
            $params[':fh'] = $filtros['fecha_hasta'];
        }

        $limit = (int)($filtros['limite'] ?? 50);
        $pagina = max(1, (int)($filtros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $limit;

        $sql = "
            SELECT
                i.*,
                u.nombre AS usuario_nombre,
                u.apellido AS usuario_apellido,
                c.nombre AS curso_nombre,
                et.nombre AS estado_nombre
            FROM inscripciones i
            LEFT JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN cursos c
                ON c.id = i.curso_id
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY i.fecha_inscripcion DESC
                LIMIT :limite OFFSET :offset';

        $stmt = $this->conexion->prepare($sql);

        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }

        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


  


    
  
    // Obtiene por estado.
    public function obtenerPorEstado(int $estado): array
{
    $stmt = $this->conexion->prepare("
        SELECT *
        FROM inscripciones
        WHERE estado_tramite_id = :estado
        ORDER BY fecha_inscripcion DESC
    ");

    $stmt->execute([
        ':estado' => $estado
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    

 
    // Actualiza la operaci?n correspondiente.
    public function actualizar(int $id, array $datos): bool
    {
    $permitidos = [
        'estado_tramite_id',
        'observaciones',
        'examen_id',
        'curso_id'
    ];

    $sets = [];
    $params = [
        ':id' => $id
    ];

    foreach ($permitidos as $campo) {

        if (array_key_exists($campo, $datos)) {

            $sets[] = "$campo = :$campo";

            $params[":$campo"] = $datos[$campo];
        }
    }

    if (empty($sets)) {
        return false;
    }

    $sql = "
        UPDATE inscripciones
        SET " . implode(',', $sets) . "
        WHERE id = :id
    ";

    $stmt = $this->conexion->prepare($sql);

    return $stmt->execute($params);
}

    
  
    // Ejecuta verificar duplicado.
    public function verificarDuplicado(int $usuarioId, int $cursoId): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM inscripciones
            WHERE usuario_id = :usuario
            AND curso_id = :curso
            AND estado_tramite_id != :cancelado
        ");

        $stmt->execute([
            ':usuario' => $usuarioId,
            ':curso' => $cursoId,
            ':cancelado' => EstadoTramite::CANCELADO
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }
    
   
    // Ejecuta contar inscriptos curso.
    public function contarInscriptosCurso(int $cursoId): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM inscripciones
            WHERE curso_id = :curso
            AND tipo_inscripcion_id = 1
            AND estado_tramite_id != :cancelado
        ");

        $stmt->execute([
            ':curso' => $cursoId,
            ':cancelado' => EstadoTramite::CANCELADO
        ]);

        return (int)$stmt->fetchColumn();
    }
    
    // Ejecuta tiene curso activo.
    public function tieneCursoActivo(int $usuarioId): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM inscripciones
            WHERE usuario_id = :usuario
            AND tipo_inscripcion_id = 1
            AND estado_tramite_id IN
            (
                :pendiente,
                :documentacion,
                :inscripto
            )
        ");

        $stmt->execute([
            ':usuario' => $usuarioId,
            ':pendiente' => EstadoTramite::PENDIENTE,
            ':documentacion' => EstadoTramite::DOCUMENTACION_APROBADA,
            ':inscripto' => EstadoTramite::INSCRIPTO_EXAMEN
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    // Ejecuta contar inscripciones.
    public function contarInscripciones(array $filtros = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filtros['id_usuario'])) {
            $where[] = 'i.usuario_id = :uid';
            $params[':uid'] = (int)$filtros['id_usuario'];
        }

        if (!empty($filtros['id_curso'])) {
            $where[] = 'i.curso_id = :cid';
            $params[':cid'] = (int)$filtros['id_curso'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = 'et.nombre = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = 'i.fecha_inscripcion >= :fd';
            $params[':fd'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = 'i.fecha_inscripcion <= :fh';
            $params[':fh'] = $filtros['fecha_hasta'];
        }

        $sql = "
            SELECT COUNT(*)
            FROM inscripciones i
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->conexion->prepare($sql);

        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    // Obtiene por id.
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                i.*,

                u.id AS usuario_id,
                u.nombre,
                u.apellido,
                u.email,
                u.dni,
                u.telefono,
                u.domicilio,

                c.id AS curso_id,
                c.nombre AS curso_nombre,

                et.nombre AS estado

            FROM inscripciones i

            LEFT JOIN usuarios u
                ON u.id = i.usuario_id

            LEFT JOIN cursos c
                ON c.id = i.curso_id

            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id

            WHERE i.id = :id
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $inscripcion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$inscripcion) {
            return null;
        }

        $stmt = $this->conexion->prepare("
                    SELECT
                        id,
                        usuario_id,
                        tipo_documento,
                        estado,
                        observaciones,
                        fecha_subida,
                        fecha_revision,
                        ruta_archivo
                    FROM documentos
                    WHERE usuario_id = :usuario_id
                    ORDER BY fecha_subida DESC
                ");

        $stmt->bindValue(':usuario_id', $inscripcion['usuario_id'], \PDO::PARAM_INT);
        $stmt->execute();

        $inscripcion['documentos'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $inscripcion;
    }
      /**
     * Actualiza el estado de una inscripción.
     *
     * @param int $id ID de la inscripción.
     * @param int $estadoNuevo ID del nuevo estado (EstadoTramite::*).
     * @return bool True si se actualizó correctamente.
     */
    public function actualizarEstadoInscripcion(int $id, int $estadoNuevo): bool
    {
        $sql = "
            UPDATE inscripciones
            SET estado_tramite_id = :estado
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':estado', $estadoNuevo, \PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Guarda o actualiza las observaciones de una inscripción.
     *
     * @param int $id ID de la inscripción.
     * @param string $observacion Observación a almacenar.
     * @return bool True si la operación fue exitosa.
     */
    public function agregarObservacion(int $id, string $observacion): bool
    {
        $sql = "
            UPDATE inscripciones
            SET observaciones = CONCAT(
                IFNULL(observaciones, ''),
                :observacion
            )
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':observacion', $observacion, \PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Obtiene pendientes.
    public function obtenerPendientes(): array
    {
        $sql = "
            SELECT
                i.id,
                i.fecha_inscripcion,
                u.id AS usuario_id,
                u.nombre,
                u.apellido,
                u.dni,
                et.nombre AS estado
            FROM inscripciones i
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            INNER JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            WHERE i.estado_tramite_id = :estado
            ORDER BY i.fecha_inscripcion ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':estado' => EstadoTramite::PENDIENTE
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene por usuario.
    public function obtenerPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT
                i.id,
                i.fecha_inscripcion,
                i.examen_id,
                i.curso_id,
                i.tipo_inscripcion_id,
                et.nombre AS estado
            FROM inscripciones i
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            WHERE i.usuario_id = :usuario
            ORDER BY i.fecha_inscripcion DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene por curso.
    public function obtenerPorCurso(int $cursoId): array
    {
        $sql = "
            SELECT
                i.id,
                i.fecha_inscripcion,
                u.id AS usuario_id,
                u.nombre,
                u.apellido,
                u.dni,
                et.nombre AS estado
            FROM inscripciones i
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            WHERE i.curso_id = :curso
            ORDER BY i.fecha_inscripcion DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':curso' => $cursoId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Crea la operaci?n correspondiente.
    public function crear(array $datos): int
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO inscripciones
            (
                usuario_id,
                curso_id,
                examen_id,
                tipo_inscripcion_id,
                fecha_inscripcion,
                estado_tramite_id,
                observaciones,
                fecha_fin_curso
            )
            VALUES
            (
                :usuario,
                :curso,
                :examen,
                :tipo,
                :fecha,
                :estado,
                :obs,
                :fin
            )
        ");

        $stmt->execute([
            ':usuario' => $datos['usuario_id'],
            ':curso' => $datos['curso_id'] ?? null,
            ':examen' => $datos['examen_id'] ?? null,
            ':tipo' => $datos['tipo_inscripcion_id'],
            ':fecha' => $datos['fecha_inscripcion'] ?? date('Y-m-d H:i:s'),
            ':estado' => EstadoTramite::PENDIENTE,
            ':obs' => $datos['observaciones'] ?? null,
            ':fin' => $datos['fecha_fin_curso'] ?? null
        ]);

        return (int)$this->conexion->lastInsertId();
    }

    // Ejecuta cancelar.
    public function cancelar(int $id, string $motivo = ''): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE inscripciones
            SET
                estado_tramite_id = :estado,
                observaciones = CONCAT(
                    IFNULL(observaciones,''),
                    :motivo
                )
            WHERE id = :id
        ");

        return $stmt->execute([
            ':estado' => EstadoTramite::CANCELADO,
            ':motivo' => "\nCancelación: ".$motivo,
            ':id' => $id
        ]);
    }
    // Ejecuta inscribir examen.
    public function inscribirExamen(int $idInscripcion, int $idExamen): bool
        {
            $stmt = $this->conexion->prepare("
                UPDATE inscripciones
                SET
                    examen_id = :examen,
                    estado_tramite_id = :estado
                WHERE id = :id
            ");

            return $stmt->execute([
                ':examen' => $idExamen,
                ':estado' => EstadoTramite::INSCRIPTO_EXAMEN,
                ':id' => $idInscripcion
            ]);
        }
    // Obtiene ultima inscripcion por usuario.
    public function obtenerUltimaInscripcionPorUsuario(int $usuarioId): ?array
    {
        $sql = "
            SELECT *
            FROM inscripciones
            WHERE usuario_id = :usuario
            ORDER BY fecha_inscripcion DESC
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
        }
          
    /// Obtener última inscripción de un usuario


    public function obtenerInscripcionesActivas(int $usuarioId): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM inscripciones
            WHERE usuario_id = :usuario AND estado_tramite_id IN
            (
                :pendiente,
                :documentacion,
                :inscripto,
                :aprobado
            )
            ORDER BY fecha_inscripcion DESC
        ");

        $stmt->execute([
            ':pendiente' => EstadoTramite::PENDIENTE,
            ':documentacion' => EstadoTramite::DOCUMENTACION_APROBADA,
            ':inscripto' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':aprobado' => EstadoTramite::APROBADO
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Confirma una inscripción a examen.
     */
    public function confirmarInscripcionExamen(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE inscripciones
            SET estado_tramite_id = :estado
            WHERE id = :id
        ");

        return $stmt->execute([
            ':estado' => EstadoTramite::INSCRIPTO_EXAMEN,
            ':id' => $id
        ]);
    }

    /**
     * Obtener la modalidad del curso asociado a una inscripción.
     */
    public function obtenerModalidadCurso(int $idInscripcion): ?string
    {
        $stmt = $this->conexion->prepare("
            SELECT c.modalidad
            FROM inscripciones i
            INNER JOIN cursos c
                ON c.id = i.curso_id
            WHERE i.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $modalidad = $stmt->fetchColumn();

        return $modalidad !== false
            ? $modalidad
            : null;
    }

    // Obtiene usuario id por inscripcion.
    public function obtenerUsuarioIdPorInscripcion(int $inscripcionId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT usuario_id
            FROM inscripciones
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $inscripcionId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ? (int)$resultado['usuario_id'] : null;
    }
    /**
     * Obtener el curso asociado a una inscripción.
     *
     * @param int $idInscripcion
     * @return array|null
     */
    public function obtenerCurso(
        int $idInscripcion
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                c.*

            FROM cursos c

            INNER JOIN inscripciones i
                ON i.curso_id = c.id

            WHERE
                i.id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $curso =
            $stmt->fetch(\PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    /**
     * Obtener el tipo de inscripción.
     *
     * @param int $idInscripcion
     * @return array|null
     */
    public function obtenerTipoInscripcion(
        int $idInscripcion
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                ti.*

            FROM tipo_inscripcion ti

            INNER JOIN inscripciones i
                ON i.tipo_inscripcion_id = ti.id

            WHERE
                i.id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $tipo =
            $stmt->fetch(\PDO::FETCH_ASSOC);

        return $tipo ?: null;
    }

    /**
     * Actualizar estado del trámite.
     *
     * @param int $idInscripcion
     * @param int $estado
     * @return bool
     */
    public function actualizarEstadoTramite(
        int $idInscripcion,
        int $estado
    ): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE inscripciones

            SET estado_tramite_id = :estado

            WHERE id = :id
        ");

        return $stmt->execute([
            ':estado' => $estado,
            ':id' => $idInscripcion
        ]);
    }
    public function obtenerPendientesValidacion(): array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                i.*,
                u.nombre,
                u.apellido,
                c.nombre AS curso_nombre,
                c.modalidad

            FROM inscripciones i

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            LEFT JOIN cursos c
                ON c.id = i.curso_id

            WHERE
                i.estado_tramite_id = :estado

            ORDER BY
                i.fecha_inscripcion ASC
        ");

        $stmt->execute([
            ':estado' =>
                EstadoTramite::PENDIENTE
        ]);

        return
            $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function tieneExamenActivo(int $usuarioId): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM inscripciones
            WHERE usuario_id = :usuario
            AND tipo_inscripcion_id = 2
            AND estado_tramite_id = :inscripto
        ");

        $stmt->execute([
            ':usuario'   => $usuarioId,
            ':inscripto' => EstadoTramite::INSCRIPTO_EXAMEN
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function obtenerProximoExamenUsuario(int $usuarioId): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                i.id,
                e.id AS examen_id,
                e.fecha,
                e.hora,
                e.ubicacion,
                e.aula,
                i.estado_tramite_id

            FROM inscripciones i
            INNER JOIN examenes e
                ON e.id = i.examen_id

            WHERE
                i.usuario_id = :usuario
                AND i.tipo_inscripcion_id = 2
                AND i.estado_tramite_id = :inscripto

            ORDER BY e.fecha ASC, e.hora ASC

            LIMIT 1
        ");

        $stmt->execute([
            ':usuario'   => $usuarioId,
            ':inscripto' => EstadoTramite::INSCRIPTO_EXAMEN
        ]);

        $examen =
            $stmt->fetch(\PDO::FETCH_ASSOC);

        return $examen ?: null;
    }
}
