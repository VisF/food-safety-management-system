<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

class ReporteRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    
    obtenerReportePorFecha()

    exportarDatos()
    
    //obtenerIndicadores()
    //obtenerKPIs()

    //obtenerCantidadCarnets
    //obtenerCantidadCursos
    //obtenerCantidadExamenes
}
