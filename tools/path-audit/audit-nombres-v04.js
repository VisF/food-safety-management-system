
'use strict';

/**
 * AUDITORIA DE NOMBRES Y REFERENCIAS V0.4
 *
 * Modo de solo lectura sobre el codigo del proyecto.
 *
 * Mejoras:
 * - Separa inclusiones PHP, recursos HTML, referencias CSS e imports JS.
 * - No busca referencias de recursos en SQL ni en strings arbitrarios.
 * - Distingue rutas locales, URL relativas y URL desde la raiz web.
 * - Reconoce rutas dinamicas y dependencias excluidas.
 * - No considera que una URL desde "/" sea relativa a la raiz del proyecto.
 * - Genera un informe JSON independiente.
 *
 * No ejecuta PHP ni JavaScript.
 * No modifica archivos de la aplicacion.
 */

const fs = require('node:fs');
const path = require('node:path');

const VERSION = '0.4.0';
const ROOT = path.resolve(__dirname, '../..');

const OUTPUT = path.join(
    __dirname,
    'auditoria-nombres-v04-resultados.json'
);

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

const EXCLUDED_FILES = new Set([
    'auditoria-nombres-v04-resultados.json'
]);

const TEXT_EXTENSIONS = new Set([
    '.php', '.phtml', '.html', '.htm',
    '.css', '.js', '.mjs', '.cjs',
    '.json', '.xml', '.svg', '.md', '.txt'
]);

const RESOURCE_EXTENSIONS = new Set([
    '.php', '.phtml', '.html', '.htm',
    '.css', '.js', '.mjs', '.cjs',
    '.json', '.xml', '.svg',
    '.png', '.jpg', '.jpeg', '.gif', '.webp', '.ico',
    '.woff', '.woff2', '.ttf', '.otf', '.eot',
    '.pdf', '.mp4', '.webm', '.mp3', '.wav'
]);

const MAX_FILE_SIZE = 2 * 1024 * 1024;

const report = {
    version: VERSION,
    fecha: new Date().toISOString(),
    raizProyecto: ROOT,
    resumen: {},
    colisiones: [],
    referenciasPHP: [],
    referenciasWeb: [],
    referenciasCSS: [],
    referenciasJavaScript: [],
    referenciasPosiblementeRotas: [],
    diferenciasMayusculas: [],
    referenciasDinamicas: [],
    referenciasNoVerificables: [],
    referenciasAExclusiones: [],
    nombresSospechosos: [],
    erroresLectura: [],
    archivosGrandesOmitidos: [],
    notas: [
        'Auditoria de solo lectura.',
        'No se ejecuta el codigo de la aplicacion.',
        'Las rutas de navegador no siempre coinciden con rutas fisicas.',
        'Las URL desde la raiz web requieren conocer el document root.',
        'Las referencias dinamicas no se consideran errores por defecto.',
        'Los archivos SQL no se analizan como referencias de recursos.',
        'Las carpetas excluidas no se recorren.'
    ]
};

const allFiles = [];
const fileIndex = new Map();
const sourceFiles = [];

let directoriesScanned = 0;
let filesScanned = 0;
let textFilesScanned = 0;

function slash(value) {
    return value.replace(/\\/g, '/');
}

function relative(file) {
    return slash(path.relative(ROOT, file));
}

function key(value) {
    return slash(value).toLocaleLowerCase('en-US');
}

function extension(file) {
    return path.extname(file).toLowerCase();
}

function isText(file) {
    return TEXT_EXTENSIONS.has(extension(file));
}

function walk(dir) {
    let entries;

    try {
        entries = fs.readdirSync(dir, { withFileTypes: true });
    } catch (error) {
        report.erroresLectura.push({
            ruta: relative(dir),
            error: error.message
        });
        return;
    }

    directoriesScanned++;

    for (const entry of entries) {
        const full = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            if (!EXCLUDED_DIRS.has(entry.name)) {
                walk(full);
            }
            continue;
        }

        if (!entry.isFile() || EXCLUDED_FILES.has(entry.name)) {
            continue;
        }

        filesScanned++;
        allFiles.push(full);

        const rel = relative(full);
        const fileKey = key(rel);

        if (!fileIndex.has(fileKey)) {
            fileIndex.set(fileKey, []);
        }
        fileIndex.get(fileKey).push(rel);

        if (!isText(full)) {
            continue;
        }

        let stat;

        try {
            stat = fs.statSync(full);
        } catch (error) {
            report.erroresLectura.push({
                ruta: rel,
                error: error.message
            });
            continue;
        }

        if (stat.size > MAX_FILE_SIZE) {
            report.archivosGrandesOmitidos.push({
                ruta: rel,
                bytes: stat.size
            });
            continue;
        }

        try {
            const content = fs.readFileSync(full, 'utf8');
            textFilesScanned++;

            sourceFiles.push({
                full,
                rel,
                ext: extension(full),
                content
            });
        } catch (error) {
            report.erroresLectura.push({
                ruta: rel,
                error: error.message
            });
        }
    }
}

function detectCollisions() {
    for (const [normalized, paths] of fileIndex) {
        const unique = [...new Set(paths)];

        if (unique.length > 1) {
            report.colisiones.push({
                clave: normalized,
                rutas: unique
            });
        }
    }
}

function detectSuspiciousNames() {
    for (const full of allFiles) {
        for (const segment of relative(full).split('/')) {
            const motivos = [];

            if (segment !== segment.trim()) {
                motivos.push('ESPACIOS_INICIALES_O_FINALES');
            }

            if (/[<>:"|?*]/.test(segment)) {
                motivos.push('CARACTER_NO_PORTABLE_EN_WINDOWS');
            }

            if (/[. ]$/.test(segment)) {
                motivos.push('TERMINA_EN_PUNTO_O_ESPACIO');
            }

            if (/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?$/i.test(segment)) {
                motivos.push('NOMBRE_RESERVADO_WINDOWS');
            }

            if (motivos.length) {
                report.nombresSospechosos.push({
                    ruta: relative(full),
                    segmento: segment,
                    motivos
                });
            }
        }
    }
}

function lineAt(content, index) {
    return content.slice(0, index).split('\n').length;
}

function addCandidate(list, file, raw, line, type, extra = {}) {
    if (!raw || !raw.trim()) return;

    list.push({
        archivo: file.rel,
        linea: line,
        tipo: type,
        referencia: raw.trim(),
        ...extra
    });
}

function isExternal(value) {
    return (
        /^[a-z][a-z0-9+.-]*:/i.test(value) ||
        value.startsWith('//') ||
        value.startsWith('#') ||
        value.startsWith('data:')
    );
}

function hasDynamicSyntax(value) {
    return (
        /<\?=?|<\?php|\?>|\$\{|\{\{|\}\}/i.test(value) ||
        /\$[A-Za-z_][A-Za-z0-9_]*/.test(value)
    );
}

function cleanUrl(value) {
    return value.trim().split(/[?#]/, 1)[0];
}

function resourceLike(value) {
    const cleaned = cleanUrl(value);
    return RESOURCE_EXTENSIONS.has(path.posix.extname(cleaned).toLowerCase());
}

function excludedTarget(value) {
    const parts = slash(value).split('/').filter(Boolean);

    return parts.find(part => EXCLUDED_DIRS.has(part)) || null;
}

/**
 * Busca referencias PHP en expresiones include/require.
 * Admite formas como:
 *   require 'archivo.php';
 *   require_once __DIR__ . '/../Servicios/Servicio.php';
 *
 * Las expresiones complejas quedan marcadas para revisión.
 */
function scanPhp(file) {
    const content = file.content;

    const regex =
        /\b(include|include_once|require|require_once)\s*(?:\(\s*)?([^;\n]+?)(?:\s*\))?\s*;/gi;

    let match;

    while ((match = regex.exec(content)) !== null) {
        const expression = match[2];
        const line = lineAt(content, match.index);

        const literals = [...expression.matchAll(/(['"])(.*?)\1/g)];

        if (!literals.length) {
            addCandidate(
                report.referenciasDinamicas,
                file,
                expression,
                line,
                'PHP_INCLUDE_EXPRESSION',
                { estado: 'EXPRESION_SIN_RUTA_LITERAL' }
            );
            continue;
        }

        const isDirRelative = /__DIR__|__FILE__|dirname\s*\(/i.test(expression);

        for (const literal of literals) {
            const value = literal[2];

            if (!resourceLike(value)) continue;

            addCandidate(
                report.referenciasPHP,
                file,
                value,
                line,
                match[1].toUpperCase(),
                {
                    base: isDirRelative
                        ? 'PHP_SOURCE_DIRECTORY'
                        : 'PHP_INCLUDE_CONTEXT',
                    expresion: expression.trim()
                }
            );
        }

        if (
            hasDynamicSyntax(expression) ||
            /\b(?:getenv|realpath|dirname)\s*\(/i.test(expression)
        ) {
            addCandidate(
                report.referenciasDinamicas,
                file,
                expression,
                line,
                'PHP_INCLUDE_EXPRESSION',
                { estado: 'EXPRESION_PHP_DINAMICA' }
            );
        }
    }
}

/**
 * Analiza atributos de recursos en HTML/PHP.
 * No supone que una URL relativa del navegador sea relativa
 * al directorio fisico del archivo de vista.
 */
function scanHtml(file) {
    const content = file.content;

    const attrRegex =
        /\b(src|href|action|poster|data)\s*=\s*(["'])(.*?)\2/gi;

    let match;

    while ((match = attrRegex.exec(content)) !== null) {
        const attr = match[1].toLowerCase();
        const value = match[3].trim();
        const line = lineAt(content, match.index);

        if (!value || isExternal(value)) continue;

        if (hasDynamicSyntax(value)) {
            const suffixMatch = value.match(
                /([A-Za-z0-9_@.-]+(?:\/[A-Za-z0-9_@.-]+)*\.[A-Za-z0-9]+)(?:[?#].*)?$/
            );

            addCandidate(
                report.referenciasDinamicas,
                file,
                value,
                line,
                'HTML_' + attr.toUpperCase(),
                {
                    sufijoEstatico: suffixMatch ? suffixMatch[1] : null,
                    estado: 'ATRIBUTO_CON_EXPRESION'
                }
            );
            continue;
        }

        if (!resourceLike(value)) continue;

        const item = {
            archivo: file.rel,
            linea: line,
            tipo: 'HTML_' + attr.toUpperCase(),
            referencia: value
        };

        report.referenciasWeb.push(item);

        if (value.startsWith('/')) {
            report.referenciasNoVerificables.push({
                ...item,
                estado: 'URL_DESDE_RAIZ_WEB',
                motivo: 'Requiere conocer la raiz publica y el prefijo de despliegue.'
            });
        } else {
            report.referenciasNoVerificables.push({
                ...item,
                estado: 'URL_RELATIVA_DEL_NAVEGADOR',
                motivo: 'La URL depende de la URL solicitada y de posibles etiquetas base.'
            });
        }
    }

    const srcsetRegex = /\bsrcset\s*=\s*(["'])(.*?)\1/gi;

    while ((match = srcsetRegex.exec(content)) !== null) {
        const line = lineAt(content, match.index);

        for (const part of match[2].split(',')) {
            const value = part.trim().split(/\s+/)[0];

            if (!value || isExternal(value) || !resourceLike(value)) {
                continue;
            }

            report.referenciasWeb.push({
                archivo: file.rel,
                linea: line,
                tipo: 'HTML_SRCSET',
                referencia: value
            });

            report.referenciasNoVerificables.push({
                archivo: file.rel,
                linea: line,
                tipo: 'HTML_SRCSET',
                referencia: value,
                estado: value.startsWith('/')
                    ? 'URL_DESDE_RAIZ_WEB'
                    : 'URL_RELATIVA_DEL_NAVEGADOR',
                motivo: 'No se puede resolver con certeza sin conocer el contexto web.'
            });
        }
    }
}

/**
 * Analiza url(...) y @import en CSS.
 * Las URL relativas CSS se resuelven respecto del archivo CSS.
 */
function scanCss(file) {
    const content = file.content;
    const matches = [];

    const patterns = [
        {
            regex: /url\(\s*(['"]?)(.*?)\1\s*\)/gi,
            type: 'CSS_URL'
        },
        {
            regex: /@import\s+(?:url\(\s*)?(['"])(.*?)\1/gi,
            type: 'CSS_IMPORT'
        }
    ];

    for (const pattern of patterns) {
        let match;

        while ((match = pattern.regex.exec(content)) !== null) {
            matches.push({
                value: match[2].trim(),
                line: lineAt(content, match.index),
                type: pattern.type
            });
        }
    }

    for (const item of matches) {
        const value = item.value;

        if (!value || isExternal(value)) continue;

        if (hasDynamicSyntax(value)) {
            addCandidate(
                report.referenciasDinamicas,
                file,
                value,
                item.line,
                item.type,
                { estado: 'CSS_DINAMICO' }
            );
            continue;
        }

        if (!resourceLike(value)) continue;

        report.referenciasCSS.push({
            archivo: file.rel,
            linea: item.line,
            tipo: item.type,
            referencia: value
        });

        if (value.startsWith('/')) {
            report.referenciasNoVerificables.push({
                archivo: file.rel,
                linea: item.line,
                tipo: item.type,
                referencia: value,
                estado: 'URL_DESDE_RAIZ_WEB',
                motivo: 'La raiz publica puede no coincidir con la raiz del proyecto.'
            });
            continue;
        }

        const excluded = excludedTarget(value);

        if (excluded) {
            report.referenciasAExclusiones.push({
                archivo: file.rel,
                linea: item.line,
                tipo: item.type,
                referencia: value,
                componente: excluded
            });
            continue;
        }

        inspectLocalPath(
            file,
            value,
            item.line,
            item.type,
            path.dirname(file.full)
        );
    }
}

/**
 * Analiza imports/require literales en JavaScript.
 * No considera strings arbitrarios como rutas de archivos.
 */
function scanJavaScript(file) {
    const content = file.content;

    const patterns = [
        {
            regex: /\bimport\s+(?:[^'";]*?\s+from\s+)?["']([^"']+)["']/g,
            type: 'JS_IMPORT'
        },
        {
            regex: /\bimport\s*\(\s*["']([^"']+)["']\s*\)/g,
            type: 'JS_DYNAMIC_IMPORT_LITERAL'
        },
        {
            regex: /\brequire\s*\(\s*["']([^"']+)["']\s*\)/g,
            type: 'JS_REQUIRE'
        }
    ];

    for (const pattern of patterns) {
        let match;

        while ((match = pattern.regex.exec(content)) !== null) {
            const value = match[1].trim();
            const line = lineAt(content, match.index);

            if (!value || isExternal(value)) continue;

            if (hasDynamicSyntax(value)) {
                addCandidate(
                    report.referenciasDinamicas,
                    file,
                    value,
                    line,
                    pattern.type,
                    { estado: 'IMPORT_DINAMICO' }
                );
                continue;
            }

            if (
                !value.startsWith('.') &&
                !value.startsWith('/') &&
                !value.includes('\\')
            ) {
                // Paquete o alias: no se asume que sea un archivo local.
                report.referenciasNoVerificables.push({
                    archivo: file.rel,
                    linea: line,
                    tipo: pattern.type,
                    referencia: value,
                    estado: 'PAQUETE_O_ALIAS',
                    motivo: 'Puede ser un paquete, alias o modulo configurado.'
                });
                continue;
            }

            if (!resourceLike(value)) continue;

            report.referenciasJavaScript.push({
                archivo: file.rel,
                linea: line,
                tipo: pattern.type,
                referencia: value
            });

            const excluded = excludedTarget(value);

            if (excluded) {
                report.referenciasAExclusiones.push({
                    archivo: file.rel,
                    linea: line,
                    tipo: pattern.type,
                    referencia: value,
                    componente: excluded
                });
                continue;
            }

            if (value.startsWith('/')) {
                report.referenciasNoVerificables.push({
                    archivo: file.rel,
                    linea: line,
                    tipo: pattern.type,
                    referencia: value,
                    estado: 'RUTA_ABSOLUTA_O_ALIAS',
                    motivo: 'No se puede asumir que corresponda a una ruta fisica del proyecto.'
                });
                continue;
            }

            inspectLocalPath(
                file,
                value,
                line,
                pattern.type,
                path.dirname(file.full)
            );
        }
    }
}

/**
 * Comprueba rutas de archivos relativas al archivo fuente.
 * Solo se usa en contextos donde la base fisica tiene sentido:
 * inclusiones PHP y referencias CSS/imports JS relativos.
 */
function inspectLocalPath(file, rawPath, line, type, baseDir) {
    const cleaned = cleanUrl(slash(rawPath));

    if (!cleaned) return;

    if (cleaned.startsWith('/')) {
        report.referenciasNoVerificables.push({
            archivo: file.rel,
            linea: line,
            tipo: type,
            referencia: rawPath,
            estado: 'RUTA_ABSOLUTA',
            motivo: 'No se asume que la raiz del proyecto sea la raiz del sistema o del servidor.'
        });
        return;
    }

    const excluded = excludedTarget(cleaned);

    if (excluded) {
        report.referenciasAExclusiones.push({
            archivo: file.rel,
            linea: line,
            tipo,
            referencia: rawPath,
            componente: excluded
        });
        return;
    }

    const absolute = path.resolve(baseDir, cleaned);
    const rel = path.relative(ROOT, absolute);

    if (
        rel === '..' ||
        rel.startsWith('..' + path.sep) ||
        path.isAbsolute(rel)
    ) {
        report.referenciasNoVerificables.push({
            archivo: file.rel,
            linea: line,
            tipo,
            referencia: rawPath,
            estado: 'FUERA_DE_LA_RAIZ_DEL_PROYECTO',
            motivo: 'La ruta resuelta queda fuera de la raiz auditada.'
        });
        return;
    }

    const result = inspectCase(absolute);

    if (result.status === 'EXISTS') return;

    if (result.status === 'CASE_MISMATCH') {
        report.diferenciasMayusculas.push({
            archivo: file.rel,
            linea: line,
            tipo,
            referencia: rawPath,
            rutaEsperada: result.expected,
            rutaReal: result.actual
        });
        return;
    }

    if (result.status === 'NOT_FOUND') {
        report.referenciasPosiblementeRotas.push({
            archivo: file.rel,
            linea: line,
            tipo,
            referencia: rawPath,
            rutaEsperada: result.expected,
            segmentoFaltante: result.segmentMissing
        });
        return;
    }

    report.referenciasNoVerificables.push({
        archivo: file.rel,
        linea: line,
        tipo,
        referencia: rawPath,
        estado: result.status,
        motivo: result.error || 'No se pudo verificar la ruta.'
    });
}

/**
 * Comprueba existencia y casing segmento por segmento.
 */
function inspectCase(absolute) {
    const rel = path.relative(ROOT, absolute);

    if (
        rel === '..' ||
        rel.startsWith('..' + path.sep) ||
        path.isAbsolute(rel)
    ) {
        return {
            status: 'OUTSIDE_PROJECT',
            expected: rel
        };
    }

    const segments = rel.split(path.sep).filter(Boolean);
    let current = ROOT;
    const actual = [];

    for (const segment of segments) {
        let entries;

        try {
            entries = fs.readdirSync(current);
        } catch (error) {
            return {
                status: 'UNVERIFIABLE',
                error: error.message
            };
        }

        if (entries.includes(segment)) {
            actual.push(segment);
            current = path.join(current, segment);
            continue;
        }

        const match = entries.find(
            name => name.toLocaleLowerCase('en-US') ===
                segment.toLocaleLowerCase('en-US')
        );

        if (match) {
            actual.push(match);

            return {
                status: 'CASE_MISMATCH',
                expected: slash(rel),
                actual: actual.concat(
                    segments.slice(actual.length)
                ).join('/')
            };
        }

        return {
            status: 'NOT_FOUND',
            expected: slash(rel),
            segmentMissing: segment
        };
    }

    return {
        status: 'EXISTS',
        expected: slash(rel)
    };
}

function dedupe(list, fields) {
    const seen = new Set();

    return list.filter(item => {
        const id = fields.map(field => item[field] ?? '').join('|');

        if (seen.has(id)) return false;

        seen.add(id);
        return true;
    });
}

function main() {
    console.log('');
    console.log('==============================================');
    console.log(' AUDITORIA DE NOMBRES Y REFERENCIAS V0.4');
    console.log('==============================================');
    console.log(`Raiz: ${ROOT}`);
    console.log('Modo: solo lectura');
    console.log('');

    walk(ROOT);
    detectCollisions();
    detectSuspiciousNames();

    for (const file of sourceFiles) {
        if (['.php', '.phtml'].includes(file.ext)) {
            scanPhp(file);
        }

        if (['.php', '.phtml', '.html', '.htm'].includes(file.ext)) {
            scanHtml(file);
        }

        if (file.ext === '.css') {
            scanCss(file);
        }

        if (['.js', '.mjs', '.cjs'].includes(file.ext)) {
            scanJavaScript(file);
        }
    }

    report.referenciasPHP = dedupe(
        report.referenciasPHP,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.referenciasWeb = dedupe(
        report.referenciasWeb,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.referenciasCSS = dedupe(
        report.referenciasCSS,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.referenciasJavaScript = dedupe(
        report.referenciasJavaScript,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.referenciasPosiblementeRotas = dedupe(
        report.referenciasPosiblementeRotas,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.diferenciasMayusculas = dedupe(
        report.diferenciasMayusculas,
        ['archivo', 'linea', 'tipo', 'referencia']
    );

    report.referenciasDinamicas = dedupe(
        report.referenciasDinamicas,
        ['archivo', 'linea', 'tipo', 'referencia', 'estado']
    );

    report.referenciasNoVerificables = dedupe(
        report.referenciasNoVerificables,
        ['archivo', 'linea', 'tipo', 'referencia', 'estado']
    );

    report.referenciasAExclusiones = dedupe(
        report.referenciasAExclusiones,
        ['archivo', 'linea', 'tipo', 'referencia', 'componente']
    );

    report.resumen = {
        directoriosEscaneados: directoriesScanned,
        archivosEscaneados: filesScanned,
        archivosTextoAnalizados: textFilesScanned,
        archivosGrandesOmitidos: report.archivosGrandesOmitidos.length,
        erroresLectura: report.erroresLectura.length,
        colisiones: report.colisiones.length,
        referenciasPHP: report.referenciasPHP.length,
        referenciasWeb: report.referenciasWeb.length,
        referenciasCSS: report.referenciasCSS.length,
        referenciasJavaScript: report.referenciasJavaScript.length,
        referenciasPosiblementeRotas:
            report.referenciasPosiblementeRotas.length,
        diferenciasMayusculas: report.diferenciasMayusculas.length,
        referenciasDinamicas: report.referenciasDinamicas.length,
        referenciasNoVerificables: report.referenciasNoVerificables.length,
        referenciasAExclusiones: report.referenciasAExclusiones.length,
        nombresSospechosos: report.nombresSospechosos.length
    };

    fs.writeFileSync(
        OUTPUT,
        JSON.stringify(report, null, 2),
        'utf8'
    );

    console.log('RESULTADOS');
    console.log('----------------------------------------------');

    for (const [name, value] of Object.entries(report.resumen)) {
        console.log(`${name}: ${value}`);
    }

    console.log('');
    console.log(`Informe: ${OUTPUT}`);
    console.log('No se modificaron archivos de la aplicacion.');
    console.log('');
}

try {
    main();
} catch (error) {
    console.error('ERROR FATAL:', error.stack || error.message);
    process.exitCode = 1;
}
