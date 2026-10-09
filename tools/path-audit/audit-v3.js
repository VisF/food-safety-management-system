/*
 * PATH AUDIT v3.0
 * Windows / Linux compatibility auditor
 *
 * IMPORTANTE:
 * - Este script es SOLO DE LECTURA.
 * - NO modifica ningún archivo del proyecto.
 * - Node.js se utiliza únicamente para ejecutar esta auditoría.
 */

'use strict';

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const REPORT_FILE = path.join(__dirname, 'audit-v3.json');

const EXCLUDED_DIRS = new Set([
  '.git',
  '.venv',
  'node_modules',
  'vendor',
  'target',
  'build',
  'dist',
  'coverage',
  '.idea',
  '.vscode',
  'path-audit'
]);

const TEXT_EXTENSIONS = new Set([
  '.php',
  '.phtml',
  '.inc',
  '.js',
  '.mjs',
  '.cjs',
  '.ts',
  '.tsx',
  '.jsx',
  '.html',
  '.htm',
  '.css',
  '.scss',
  '.sass',
  '.less',
  '.json',
  '.xml',
  '.yml',
  '.yaml',
  '.ini',
  '.env',
  '.sql',
  '.md',
  '.txt',
  '.conf',
  '.config',
  '.htaccess',
  '.sh',
  '.bat',
  '.cmd',
  '.ps1'
]);

const FS_APIS = [
  'file_exists',
  'is_file',
  'is_dir',
  'is_readable',
  'is_writable',
  'file_get_contents',
  'file_put_contents',
  'fopen',
  'mkdir',
  'rmdir',
  'unlink',
  'rename',
  'copy',
  'move_uploaded_file',
  'scandir',
  'glob',
  'opendir',
  'realpath',
  'readfile',
  'require',
  'require_once',
  'include',
  'include_once'
];

const findings = [];
const files = [];
const directories = [];

function relative(filePath) {
  return path.relative(PROJECT_ROOT, filePath) || '.';
}

function addFinding(
  severity,
  rule,
  file,
  line,
  evidence,
  message
) {
  findings.push({
    severity,
    rule,
    file: relative(file),
    line: line || null,
    evidence: evidence || null,
    message
  });
}

function shouldExclude(name) {
  return EXCLUDED_DIRS.has(name);
}

function isTextFile(file) {
  const base = path.basename(file).toLowerCase();

  if (base === '.htaccess') {
    return true;
  }

  return TEXT_EXTENSIONS.has(
    path.extname(file).toLowerCase()
  );
}

/*
 * Recorre todo el proyecto.
 * También busca colisiones de nombres por mayúsculas/minúsculas.
 */
function scanTree(dir) {
  let entries;

  try {
    entries = fs.readdirSync(dir, {
      withFileTypes: true
    });
  } catch (err) {
    addFinding(
      'HIGH',
      'UNREADABLE_DIRECTORY',
      dir,
      null,
      null,
      `No se pudo leer el directorio: ${err.message}`
    );

    return;
  }

  const caseMap = new Map();

  for (const entry of entries) {
    if (shouldExclude(entry.name)) {
      continue;
    }

    const fullPath = path.join(dir, entry.name);
    const lowerName = entry.name.toLowerCase();

    if (!caseMap.has(lowerName)) {
      caseMap.set(lowerName, []);
    }

    caseMap.get(lowerName).push(entry.name);

    if (entry.isDirectory()) {
      directories.push(fullPath);
      scanTree(fullPath);
    } else if (entry.isFile()) {
      files.push(fullPath);
    } else if (entry.isSymbolicLink()) {
      addFinding(
        'MEDIUM',
        'SYMLINK',
        fullPath,
        null,
        entry.name,
        'Enlace simbólico detectado. Verificar comportamiento equivalente en Windows/Linux.'
      );
    }
  }

  /*
   * Windows normalmente no diferencia mayúsculas/minúsculas.
   * Linux sí puede diferenciarlas.
   */
  for (const variants of caseMap.values()) {
    const unique = [...new Set(variants)];

    if (unique.length > 1) {
      addFinding(
        'HIGH',
        'CASE_COLLISION',
        dir,
        null,
        unique.join(' | '),
        `Nombres que difieren solamente por mayúsculas/minúsculas: ${unique.join(', ')}. Esto puede provocar problemas entre Windows y Linux.`
      );
    }
  }
}

/*
 * Revisa nombres de archivos/directorios.
 */
function scanFilename(file) {
  const relativePath = relative(file);
  const base = path.basename(file);

  /*
   * Caracteres no permitidos por Windows.
   */
  if (/[<>:"|?*]/.test(base)) {
    addFinding(
      'HIGH',
      'INVALID_WINDOWS_FILENAME',
      file,
      null,
      base,
      'El nombre contiene caracteres no permitidos por Windows.'
    );
  }

  /*
   * Windows no admite nombres terminados en espacio o punto.
   */
  if (/[ .]$/.test(base)) {
    addFinding(
      'HIGH',
      'TRAILING_SPACE_OR_DOT',
      file,
      null,
      base,
      'El nombre termina en espacio o punto, algo problemático/no válido en Windows.'
    );
  }

  /*
   * Nombres reservados de Windows.
   */
  const stem = path.parse(base).name.toUpperCase();

  const reserved = new Set([
    'CON',
    'PRN',
    'AUX',
    'NUL',

    'COM1',
    'COM2',
    'COM3',
    'COM4',
    'COM5',
    'COM6',
    'COM7',
    'COM8',
    'COM9',

    'LPT1',
    'LPT2',
    'LPT3',
    'LPT4',
    'LPT5',
    'LPT6',
    'LPT7',
    'LPT8',
    'LPT9'
  ]);

  if (reserved.has(stem)) {
    addFinding(
      'HIGH',
      'WINDOWS_RESERVED_FILENAME',
      file,
      null,
      base,
      'El nombre coincide con un nombre reservado de Windows.'
    );
  }

  /*
   * No significa necesariamente que sea un error.
   * Es solamente una advertencia.
   */
  if (relativePath.length >= 240) {
    addFinding(
      'MEDIUM',
      'LONG_PATH_ADVISORY',
      file,
      null,
      `${relativePath.length} caracteres`,
      'Ruta relativamente larga. Conviene verificar la configuración de long paths y las herramientas de despliegue.'
    );
  }
}

/*
 * Revisa el contenido de archivos de texto.
 */
function scanText(file) {
  let text;

  try {
    text = fs.readFileSync(file, 'utf8');
  } catch (err) {
    addFinding(
      'MEDIUM',
      'UNREADABLE_FILE',
      file,
      null,
      null,
      `No se pudo leer como texto: ${err.message}`
    );

    return;
  }

  const lines = text.split(/\r?\n/);

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimmed = line.trim();

    /*
     * Comentarios evidentes.
     */
    const isComment =
      trimmed.startsWith('//') ||
      trimmed.startsWith('#') ||
      trimmed.startsWith('*') ||
      trimmed.startsWith('/*') ||
      trimmed.startsWith('<!--') ||
      trimmed.startsWith('--');

    /*
     * ============================================================
     * 1. RUTAS ABSOLUTAS WINDOWS
     * ============================================================
     *
     * Ejemplos:
     *
     * C:\xampp\htdocs\...
     * C:/xampp/htdocs/...
     */
    const windowsAbsolute =
      /(?:[A-Za-z]:[\\/])[^"'<>|*?\r\n]*/g;

    for (const match of line.matchAll(windowsAbsolute)) {
      addFinding(
        'HIGH',
        'WINDOWS_ABSOLUTE_PATH',
        file,
        i + 1,
        match[0],
        'Ruta absoluta dependiente de Windows. Debe construirse de forma portable.'
      );
    }

    /*
     * ============================================================
     * 2. RUTAS ABSOLUTAS UNIX / LINUX
     * ============================================================
     *
     * Solamente buscamos ubicaciones que realmente parecen
     * filesystem. Así evitamos detectar rutas web como:
     *
     * /login
     * /usuarios
     * /api/users
     */
    if (
      !isComment &&
      !/https?:\/\//i.test(line)
    ) {
      const unixAbsolute =
        /(^|["'(=\s])\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)(?:[\/\\][^"'<>|*?\r\n]*)?/g;

      for (const match of line.matchAll(unixAbsolute)) {
        const value = match[0]
          .trim()
          .replace(/^[("'=\s]+/, '');

        addFinding(
          'HIGH',
          'UNIX_ABSOLUTE_PATH',
          file,
          i + 1,
          value,
          'Ruta absoluta Unix/Linux detectada. Debe evitarse como dependencia fija.'
        );
      }
    }

    /*
     * ============================================================
     * 3. APIs DE FILESYSTEM
     * ============================================================
     *
     * Buscamos llamadas reales como:
     *
     * file_exists(...)
     * fopen(...)
     * file_get_contents(...)
     * move_uploaded_file(...)
     */
    for (const api of FS_APIS) {
      const apiRegex = new RegExp(
        `\\b${api}\\s*\\(\\s*([^)]{0,500})\\)`,
        'g'
      );

      for (const match of line.matchAll(apiRegex)) {
        const args = match[1];

        /*
         * Las URLs web no nos interesan.
         */
        if (/https?:\/\//i.test(args)) {
          continue;
        }

        /*
         * Backslash dentro de un string usado como posible
         * separador de filesystem.
         */
        const quotedBackslashPath =
          /['"`][^'"`\\]*(?:\\\\|\\)[^'"`]*(?:\.[A-Za-z0-9]{1,8})?['"`]/;

        if (quotedBackslashPath.test(args)) {
          addFinding(
            'HIGH',
            'BACKSLASH_IN_FILESYSTEM_API',
            file,
            i + 1,
            `${api}(${args})`,
            `Uso de backslash dentro de un argumento de ${api}(). Revisar si representa un separador de ruta real.`
          );
        }

        /*
         * Ruta absoluta pasada directamente a la API.
         */
        if (
          /[A-Za-z]:[\\/]/.test(args) ||
          /['"`]\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)[\\/]/.test(args)
        ) {
          addFinding(
            'HIGH',
            'HARDCODED_FS_API_PATH',
            file,
            i + 1,
            `${api}(${args})`,
            'La API de filesystem recibe una ruta absoluta fija.'
          );
        }
      }
    }

    /*
     * ============================================================
     * 4. DIRECTORY_SEPARATOR
     * ============================================================
     *
     * Esto NO es un problema.
     * Lo reportamos como INFO para poder estudiar cómo se utiliza.
     */
    if (/\bDIRECTORY_SEPARATOR\b/.test(line)) {
      addFinding(
        'INFO',
        'DIRECTORY_SEPARATOR_USAGE',
        file,
        i + 1,
        line.trim(),
        'Uso de DIRECTORY_SEPARATOR detectado. Es portable; verificar solamente que no se mezcle con separadores hard-coded.'
      );
    }

    /*
     * ============================================================
     * 5. __DIR__, dirname(), realpath()
     * ============================================================
     *
     * Normalmente son buenas prácticas.
     *
     * Solo avisamos si además aparece un backslash.
     */
    if (
      /(?:__DIR__|dirname\s*\(|realpath\s*\()[^;\n]*\\/.test(line)
    ) {
      addFinding(
        'MEDIUM',
        'PORTABLE_BASE_WITH_BACKSLASH',
        file,
        i + 1,
        line.trim(),
        'Construcción basada en __DIR__/dirname()/realpath() que también contiene backslash. Revisar si el backslash es realmente un separador.'
      );
    }

    /*
     * ============================================================
     * 6. BACKSLASH EN CONTEXTOS QUE PARECEN FILESYSTEM
     * ============================================================
     */
    if (!isComment) {
      const likelyFilesystem =
        /(?:uploads?|storage|logs?|cache|files?|images?|documents?|backups?|temp|tmp|public|assets|config|database|db)[^"'`\r\n]{0,80}\\[^"'`\r\n]{1,100}/i;

      if (
        likelyFilesystem.test(line) &&
        !/\\\\[A-Za-znrt'"`]/.test(line)
      ) {
        addFinding(
          'MEDIUM',
          'LIKELY_WINDOWS_FS_SEPARATOR',
          file,
          i + 1,
          line.trim(),
          'Backslash detectado en un contexto que parece representar una ruta de filesystem.'
        );
      }
    }

    /*
     * ============================================================
     * 7. COMANDOS DEL SISTEMA
     * ============================================================
     *
     * Estas funciones pueden introducir dependencia del SO.
     */
    if (
      /\b(?:exec|shell_exec|system|passthru|proc_open|popen)\s*\(/.test(line)
    ) {
      if (
        /[A-Za-z]:\\|\/(?:usr|bin|opt|var|home)\//.test(line)
      ) {
        addFinding(
          'HIGH',
          'OS_SPECIFIC_COMMAND_PATH',
          file,
          i + 1,
          line.trim(),
          'Comando con ruta específica de sistema operativo.'
        );
      }
    }
  }
}

/*
 * ============================================================
 * MAIN
 * ============================================================
 */
function main() {
  console.log('='.repeat(72));
  console.log('PATH AUDIT v3.0 - WINDOWS / LINUX COMPATIBILITY');
  console.log('='.repeat(72));

  console.log(`Project root: ${PROJECT_ROOT}`);
  console.log('');

  /*
   * PRIMER PASO:
   * recorrer el proyecto.
   */
  scanTree(PROJECT_ROOT);

  /*
   * SEGUNDO PASO:
   * revisar nombres y contenido.
   */
  for (const file of files) {
    scanFilename(file);

    if (isTextFile(file)) {
      scanText(file);
    }
  }

  /*
   * Resumen.
   */
  const counts = {
    CRITICAL: findings.filter(
      f => f.severity === 'CRITICAL'
    ).length,

    HIGH: findings.filter(
      f => f.severity === 'HIGH'
    ).length,

    MEDIUM: findings.filter(
      f => f.severity === 'MEDIUM'
    ).length,

    LOW: findings.filter(
      f => f.severity === 'LOW'
    ).length,

    INFO: findings.filter(
      f => f.severity === 'INFO'
    ).length
  };

  /*
   * Generamos JSON.
   *
   * IMPORTANTE:
   * Esto solamente crea/actualiza el reporte dentro de
   * tools/path-audit.
   *
   * No modifica el código del proyecto.
   */
  const report = {
    version: '3.0',

    generatedAt: new Date().toISOString(),

    projectRoot: PROJECT_ROOT,

    readOnly: true,

    directoriesScanned: directories.length,

    filesScanned: files.length,

    textFilesScanned:
      files.filter(isTextFile).length,

    findings,

    summary: counts
  };

  fs.writeFileSync(
    REPORT_FILE,
    JSON.stringify(report, null, 2),
    'utf8'
  );

  /*
   * Mostrar resumen.
   */
  console.log(`Directories scanned : ${directories.length}`);
  console.log(`Files scanned       : ${files.length}`);
  console.log(
    `Text files scanned  : ${files.filter(isTextFile).length}`
  );

  console.log('');

  console.log('Findings:');

  console.log(`  CRITICAL : ${counts.CRITICAL}`);
  console.log(`  HIGH     : ${counts.HIGH}`);
  console.log(`  MEDIUM   : ${counts.MEDIUM}`);
  console.log(`  LOW      : ${counts.LOW}`);
  console.log(`  INFO     : ${counts.INFO}`);

  console.log('');

  /*
   * Mostrar únicamente problemas que merecen revisión.
   *
   * Los INFO no se muestran acá porque pueden ser
   * perfectamente correctos.
   */
  const actionable = findings.filter(
    f =>
      ['CRITICAL', 'HIGH', 'MEDIUM']
        .includes(f.severity)
  );

  if (actionable.length === 0) {
    console.log(
      'RESULT: No se detectaron problemas de portabilidad de alta confianza.'
    );
  } else {
    console.log(
      'RESULT: Hay hallazgos que requieren revisión.'
    );

    console.log('');

    for (const finding of actionable) {
      console.log(
        `[${finding.severity}] ${finding.rule}`
      );

      console.log(
        `  ${finding.file}${
          finding.line
            ? ':' + finding.line
            : ''
        }`
      );

      if (finding.evidence) {
        console.log(
          `  Evidence: ${finding.evidence}`
        );
      }

      console.log(
        `  ${finding.message}`
      );

      console.log('');
    }
  }

  console.log(
    `Report: ${REPORT_FILE}`
  );

  console.log('');

  console.log('AUDIT FINISHED.');
  console.log(
    'NO PROJECT FILES WERE MODIFIED.'
  );
}

main();