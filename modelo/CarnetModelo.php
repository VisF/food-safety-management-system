<?php
declare(strict_types=1);

/**
 * CarnetModelo - Gestión de carnets de manipulador de alimentos
 * 
 * Propiedades:
 * - id: identificador único
 * - id_inscripcion: ID de la inscripción
 * - numero_carnet: número único del carnet
 * - fecha_emision: fecha de emisión del carnet
 * - fecha_vencimiento: fecha de vencimiento del carnet
 * - ruta_pdf: ruta del archivo PDF del carnet
 * - vigente: estado de vigencia (1=vigente, 0=vencido)
 */

class CarnetModelo
{
    private int $id;
    private int $id_inscripcion;
    private string $numero_carnet;
    private string $fecha_emision;
    private string $fecha_vencimiento;
    private string $ruta_pdf;
    private int $vigente;

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
     * Crear nuevo carnet
     * @param array $data Datos: id_inscripcion, numero_carnet, fecha_emision, fecha_vencimiento, ruta_pdf
     * @return array|false Retorna array con id y datos, false si falla
     */
    public function crear(array $data)
    {
        if (!$this->conexion) return false;

        $id_inscripcion = (int)($data['id_inscripcion'] ?? 0);
        $numero = $data['numero_carnet'] ?? '';
        $fecha_emision = $data['fecha_emision'] ?? date('Y-m-d');
        $fecha_venc = $data['fecha_vencimiento'] ?? null;
        $ruta = $data['ruta_pdf'] ?? '';

        if ($id_inscripcion <= 0 || !$numero || !$fecha_venc || !$ruta) return false;

        // verificar numero único
        $stmt = $this->conexion->prepare('SELECT id FROM carnets WHERE numero_carnet = :num');
        $stmt->execute([':num' => $numero]);
        if ($stmt->fetch()) return false;

        $sql = 'INSERT INTO carnets (inscripcion_id, numero_carnet, fecha_emision, fecha_vencimiento, ruta_pdf, vigente) VALUES (:iid, :num, :femi, :fven, :ruta, 1)';
        $stmt = $this->conexion->prepare($sql);
        $params = [':iid' => $id_inscripcion, ':num' => $numero, ':femi' => $fecha_emision, ':fven' => $fecha_venc, ':ruta' => $ruta];
        if ($stmt->execute($params)) {
            $id = (int)$this->conexion->lastInsertId();
            return ['id' => $id, 'numero_carnet' => $numero, 'id_inscripcion' => $id_inscripcion];
        }
        return false;
    }

    /**
     * Obtener carnet de una inscripción
     * @param int $id_inscripcion ID de la inscripción
     * @return array|null Datos del carnet o null si no existe
     */
    public function obtenerPorInscripcion(int $id_inscripcion): ?array
    {
        if (!$this->conexion) return null;

        $stmt = $this->conexion->prepare('SELECT * FROM carnets WHERE inscripcion_id = :iid');
        $stmt->execute([':iid' => $id_inscripcion]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtener carnet por DNI del usuario
     * @param string $dni DNI del usuario
     * @return array|null Datos del carnet o null si no existe
     */
    public function obtenerPorDNI(string $dni): ?array
    {
        if (!$this->conexion) return null;

        $sql = 'SELECT c.* FROM carnets c JOIN inscripciones i ON c.inscripcion_id = i.id JOIN usuarios u ON i.usuario_id = u.id WHERE u.dni = :dni';
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':dni' => $dni]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verificar si el carnet está vigente
     * @param int $id ID del carnet
     * @return bool true si está vigente, false si no
     */
    public function verificarVigencia(int $id): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('SELECT fecha_vencimiento, vigente FROM carnets WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return false;
        if ((int)$row['vigente'] === 0) return false;
        return strtotime($row['fecha_vencimiento']) > time();
    }

    /**
     * Actualizar carnet
     * @param int $id ID del carnet
     * @param array $data Datos a actualizar
     * @return bool true si fue exitoso, false si falla
     */
    public function actualizar(int $id, array $data): bool
    {
        if (!$this->conexion) return false;

        $allowed = ['fecha_emision','fecha_vencimiento','ruta_pdf','vigente','numero_carnet'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $sets[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }
        if (empty($sets)) return false;

        $sql = 'UPDATE carnets SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conexion->prepare($sql);
        return (bool)$stmt->execute($params);
    }

    /**
     * Obtener carnets vencidos
     * @return array Array de carnets vencidos
     */
    public function obtenerCarnetesVencidos(): array
    {
        if (!$this->conexion) return [];

        $stmt = $this->conexion->query('SELECT * FROM carnets WHERE fecha_vencimiento < NOW() AND vigente = 1 ORDER BY fecha_vencimiento ASC');
        return $stmt->fetchAll();
    }

    /**
     * Renovar carnet
     * @param int $id ID del carnet
     * @param string $nuevaFechaVencimiento Nueva fecha de vencimiento
     * @param string $nuevaRutaPdf Nueva ruta del PDF
     * @return bool true si fue exitoso, false si falla
     */
    public function renovar(int $id, string $nuevaFechaVencimiento, string $nuevaRutaPdf): bool
    {
        if (!$this->conexion) return false;

        $stmt = $this->conexion->prepare('UPDATE carnets SET fecha_emision = NOW(), fecha_vencimiento = :fv, ruta_pdf = :ruta, vigente = 1 WHERE id = :id');
        return (bool)$stmt->execute([':fv' => $nuevaFechaVencimiento, ':ruta' => $nuevaRutaPdf, ':id' => $id]);
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
     * Obtener número de carnet
     * @return string
     */
    public function getNumeroCarnet(): string
    {
        return $this->numero_carnet;
    }

    /**
     * Obtener fecha de emisión
     * @return string
     */
    public function getFechaEmision(): string
    {
        return $this->fecha_emision;
    }

    /**
     * Obtener fecha de vencimiento
     * @return string
     */
    public function getFechaVencimiento(): string
    {
        return $this->fecha_vencimiento;
    }

    /**
     * Obtener ruta del PDF
     * @return string
     */
    public function getRutaPdf(): string
    {
        return $this->ruta_pdf;
    }

    /**
     * Obtener estado vigente
     * @return int
     */
    public function getVigente(): int
    {
        return $this->vigente;
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
     * Establecer número de carnet
     * @param string $numero_carnet
     * @return void
     */
    public function setNumeroCarnet(string $numero_carnet): void
    {
        $this->numero_carnet = $numero_carnet;
    }

    /**
     * Establecer fecha de emisión
     * @param string $fecha_emision
     * @return void
     */
    public function setFechaEmision(string $fecha_emision): void
    {
        $this->fecha_emision = $fecha_emision;
    }

    /**
     * Establecer fecha de vencimiento
     * @param string $fecha_vencimiento
     * @return void
     */
    public function setFechaVencimiento(string $fecha_vencimiento): void
    {
        $this->fecha_vencimiento = $fecha_vencimiento;
    }

    /**
     * Establecer ruta del PDF
     * @param string $ruta_pdf
     * @return void
     */
    public function setRutaPdf(string $ruta_pdf): void
    {
        $this->ruta_pdf = $ruta_pdf;
    }

    /**
     * Establecer estado vigente
     * @param int $vigente
     * @return void
     */
    public function setVigente(int $vigente): void
    {
        $this->vigente = $vigente;
    }
}
?>
