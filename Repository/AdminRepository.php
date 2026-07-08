<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

require_once __DIR__.'/../Constant/EstadoTramite.php';

class AdminRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }


    // ============================
    // Dashboard
    // ============================

    public function obtenerEstadisticas(): array
    {
        $estadisticas = [];

        // Usuarios
        $estadisticas['total_usuarios'] = $this->contar('usuarios');
        $estadisticas['usuarios_activos'] = $this->contar('usuarios', 'activo = 1');

        // Inscripciones
        $estadisticas['total_inscripciones'] = $this->contar('inscripciones');

        $estadisticas['inscripciones_pendientes'] = (int) $this->conexion
            ->query("
                SELECT COUNT(*)
                FROM inscripciones
                WHERE estado_tramite_id IN (
                    " . EstadoTramite::PENDIENTE . ",
                    " . EstadoTramite::DOCUMENTACION_APROBADA . ",
                    " . EstadoTramite::INSCRIPTO_EXAMEN . "
                )
            ")
            ->fetchColumn();

        $estadisticas['inscripciones_aprobadas'] = $this->contar(
            'inscripciones',
            'estado_tramite_id = ' . EstadoTramite::APROBADO
        );

        $estadisticas['cursos_activos'] = (int) $this->conexion
            ->query("
                SELECT COUNT(*)
                FROM inscripciones
                WHERE tipo_inscripcion_id = 1
                AND fecha_fin_curso >= CURDATE()
            ")
            ->fetchColumn();

        // Exámenes
        $estadisticas['total_examenes'] = $this->contar('examenes');

        $aprobados = $this->contar('resultado_examen', 'aprobado = 1');
        $reprobados = $this->contar('resultado_examen', 'aprobado = 0');

        $estadisticas['tasa_aprobacion'] =
            ($aprobados + $reprobados) > 0
                ? round(($aprobados / ($aprobados + $reprobados)) * 100, 2)
                : 0.0;

        // Carnets
        $estadisticas['carnets_emitidos'] = $this->contar('carnets');

        return $estadisticas;
    }

    public function obtenerActividadReciente(int $limite = 20): array
    {
        $limite = max(1, min(100, $limite));

        $sql = "
            SELECT
                i.id,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre,
                u.dni,
                LOWER(et.nombre) AS estado_class,
                UPPER(REPLACE(et.nombre, '_', ' ')) AS estado
            FROM inscripciones i
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id
            ORDER BY i.fecha_inscripcion DESC
            LIMIT :limite
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

//Metodos privados
    private function contar(string $tabla, string $where = ''): int
        {
            $sql = "SELECT COUNT(*) FROM {$tabla}";

            if ($where !== '') {
                $sql .= " WHERE {$where}";
            }

            return (int) $this->conexion
                ->query($sql)
                ->fetchColumn();
        }
  
        //====================================================
        // USUARIOS
        //====================================================

        public function obtenerUsuarios(): array
        {
            $sql = "
                SELECT
                    u.id,
                    u.nombre,
                    u.apellido,
                    u.dni,
                    u.email,
                    u.telefono,
                    u.activo,
                    u.fecha_creacion,
                    GROUP_CONCAT(r.nombre SEPARATOR ', ') AS roles
                FROM usuarios u
                LEFT JOIN usuario_roles ur
                    ON ur.usuario_id = u.id
                LEFT JOIN roles r
                    ON r.id = ur.rol_id
                GROUP BY u.id
                ORDER BY u.apellido,u.nombre
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerUsuarioPorId(int $id): ?array
        {
            $sql = "
                SELECT *
                FROM usuarios
                WHERE id=:id
            ";

            $stmt = $this->conexion->prepare($sql);

            $stmt->execute([
                ':id'=>$id
            ]);

            $usuario=$stmt->fetch(PDO::FETCH_ASSOC);

            return $usuario?:null;
        }

        public function buscarUsuarios(string $texto): array
        {
            $sql = "
                SELECT *
                FROM usuarios
                WHERE
                    nombre LIKE :texto
                    OR apellido LIKE :texto
                    OR dni LIKE :texto
                    OR email LIKE :texto
                ORDER BY apellido,nombre
            ";

            $stmt=$this->conexion->prepare($sql);

            $stmt->execute([
                ':texto'=>"%{$texto}%"
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function actualizarUsuario(
            int $id,
            array $datos
        ): bool
        {
            $permitidos=[
                'nombre',
                'apellido',
                'telefono',
                'email',
                'domicilio',
                'activo'
            ];

            $sets=[];

            $params=[
                ':id'=>$id
            ];

            foreach($permitidos as $campo){

                if(isset($datos[$campo])){

                    $sets[]="$campo=:$campo";

                    $params[":$campo"]=$datos[$campo];
                }
            }

            if(empty($sets)){
                return false;
            }

            $sql="UPDATE usuarios
                    SET ".implode(',',$sets)."
                WHERE id=:id";

            return $this->conexion
                ->prepare($sql)
                ->execute($params);
        }

        public function cambiarEstadoUsuario(
            int $id,
            bool $activo
        ): bool
        {
            $stmt=$this->conexion->prepare("
                UPDATE usuarios
                SET activo=:activo
                WHERE id=:id
            ");

            return $stmt->execute([
                ':activo'=>$activo?1:0,
                ':id'=>$id
            ]);
        }

        public function eliminarUsuario(int $id): bool
        {
            return $this
                ->cambiarEstadoUsuario($id,false);
        }

        public function obtenerUsuariosActivos(): array
        {
            return $this->conexion
                ->query("
                    SELECT *
                    FROM usuarios
                    WHERE activo=1
                    ORDER BY apellido,nombre
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerUsuariosInactivos(): array
        {
            return $this->conexion
                ->query("
                    SELECT *
                    FROM usuarios
                    WHERE activo=0
                    ORDER BY apellido,nombre
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        //====================================================
        // DOCUMENTOS
        //====================================================

        public function obtenerDocumentos(): array
        {
            $sql = "
                SELECT
                    d.id,
                    d.usuario_id,
                    CONCAT(u.nombre,' ',u.apellido) AS usuario,
                    u.dni,
                    d.tipo_documento,
                    d.nombre_original,
                    d.estado,
                    d.fecha_subida,
                    d.fecha_revision,
                    d.observaciones
                FROM documentos d
                INNER JOIN usuarios u
                    ON u.id = d.usuario_id
                ORDER BY d.fecha_subida DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerDocumentoPorId(int $id): ?array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM documentos
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $documento = $stmt->fetch(PDO::FETCH_ASSOC);

            return $documento ?: null;
        }

        public function obtenerDocumentosPendientes(): array
        {
            $sql = "
                SELECT
                    d.id,
                    d.usuario_id,
                    CONCAT(u.nombre,' ',u.apellido) AS usuario,
                    u.dni,
                    d.tipo_documento,
                    d.nombre_original,
                    d.fecha_subida
                FROM documentos d
                INNER JOIN usuarios u
                    ON u.id=d.usuario_id
                WHERE d.estado='pendiente'
                ORDER BY d.fecha_subida ASC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerDocumentosAprobados(): array
        {
            $sql = "
                SELECT
                    d.*,
                    CONCAT(u.nombre,' ',u.apellido) usuario,
                    u.dni
                FROM documentos d
                INNER JOIN usuarios u
                    ON u.id=d.usuario_id
                WHERE d.estado='aprobado'
                ORDER BY d.fecha_revision DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerDocumentosRechazados(): array
        {
            $sql = "
                SELECT
                    d.*,
                    CONCAT(u.nombre,' ',u.apellido) usuario,
                    u.dni
                FROM documentos d
                INNER JOIN usuarios u
                    ON u.id=d.usuario_id
                WHERE d.estado='rechazado'
                ORDER BY d.fecha_revision DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerDocumentosUsuario(int $usuarioId): array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM documentos
                WHERE usuario_id = :usuario
                ORDER BY fecha_subida DESC
            ");

            $stmt->execute([
                ':usuario' => $usuarioId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function aprobarDocumento(
            int $id,
            string $observaciones = ''
        ): bool
        {
            $stmt = $this->conexion->prepare("
                UPDATE documentos
                SET
                    estado='aprobado',
                    observaciones=:obs,
                    fecha_revision=NOW()
                WHERE id=:id
            ");

            return $stmt->execute([
                ':id'=>$id,
                ':obs'=>$observaciones
            ]);
        }

        public function rechazarDocumento(
            int $id,
            string $motivo
        ): bool
        {
            $stmt = $this->conexion->prepare("
                UPDATE documentos
                SET
                    estado='rechazado',
                    observaciones=:motivo,
                    fecha_revision=NOW()
                WHERE id=:id
            ");

            return $stmt->execute([
                ':id'=>$id,
                ':motivo'=>$motivo
            ]);
        }

        public function eliminarDocumento(int $id): bool
        {
            $stmt = $this->conexion->prepare("
                DELETE
                FROM documentos
                WHERE id=:id
            ");

            return $stmt->execute([
                ':id'=>$id
            ]);
        }

        public function obtenerCantidadDocumentosPendientes(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM documentos
                    WHERE estado='pendiente'
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadDocumentosAprobados(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM documentos
                    WHERE estado='aprobado'
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadDocumentosRechazados(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM documentos
                    WHERE estado='rechazado'
                ")
                ->fetchColumn();
        }

        //====================================================
        // EXÁMENES
        //====================================================

        public function obtenerExamenes(): array
        {
            $sql = "
                SELECT *
                FROM examenes
                ORDER BY fecha DESC, hora DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerExamenPorId(int $id): ?array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM examenes
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $examen = $stmt->fetch(PDO::FETCH_ASSOC);

            return $examen ?: null;
        }

        public function obtenerProximosExamenes(int $limite = 10): array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM examenes
                WHERE fecha >= CURDATE()
                ORDER BY fecha ASC, hora ASC
                LIMIT :limite
            ");

            $stmt->bindValue(
                ':limite',
                $limite,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function eliminarExamen(int $id): bool
        {
            $stmt = $this->conexion->prepare("
                DELETE
                FROM examenes
                WHERE id = :id
            ");

            return $stmt->execute([
                ':id' => $id
            ]);
        }

        public function actualizarExamen(
            int $id,
            array $datos
        ): bool
        {
            $permitidos = [
                'fecha',
                'hora',
                'ubicacion',
                'aula',
                'cupos',
                'activo'
            ];

            $sets = [];

            $params = [
                ':id' => $id
            ];

            foreach ($permitidos as $campo) {

                if (isset($datos[$campo])) {

                    $sets[] = "{$campo}=:$campo";

                    $params[":{$campo}"] =
                        $datos[$campo];
                }
            }

            if (empty($sets)) {
                return false;
            }

            $sql = "
                UPDATE examenes
                SET " . implode(',', $sets) . "
                WHERE id=:id
            ";

            return $this->conexion
                ->prepare($sql)
                ->execute($params);
        }

        public function obtenerCantidadExamenes(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM examenes
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadExamenesActivos(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM examenes
                    WHERE activo = 1
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadExamenesFuturos(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM examenes
                    WHERE fecha >= CURDATE()
                ")
                ->fetchColumn();
        }


        //====================================================
        // CARNETS
        //====================================================

        public function obtenerCarnets(): array
        {
            $sql = "
                SELECT
                    c.*,
                    u.nombre,
                    u.apellido,
                    u.dni
                FROM carnets c
                INNER JOIN inscripciones i
                    ON i.id = c.inscripcion_id
                INNER JOIN usuarios u
                    ON u.id = i.usuario_id
                ORDER BY c.fecha_emision DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerCarnetPorId(int $id): ?array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM carnets
                WHERE id = :id
            ");

            $stmt->execute([
                ':id'=>$id
            ]);

            $carnet = $stmt->fetch(PDO::FETCH_ASSOC);

            return $carnet ?: null;
        }

        public function obtenerCarnetsVigentes(): array
        {
            return $this->conexion
                ->query("
                    SELECT *
                    FROM carnets
                    WHERE
                        activo = 1
                        AND fecha_vencimiento >= CURDATE()
                    ORDER BY fecha_vencimiento
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerCarnetsVencidos(): array
        {
            return $this->conexion
                ->query("
                    SELECT *
                    FROM carnets
                    WHERE
                        fecha_vencimiento < CURDATE()
                    ORDER BY fecha_vencimiento DESC
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function renovarCarnet(
            int $id,
            string $fechaVencimiento
        ): bool
        {
            $stmt = $this->conexion->prepare("
                UPDATE carnets
                SET
                    fecha_emision = CURDATE(),
                    fecha_vencimiento = :fecha,
                    activo = 1
                WHERE id = :id
            ");

            return $stmt->execute([
                ':id'=>$id,
                ':fecha'=>$fechaVencimiento
            ]);
        }

        public function anularCarnet(int $id): bool
        {
            $stmt = $this->conexion->prepare("
                UPDATE carnets
                SET activo = 0
                WHERE id = :id
            ");

            return $stmt->execute([
                ':id'=>$id
            ]);
        }

        public function obtenerCantidadCarnets(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM carnets
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadCarnetsActivos(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM carnets
                    WHERE activo = 1
                ")
                ->fetchColumn();
        }

        public function obtenerCantidadCarnetsVencidos(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM carnets
                    WHERE fecha_vencimiento < CURDATE()
                ")
                ->fetchColumn();
        }
        //====================================================
        // INSCRIPCIONES
        //====================================================

        public function obtenerInscripciones(): array
        {
            $sql = "
                SELECT
                    i.id,
                    CONCAT(u.nombre,' ',u.apellido) AS usuario,
                    u.dni,
                    i.fecha_inscripcion,
                    et.nombre AS estado,
                    ti.nombre AS tipo
                FROM inscripciones i
                INNER JOIN usuarios u
                    ON u.id=i.usuario_id
                LEFT JOIN estados_tramite et
                    ON et.id=i.estado_tramite_id
                LEFT JOIN tipos_inscripcion ti
                    ON ti.id=i.tipo_inscripcion_id
                ORDER BY i.fecha_inscripcion DESC
            ";

            return $this->conexion
                ->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerInscripcionPorId(int $id): ?array
        {
            $stmt=$this->conexion->prepare("
                SELECT *
                FROM inscripciones
                WHERE id=:id
            ");

            $stmt->execute([
                ':id'=>$id
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function obtenerInscripcionesPendientes(): array
        {
            $stmt=$this->conexion->prepare("
                SELECT
                    i.*,
                    CONCAT(u.nombre,' ',u.apellido) usuario,
                    u.dni
                FROM inscripciones i
                INNER JOIN usuarios u
                    ON u.id=i.usuario_id
                WHERE i.estado_tramite_id=:estado
                ORDER BY i.fecha_inscripcion ASC
            ");

            $stmt->execute([
                ':estado'=>EstadoTramite::PENDIENTE
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerCantidadInscripciones(): int
        {
            return $this->contar('inscripciones');
        }

        public function obtenerCantidadInscripcionesPendientes(): int
        {
            return (int)$this->conexion
                ->query("
                    SELECT COUNT(*)
                    FROM inscripciones
                    WHERE estado_tramite_id=".EstadoTramite::PENDIENTE
                )
                ->fetchColumn();
        }


        //====================================================
        // BUSQUEDAS
        //====================================================

        public function buscarPorDni(string $dni): ?array
        {
            $stmt=$this->conexion->prepare("
                SELECT
                    u.*,
                    c.numero_carnet,
                    c.fecha_vencimiento,
                    c.activo
                FROM usuarios u
                LEFT JOIN inscripciones i
                    ON i.usuario_id=u.id
                LEFT JOIN carnets c
                    ON c.inscripcion_id=i.id
                WHERE u.dni=:dni
                LIMIT 1
            ");

            $stmt->execute([
                ':dni'=>$dni
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function buscarCarnet(string $numero): ?array
        {
            $stmt=$this->conexion->prepare("
                SELECT *
                FROM carnets
                WHERE numero_carnet=:numero
            ");

            $stmt->execute([
                ':numero'=>$numero
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function buscarUsuario(string $texto): array
        {
            $stmt=$this->conexion->prepare("
                SELECT *
                FROM usuarios
                WHERE
                    nombre LIKE :texto
                    OR apellido LIKE :texto
                    OR dni LIKE :texto
                ORDER BY apellido,nombre
            ");

            $stmt->execute([
                ':texto'=>"%{$texto}%"
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }


        //====================================================
        // DASHBOARD AUXILIAR
        //====================================================

        public function obtenerUltimosUsuarios(int $limite=10): array
        {
            $stmt=$this->conexion->prepare("
                SELECT
                    nombre,
                    apellido,
                    dni,
                    fecha_creacion
                FROM usuarios
                ORDER BY fecha_creacion DESC
                LIMIT :limite
            ");

            $stmt->bindValue(
                ':limite',
                $limite,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerUltimosCarnets(int $limite=10): array
        {
            $stmt=$this->conexion->prepare("
                SELECT *
                FROM carnets
                ORDER BY fecha_emision DESC
                LIMIT :limite
            ");

            $stmt->bindValue(
                ':limite',
                $limite,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerUltimosExamenes(int $limite=10): array
        {
            $stmt=$this->conexion->prepare("
                SELECT *
                FROM examenes
                ORDER BY fecha DESC
                LIMIT :limite
            ");

            $stmt->bindValue(
                ':limite',
                $limite,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerResumenGeneral(): array
        {
            return [
                'usuarios'=>$this->obtenerCantidadUsuarios(),
                'inscripciones'=>$this->obtenerCantidadInscripciones(),
                'documentosPendientes'=>$this->obtenerCantidadDocumentosPendientes(),
                'examenes'=>$this->obtenerCantidadExamenes(),
                'carnets'=>$this->obtenerCantidadCarnets()
            ];
        }

        public function obtenerCantidadUsuarios(): int
        {
            return $this->contar('usuarios');
        }

        public function obtenerCantidadUsuariosActivos(): int
        {
            return $this->contar(
                'usuarios',
                'activo = 1'
            );
        }

        /**
         * Obtener una solicitud por ID.
         */
        public function obtenerSolicitud(int $id): ?array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM solicitudes
                WHERE id = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $fila ?: null;
        }
        /**
         * Registrar una respuesta a una solicitud.
         */
        public function responderSolicitud(int $idSolicitud, array $respuesta): ?int
        {
            $stmt = $this->conexion->query("
                SHOW TABLES LIKE 'solicitud_respuestas'
            ");

            if ($stmt->rowCount() > 0) {

                $insert = $this->conexion->prepare("
                    INSERT INTO solicitud_respuestas
                    (
                        solicitud_id,
                        contenido,
                        creado_por,
                        fecha_creacion
                    )
                    VALUES
                    (
                        :sid,
                        :contenido,
                        :creador,
                        NOW()
                    )
                ");

                $insert->execute([
                    ':sid' => $idSolicitud,
                    ':contenido' => $respuesta['contenido'] ?? '',
                    ':creador' => $respuesta['creador'] ?? 'admin'
                ]);

                $idRespuesta =
                    (int)$this->conexion->lastInsertId();

                $update = $this->conexion->prepare("
                    UPDATE solicitudes
                    SET estado = 'respondida'
                    WHERE id = :id
                ");

                $update->execute([
                    ':id' => $idSolicitud
                ]);

                return $idRespuesta;
            }

            $update = $this->conexion->prepare("
                UPDATE solicitudes
                SET
                    estado = 'respondida',
                    respuesta = :respuesta,
                    fecha_respuesta = NOW()
                WHERE id = :id
            ");

            $update->execute([
                ':respuesta' => $respuesta['contenido'] ?? '',
                ':id' => $idSolicitud
            ]);

            return null;
        }
        /**
         * Obtener solicitudes pendientes.
         */
        public function obtenerSolicitudesPendientes(): array
        {
            $stmt = $this->conexion->prepare("
                SELECT *
                FROM solicitudes
                WHERE estado IN
                (
                    'pendiente',
                    'nuevo'
                )
                ORDER BY fecha_creacion DESC
            ");

            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        /**
         * Obtener datos para exportación.
         */
        public function obtenerDatosExportacion(): array
        {
            $stmt = $this->conexion->prepare("
                SELECT
                    u.id AS usuario_id,
                    u.nombre,
                    u.apellido,
                    u.email,
                    u.dni,

                    i.id AS inscripcion_id,
                    i.curso_id,
                    i.estado_tramite_id,
                    i.fecha_inscripcion

                FROM usuarios u

                LEFT JOIN inscripciones i
                    ON i.usuario_id = u.id
            ");

            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }


}   