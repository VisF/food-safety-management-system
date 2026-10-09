/*
 * PATH AUDIT v4.0
 * Windows / Linux compatibility auditor
 *
 * OBJETIVO:
 * Detectar problemas reales de rutas y nombres de archivos
 * sin confundirlos con:
 *
 * - URLs
 * - namespaces PHP
 * - expresiones regulares
 * - escapes
 * - rutas web
 * - código PHP portable
 *
 * IMPORTANTE:
 * - SOLO LECTURA.
 * - NO modifica archivos del proyecto.
 * - Node.js solo se utiliza para ejecutar esta auditoría.
 */

'use strict';

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const REPORT_FILE = path.join(__dirname, 'audit-v4.json');

/*
 * ============================================================
 * DIRECTORIOS EXCLUIDOS
 * ============================================================
 */

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

/*
 * ============================================================
 * EXTENSIONES DE TEXTO
 * ============================================================
 */

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

  '.sh',
  '.bat',
  '.cmd',
  '.ps1'
]);

/*
 * ============================================================
 * APIS DE FILESYSTEM
 * ============================================================
 *
 * Estas son las funciones que realmente nos interesan.
 */

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

/*
 * ============================================================
 * RESULTADOS
 * ============================================================
 */

const findings = [];

const safePatterns = [];

const files = [];

const directories = [];

/*
 * ============================================================
 * UTILIDADES
 * ============================================================
 */

function relative(filePath) {
  return path.relative(
    PROJECT_ROOT,
    filePath
  ) || '.';
}

function addFinding(
  classification,
  severity,
  rule,
  file,
  line,
  evidence,
  message
) {
  findings.push({
    classification,
    severity,
    rule,
    file: relative(file),
    line: line || null,
    evidence: evidence || null,
    message
  });
}

function addSafe(
  rule,
  file,
  line,
  evidence,
  message
) {
  safePatterns.push({
    classification: 'SAFE',
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
 * ============================================================
 * DETECCIÓN DE URL
 * ============================================================
 */

function isLikelyUrl(value) {
  return (
    /^(?:https?|ftp):\/\//i.test(value.trim()) ||
    /(?:https?|ftp):\/\//i.test(value)
  );
}

/*
 * ============================================================
 * DETECCIÓN DE NAMESPACE PHP
 * ============================================================
 *
 * Ejemplos:
 *
 * \PDO
 * \Exception
 * App\Config\Database
 *
 * NO son rutas de filesystem.
 */

function isPhpNamespace(value) {
  const trimmed = value.trim();

  if (
    /^\\[A-Za-z_][A-Za-z0-9_]*(?:\\[A-Za-z_][A-Za-z0-9_]*)*$/.test(
      trimmed
    )
  ) {
    return true;
  }

  if (
    /^[A-Za-z_][A-Za-z0-9_]*(?:\\[A-Za-z_][A-Za-z0-9_]*)+$/.test(
      trimmed
    )
  ) {
    return true;
  }

  return false;
}

/*
 * ============================================================
 * DETECCIÓN DE RUTA WINDOWS
 * ============================================================
 */

function isWindowsAbsolutePath(value) {
  return /^[A-Za-z]:[\\/]/.test(
    value.trim()
  );
}

/*
 * ============================================================
 * DETECCIÓN DE RUTA UNIX
 * ============================================================
 */

function isUnixAbsolutePath(value) {
  return /^\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)(?:\/|\\)/.test(
    value.trim()
  );
}

/*
 * ============================================================
 * DETECCIÓN DE RUTA WEB
 * ============================================================
 *
 * Ejemplos:
 *
 * /login
 * /usuarios
 * /api/users
 *
 * NO son necesariamente rutas de filesystem.
 */

function isLikelyWebPath(value) {
  const trimmed = value.trim();

  if (!trimmed.startsWith('/')) {
    return false;
  }

  if (
    isUnixAbsolutePath(trimmed)
  ) {
    return false;
  }

  return true;
}

/*
 * ============================================================
 * DETECCIÓN DE ESCAPE
 * ============================================================
 */

function isLikelyEscape(value) {
  return /\\[nrt"'$\\]/.test(value);
}

/*
 * ============================================================
 * DETECCIÓN DE BACKSLASH COMO SEPARADOR
 * ============================================================
 *
 * Buscamos cosas como:
 *
 * uploads\archivo.pdf
 * storage\images
 * C:\...
 *
 * Pero NO:
 *
 * \PDO
 * \Exception
 * \n
 * \"
 * \\
 */

function hasFilesystemBackslash(value) {
  const trimmed = value.trim();

  if (isPhpNamespace(trimmed)) {
    return false;
  }

  if (isLikelyEscape(trimmed)) {
    return false;
  }

  if (isWindowsAbsolutePath(trimmed)) {
    return true;
  }

  /*
   * Dos segmentos separados por backslash.
   */
  if (
    /[A-Za-z0-9_.-]+\\[A-Za-z0-9_.-]+/.test(
      trimmed
    )
  ) {
    return true;
  }

  return false;
}

/*
 * ============================================================
 * ÁRBOL DEL PROYECTO
 * ============================================================
 */

function scanTree(dir) {
  let entries;

  try {
    entries = fs.readdirSync(
      dir,
      {
        withFileTypes: true
      }
    );
  } catch (error) {
    addFinding(
      'REVIEW',
      'HIGH',
      'UNREADABLE_DIRECTORY',
      dir,
      null,
      null,
      `No se pudo leer el directorio: ${error.message}`
    );

    return;
  }

  /*
   * Mapa para detectar:
   *
   * Usuario.php
   * usuario.php
   *
   * En Linux son archivos distintos.
   * En Windows pueden entrar en conflicto.
   */

  const caseMap = new Map();

  for (const entry of entries) {
    if (shouldExclude(entry.name)) {
      continue;
    }

    const fullPath = path.join(
      dir,
      entry.name
    );

    const lowerName =
      entry.name.toLowerCase();

    if (!caseMap.has(lowerName)) {
      caseMap.set(
        lowerName,
        []
      );
    }

    caseMap
      .get(lowerName)
      .push(entry.name);

    if (entry.isDirectory()) {
      directories.push(fullPath);

      scanTree(fullPath);

    } else if (entry.isFile()) {
      files.push(fullPath);

    } else if (entry.isSymbolicLink()) {
      addFinding(
        'REVIEW',
        'MEDIUM',
        'SYMLINK',
        fullPath,
        null,
        entry.name,
        'Enlace simbólico detectado. Revisar su compatibilidad entre Windows y Linux.'
      );
    }
  }

  /*
   * Colisiones de mayúsculas/minúsculas.
   */

  for (const variants of caseMap.values()) {
    const unique = [
      ...new Set(variants)
    ];

    if (unique.length > 1) {
      addFinding(
        'REAL',
        'HIGH',
        'CASE_COLLISION',
        dir,
        null,
        unique.join(' | '),
        `Existen nombres que solamente difieren por mayúsculas/minúsculas: ${unique.join(', ')}.`
      );
    }
  }
}

/*
 * ============================================================
 * NOMBRES DE ARCHIVOS
 * ============================================================
 */

function scanFilename(file) {
  const base =
    path.basename(file);

  const relativePath =
    relative(file);

  /*
   * Caracteres inválidos Windows.
   */

  if (/[<>:"|?*]/.test(base)) {
    addFinding(
      'REAL',
      'HIGH',
      'INVALID_WINDOWS_FILENAME',
      file,
      null,
      base,
      'El nombre contiene caracteres no permitidos por Windows.'
    );
  }

  /*
   * Espacio o punto final.
   */

  if (/[ .]$/.test(base)) {
    addFinding(
      'REAL',
      'HIGH',
      'TRAILING_SPACE_OR_DOT',
      file,
      null,
      base,
      'El nombre termina en espacio o punto.'
    );
  }

  /*
   * Nombres reservados.
   */

  const stem =
    path.parse(base)
      .name
      .toUpperCase();

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
      'REAL',
      'HIGH',
      'WINDOWS_RESERVED_FILENAME',
      file,
      null,
      base,
      'El nombre coincide con un nombre reservado de Windows.'
    );
  }

  /*
   * Ruta larga.
   */

  if (relativePath.length >= 240) {
    addFinding(
      'REVIEW',
      'MEDIUM',
      'LONG_PATH_ADVISORY',
      file,
      null,
      `${relativePath.length} caracteres`,
      'Ruta relativamente larga. Revisar despliegue y configuración de long paths.'
    );
  }
}

/*
 * ============================================================
 * EXTRACCIÓN DE ARGUMENTOS DE FUNCIONES
 * ============================================================
 *
 * No pretende ser un parser PHP.
 * Es deliberadamente conservador.
 */

function extractFunctionArguments(
  line,
  functionName
) {
  const regex =
    new RegExp(
      `\\b${functionName}\\s*\\(`,
      'g'
    );

  const matches = [];

  for (
    const match of line.matchAll(regex)
  ) {
    const start =
      match.index +
      match[0].length;

    let depth = 1;

    let quote = null;

    let escaped = false;

    let end = start;

    for (
      let i = start;
      i < line.length;
      i++
    ) {
      const char = line[i];

      if (escaped) {
        escaped = false;
        continue;
      }

      if (
        char === '\\'
      ) {
        escaped = true;
        continue;
      }

      if (quote) {
        if (char === quote) {
          quote = null;
        }

        continue;
      }

      if (
        char === '"' ||
        char === "'" ||
        char === '`'
      ) {
        quote = char;
        continue;
      }

      if (char === '(') {
        depth++;
      }

      if (char === ')') {
        depth--;

        if (depth === 0) {
          end = i;

          break;
        }
      }
    }

    matches.push(
      line.slice(
        start,
        end
      )
    );
  }

  return matches;
}

/*
 * ============================================================
 * ANÁLISIS DE ARGUMENTO DE FILESYSTEM
 * ============================================================
 */

function analyzeFilesystemArgument(
  file,
  lineNumber,
  api,
  args
) {
  const trimmed =
    args.trim();

  /*
   * ------------------------------------------------------------
   * URL
   * ------------------------------------------------------------
   */

  if (
    isLikelyUrl(trimmed)
  ) {
    addSafe(
      'URL_ARGUMENT',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Argumento identificado como URL, no como ruta de filesystem.'
    );

    return;
  }

  /*
   * ------------------------------------------------------------
   * NAMESPACE PHP
   * ------------------------------------------------------------
   */

  if (
    isPhpNamespace(trimmed)
  ) {
    addSafe(
      'PHP_NAMESPACE',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Backslash identificado como namespace PHP, no como separador de filesystem.'
    );

    return;
  }

  /*
   * ------------------------------------------------------------
   * WINDOWS ABSOLUTE
   * ------------------------------------------------------------
   */

  if (
    isWindowsAbsolutePath(trimmed)
  ) {
    addFinding(
      'REAL',
      'HIGH',
      'WINDOWS_ABSOLUTE_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta absoluta Windows utilizada directamente por una API de filesystem.'
    );

    return;
  }

  /*
   * ------------------------------------------------------------
   * UNIX ABSOLUTE
   * ------------------------------------------------------------
   */

  if (
    isUnixAbsolutePath(trimmed)
  ) {
    addFinding(
      'REAL',
      'HIGH',
      'UNIX_ABSOLUTE_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta absoluta Unix/Linux utilizada directamente por una API de filesystem.'
    );

    return;
  }

  /*
   * ------------------------------------------------------------
   * BACKSLASH
   * ------------------------------------------------------------
   */

  if (
    hasFilesystemBackslash(trimmed)
  ) {
    addFinding(
      'REVIEW',
      'MEDIUM',
      'BACKSLASH_FILESYSTEM_ARGUMENT',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'El argumento parece contener un backslash utilizado como separador de filesystem. Revisar portabilidad.'
    );

    return;
  }

  /*
   * ------------------------------------------------------------
   * RUTA WEB
   * ------------------------------------------------------------
   */

  if (
    isLikelyWebPath(trimmed)
  ) {
    addSafe(
      'WEB_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'La ruta parece ser una ruta web relativa/absoluta, no una ruta física del sistema.'
    );

    return;
  }
}

/*
 * ============================================================
 * ANÁLISIS DE ARCHIVOS DE TEXTO
 * ============================================================
 */

function scanText(file) {
  let text;

  try {
    text =
      fs.readFileSync(
        file,
        'utf8'
      );
  } catch (error) {
    addFinding(
      'REVIEW',
      'MEDIUM',
      'UNREADABLE_TEXT_FILE',
      file,
      null,
      null,
      `No se pudo leer como texto: ${error.message}`
    );

    return;
  }

  const lines =
    text.split(/\r?\n/);

  for (
    let i = 0;
    i < lines.length;
    i++
  ) {
    const line =
      lines[i];

    const trimmed =
      line.trim();

    /*
     * ----------------------------------------------------------
     * URLs
     * ----------------------------------------------------------
     *
     * Si encontramos https:// no debemos tratar la barra
     * como filesystem.
     */

    const urlMatches =
      line.match(
        /(?:https?|ftp):\/\/[^\s"'<>]+/gi
      );

    if (urlMatches) {
      for (
        const url of urlMatches
      ) {
        addSafe(
          'URL',
          file,
          i + 1,
          url,
          'URL reconocida; no se considera ruta de filesystem.'
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * RUTAS WINDOWS ABSOLUTAS
     * ----------------------------------------------------------
     */

    const windowsMatches =
      line.match(
        /[A-Za-z]:[\\/][^"'<>|*?\r\n]*/g
      );

    if (windowsMatches) {
      for (
        const value of windowsMatches
      ) {
        /*
         * Evitar URLs raras.
         */

        if (
          isLikelyUrl(value)
        ) {
          continue;
        }

        addFinding(
          'REAL',
          'HIGH',
          'WINDOWS_ABSOLUTE_PATH',
          file,
          i + 1,
          value,
          'Ruta absoluta Windows detectada en el código.'
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * RUTAS UNIX ABSOLUTAS
     * ----------------------------------------------------------
     */

    const unixMatches =
      line.match(
        /(?:^|["'(=\s])\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)(?:\/|\\)[^"'<>|*?\r\n]*/g
      );

    if (unixMatches) {
      for (
        const raw of unixMatches
      ) {
        const value =
          raw
            .trim()
            .replace(
              /^[("'=\s]+/,
              ''
            );

        addFinding(
          'REAL',
          'HIGH',
          'UNIX_ABSOLUTE_PATH',
          file,
          i + 1,
          value,
          'Ruta absoluta Unix/Linux detectada.'
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * NAMESPACE PHP
     * ----------------------------------------------------------
     *
     * Si la línea parece utilizar namespaces PHP,
     * registramos el patrón como seguro.
     */

    if (
      /(?:^|\s)(?:use|new|extends|implements|instanceof)\s+\\?[A-Za-z_][A-Za-z0-9_\\]*/.test(
        line
      )
    ) {
      const namespaceMatches =
        line.match(
          /\\[A-Za-z_][A-Za-z0-9_\\]*/g
        );

      if (namespaceMatches) {
        for (
          const value of namespaceMatches
        ) {
          addSafe(
            'PHP_NAMESPACE',
            file,
            i + 1,
            value,
            'Backslash identificado dentro de un namespace PHP.'
          );
        }
      }
    }

    /*
     * ----------------------------------------------------------
     * ESCAPES PHP / JS
     * ----------------------------------------------------------
     *
     * No son rutas.
     */

    const escapeMatches =
      line.match(
        /\\(?:n|r|t|f|v|0|\\|"|')/g
      );

    if (escapeMatches) {
      for (
        const value of escapeMatches
      ) {
        addSafe(
          'ESCAPE_SEQUENCE',
          file,
          i + 1,
          value,
          'Secuencia de escape reconocida; no se considera separador de filesystem.'
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * APIs DE FILESYSTEM
     * ----------------------------------------------------------
     */

    for (
      const api of FS_APIS
    ) {
      const argumentsList =
        extractFunctionArguments(
          line,
          api
        );

      for (
        const args of argumentsList
      ) {
        analyzeFilesystemArgument(
          file,
          i + 1,
          api,
          args
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * DIRECTORY_SEPARATOR
     * ----------------------------------------------------------
     */

    if (
      /\bDIRECTORY_SEPARATOR\b/.test(
        line
      )
    ) {
      addSafe(
        'DIRECTORY_SEPARATOR',
        file,
        i + 1,
        trimmed,
        'Uso de DIRECTORY_SEPARATOR. Es portable entre Windows y Linux.'
      );
    }

    /*
     * ----------------------------------------------------------
     * __DIR__
     * ----------------------------------------------------------
     */

    if (
      /__DIR__/.test(
        line
      )
    ) {
      /*
       * Si además hay una ruta literal Windows,
       * ya fue detectada arriba.
       */

      if (
        !/[A-Za-z]:[\\/]/.test(
          line
        )
      ) {
        addSafe(
          'PHP_DIR',
          file,
          i + 1,
          trimmed,
          'Uso de __DIR__. La construcción de la ruta parte de la ubicación real del archivo.'
        );
      }
    }

    /*
     * ----------------------------------------------------------
     * dirname()
     * ----------------------------------------------------------
     */

    if (
      /\bdirname\s*\(/.test(
        line
      )
    ) {
      addSafe(
        'PHP_DIRNAME',
        file,
        i + 1,
        trimmed,
        'Uso de dirname(). No se considera una dependencia de Windows por sí mismo.'
      );
    }

    /*
     * ----------------------------------------------------------
     * realpath()
     * ----------------------------------------------------------
     */

    if (
      /\brealpath\s*\(/.test(
        line
      )
    ) {
      addSafe(
        'PHP_REALPATH',
        file,
        i + 1,
        trimmed,
        'Uso de realpath(). No se considera una dependencia de Windows por sí mismo.'
      );
    }

    /*
     * ----------------------------------------------------------
     * rtrim con /\
     * ----------------------------------------------------------
     *
     * Esto es normalmente manipulación de separadores,
     * no una ruta Windows.
     */

    if (
      /rtrim\s*\([^)]*['"`]\/\\\\['"`]/.test(
        line
      )
    ) {
      addSafe(
        'SEPARATOR_TRIM',
        file,
        i + 1,
        trimmed,
        'rtrim() elimina ambos tipos de separadores; no se considera un problema de portabilidad.'
      );
    }

    /*
     * ----------------------------------------------------------
     * COMANDOS DEL SISTEMA
     * ----------------------------------------------------------
     */

    if (
      /\b(?:exec|shell_exec|system|passthru|proc_open|popen)\s*\(/.test(
        line
      )
    ) {
      if (
        /[A-Za-z]:\\/.test(line) ||
        /\/(?:usr|bin|opt|var|home)\//.test(line)
      ) {
        addFinding(
          'REAL',
          'HIGH',
          'OS_SPECIFIC_COMMAND_PATH',
          file,
          i + 1,
          trimmed,
          'Comando del sistema con una ruta específica del sistema operativo.'
        );
      } else {
        addFinding(
          'REVIEW',
          'MEDIUM',
          'SYSTEM_COMMAND',
          file,
          i + 1,
          trimmed,
          'Se utiliza una función para ejecutar comandos del sistema. Revisar si el comando funciona en Windows y Linux.'
        );
      }
    }
  }
}

/*
 * ============================================================
 * RESUMEN
 * ============================================================
 */

function buildSummary() {
  const summary = {
    REAL: {
      total: 0,
      HIGH: 0,
      MEDIUM: 0,
      LOW: 0
    },

    REVIEW: {
      total: 0,
      HIGH: 0,
      MEDIUM: 0,
      LOW: 0
    },

    SAFE: {
      total: safePatterns.length
    }
  };

  for (
    const finding of findings
  ) {
    if (
      !summary[
        finding.classification
      ]
    ) {
      continue;
    }

    summary[
      finding.classification
    ].total++;

    if (
      summary[
        finding.classification
      ][finding.severity] !== undefined
    ) {
      summary[
        finding.classification
      ][finding.severity]++;
    }
  }

  return summary;
}

/*
 * ============================================================
 * MAIN
 * ============================================================
 */

function main() {
  console.log(
    '='.repeat(72)
  );

  console.log(
    'PATH AUDIT v4.0 - WINDOWS / LINUX COMPATIBILITY'
  );

  console.log(
    '='.repeat(72)
  );

  console.log(
    `Project root: ${PROJECT_ROOT}`
  );

  console.log('');

  /*
   * Árbol.
   */

  scanTree(
    PROJECT_ROOT
  );

  /*
   * Archivos.
   */

  for (
    const file of files
  ) {
    scanFilename(file);

    if (
      isTextFile(file)
    ) {
      scanText(file);
    }
  }

  /*
   * Resumen.
   */

  const summary =
    buildSummary();

  console.log(
    `Directories scanned : ${directories.length}`
  );

  console.log(
    `Files scanned       : ${files.length}`
  );

  console.log(
    `Text files scanned  : ${
      files.filter(isTextFile).length
    }`
  );

  console.log('');

  console.log(
    'CLASSIFICATION'
  );

  console.log(
    `  REAL     : ${summary.REAL.total}`
  );

  console.log(
    `  REVIEW   : ${summary.REVIEW.total}`
  );

  console.log(
    `  SAFE     : ${summary.SAFE.total}`
  );

  console.log('');

  /*
   * Mostrar REAL.
   */

  const realFindings =
    findings.filter(
      f =>
        f.classification ===
        'REAL'
    );

  console.log(
    '='.repeat(72)
  );

  console.log(
    'REAL FINDINGS'
  );

  console.log(
    '='.repeat(72)
  );

  if (
    realFindings.length === 0
  ) {
    console.log(
      'No se detectaron problemas reales de alta confianza.'
    );
  } else {
    for (
      const finding of realFindings
    ) {
      console.log('');

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

      if (
        finding.evidence
      ) {
        console.log(
          `  Evidence: ${finding.evidence}`
        );
      }

      console.log(
        `  ${finding.message}`
      );
    }
  }

  console.log('');

  /*
   * Mostrar REVIEW.
   */

  const reviewFindings =
    findings.filter(
      f =>
        f.classification ===
        'REVIEW'
    );

  console.log(
    '='.repeat(72)
  );

  console.log(
    'REVIEW FINDINGS'
  );

  console.log(
    '='.repeat(72)
  );

  if (
    reviewFindings.length === 0
  ) {
    console.log(
      'No hay elementos que requieran revisión manual.'
    );
  } else {
    for (
      const finding of reviewFindings
    ) {
      console.log('');

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

      if (
        finding.evidence
      ) {
        console.log(
          `  Evidence: ${finding.evidence}`
        );
      }

      console.log(
        `  ${finding.message}`
      );
    }
  }

  /*
   * Reporte JSON.
   */

  const report = {
    version: '4.0',

    generatedAt:
      new Date().toISOString(),

    projectRoot:
      PROJECT_ROOT,

    readOnly: true,

    directoriesScanned:
      directories.length,

    filesScanned:
      files.length,

    textFilesScanned:
      files.filter(isTextFile).length,

    summary,

    findings,

    safePatterns
  };

  fs.writeFileSync(
    REPORT_FILE,
    JSON.stringify(
      report,
      null,
      2
    ),
    'utf8'
  );

  console.log('');

  console.log(
    '='.repeat(72)
  );

  console.log(
    `Report: ${REPORT_FILE}`
  );

  console.log('');

  console.log(
    'AUDIT FINISHED.'
  );

  console.log(
    'NO PROJECT FILES WERE MODIFIED.'
  );
}

main();