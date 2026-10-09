
'use strict';

/**
 * AUDITORÍA DE NOMBRES Y REFERENCIAS V0.2
 * Proyecto: Manipulación de Alimentos
 *
 * EJECUCIÓN:
 *   node audit-nombres-v02.js
 *
 * SEGURIDAD:
 *   - No modifica archivos del proyecto.
 *   - No renombra archivos ni carpetas.
 *   - Solo genera un reporte JSON en tools/path-audit.
 *
 * DETECTA:
 *   - Colisiones de nombres por mayúsculas/minúsculas.
 *   - Referencias a recursos en HTML, PHP, CSS y JavaScript.
 *   - Referencias relativas explícitas potencialmente inexistentes.
 *   - Diferencias de capitalización en rutas encontradas.
 *   - Nombres de archivos potencialmente problemáticos.
 *
 * IMPORTANTE:
 * Los hallazgos son candidatos a revisión.
 * No interpreta completamente PHP, JavaScript ni sus expresiones dinámicas.
 */

const fs = require('node:fs');
const path = require('node:path');

// =====================================================
// 1. CONFIGURACIÓN
// =====================================================

const ROOT = path.resolve(__dirname, '..', '..');

const REPORT_FILE = path.join(
    __dirname,
    'auditoria-nombres-v02-resultados.json'
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

const TEXT_EXTENSIONS = new Set([
    '.php', '.phtml', '.inc',
    '.html', '.htm',
    '.js', '.mjs', '.cjs',
    '.ts', '.tsx', '.jsx',
    '.css', '.scss', '.sass', '.less',
    '.json', '.xml',
    '.yml', '.yaml',
    '.md', '.txt',
    '.sql', '.env', '.ini', '.conf',
    '.config', '.svg'
]);

const RESOURCE_EXTENSIONS = new Set([
    '.php', '.phtml', '.inc',
    '.html', '.htm',
    '.js', '.mjs', '.cjs',
    '.ts', '.tsx', '.jsx',
    '.css', '.scss', '.sass', '.less',
    '.json', '.xml',
    '.yml', '.yaml',
    '.md', '.txt', '.sql',
    '.png', '.jpg', '.jpeg', '.gif',
    '.webp', '.avif', '.svg', '.ico',
    '.pdf',
    '.woff', '.woff2', '.ttf', '.otf', '.eot',
    '.mp3', '.wav', '.mp4', '.webm',
    '.map'
]);

const MAX_FILE_SIZE = 2 * 1024 * 1024;

// =====================================================
// 2. ESTADO
// =====================================================

const files = [];
const directories = [];
const textFiles = [];

const findings = {
    CASE_COLLISION: [],
    CASE_MISMATCH: [],
    POSSIBLE_MISSING_REFERENCE: [],
    DYNAMIC_REFERENCE: [],
    SUSPICIOUS_NAME: [],
    REVIEW: []
};

let skippedLargeFiles = 0;
let unreadableFiles = 0;

// =====================================================
// 3. UTILIDADES
// =====================================================

function relativePath(absolutePath) {
    return path.relative(ROOT, absolutePath)
        .split(path.sep)
        .join('/');
}

function normalizeRelative(value) {
    return value
        .replace(/\\/g, '/')
        .replace(/\/+/g, '/')
        .replace(/^\.\//, '');
}

function isInsideRoot(absolutePath) {
    const relative = path.relative(ROOT, absolutePath);

    return relative === '' ||
        (
            relative !== '..' &&
            !relative.startsWith('..' + path.sep) &&
            !path.isAbsolute(relative)
        );
}

function addFinding(category, data) {
    findings[category].push(data);
}

function getLineNumber(content, offset) {
    return content.slice(0, offset).split('\n').length;
}

function isTextFile(filePath) {
    return TEXT_EXTENSIONS.has(
        path.extname(filePath).toLowerCase()
    );
}

function stripQueryAndFragment(value) {
    return value.split(/[?#]/)[0];
}

function hasResourceExtension(value) {
    const clean = stripQueryAndFragment(value);
    const ext = path.extname(clean).toLowerCase();

    return RESOURCE_EXTENSIONS.has(ext);
}

function isExternalOrSpecialReference(value) {
    return (
        /^[a-z][a-z0-9+.-]*:\/\//i.test(value) ||
        /^data:/i.test(value) ||
        /^mailto:/i.test(value) ||
        /^tel:/i.test(value) ||
        /^javascript:/i.test(value) ||
        /^blob:/i.test(value) ||
        /^#/.test(value) ||
        /^about:/i.test(value)
    );
}

// =====================================================
// 4. RECORRIDO DEL PROYECTO
// =====================================================

function walkDirectory(directory) {
    let entries;

    try {
        entries = fs.readdirSync(directory, {
            withFileTypes: true
        });
    } catch (error) {
        unreadableFiles++;

        addFinding('REVIEW', {
            path: relativePath(directory),
            reason: 'No se pudo leer el directorio.',
            error: error.message
        });

        return;
    }

    for (const entry of entries) {
        const absolutePath = path.join(
            directory,
            entry.name
        );

        // No seguir enlaces simbólicos.
        if (entry.isSymbolicLink()) {
            continue;
        }

        if (entry.isDirectory()) {
            if (EXCLUDED_DIRS.has(entry.name)) {
                continue;
            }

            directories.push(absolutePath);
            walkDirectory(absolutePath);
            continue;
        }

        if (!entry.isFile()) {
            continue;
        }

        files.push(absolutePath);

        if (isTextFile(absolutePath)) {
            textFiles.push(absolutePath);
        }

        if (
            /[. ]$/.test(entry.name) ||
            /[\x00-\x1f<>:"|?*]/.test(entry.name)
        ) {
            addFinding('SUSPICIOUS_NAME', {
                path: relativePath(absolutePath),
                reason:
                    'El nombre contiene caracteres o terminaciones ' +
                    'potencialmente problemáticos.'
            });
        }
    }
}

// =====================================================
// 5. ÍNDICES DE ARCHIVOS Y DIRECTORIOS
// =====================================================

function buildIndexes() {
    const allPaths = [
        ...directories,
        ...files
    ];

    const exactIndex = new Map();
    const lowerIndex = new Map();

    for (const absolutePath of allPaths) {
        const rel = normalizeRelative(
            relativePath(absolutePath)
        );

        exactIndex.set(rel, absolutePath);

        const lower = rel.toLowerCase();

        if (!lowerIndex.has(lower)) {
            lowerIndex.set(lower, []);
        }

        lowerIndex.get(lower).push(absolutePath);
    }

    return {
        exactIndex,
        lowerIndex
    };
}

function auditCollisions(lowerIndex) {
    for (const [normalized, matches] of lowerIndex) {
        const names = [
            ...new Set(matches.map(relativePath))
        ];

        if (names.length < 2) {
            continue;
        }

        addFinding('CASE_COLLISION', {
            normalizedName: normalized,
            paths: names,
            reason:
                'Hay nombres distintos que colisionan al ignorar ' +
                'mayúsculas/minúsculas.'
        });
    }
}

// =====================================================
// 6. LECTURA DE ARCHIVOS
// =====================================================

function readTextFile(filePath) {
    try {
        const stats = fs.statSync(filePath);

        if (stats.size > MAX_FILE_SIZE) {
            skippedLargeFiles++;
            return null;
        }

        const buffer = fs.readFileSync(filePath);

        // Omitir archivos binarios.
        if (buffer.includes(0)) {
            return null;
        }

        return buffer.toString('utf8');
    } catch (error) {
        unreadableFiles++;

        addFinding('REVIEW', {
            path: relativePath(filePath),
            reason: 'No se pudo leer el archivo.',
            error: error.message
        });

        return null;
    }
}

// =====================================================
// 7. EXTRACCIÓN DE REFERENCIAS
// =====================================================

function extractReferences(content, sourceFile) {
    const refs = [];
    const seen = new Set();

    function add(value, offset, type, base = 'AUTO') {
        if (typeof value !== 'string') {
            return;
        }

        value = value.trim();

        if (!value || value.length > 1000) {
            return;
        }

        if (isExternalOrSpecialReference(value)) {
            return;
        }

        // Ignorar interpolaciones y expresiones dinámicas.
        if (
            value.includes('${') ||
            value.includes('<?') ||
            value.includes('?>') ||
            value.includes('<') ||
            value.includes('>') ||
            value.includes('*')
        ) {
            addFinding('DYNAMIC_REFERENCE', {
                source: relativePath(sourceFile),
                line: getLineNumber(content, offset),
                reference: value,
                type,
                reason:
                    'La referencia contiene una expresión o patrón ' +
                    'que no se puede resolver con seguridad.'
            });

            return;
        }

        // No analizar rutas absolutas de Windows ni UNC.
        if (
            /^[a-z]:[\\/]/i.test(value) ||
            value.startsWith('\\\\')
        ) {
            return;
        }

        const clean = stripQueryAndFragment(value);

        if (!clean) {
            return;
        }

        const explicitRelative =
            /^(?:\.{1,2})[\\/]/.test(clean);

        const hasSeparator = /[\\/]/.test(clean);
        const hasExtension = hasResourceExtension(clean);

        if (!hasSeparator && !hasExtension) {
            return;
        }

        // No tratar namespaces PHP ni nombres de clases como rutas.
        if (
            type === 'STRING' &&
            !explicitRelative &&
            !hasSeparator
        ) {
            return;
        }

        const key = [
            value,
            offset,
            type,
            base
        ].join('|');

        if (seen.has(key)) {
            return;
        }

        seen.add(key);

        refs.push({
            value: clean,
            originalValue: value,
            offset,
            type,
            base,
            explicitRelative
        });
    }

    // HTML: src, href, poster, action, data-src y otros atributos.
    const attributePattern =
        /\b(src|href|poster|action|formaction|data-src|data-href|data-url|manifest|cite)\s*=\s*(["'])(.*?)\2/gi;

    let match;

    while ((match = attributePattern.exec(content)) !== null) {
        add(
            match[3],
            match.index,
            'HTML_ATTRIBUTE',
            'AUTO'
        );
    }

    // HTML srcset: varios recursos separados por comas.
    const srcsetPattern =
        /\bsrcset\s*=\s*(["'])(.*?)\1/gi;

    while ((match = srcsetPattern.exec(content)) !== null) {
        const value = match[2];

        for (const item of value.split(',')) {
            const candidate = item.trim().split(/\s+/)[0];

            add(
                candidate,
                match.index,
                'HTML_SRCSET',
                'AUTO'
            );
        }
    }

    // CSS url(...).
    const cssUrlPattern =
        /\burl\(\s*(?:(["'])(.*?)\1|([^)]*?))\s*\)/gi;

    while ((match = cssUrlPattern.exec(content)) !== null) {
        const value = match[2] ?? match[3] ?? '';

        add(
            value,
            match.index,
            'CSS_URL',
            'AUTO'
        );
    }

    // CSS @import.
    const cssImportPattern =
        /@import\s+(?:url\(\s*)?(["'])(.*?)\1\s*\)?/gi;

    while ((match = cssImportPattern.exec(content)) !== null) {
        add(
            match[2],
            match.index,
            'CSS_IMPORT',
            'AUTO'
        );
    }

    // JavaScript / TypeScript import ... from "..."
    const importFromPattern =
        /\bimport\s+(?:[^;'"]*?\s+from\s+)?(["'])(.*?)\1/g;

    while ((match = importFromPattern.exec(content)) !== null) {
        add(
            match[2],
            match.index,
            'JS_IMPORT',
            'SOURCE_RELATIVE'
        );
    }

    // JavaScript dynamic import("...").
    const dynamicImportPattern =
        /\bimport\s*\(\s*(["'])(.*?)\1\s*\)/g;

    while ((match = dynamicImportPattern.exec(content)) !== null) {
        add(
            match[2],
            match.index,
            'JS_DYNAMIC_IMPORT',
            'SOURCE_RELATIVE'
        );
    }

    // PHP require/include con cadena literal.
    const phpIncludePattern =
        /\b(?:require|require_once|include|include_once)\s*\(?\s*(["'])(.*?)\1/g;

    while ((match = phpIncludePattern.exec(content)) !== null) {
        add(
            match[2],
            match.index,
            'PHP_INCLUDE',
            'SOURCE_RELATIVE'
        );
    }

    // PHP/JS/otros: cadenas con rutas relativas explícitas.
    // Se limita a cadenas con extensión conocida para reducir ruido.
    const stringPattern =
        /(["'`])((?:\\.|(?!\1)[\s\S])*?)\1/g;

    while ((match = stringPattern.exec(content)) !== null) {
        const value = match[2];

        if (!value || value.length > 500) {
            continue;
        }

        const candidate = value.trim();

        if (!hasResourceExtension(candidate)) {
            continue;
        }

        const explicitRelative =
            /^(?:\.{1,2})[\\/]/.test(candidate);

        if (!explicitRelative) {
            continue;
        }

        add(
            candidate,
            match.index,
            'STRING',
            'SOURCE_RELATIVE'
        );
    }

    return refs;
}

// =====================================================
// 8. RESOLUCIÓN DE RUTAS
// =====================================================

function candidatePaths(reference, sourceFile) {
    const value = reference.value.replace(/\\/g, '/');

    const results = [];

    function addCandidate(candidate) {
        const absolute = path.resolve(candidate);

        if (
            isInsideRoot(absolute) &&
            !results.includes(absolute)
        ) {
            results.push(absolute);
        }
    }

    if (reference.base === 'SOURCE_RELATIVE') {
        addCandidate(
            path.resolve(path.dirname(sourceFile), value)
        );

        return results;
    }

    if (reference.explicitRelative) {
        addCandidate(
            path.resolve(path.dirname(sourceFile), value)
        );

        return results;
    }

    // Las rutas web que empiezan por / se interpretan respecto
    // de la raíz del proyecto, no de la raíz del disco.
    if (value.startsWith('/')) {
        addCandidate(
            path.resolve(ROOT, '.' + value)
        );

        // También prueba quitar el nombre de la aplicación
        // cuando la URL incluye /manipulacionDeAlimentos/.
        const appPrefix = '/manipulacionDeAlimentos/';

        if (value.startsWith(appPrefix)) {
            addCandidate(
                path.resolve(
                    ROOT,
                    value.slice(appPrefix.length)
                )
            );
        }

        return results;
    }

    // Una referencia como css/app.css puede estar relacionada
    // con la raíz del proyecto o con el directorio del archivo.
    addCandidate(path.resolve(ROOT, value));
    addCandidate(path.resolve(path.dirname(sourceFile), value));

    return results;
}

// =====================================================
// 9. AUDITORÍA DE REFERENCIAS
// =====================================================

function auditReferences(exactIndex, lowerIndex) {
    const reported = new Set();

    for (const sourceFile of textFiles) {
        const content = readTextFile(sourceFile);

        if (content === null) {
            continue;
        }

        const refs = extractReferences(content, sourceFile);

        for (const ref of refs) {
            const candidates = candidatePaths(ref, sourceFile);

            if (candidates.length === 0) {
                continue;
            }

            let exactMatch = null;
            let caseMatch = null;

            for (const absoluteCandidate of candidates) {
                const rel = normalizeRelative(
                    relativePath(absoluteCandidate)
                );

                if (exactIndex.has(rel)) {
                    exactMatch = rel;
                    break;
                }

                const alternatives = lowerIndex.get(
                    rel.toLowerCase()
                );

                if (alternatives && alternatives.length > 0) {
                    caseMatch = {
                        expected: rel,
                        actual: alternatives.map(relativePath)
                    };

                    break;
                }
            }

            const line = getLineNumber(content, ref.offset);

            if (exactMatch) {
                continue;
            }

            const key = [
                relativePath(sourceFile),
                line,
                ref.originalValue,
                ref.type
            ].join('|');

            if (reported.has(key)) {
                continue;
            }

            reported.add(key);

            const contextStart = Math.max(0, ref.offset - 70);
            const contextEnd = Math.min(
                content.length,
                ref.offset + ref.originalValue.length + 120
            );

            const context = content
                .slice(contextStart, contextEnd)
                .replace(/\s+/g, ' ')
                .trim();

            if (caseMatch) {
                addFinding('CASE_MISMATCH', {
                    source: relativePath(sourceFile),
                    line,
                    reference: ref.originalValue,
                    type: ref.type,
                    expectedPath: caseMatch.expected,
                    actualPaths: caseMatch.actual,
                    context,
                    reason:
                        'La referencia coincide ignorando mayúsculas/' +
                        'minúsculas, pero no exactamente.'
                });

                continue;
            }

            if (ref.explicitRelative || ref.type === 'PHP_INCLUDE') {
                addFinding('POSSIBLE_MISSING_REFERENCE', {
                    source: relativePath(sourceFile),
                    line,
                    reference: ref.originalValue,
                    type: ref.type,
                    context,
                    reason:
                        'No se encontró una coincidencia exacta. ' +
                        'Puede tratarse de una referencia dinámica, ' +
                        'un archivo generado o una ruta realmente rota.'
                });
            } else {
                // Referencias no explícitas se informan para revisión,
                // pero no se marcan automáticamente como rotas.
                addFinding('REVIEW', {
                    source: relativePath(sourceFile),
                    line,
                    reference: ref.originalValue,
                    type: ref.type,
                    context,
                    reason:
                        'No se pudo confirmar la ruta. Revisar si ' +
                        'depende de una URL base, configuración o lógica dinámica.'
                });
            }
        }
    }
}

// =====================================================
// 10. REPORTE
// =====================================================

function printCategory(title, entries, limit = 100) {
    console.log(`\n${title}: ${entries.length}`);

    for (const entry of entries.slice(0, limit)) {
        console.log(' - ' + JSON.stringify(entry));
    }

    if (entries.length > limit) {
        console.log(
            ` ... y ${entries.length - limit} resultados adicionales.`
        );
    }
}

function main() {
    console.log('==============================================');
    console.log(' AUDITORÍA DE NOMBRES Y REFERENCIAS V0.2');
    console.log('==============================================');
    console.log(`Raíz del proyecto: ${ROOT}`);
    console.log('Modo: SOLO LECTURA');
    console.log('No se renombrarán ni modificarán archivos.');

    walkDirectory(ROOT);

    const {
        exactIndex,
        lowerIndex
    } = buildIndexes();

    auditCollisions(lowerIndex);
    auditReferences(exactIndex, lowerIndex);

    const report = {
        generatedAt: new Date().toISOString(),
        projectRoot: ROOT,
        mode: 'READ_ONLY',
        scannedDirectories: directories.length,
        scannedFiles: files.length,
        scannedTextFiles: textFiles.length,
        skippedLargeFiles,
        unreadableFiles,
        exclusions: [...EXCLUDED_DIRS],
        summary: Object.fromEntries(
            Object.entries(findings).map(
                ([key, values]) => [key, values.length]
            )
        ),
        findings
    };

    try {
        fs.writeFileSync(
            REPORT_FILE,
            JSON.stringify(report, null, 2),
            'utf8'
        );

        console.log(
            `\nReporte JSON: ${relativePath(REPORT_FILE)}`
        );
    } catch (error) {
        console.error(
            '\nNo se pudo escribir el reporte:',
            error.message
        );
    }

    console.log('\n============== RESUMEN ==============');
    console.log(`Directorios: ${directories.length}`);
    console.log(`Archivos: ${files.length}`);
    console.log(`Archivos de texto: ${textFiles.length}`);
    console.log(`Archivos grandes omitidos: ${skippedLargeFiles}`);
    console.log(`Errores de lectura: ${unreadableFiles}`);

    printCategory(
        'COLISIONES DE CAPITALIZACIÓN',
        findings.CASE_COLLISION
    );

    printCategory(
        'REFERENCIAS CON CAPITALIZACIÓN DISTINTA',
        findings.CASE_MISMATCH
    );

    printCategory(
        'POSIBLES REFERENCIAS ROTAS',
        findings.POSSIBLE_MISSING_REFERENCE
    );

    printCategory(
        'NOMBRES SOSPECHOSOS',
        findings.SUSPICIOUS_NAME
    );

    printCategory(
        'CASOS PARA REVISIÓN',
        findings.REVIEW
    );

    printCategory(
        'REFERENCIAS DINÁMICAS OMITIDAS',
        findings.DYNAMIC_REFERENCE
    );

    console.log('\nAuditoría terminada.');
    console.log(
        'Los resultados son candidatos a revisión, no cambios automáticos.'
    );
}

main();
