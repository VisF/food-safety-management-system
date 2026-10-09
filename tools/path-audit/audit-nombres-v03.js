
'use strict';

/**
 * AUDITORÍA DE NOMBRES Y REFERENCIAS V0.3
 *
 * Objetivos:
 * - Detectar colisiones de nombres por mayúsculas/minúsculas.
 * - Detectar referencias con diferencias de casing.
 * - Detectar posibles referencias rotas.
 * - Analizar referencias en PHP, HTML, CSS y JavaScript.
 * - Resolver la parte estática de rutas que contienen variables PHP.
 * - Diferenciar rutas locales, rutas web-root y referencias dinámicas.
 * - Generar un JSON con los resultados.
 *
 * IMPORTANTE:
 * - No modifica archivos del proyecto.
 * - Solo lee archivos y genera el informe JSON.
 * - No ejecuta PHP ni JavaScript del proyecto.
 */

const fs = require('node:fs');
const path = require('node:path');

const VERSION = '0.3.0';

const PROJECT_ROOT = path.resolve(__dirname, '../..');
const OUTPUT_FILE = path.join(
    __dirname,
    'auditoria-nombres-v03-resultados.json'
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
    'auditoria-nombres-v03-resultados.json'
]);

const MAX_FILE_SIZE = 2 * 1024 * 1024;

const TEXT_EXTENSIONS = new Set([
    '.php',
    '.phtml',
    '.html',
    '.htm',
    '.css',
    '.js',
    '.mjs',
    '.cjs',
    '.json',
    '.xml',
    '.svg',
    '.md',
    '.txt',
    '.yml',
    '.yaml',
    '.sql'
]);

const RESOURCE_EXTENSIONS = new Set([
    '.css',
    '.js',
    '.mjs',
    '.cjs',
    '.php',
    '.phtml',
    '.html',
    '.htm',
    '.json',
    '.xml',
    '.svg',
    '.png',
    '.jpg',
    '.jpeg',
    '.gif',
    '.webp',
    '.ico',
    '.woff',
    '.woff2',
    '.ttf',
    '.otf',
    '.eot',
    '.pdf',
    '.mp4',
    '.webm',
    '.mp3',
    '.wav'
]);

const report = {
    version: VERSION,
    fecha: new Date().toISOString(),
    raizProyecto: PROJECT_ROOT,
    resumen: {},
    colisiones: [],
    diferenciasMayusculas: [],
    referenciasPosiblementeRotas: [],
    referenciasDinamicas: [],
    nombresSospechosos: [],
    erroresLectura: [],
    archivosGrandesOmitidos: [],
    notas: [
        'Auditoria de solo lectura.',
        'Una referencia dinamica no implica que este rota.',
        'Las rutas web-root no siempre coinciden con la raiz del proyecto.',
        'Las rutas generadas en tiempo de ejecucion requieren revision manual.',
        'La auditoria no ejecuta PHP ni JavaScript.'
    ]
};

const fileIndex = new Map();
const allFiles = [];
const textFiles = [];

let directoriesScanned = 0;
let filesScanned = 0;
let textFilesScanned = 0;

/**
 * Normaliza separadores para comparar rutas.
 */
function normalizeSeparators(value) {
    return value.replace(/\\/g, '/');
}

/**
 * Ruta relativa legible respecto de la raiz del proyecto.
 */
function relativeToRoot(filePath) {
    return normalizeSeparators(
        path.relative(PROJECT_ROOT, filePath)
    );
}

/**
 * Convierte una ruta a una clave independiente de casing.
 */
function caseKey(value) {
    return normalizeSeparators(value).toLocaleLowerCase('en-US');
}

/**
 * Determina si una extension corresponde a un archivo de texto.
 */
function isTextFile(filePath) {
    return TEXT_EXTENSIONS.has(
        path.extname(filePath).toLowerCase()
    );
}

/**
 * Recorre el proyecto sin entrar en directorios excluidos.
 */
function walkDirectory(directory) {
    let entries;

    try {
        entries = fs.readdirSync(directory, {
            withFileTypes: true
        });
    } catch (error) {
        report.erroresLectura.push({
            ruta: relativeToRoot(directory),
            error: error.message
        });
        return;
    }

    directoriesScanned++;

    for (const entry of entries) {
        const absolutePath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            if (EXCLUDED_DIRS.has(entry.name)) {
                continue;
            }

            walkDirectory(absolutePath);
            continue;
        }

        if (!entry.isFile()) {
            continue;
        }

        if (EXCLUDED_FILES.has(entry.name)) {
            continue;
        }

        filesScanned++;
        allFiles.push(absolutePath);

        const relativePath = relativeToRoot(absolutePath);
        const key = caseKey(relativePath);

        if (!fileIndex.has(key)) {
            fileIndex.set(key, []);
        }

        fileIndex.get(key).push(relativePath);

        if (!isTextFile(absolutePath)) {
            continue;
        }

        let stats;

        try {
            stats = fs.statSync(absolutePath);
        } catch (error) {
            report.erroresLectura.push({
                ruta: relativePath,
                error: error.message
            });
            continue;
        }

        if (stats.size > MAX_FILE_SIZE) {
            report.archivosGrandesOmitidos.push({
                ruta: relativePath,
                bytes: stats.size
            });
            continue;
        }

        try {
            const content = fs.readFileSync(
                absolutePath,
                'utf8'
            );

            textFilesScanned++;

            textFiles.push({
                absolutePath,
                relativePath,
                content
            });
        } catch (error) {
            report.erroresLectura.push({
                ruta: relativePath,
                error: error.message
            });
        }
    }
}

/**
 * Busca colisiones de nombres que solo difieren por casing.
 */
function detectCollisions() {
    for (const [key, files] of fileIndex.entries()) {
        if (files.length < 2) {
            continue;
        }

        const uniqueNames = [...new Set(files)];

        if (uniqueNames.length > 1) {
            report.colisiones.push({
                clave: key,
                rutas: uniqueNames
            });
        }
    }
}

/**
 * Extrae rutas candidatas de referencias de recursos.
 *
 * Cada candidato contiene:
 * - raw: texto original
 * - line: linea aproximada
 * - source: mecanismo detectado
 */
function extractCandidates(content) {
    const candidates = [];

    function addMatches(regex, source, group = 1) {
        regex.lastIndex = 0;

        let match;

        while ((match = regex.exec(content)) !== null) {
            const raw = match[group];

            if (!raw) {
                continue;
            }

            const line = content
                .slice(0, match.index)
                .split('\n').length;

            candidates.push({
                raw: raw.trim(),
                line,
                source
            });

            if (match[0].length === 0) {
                regex.lastIndex++;
            }
        }
    }

    // HTML: src, href, action, poster y data.
    addMatches(
        /\b(?:src|href|action|poster|data)\s*=\s*["']([^"'<>]+)["']/gi,
        'HTML_ATTRIBUTE'
    );

    // srcset: puede contener varias URL separadas por comas.
    addMatches(
        /\bsrcset\s*=\s*["']([^"']+)["']/gi,
        'HTML_SRCSET'
    );

    // CSS: url(...).
    addMatches(
        /url\(\s*["']?([^"')]+)["']?\s*\)/gi,
        'CSS_URL'
    );

    // CSS: @import.
    addMatches(
        /@import\s+(?:url\(\s*)?["']([^"']+)["']/gi,
        'CSS_IMPORT'
    );

    // JavaScript: import ... from 'ruta'.
    addMatches(
        /\bimport\s+(?:[^'";]*?\s+from\s+)?["']([^"']+)["']/g,
        'JS_IMPORT'
    );

    // JavaScript: import('ruta').
    addMatches(
        /\bimport\s*\(\s*["']([^"']+)["']\s*\)/g,
        'JS_DYNAMIC_IMPORT_LITERAL'
    );

    // JavaScript: require('ruta').
    addMatches(
        /\brequire\s*\(\s*["']([^"']+)["']\s*\)/g,
        'JS_REQUIRE'
    );

    // PHP: include, require y variantes.
    addMatches(
        /\b(?:include|include_once|require|require_once)\s*(?:\(\s*)?["']([^"']+)["']/gi,
        'PHP_INCLUDE'
    );

    // Referencias PHP literales a recursos dentro de strings.
    // Ejemplo: 'Views/admin.php' o '../css/app.css'.
    addMatches(
        /["']((?:\.{1,2}\/|\/)?(?:[A-Za-z0-9_@.-]+\/)*[A-Za-z0-9_@.-]+\.(?:css|js|mjs|cjs|php|phtml|html?|json|xml|svg|png|jpe?g|gif|webp|ico|woff2?|ttf|otf|eot|pdf|mp4|webm|mp3|wav)(?:\?[^"'<> ]*)?(?:#[^"'<> ]*)?)["']/gi,
        'STRING_RESOURCE_LITERAL'
    );

    return candidates;
}

/**
 * Clasifica el texto de una referencia antes de intentar resolverla.
 */
function classifyReference(raw) {
    const value = raw.trim();

    if (!value) {
        return {
            type: 'EMPTY',
            value
        };
    }

    // Esquemas externos: http:, https:, data:, mailto:, etc.
    if (/^[a-z][a-z0-9+.-]*:/i.test(value)) {
        return {
            type: 'EXTERNAL_OR_SCHEME',
            value
        };
    }

    if (
        value.startsWith('#') ||
        value.startsWith('?') ||
        value.startsWith('//')
    ) {
        return {
            type: 'NON_LOCAL',
            value
        };
    }

    // Variables, concatenaciones y expresiones PHP/JS.
    if (
        /<\?=|<\?php|\?>|\$\w+|\{\{|\}\}|\$\{|\bBASE_URL\b|\bURL_BASE\b|\b__DIR__\b|\b__FILE__\b|\bdirname\s*\(|\bgetRoute\s*\(|\basset\s*\(/i.test(value)
    ) {
        return {
            type: 'DYNAMIC',
            value
        };
    }

    return {
        type: 'STATIC',
        value
    };
}

/**
 * Elimina query strings y fragmentos antes de resolver archivos.
 */
function stripQueryAndFragment(value) {
    return value.split(/[?#]/, 1)[0];
}

/**
 * Intenta separar una referencia PHP/HTML mixta en:
 * - prefijo dinámico
 * - sufijo estático de ruta
 *
 * Ejemplo:
 *   <?= BASE_URL ?>/css/app.css
 *
 * Se intenta verificar css/app.css sin asumir que el prefijo
 * BASE_URL apunta necesariamente a la raiz del proyecto.
 */
function extractStaticSuffix(raw) {
    const value = raw.trim();

    // Referencia completa estática.
    if (
        !/<|\?>|\$[A-Za-z_]|BASE_URL|URL_BASE|__DIR__|__FILE__|getRoute\s*\(/i.test(value)
    ) {
        return null;
    }

    // Busca una extensión de recurso al final y toma su ruta
    // desde el último separador que precede a un segmento.
    const cleaned = value.replace(/["'\s]+$/g, '');

    const extensionMatch = cleaned.match(
        /([A-Za-z0-9_@.-]+(?:\/[A-Za-z0-9_@.-]+)*\.(?:css|js|mjs|cjs|php|phtml|html?|json|xml|svg|png|jpe?g|gif|webp|ico|woff2?|ttf|otf|eot|pdf|mp4|webm|mp3|wav))(?:\?[^"'<> ]*)?(?:#[^"'<> ]*)?$/i
    );

    if (!extensionMatch) {
        return null;
    }

    const suffix = extensionMatch[1];

    // Evita devolver un fragmento que siga conteniendo expresiones.
    if (/<|\?>|\$|\s/.test(suffix)) {
        return null;
    }

    return suffix;
}

/**
 * Obtiene posibles bases para una ruta.
 *
 * Convención:
 * - PHP_INCLUDE y JS_IMPORT suelen ser relativos al archivo fuente.
 * - URLs relativas en HTML/CSS/JS se prueban contra el directorio
 *   del archivo fuente y contra la raiz del proyecto.
 * - Las rutas que empiezan por / se tratan como web-root.
 *
 * La raiz web real puede ser distinta de la raiz del proyecto.
 */
function getResolutionCandidates(
    reference,
    sourceFile,
    sourceType
) {
    const normalized = normalizeSeparators(reference);
    const cleaned = stripQueryAndFragment(normalized);

    if (!cleaned) {
        return [];
    }

    if (cleaned.startsWith('/')) {
        return [{
            absolutePath: path.resolve(
                PROJECT_ROOT,
                '.' + cleaned
            ),
            basis: 'WEB_ROOT_ASSUMED_PROJECT_ROOT'
        }];
    }

    const sourceDir = path.dirname(sourceFile);

    if (
        sourceType === 'PHP_INCLUDE' ||
        sourceType === 'JS_IMPORT' ||
        sourceType === 'JS_DYNAMIC_IMPORT_LITERAL' ||
        sourceType === 'JS_REQUIRE'
    ) {
        return [{
            absolutePath: path.resolve(sourceDir, cleaned),
            basis: 'SOURCE_RELATIVE'
        }];
    }

    return [
        {
            absolutePath: path.resolve(sourceDir, cleaned),
            basis: 'SOURCE_RELATIVE'
        },
        {
            absolutePath: path.resolve(PROJECT_ROOT, cleaned),
            basis: 'PROJECT_ROOT_RELATIVE'
        }
    ];
}

/**
 * Resuelve una ruta comprobando el casing de cada segmento.
 */
function inspectPathCase(absolutePath) {
    const relative = path.relative(
        PROJECT_ROOT,
        absolutePath
    );

    if (
        relative.startsWith('..' + path.sep) ||
        relative === '..' ||
        path.isAbsolute(relative)
    ) {
        return {
            status: 'OUTSIDE_PROJECT',
            expected: relative
        };
    }

    const segments = relative
        .split(path.sep)
        .filter(Boolean);

    let current = PROJECT_ROOT;
    const actualSegments = [];

    for (const segment of segments) {
        let entries;

        try {
            entries = fs.readdirSync(current);
        } catch (error) {
            return {
                status: 'UNVERIFIABLE',
                expected: relative,
                error: error.message
            };
        }

        if (entries.includes(segment)) {
            actualSegments.push(segment);
            current = path.join(current, segment);
            continue;
        }

        const insensitiveMatch = entries.find(
            entry =>
                entry.toLocaleLowerCase('en-US') ===
                segment.toLocaleLowerCase('en-US')
        );

        if (insensitiveMatch) {
            actualSegments.push(insensitiveMatch);

            return {
                status: 'CASE_MISMATCH',
                expected: relative,
                actual: actualSegments
                    .concat(segments.slice(actualSegments.length))
                    .join('/')
            };
        }

        return {
            status: 'NOT_FOUND',
            expected: relative,
            segmentMissing: segment,
            parent: relativeToRoot(current)
        };
    }

    return {
        status: 'EXISTS',
        expected: relative,
        actual: actualSegments.join('/')
    };
}

/**
 * Registra una referencia dinámica y, cuando se puede,
 * verifica el sufijo estático.
 */
function processDynamicReference(
    candidate,
    sourceFile
) {
    const suffix = extractStaticSuffix(candidate.raw);

    const entry = {
        archivo: sourceFile.relativePath,
        linea: candidate.line,
        tipo: candidate.source,
        referencia: candidate.raw,
        sufijoEstatico: suffix,
        estado: 'DYNAMIC_UNRESOLVED'
    };

    if (suffix) {
        const results = getResolutionCandidates(
            suffix,
            sourceFile.absolutePath,
            candidate.source
        );

        const inspections = results.map(result => ({
            base: result.basis,
            ...inspectPathCase(result.absolutePath)
        }));

        entry.comprobacionesSufijo = inspections;

        const exists = inspections.find(
            item => item.status === 'EXISTS'
        );

        const caseMismatch = inspections.find(
            item => item.status === 'CASE_MISMATCH'
        );

        const missing = inspections.filter(
            item => item.status === 'NOT_FOUND'
        );

        if (exists) {
            entry.estado = 'STATIC_SUFFIX_EXISTS';
        } else if (caseMismatch) {
            entry.estado = 'STATIC_SUFFIX_CASE_MISMATCH';
        } else if (
            missing.length > 0 &&
            inspections.every(
                item => item.status === 'NOT_FOUND'
            )
        ) {
            entry.estado = 'STATIC_SUFFIX_NOT_FOUND';
        }
    }

    report.referenciasDinamicas.push(entry);
}

/**
 * Procesa referencias estáticas.
 */
function processStaticReference(
    candidate,
    sourceFile
) {
    const raw = candidate.raw;
    const reference = stripQueryAndFragment(
        normalizeSeparators(raw)
    );

    if (!reference) {
        return;
    }

    // Ignora valores que no parecen rutas.
    if (
        reference.startsWith('javascript:') ||
        reference.startsWith('mailto:') ||
        reference.startsWith('tel:')
    ) {
        return;
    }

    // Evita clasificar texto normal como ruta.
    if (
        !/[/.]/.test(reference) ||
        !RESOURCE_EXTENSIONS.has(
            path.extname(reference).toLowerCase()
        )
    ) {
        return;
    }

    const candidates = getResolutionCandidates(
        reference,
        sourceFile.absolutePath,
        candidate.source
    );

    if (candidates.length === 0) {
        return;
    }

    const inspections = candidates.map(result => ({
        base: result.basis,
        ...inspectPathCase(result.absolutePath)
    }));

    const exists = inspections.find(
        item => item.status === 'EXISTS'
    );

    if (exists) {
        return;
    }

    const caseMismatch = inspections.find(
        item => item.status === 'CASE_MISMATCH'
    );

    if (caseMismatch) {
        report.diferenciasMayusculas.push({
            archivo: sourceFile.relativePath,
            linea: candidate.line,
            tipo: candidate.source,
            referencia: raw,
            rutaEsperada: caseMismatch.expected,
            rutaReal: caseMismatch.actual,
            base: caseMismatch.base
        });
        return;
    }

    // Si una base posible encuentra el archivo fuera del proyecto
    // o no puede verificarlo, no lo marca como roto.
    const unverifiable = inspections.some(
        item =>
            item.status === 'OUTSIDE_PROJECT' ||
            item.status === 'UNVERIFIABLE'
    );

    if (unverifiable) {
        report.referenciasDinamicas.push({
            archivo: sourceFile.relativePath,
            linea: candidate.line,
            tipo: candidate.source,
            referencia: raw,
            estado: 'UNVERIFIABLE_BASE',
            comprobaciones: inspections
        });
        return;
    }

    const allNotFound = inspections.every(
        item => item.status === 'NOT_FOUND'
    );

    if (allNotFound) {
        report.referenciasPosiblementeRotas.push({
            archivo: sourceFile.relativePath,
            linea: candidate.line,
            tipo: candidate.source,
            referencia: raw,
            comprobaciones: inspections
        });
    }
}

/**
 * Detecta nombres con espacios iniciales/finales y caracteres
 * que conviene revisar al trabajar en distintos sistemas.
 */
function detectSuspiciousNames() {
    for (const file of allFiles) {
        const relative = relativeToRoot(file);
        const segments = relative.split('/');

        for (const segment of segments) {
            const reasons = [];

            if (segment !== segment.trim()) {
                reasons.push('ESPACIOS_INICIALES_O_FINALES');
            }

            if (/[<>:"|?*]/.test(segment)) {
                reasons.push('CARACTER_NO_PORTABLE_EN_WINDOWS');
            }

            if (/[. ]$/.test(segment)) {
                reasons.push('TERMINA_EN_PUNTO_O_ESPACIO');
            }

            if (
                /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?$/i
                    .test(segment)
            ) {
                reasons.push('NOMBRE_RESERVADO_WINDOWS');
            }

            if (reasons.length > 0) {
                report.nombresSospechosos.push({
                    ruta: relative,
                    segmento: segment,
                    motivos: reasons
                });
            }
        }
    }
}

/**
 * Deduplica resultados manteniendo las entradas distintas.
 */
function deduplicate(array, keyFn) {
    const seen = new Set();

    return array.filter(item => {
        const key = keyFn(item);

        if (seen.has(key)) {
            return false;
        }

        seen.add(key);
        return true;
    });
}

/**
 * Ejecuta toda la auditoría.
 */
function main() {
    console.log('');
    console.log('==============================================');
    console.log(' AUDITORIA DE NOMBRES Y REFERENCIAS V0.3');
    console.log('==============================================');
    console.log(`Raiz: ${PROJECT_ROOT}`);
    console.log('Modo: solo lectura');
    console.log('');

    walkDirectory(PROJECT_ROOT);

    detectCollisions();
    detectSuspiciousNames();

    for (const sourceFile of textFiles) {
        const candidates = extractCandidates(
            sourceFile.content
        );

        for (const candidate of candidates) {
            const classification = classifyReference(
                candidate.raw
            );

            if (
                classification.type === 'EXTERNAL_OR_SCHEME' ||
                classification.type === 'NON_LOCAL' ||
                classification.type === 'EMPTY'
            ) {
                continue;
            }

            if (classification.type === 'DYNAMIC') {
                processDynamicReference(
                    candidate,
                    sourceFile
                );
                continue;
            }

            processStaticReference(
                candidate,
                sourceFile
            );
        }
    }

    report.diferenciasMayusculas = deduplicate(
        report.diferenciasMayusculas,
        item => [
            item.archivo,
            item.linea,
            item.referencia,
            item.rutaReal
        ].join('|')
    );

    report.referenciasPosiblementeRotas = deduplicate(
        report.referenciasPosiblementeRotas,
        item => [
            item.archivo,
            item.linea,
            item.referencia
        ].join('|')
    );

    report.referenciasDinamicas = deduplicate(
        report.referenciasDinamicas,
        item => [
            item.archivo,
            item.linea,
            item.referencia,
            item.estado
        ].join('|')
    );

    report.resumen = {
        directoriosEscaneados: directoriesScanned,
        archivosEscaneados: filesScanned,
        archivosTextoAnalizados: textFilesScanned,
        archivosGrandesOmitidos:
            report.archivosGrandesOmitidos.length,
        erroresLectura: report.erroresLectura.length,
        colisiones: report.colisiones.length,
        diferenciasMayusculas:
            report.diferenciasMayusculas.length,
        referenciasPosiblementeRotas:
            report.referenciasPosiblementeRotas.length,
        referenciasDinamicas:
            report.referenciasDinamicas.length,
        nombresSospechosos:
            report.nombresSospechosos.length
    };

    fs.writeFileSync(
        OUTPUT_FILE,
        JSON.stringify(report, null, 2),
        'utf8'
    );

    console.log('RESULTADOS');
    console.log('----------------------------------------------');
    console.log(`Directorios: ${directoriesScanned}`);
    console.log(`Archivos: ${filesScanned}`);
    console.log(`Archivos de texto: ${textFilesScanned}`);
    console.log(
        `Colisiones: ${report.colisiones.length}`
    );
    console.log(
        `Diferencias de mayusculas: ${report.diferenciasMayusculas.length}`
    );
    console.log(
        `Referencias posiblemente rotas: ${report.referenciasPosiblementeRotas.length}`
    );
    console.log(
        `Referencias dinamicas: ${report.referenciasDinamicas.length}`
    );
    console.log(
        `Nombres sospechosos: ${report.nombresSospechosos.length}`
    );
    console.log(
        `Errores de lectura: ${report.erroresLectura.length}`
    );
    console.log('');
    console.log(`Informe generado: ${OUTPUT_FILE}`);
    console.log('');
    console.log(
        'IMPORTANTE: revisar el JSON antes de modificar archivos.'
    );
}

try {
    main();
} catch (error) {
    console.error('');
    console.error('ERROR FATAL EN LA AUDITORIA');
    console.error(error.stack || error.message);
    process.exitCode = 1;
}
