/*
 * PATH AUDIT v5.0
 * High-confidence Windows/Linux portability audit
 *
 * IMPORTANTE:
 * - SOLO LECTURA.
 * - NO modifica archivos del proyecto.
 * - Node.js solamente ejecuta esta auditoría.
 *
 * V5 prioriza PRECISIÓN sobre cantidad de resultados.
 *
 * No considera problemas:
 * - https://...
 * - http://...
 * - namespaces PHP
 * - \n, \r, \t, \\, \"
 * - expresiones regulares
 * - rutas web
 * - PDO::exec()
 * - RegExp.exec()
 * - __DIR__
 * - dirname()
 * - realpath()
 * - DIRECTORY_SEPARATOR
 *
 * Solo intenta marcar rutas físicas reales.
 */

'use strict';

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const REPORT_FILE = path.join(__dirname, 'audit-v5.json');

/*
 * ============================================================
 * CONFIGURACIÓN
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

/*
 * APIs que trabajan con filesystem.
 *
 * IMPORTANTE:
 * No incluimos PDO::exec ni RegExp.exec.
 */
const FILESYSTEM_APIS = [
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
 * Contextos que suelen indicar almacenamiento físico.
 *
 * Sirven solamente para aumentar la confianza.
 */
const FILESYSTEM_CONTEXTS = [
  'upload',
  'uploads',
  'storage',
  'file',
  'files',
  'document',
  'documents',
  'image',
  'images',
  'attachment',
  'attachments',
  'backup',
  'backups',
  'cache',
  'temp',
  'tmp',
  'log',
  'logs',
  'clamav',
  'database',
  'db',
  'public',
  'assets'
];

/*
 * ============================================================
 * RESULTADOS
 * ============================================================
 */

const findings = [];

const safe = [];

const files = [];

const directories = [];

/*
 * ============================================================
 * UTILIDADES
 * ============================================================
 */

function relative(file) {
  return path.relative(
    PROJECT_ROOT,
    file
  ) || '.';
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

function addSafe(
  rule,
  file,
  line,
  evidence,
  message
) {
  safe.push({
    rule,
    file: relative(file),
    line: line || null,
    evidence: evidence || null,
    message
  });
}

function isTextFile(file) {
  const base =
    path.basename(file).toLowerCase();

  if (base === '.htaccess') {
    return true;
  }

  return TEXT_EXTENSIONS.has(
    path.extname(file).toLowerCase()
  );
}

function shouldExclude(name) {
  return EXCLUDED_DIRS.has(name);
}

/*
 * ============================================================
 * RECONOCIMIENTO DE CONSTRUCCIONES SEGURAS
 * ============================================================
 */

function isUrl(value) {
  return /^(?:https?|ftp):\/\//i.test(
    value.trim()
  );
}

function isPhpNamespace(value) {
  const v = value.trim();

  return (
    /^\\[A-Za-z_][A-Za-z0-9_]*(?:\\[A-Za-z_][A-Za-z0-9_]*)*$/.test(v) ||
    /^[A-Za-z_][A-Za-z0-9_]*(?:\\[A-Za-z_][A-Za-z0-9_]*)+$/.test(v)
  );
}

function isEscape(value) {
  return /\\(?:n|r|t|f|v|0|\\|"|')/.test(
    value
  );
}

function isWindowsAbsolute(value) {
  return /^[A-Za-z]:[\\/]/.test(
    value.trim()
  );
}

function isUnixAbsolute(value) {
  return /^\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)(?:\/|\\)/.test(
    value.trim()
  );
}

function hasBackslashSeparator(value) {
  const v =
    value.trim();

  if (isPhpNamespace(v)) {
    return false;
  }

  if (isEscape(v)) {
    return false;
  }

  if (isWindowsAbsolute(v)) {
    return true;
  }

  /*
   * Ejemplo:
   *
   * uploads\archivo.pdf
   * storage\images
   *
   * Exigimos caracteres a ambos lados.
   */
  return /[A-Za-z0-9_.-]+\\[A-Za-z0-9_.-]+/.test(
    v
  );
}

function containsFilesystemContext(value) {
  const lower =
    value.toLowerCase();

  return FILESYSTEM_CONTEXTS.some(
    context =>
      lower.includes(context)
  );
}

/*
 * ============================================================
 * EXTRACCIÓN DE STRINGS
 * ============================================================
 *
 * Obtiene strings simples:
 *
 * 'algo'
 * "algo"
 *
 * No pretende reemplazar un parser PHP/JS.
 */

function extractQuotedStrings(line) {
  const results = [];

  const regex =
    /(['"])(.*?)\1/g;

  for (
    const match of line.matchAll(regex)
  ) {
    results.push({
      value: match[2],
      index: match.index
    });
  }

  return results;
}

/*
 * ============================================================
 * ÁRBOL DEL PROYECTO
 * ============================================================
 */

function scanTree(dir) {
  let entries;

  try {
    entries =
      fs.readdirSync(
        dir,
        {
          withFileTypes: true
        }
      );
  } catch (error) {
    addFinding(
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
   * Detectar colisiones de nombres.
   */

  const caseMap =
    new Map();

  for (
    const entry of entries
  ) {
    if (
      shouldExclude(
        entry.name
      )
    ) {
      continue;
    }

    const full =
      path.join(
        dir,
        entry.name
      );

    const lower =
      entry.name.toLowerCase();

    if (
      !caseMap.has(lower)
    ) {
      caseMap.set(
        lower,
        []
      );
    }

    caseMap
      .get(lower)
      .push(entry.name);

    if (
      entry.isDirectory()
    ) {
      directories.push(
        full
      );

      scanTree(full);

    } else if (
      entry.isFile()
    ) {
      files.push(
        full
      );

    } else if (
      entry.isSymbolicLink()
    ) {
      addFinding(
        'MEDIUM',
        'SYMLINK',
        full,
        null,
        entry.name,
        'Enlace simbólico detectado. Revisar compatibilidad entre Windows y Linux.'
      );
    }
  }

  /*
   * Diferencias solamente por mayúsculas.
   */

  for (
    const variants of caseMap.values()
  ) {
    const unique = [
      ...new Set(
        variants
      )
    ];

    if (
      unique.length > 1
    ) {
      addFinding(
        'HIGH',
        'CASE_COLLISION',
        dir,
        null,
        unique.join(' | '),
        'Existen nombres que difieren solamente por mayúsculas/minúsculas.'
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

  if (
    /[<>:"|?*]/.test(base)
  ) {
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
   * Punto o espacio final.
   */

  if (
    /[ .]$/.test(base)
  ) {
    addFinding(
      'HIGH',
      'TRAILING_SPACE_OR_DOT',
      file,
      null,
      base,
      'El nombre termina en espacio o punto.'
    );
  }

  /*
   * Reservados Windows.
   */

  const stem =
    path.parse(base)
      .name
      .toUpperCase();

  const reserved =
    new Set([
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

  if (
    reserved.has(stem)
  ) {
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
   * Rutas largas.
   */

  if (
    relativePath.length >= 240
  ) {
    addFinding(
      'MEDIUM',
      'LONG_PATH',
      file,
      null,
      `${relativePath.length} caracteres`,
      'Ruta larga que conviene revisar para despliegue.'
    );
  }
}

/*
 * ============================================================
 * EXTRAER ARGUMENTOS DE UNA FUNCIÓN
 * ============================================================
 */

function extractFunctionCalls(
  line,
  functionName
) {
  const results = [];

  const regex =
    new RegExp(
      `\\b${functionName}\\s*\\(`,
      'g'
    );

  for (
    const match of line.matchAll(regex)
  ) {
    let depth = 1;

    let quote = null;

    let escaped = false;

    const start =
      match.index +
      match[0].length;

    let end =
      line.length;

    for (
      let i = start;
      i < line.length;
      i++
    ) {
      const char =
        line[i];

      if (
        escaped
      ) {
        escaped = false;
        continue;
      }

      if (
        char === '\\'
      ) {
        escaped = true;
        continue;
      }

      if (
        quote
      ) {
        if (
          char === quote
        ) {
          quote = null;
        }

        continue;
      }

      if (
        char === "'" ||
        char === '"'
      ) {
        quote = char;
        continue;
      }

      if (
        char === '('
      ) {
        depth++;
      }

      if (
        char === ')'
      ) {
        depth--;

        if (
          depth === 0
        ) {
          end =
            i;

          break;
        }
      }
    }

    results.push(
      line.slice(
        start,
        end
      )
    );
  }

  return results;
}

/*
 * ============================================================
 * ANALIZAR LLAMADA FILESYSTEM
 * ============================================================
 */

function analyzeFilesystemCall(
  file,
  lineNumber,
  api,
  args
) {
  const trimmed =
    args.trim();

  /*
   * URL
   */

  if (
    isUrl(trimmed)
  ) {
    addSafe(
      'URL_IN_FILESYSTEM_CALL',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'La cadena parece una URL y no una ruta física.'
    );

    return;
  }

  /*
   * Namespace PHP.
   */

  if (
    isPhpNamespace(trimmed)
  ) {
    addSafe(
      'PHP_NAMESPACE',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'El backslash pertenece a un namespace PHP.'
    );

    return;
  }

  /*
   * Ruta Windows absoluta.
   */

  if (
    isWindowsAbsolute(
      trimmed
    )
  ) {
    addFinding(
      'HIGH',
      'WINDOWS_ABSOLUTE_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta absoluta Windows pasada a una función de filesystem.'
    );

    return;
  }

  /*
   * Ruta Linux absoluta.
   */

  if (
    isUnixAbsolute(
      trimmed
    )
  ) {
    addFinding(
      'HIGH',
      'UNIX_ABSOLUTE_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta absoluta Unix/Linux pasada a una función de filesystem.'
    );

    return;
  }

  /*
   * Backslash.
   */

  if (
    hasBackslashSeparator(
      trimmed
    )
  ) {
    addFinding(
      'MEDIUM',
      'BACKSLASH_FILESYSTEM_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Posible separador de filesystem Windows utilizado en una llamada de filesystem.'
    );

    return;
  }

  /*
   * Si la llamada utiliza __DIR__, dirname(),
   * realpath() o DIRECTORY_SEPARATOR,
   * la consideramos portable.
   */

  if (
    /__DIR__|dirname\s*\(|realpath\s*\(|DIRECTORY_SEPARATOR/.test(
      trimmed
    )
  ) {
    addSafe(
      'PORTABLE_PATH_CONSTRUCTION',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta construida mediante mecanismos portables de PHP.'
    );

    return;
  }

  /*
   * No marcar cualquier string como problema.
   *
   * Solamente hacemos revisión si el contexto parece
   * relacionado con filesystem.
   */

  if (
    containsFilesystemContext(
      trimmed
    )
  ) {
    addSafe(
      'FILESYSTEM_RELATIVE_PATH',
      file,
      lineNumber,
      `${api}(${trimmed})`,
      'Ruta relativa asociada a un contexto de filesystem; no parece depender de Windows.'
    );
  }
}

/*
 * ============================================================
 * ANALIZAR CONTENIDO
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
      'MEDIUM',
      'UNREADABLE_FILE',
      file,
      null,
      null,
      `No se pudo leer el archivo: ${error.message}`
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
     * ========================================================
     * URLS
     * ========================================================
     */

    const urls =
      line.match(
        /(?:https?|ftp):\/\/[^\s"'<>]+/gi
      );

    if (
      urls
    ) {
      for (
        const url of urls
      ) {
        addSafe(
          'URL',
          file,
          i + 1,
          url,
          'URL reconocida. No es una ruta de filesystem.'
        );
      }
    }

    /*
     * ========================================================
     * RUTAS WINDOWS ABSOLUTAS
     * ========================================================
     *
     * Primero eliminamos las URLs de la evaluación.
     */

    const lineWithoutUrls =
      line.replace(
        /(?:https?|ftp):\/\/[^\s"'<>]+/gi,
        ''
      );

    const windowsPaths =
      lineWithoutUrls.match(
        /(?:^|["'(=\s])([A-Za-z]:[\\/][^"'<>|*?\r\n]*)/g
      );

    if (
      windowsPaths
    ) {
      for (
        const raw of windowsPaths
      ) {
        const value =
          raw
            .trim()
            .replace(
              /^[("'=\s]+/,
              ''
            );

        if (
          isWindowsAbsolute(
            value
          )
        ) {
          addFinding(
            'HIGH',
            'WINDOWS_ABSOLUTE_PATH',
            file,
            i + 1,
            value,
            'Ruta absoluta Windows detectada.'
          );
        }
      }
    }

    /*
     * ========================================================
     * RUTAS UNIX ABSOLUTAS
     * ========================================================
     */

    const unixPaths =
      line.match(
        /(?:^|["'(=\s])\/(?:var|home|opt|srv|mnt|media|tmp|usr|etc|www|xampp)(?:\/|\\)[^"'<>|*?\r\n]*/g
      );

    if (
      unixPaths
    ) {
      for (
        const raw of unixPaths
      ) {
        const value =
          raw
            .trim()
            .replace(
              /^[("'=\s]+/,
              ''
            );

        /*
         * No analizar si está dentro de una URL.
         */

        if (
          /https?:\/\//i.test(
            raw
          )
        ) {
          continue;
        }

        if (
          isUnixAbsolute(
            value
          )
        ) {
          addFinding(
            'HIGH',
            'UNIX_ABSOLUTE_PATH',
            file,
            i + 1,
            value,
            'Ruta absoluta Unix/Linux detectada.'
          );
        }
      }
    }

    /*
     * ========================================================
     * LLAMADAS FILESYSTEM
     * ========================================================
     */

    for (
      const api of FILESYSTEM_APIS
    ) {
      const calls =
        extractFunctionCalls(
          line,
          api
        );

      for (
        const args of calls
      ) {
        analyzeFilesystemCall(
          file,
          i + 1,
          api,
          args
        );
      }
    }

    /*
     * ========================================================
     * DIRECTORY_SEPARATOR
     * ========================================================
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
        'DIRECTORY_SEPARATOR es portable entre Windows y Linux.'
      );
    }

    /*
     * ========================================================
     * __DIR__
     * ========================================================
     */

    if (
      /\b__DIR__\b/.test(
        line
      )
    ) {
      addSafe(
        'PHP_DIR',
        file,
        i + 1,
        trimmed,
        '__DIR__ obtiene la ubicación real del archivo y es portable.'
      );
    }

    /*
     * ========================================================
     * dirname()
     * ========================================================
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
        'dirname() no introduce por sí mismo una dependencia de Windows.'
      );
    }

    /*
     * ========================================================
     * realpath()
     * ========================================================
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
        'realpath() trabaja con rutas del sistema y no fija un separador específico.'
      );
    }

    /*
     * ========================================================
     * SEPARADORES
     * ========================================================
     *
     * Ejemplo:
     *
     * rtrim($path, '/\\')
     *
     * Esto es normal.
     */

    if (
      /rtrim\s*\([^)]*['"`]\/\\\\['"`]/.test(
        line
      )
    ) {
      addSafe(
        'SEPARATOR_NORMALIZATION',
        file,
        i + 1,
        trimmed,
        'Normalización de separadores; no representa una ruta Windows fija.'
      );
    }

    /*
     * ========================================================
     * COMANDOS DEL SISTEMA
     * ========================================================
     *
     * Solo analizamos funciones reales de ejecución.
     *
     * NO:
     * PDO::exec()
     * RegExp.exec()
     */

    const systemCommand =
      /(?:^|[^\w:])(?:exec|shell_exec|system|passthru|proc_open|popen)\s*\(/;

    if (
      systemCommand.test(
        line
      )
    ) {
      /*
       * Si encontramos una ruta explícita,
       * es un problema real.
       */

      if (
        /[A-Za-z]:[\\/]/.test(
          line
        ) ||
        /\/(?:usr|bin|opt|var|home)\//.test(
          line
        )
      ) {
        addFinding(
          'HIGH',
          'OS_SPECIFIC_COMMAND',
          file,
          i + 1,
          trimmed,
          'Comando del sistema con una ruta específica de Windows o Linux.'
        );
      } else {
        addFinding(
          'MEDIUM',
          'SYSTEM_COMMAND',
          file,
          i + 1,
          trimmed,
          'Uso de un comando del sistema. Revisar si el comando es portable entre Windows y Linux.'
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
  console.log(
    '='.repeat(72)
  );

  console.log(
    'PATH AUDIT v5.0 - HIGH CONFIDENCE'
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
   * ==========================================================
   * RESUMEN
   * ==========================================================
   */

  const high =
    findings.filter(
      f =>
        f.severity === 'HIGH'
    );

  const medium =
    findings.filter(
      f =>
        f.severity === 'MEDIUM'
    );

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
    'RESULTS'
  );

  console.log(
    `  HIGH    : ${high.length}`
  );

  console.log(
    `  MEDIUM  : ${medium.length}`
  );

  console.log(
    `  SAFE    : ${safe.length}`
  );

  console.log('');

  /*
   * ==========================================================
   * HIGH
   * ==========================================================
   */

  console.log(
    '='.repeat(72)
  );

  console.log(
    'HIGH CONFIDENCE FINDINGS'
  );

  console.log(
    '='.repeat(72)
  );

  if (
    high.length === 0
  ) {
    console.log(
      'NINGUNO.'
    );
  } else {
    for (
      const finding of high
    ) {
      console.log('');

      console.log(
        `[HIGH] ${finding.rule}`
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
   * ==========================================================
   * MEDIUM
   * ==========================================================
   */

  console.log('');

  console.log(
    '='.repeat(72)
  );

  console.log(
    'REVIEW'
  );

  console.log(
    '='.repeat(72)
  );

  if (
    medium.length === 0
  ) {
    console.log(
      'NINGUNO.'
    );
  } else {
    for (
      const finding of medium
    ) {
      console.log('');

      console.log(
        `[MEDIUM] ${finding.rule}`
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
   * ==========================================================
   * REPORTE JSON
   * ==========================================================
   */

  const report = {
    version: '5.0',

    generatedAt:
      new Date().toISOString(),

    projectRoot:
      PROJECT_ROOT,

    readOnly:
      true,

    directoriesScanned:
      directories.length,

    filesScanned:
      files.length,

    textFilesScanned:
      files.filter(
        isTextFile
      ).length,

    summary: {
      high: high.length,
      medium: medium.length,
      safe: safe.length
    },

    findings,

    safe
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