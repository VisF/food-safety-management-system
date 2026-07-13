<?php
declare(strict_types=1);


/**
 * AdminReporteControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * AdminReporteControlador - Gestión administrativa del sistema
 * 
 * Dependencias esperadas:
 * -  cursoService, fechacursoService, InscripcionService, 
 *   DocumentoService, UsuarioService
 * 
 * Vistas esperadas:
 * - vistas/panel_admin.php
 * - vistas/crear_curso.php
 * - vistas/crear_examen.php
 * - vistas/validacion_documentos.php
 * - vistas/crear_respuesta_admin.php
 */

class AdminReporteControlador
{
    private const LOG_FILE = __DIR__ . '/../logs/admin_controller.log';
    
    private ?object $cursoService = null;
    private ?object $fechacursoService = null;
    private ?object $InscripcionService = null;
    private ?object $DocumentoService = null;
    private ?object $UsuarioService = null;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);
        $this->cursoService = new cursoService();
        $this->fechacursoService = new FechacursoService();
        $this->InscripcionService = new InscripcionService();
        $this->DocumentoService = new DocumentoService();
        $this->UsuarioService = new UsuarioService();
    }

    

    /**
     * Obtener reporte de actividad por período
     * 
     * @param string $fecha_inicio Fecha en formato Y-m-d
     * @param string $fecha_fin Fecha en formato Y-m-d
     * @return array [
     *   'success' => bool,
     *   'reporte' => [
     *     'periodo' => string,
     *     'nuevas_inscripciones' => int,
     *     'documentacion_validada' => int,
     *     'exámenes_realizados' => int,
     *     'aprobados' => int,
     *     'reprobados' => int,
     *     'carnets_emitidos' => int,
     *     'detalles' => array
     *   ]
     * ]
     */
    public function obtenerReportePorFecha(string $fecha_inicio, string $fecha_fin): array
    {
        try {
            $f1 = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
            $f2 = DateTime::createFromFormat('Y-m-d', $fecha_fin);
            if (!$f1 || !$f2) return ['success' => false, 'reporte' => [], 'message' => 'Fechas inválidas'];
            $d1 = $f1->format('Y-m-d') . ' 00:00:00';
            $d2 = $f2->format('Y-m-d') . ' 23:59:59';

            $pdoFile = __DIR__ . '/../db/Connection.php';
            if (!file_exists($pdoFile)) return ['success' => false, 'reporte' => []];
            require_once $pdoFile;
            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                                    'SELECT COUNT(*)
                                    FROM inscripciones
                                    WHERE fecha_inscripcion BETWEEN :d1 AND :d2'
                                );

            $stmt->execute([
                ':d1' => $d1,
                ':d2' => $d2
            ]);

            $nuevas = (int)$stmt->fetchColumn();


            // documentacion validada: asumimos que existe updated fecha o estado 2
            $docVal = (int)$pdo->query(
                                            "SELECT COUNT(*)
                                            FROM documentos
                                            WHERE estado = 'aprobado'
                                            AND fecha_subida BETWEEN '$d1' AND '$d2'"
                                        )->fetchColumn();
            $exReal = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $ap = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 1 AND fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $rep = (int)$pdo->query("SELECT COUNT(*) FROM resultado_examen WHERE aprobado = 0 AND fecha_resultado BETWEEN '$d1' AND '$d2'")->fetchColumn();
            $carn = (int)$pdo->query("SELECT COUNT(*) FROM carnets WHERE fecha_emision BETWEEN '$d1' AND '$d2'")->fetchColumn();

            $detalles = ['inscripciones' => [], 'resultados' => []];
            $rows = $pdo->query("SELECT * FROM inscripciones WHERE fecha_inscripcion BETWEEN '$d1' AND '$d2' ORDER BY fecha_inscripcion DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $detalles['inscripciones'] = $rows;
            $detalles['resultados'] = $pdo->query(
                                                    "SELECT *
                                                    FROM resultado_examen
                                                    WHERE fecha_resultado BETWEEN '$d1' AND '$d2'
                                                    ORDER BY fecha_resultado DESC"
                                                )->fetchAll(\PDO::FETCH_ASSOC);

            $this->log('Reporte generado', 'INFO', ['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);
            return ['success' => true, 'reporte' => 
                                        ['periodo' => "{$fecha_inicio} a {$fecha_fin}",
                                        'nuevas_inscripciones' => (int)$nuevas, 
                                        'documentacion_validada' => (int)$docVal, 
                                        'exámenes_realizados' => (int)$exReal, 
                                        'aprobados' => (int)$ap, 
                                        'reprobados' => (int)$rep, 
                                        'carnets_emitidos' => (int)$carn, 
                                        'detalles' => $detalles]];
        } catch (Exception $e) {
            $this->log('Error al generar reporte', 'ERROR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'reporte' => []
            ];
        }
    }
    //TODO
    //exportarDatos
}
