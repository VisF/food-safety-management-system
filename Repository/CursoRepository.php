<?php
declare(strict_types=1);


/**
 * CursoRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * CursoRepository - Repositorio para operaciones de cursos
 * 
 * Dependencias:
 * - db/Connection.php
 * 
 * Funciones principales:
 *
 * listar()

 * obtenerPorId()

 * crear()

 * actualizar()

 * activar()

 * desactivar()

 * obtenerActivos()

 * obtenerPorModalidad()

 * existeNombre()

 * contarInscripciones()
 */


 
require_once __DIR__ . '/../db/Connection.php';

class CursoRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    // ============================
    // Consultas
    // ============================

    public function listar(): array
    {
        $sql = "
            SELECT
                *
            FROM cursos
            ORDER BY fecha_inicio DESC, nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Obtiene por id.
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                *
            FROM cursos
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $curso = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    // Obtiene activos.
    public function obtenerActivos(): array
    {
        $sql = "
            SELECT
                *
            FROM cursos
            WHERE activo = 1
            ORDER BY fecha_inicio DESC, nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Obtiene por modalidad.
    public function obtenerPorModalidad(string $modalidad): array
    {
        $sql = "
            SELECT
                *
            FROM cursos
            WHERE modalidad = :modalidad
            AND activo = 1
            ORDER BY fecha_inicio DESC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':modalidad', $modalidad);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ============================
    // Altas
    // ============================

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO cursos
            (
                nombre,
                modalidad,
                descripcion,
                activo,
                fecha_inicio,
                hora_inicio,
                ubicacion,
                cupos
            )
            VALUES
            (
                :nombre,
                :modalidad,
                :descripcion,
                1,
                :fecha_inicio,
                :hora_inicio,
                :ubicacion,
                :cupos
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':nombre', $datos['nombre']);
        $stmt->bindValue(':modalidad', $datos['modalidad']);
        $stmt->bindValue(':descripcion', $datos['descripcion']);
        $stmt->bindValue(':fecha_inicio', $datos['fecha_inicio']);
        $stmt->bindValue(':hora_inicio', $datos['hora_inicio']);
        $stmt->bindValue(':ubicacion', $datos['ubicacion']);
        $stmt->bindValue(':cupos', $datos['cupos'], \PDO::PARAM_INT);

        $stmt->execute();

        return (int)$this->conexion->lastInsertId();
    }

    // ============================
    // Modificaciones
    // ============================

    public function actualizar(int $id, array $datos): bool
    {
        $sql = "
            UPDATE cursos
            SET
                nombre = :nombre,
                modalidad = :modalidad,
                descripcion = :descripcion,
                fecha_inicio = :fecha_inicio,
                hora_inicio = :hora_inicio,
                ubicacion = :ubicacion,
                cupos = :cupos
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $datos['nombre']);
        $stmt->bindValue(':modalidad', $datos['modalidad']);
        $stmt->bindValue(':descripcion', $datos['descripcion']);
        $stmt->bindValue(':fecha_inicio', $datos['fecha_inicio']);
        $stmt->bindValue(':hora_inicio', $datos['hora_inicio']);
        $stmt->bindValue(':ubicacion', $datos['ubicacion']);
        $stmt->bindValue(':cupos', $datos['cupos'], \PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Ejecuta activar.
    public function activar(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE cursos
            SET activo = 1
            WHERE id = :id
        ");

        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Ejecuta desactivar.
    public function desactivar(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE cursos
            SET activo = 0
            WHERE id = :id
        ");

        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ============================
    // Validaciones
    // ============================

    public function existeNombre(string $nombre): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM cursos
            WHERE LOWER(nombre) = LOWER(:nombre)
        ");

        $stmt->bindValue(':nombre', trim($nombre));
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    // Ejecuta contar inscripciones.
    public function contarInscripciones(int $idCurso): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM inscripciones
            WHERE curso_id = :curso
            AND estado_tramite_id IN
            (
                :pendiente,
                :documentacion,
                :inscripto,
                :aprobado
            )
        ");

        $stmt->bindValue(':curso', $idCurso, \PDO::PARAM_INT);
        $stmt->bindValue(':pendiente', EstadoTramite::PENDIENTE, \PDO::PARAM_INT);
        $stmt->bindValue(':documentacion', EstadoTramite::DOCUMENTACION_APROBADA, \PDO::PARAM_INT);
        $stmt->bindValue(':inscripto', EstadoTramite::INSCRIPTO_EXAMEN, \PDO::PARAM_INT);
        $stmt->bindValue(':aprobado', EstadoTramite::APROBADO, \PDO::PARAM_INT);

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}
