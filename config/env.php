<?php
declare(strict_types=1);

/**
 * Cargador simple de variables desde archivo .env en el raíz del proyecto.
 * Uso: require_once __DIR__ . '/config/env.php';
 */

if (!function_exists('load_dotenv')) {
    function load_dotenv(string $path = __DIR__ . '/../.env'): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;

            // KEY=VALUE
            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $m)) continue;
            $name = $m[1];
            $value = $m[2];

            // remover comillas externas
            $first = $value[0] ?? '';
            $last = $value[strlen($value)-1] ?? '';
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }

            // sustituir variables de entorno existentes solo si no están seteadas
            if (getenv($name) === false) {
                putenv("{$name}={$value}");
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Cargar por defecto si existe .env en la raíz del proyecto
load_dotenv(__DIR__ . '/../.env');
