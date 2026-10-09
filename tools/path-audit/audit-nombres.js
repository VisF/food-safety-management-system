
'use strict';

/**
 * AUDITORÍA DE NOMBRES Y REFERENCIAS
 * Proyecto: Manipulación de Alimentos
 *
 * Uso:
 *   node auditoria-nombres.js
 *
 * Este script es de SOLO LECTURA respecto del proyecto.
 * No renombra archivos ni modifica código.
 *
 * Detecta:
 *   1. Colisiones de nombres por mayúsculas/minúsculas.
 *   2. Referencias con diferencias de capitalización.
 *   3. Referencias relativas explícitas que parecen no existir.
 *   4. Nombres con espacios o caracteres problemáticos.
 *
 * Los resultados requieren revisión antes de corregir.
 */

const fs = require('node:fs');
const path = require('node:path');

// =====================================================
// CONFIGURACIÓN
// =====================================================

const ROOT = path.resolve(__dirname, '..', '..');

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
    '.html',
    '.htm',
    '.js',
    '.mjs',
    '.cjs',
    '.ts',
    '.tsx',
    '.jsx',
    '.css',
    '.scss',
    '.sass',
    '.less',
    '.json',
    '.xml',
    '.yml',
    '.yaml',
    '.md',
    '.txt',
    '.sql',
    '.env',
    '.ini',
    '.conf',
    '.config',
    '.svg'
]);

const MAX_FILE_SIZE = 2 * 1024 * 1024;

const REPORT_FILE = path.join(
    __dirname,
    'auditoria-nombres-resultados.json'
);

// Extensiones que permiten reconocer una referencia a archivo.
const FILE_EXTENSION_PATTERN =
    /\.(php|phtml|inc|html?|js|mjs|cjs|ts|tsx|jsx|css|scss|sass|less|json|xml|ya?ml|md|txt|sql|svg|png|jpe?g|gif|webp|ico|pdf|woff2?|ttf|eot|mp4|webm)$/i;

// =====================================================
// ESTADO
// =====================================================

const files = [];
const directories = [];
const textFiles = [];

const findings = {
    CASE_COLLISION: [],
    CASE_MISMATCH: [],
    POSSIBLE_MISSING_REFERENCE: [],
    SUSPICIOUS_NAME: [],
    REVIEW: []
};

let skippedLargeFiles = 0;
let unreadableFiles = 0;

// =====================================================
// UTILIDADES
// =====================================================

function relativePath(absolutePath) {
    return path.relative(ROOT, absolutePath)
        .split(path.sep)
        .join('/');
}

function normalizeRelative(value) {
    return value
        .replace(/\\/g, '/')
        .replace(/^\.\//, '')
        .replace(/\/+/g, '/');
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

function isTextFile(filePath) {
    return TEXT_EXTENSIONS.has(
        path.extname(filePath).toLowerCase()
    );
}

function addFinding(category, data) {
    findings[category].push(data);
}

// =====================================================
// RECORRIDO DEL PROYECTO
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

        // Señala nombres que conviene revisar.
        if (
            /[. ]$/.test(entry.name) ||
            /[\x00-\x1f<>:"|?*]/.test(entry.name)
        ) {
            addFinding('SUSPICIOUS_NAME', {
                path: relativePath(absolutePath),
                reason:
                    'El nombre contiene caracteres o terminaciones ' +
                    'problemáticas para algunos sistemas de archivos.'
            });
        }
    }
}

// =====================================================
// ÍNDICE DE NOMBRES
// =====================================================

function buildPathIndexes() {
    const allPaths = [
        ...directories,
        ...files
    ];

    const exactIndex = new Map();
    const caseInsensitiveIndex = new Map();

    for (const absolutePath of allPaths) {
        const rel = normalizeRelative(
            relativePath(absolutePath)
        );

        const lower = rel.toLowerCase();

        exactIndex.set(rel, absolutePath);

        if (!caseInsensitiveIndex.has(lower)) {
            caseInsensitiveIndex.set(lower, []);
        }

        caseInsensitiveIndex.get(lower).push(
            absolutePath
        );
    }

    return {
        exactIndex,
        caseInsensitiveIndex
    };
}

// =====================================================
// COLISIONES DE CAPITALIZACIÓN
// =====================================================

function auditCaseCollisions(caseInsensitiveIndex) {
    for (const [lowerName, matches] of caseInsensitiveIndex) {
        if (matches.length < 2) {
            continue;
        }

        const distinctNames = [
            ...new Set(
                matches.map(relativePath)
            )
        ];

        if (distinctNames.length < 2) {
            continue;
        }

        addFinding('CASE_COLLISION', {
            normalizedName: lowerName,
            paths: distinctNames,
            reason:
                'Hay nombres que solo se diferencian por ' +
                'mayúsculas/minúsculas. Puede causar conflictos en Linux.'
        });
    }
}

// =====================================================
// LECTURA DE ARCHIVOS DE TEXTO
// =====================================================

function readTextFile(filePath) {
    try {
        const stats = fs.statSync(filePath);

        if (stats.size > MAX_FILE_SIZE) {
            skippedLargeFiles++;
            return null;
        }

        const buffer = fs.readFileSync(filePath);

        // Omite archivos con NUL, probablemente binarios.
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
// EXTRACCIÓN CONSERVADORA DE REFERENCIAS
// =====================================================

function extractReferences(content) {
    const references = [];

    // Busca cadenas entre comillas. No pretende interpretar
    // completamente PHP, JavaScript, HTML ni sus concatenaciones.
    const stringPattern =
        /(['"`])((?:\\.|(?!\1)[\s\S])*?)\1/g;

    let match;

    while ((match = stringPattern.exec(content)) !== null) {
        const value = match[2];

        if (!value || value.length > 500) {
            continue;
        }

        const candidate = value.trim();

        if (!candidate || /\$\{|<\?php|\?>/.test(candidate)) {
            continue;
        }

        // Excluir URLs, URIs, correo y esquemas de servicios.
        if (
            /^[a-z][a-z0-9+.-]*:\/\//i.test(candidate) ||
            /^[a-z][a-z0-9+.-]*:/i.test(candidate) ||
            /^mailto:/i.test(candidate) ||
            /^data:/i.test(candidate) ||
            candidate.startsWith('#')
        ) {
            continue;
        }

        // Solo rutas explícitas o cadenas con apariencia de archivo.
        const explicitRelative =
            candidate.startsWith('./') ||
            candidate.startsWith('../') ||
            candidate.startsWith('.\\') ||
            candidate.startsWith('..\\');

        const rootRelative = /^[\\/]/.test(candidate);

        const hasSeparator = /[\\/]/.test(candidate);

        const looksLikeFile =
            FILE_EXTENSION_PATTERN.test(
                candidate.split(/[?#]/)[0]
            );

        if (!hasSeparator) {
            continue;
        }

        if (
            !explicitRelative &&
            !rootRelative &&
            !looksLikeFile
        ) {
            continue;
        }

        // No analizar rutas absolutas de Windows ni UNC.
        if (
            /^[a-z]:[\\/]/i.test(candidate) ||
            candidate.startsWith('\\\\')
        ) {
            continue;
        }

        // Evitar fragmentos dinámicos, comodines y expresiones.
        if (
            /[*<>|]/.test(candidate) ||
            candidate.includes('${') ||
            candidate.includes('(') ||
            candidate.includes(')')
        ) {
            continue;
        }

        references.push({
            value: candidate,
            offset: match.index
        });
    }

    return references;
}

// =====================================================
// COMPROBACIÓN DE REFERENCIAS
// =====================================================

function resolveCandidatePaths(
    candidate,
    sourceFile
) {
    const clean = candidate
        .split(/[?#]/)[0]
        .replace(/\\/g, '/');

    if (!clean) {
        return [];
    }

    let possibilities = [];

    if (clean.startsWith('../') || clean.startsWith('./')) {
        // Las referencias explícitas se resuelven desde el archivo.
        possibilities = [
            path.resolve(
                path.dirname(sourceFile),
                clean
            )
        ];
    } else if (clean.startsWith('/')) {
        // Referencia web raíz: se compara con la raíz del proyecto.
        possibilities = [
            path.resolve(ROOT, '.' + clean)
        ];
    } else {
        // En referencias ambiguas se prueban ambas convenciones.
        possibilities = [
            path.resolve(path.dirname(sourceFile), clean),
            path.resolve(ROOT, clean)
        ];
    }

    return possibilities.filter(isInsideRoot);
}

function auditReferences(
    exactIndex,
    caseInsensitiveIndex
) {
    for (const sourceFile of textFiles) {
        const content = readTextFile(sourceFile);

        if (content === null) {
            continue;
        }

        const references = extractReferences(content);

        for (const reference of references) {
            const candidates = resolveCandidatePaths(
                reference.value,
                sourceFile
            );

            if (candidates.length === 0) {
                continue;
            }

            let exactMatch = false;
            let caseMismatch = null;

            for (const absoluteCandidate of candidates) {
                const candidateRelative = normalizeRelative(
                    relativePath(absoluteCandidate)
                );

                if (exactIndex.has(candidateRelative)) {
                    exactMatch = true;
                    break;
                }

                const lower = candidateRelative.toLowerCase();

                const alternatives =
                    caseInsensitiveIndex.get(lower);

                if (alternatives && alternatives.length > 0) {
                    caseMismatch = {
                        expected: candidateRelative,
                        actual: alternatives.map(relativePath)
                    };

                    break;
                }
            }

            if (exactMatch) {
                continue;
            }

            const contextStart = Math.max(
                0,
                reference.offset - 100
            );

            const contextEnd = Math.min(
                content.length,
                reference.offset + reference.value.length + 150
            );

            const context = content
                .slice(contextStart, contextEnd)
                .replace(/\s+/g, ' ')
                .trim();

            if (caseMismatch) {
                addFinding('CASE_MISMATCH', {
                    source: relativePath(sourceFile),
                    line: getLineNumber(
                        content,
                        reference.offset
                    ),
                    reference: reference.value,
                    expectedPath: caseMismatch.expected,
                    actualPaths: caseMismatch.actual,
                    context,
                    reason:
                        'La referencia coincide ignorando mayúsculas/minúsculas, ' +
                        'pero no coincide exactamente con el nombre existente.'
                });

                continue;
            }

            const isExplicit =
                reference.value.startsWith('./') ||
                reference.value.startsWith('../') ||
                reference.value.startsWith('.\\') ||
                reference.value.startsWith('..\\');

            const hasKnownExtension =
                FILE_EXTENSION_PATTERN.test(
                    reference.value.split(/[?#]/)[0]
                );

            if (isExplicit && hasKnownExtension) {
                addFinding('POSSIBLE_MISSING_REFERENCE', {
                    source: relativePath(sourceFile),
                    line: getLineNumber(
                        content,
                        reference.offset
                    ),
                    reference: reference.value,
                    context,
                    reason:
                        'La referencia relativa explícita no coincide ' +
                        'con un archivo o directorio indexado. Puede ser ' +
                        'dinámica, generada o un falso positivo.'
                });
            }
        }
    }
}

function getLineNumber(content, offset) {
    return content
        .slice(0, offset)
        .split('\n').length;
}

// =====================================================
// RESUMEN Y REPORTE
// =====================================================

function printCategory(title, entries) {
    console.log(`\n${title}: ${entries.length}`);

    for (const entry of entries.slice(0, 100)) {
        console.log(
            ' - ' + JSON.stringify(entry)
        );
    }

    if (entries.length > 100) {
        console.log(
            ` ... y ${entries.length - 100} resultados adicionales.`
        );
    }
}

function main() {
    console.log('==============================================');
    console.log(' AUDITORÍA DE NOMBRES Y REFERENCIAS');
    console.log('==============================================');
    console.log(`Raíz del proyecto: ${ROOT}`);
    console.log('Modo: SOLO LECTURA');
    console.log('No se renombrarán ni modificarán archivos.');

    walkDirectory(ROOT);

    const {
        exactIndex,
        caseInsensitiveIndex
    } = buildPathIndexes();

    auditCaseCollisions(caseInsensitiveIndex);

    auditReferences(
        exactIndex,
        caseInsensitiveIndex
    );

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
                ([key, value]) => [key, value.length]
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
        'POSIBLES REFERENCIAS RELATIVAS ROTAS',
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

    console.log('\nAuditoría terminada.');
    console.log('Los resultados son candidatos a revisión, no cambios automáticos.');
}

main();
