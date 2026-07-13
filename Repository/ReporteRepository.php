<?php

declare(strict_types=1);


/**
 * ReporteRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../db/Connection.php';

class ReporteRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    
    //obtenerReportePorFecha()

    //exportarDatos()
    
    //obtenerIndicadores()
    //obtenerKPIs()

    //obtenerCantidadCarnets
    //obtenerCantidadCursos
    //obtenerCantidadExamenes
}
