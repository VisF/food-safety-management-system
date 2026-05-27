<?php
class MigracionControlador {
    public function __construct() {}

    // Importa lote de registros provenientes de DIPA o legacy
    public function importarDipaBatch(string $filePath) {
        // TODO: leer CSV/SQL/XML y mapear
    }

    public function mapearRegistroLegacy(array $row): array {
        // TODO: mapear columnas legacy a entidades del sistema
        return [];
    }

    public function procesarLote(array $rows) {
        // TODO: procesar lote y registrar auditoría
    }
}
