
'use strict';

/**
 * AUDITORÍA DE NOMBRES Y REFERENCIAS V0.5
 * Solo lectura sobre el proyecto. Únicamente escribe el JSON de resultados.
 * Ejecutar: node audit-nombres-v05.js
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..');
const OUT = path.join(__dirname, 'auditoria-nombres-v05-resultados.json');
const MAX_BYTES = 2 * 1024 * 1024;

const EXCLUDED = new Set([
  '.git', '.venv', 'node_modules', 'vendor', 'target', 'build',
  'dist', 'coverage', '.idea', '.vscode', 'path-audit'
]);

const TEXT_EXT = new Set([
  '.php', '.phtml', '.html', '.htm', '.css', '.js', '.mjs',
  '.cjs', '.json', '.xml', '.svg', '.md', '.txt'
]);

const RESOURCE_EXT = new Set([
  '.php', '.phtml', '.html', '.htm', '.css', '.js', '.mjs',
  '.cjs', '.json', '.xml', '.svg', '.png', '.jpg', '.jpeg',
  '.gif', '.webp', '.ico', '.woff', '.woff2', '.ttf', '.otf',
  '.eot', '.pdf', '.mp4', '.webm', '.mp3', '.wav', '.avif'
]);

const report = {
  colisiones: [],
  nombresSospechosos: [],
  referenciasPHP: [],
  referenciasCSS: [],
  referenciasJavaScript: [],
  referenciasHTMLNoVerificables: [],
  referenciasRotas: [],
  diferenciasMayusculas: [],
  referenciasDinamicas: [],
  referenciasNoVerificables: [],
  referenciasAExclusiones: [],
  erroresLectura: [],
  archivosGrandesOmitidos: []
};

const files = [];
const dirs = [];
const texts = [];

const rel = p =>
  path.relative(ROOT, p).split(path.sep).join('/');

const inside = p => {
  const r = path.relative(ROOT, path.resolve(p));
  return r === '' ||
    (r !== '..' &&
     !r.startsWith('..' + path.sep) &&
     !path.isAbsolute(r));
};

function walk(dir) {
  let entries;

  try {
    entries = fs.readdirSync(dir, { withFileTypes: true });
  } catch (e) {
    report.erroresLectura.push({
      archivo: rel(dir),
      error: e.message
    });
    return;
  }

  for (const ent of entries) {
    const full = path.join(dir, ent.name);

    if (ent.isDirectory()) {
      if (!EXCLUDED.has(ent.name)) {
        dirs.push(full);
        walk(full);
      }
      continue;
    }

    if (!ent.isFile()) continue;

    files.push(full);

    if (!TEXT_EXT.has(path.extname(ent.name).toLowerCase())) {
      continue;
    }

    let st;

    try {
      st = fs.statSync(full);
    } catch (e) {
      report.erroresLectura.push({
        archivo: rel(full),
        error: e.message
      });
      continue;
    }

    if (st.size > MAX_BYTES) {
      report.archivosGrandesOmitidos.push({
        archivo: rel(full),
        bytes: st.size
      });
      continue;
    }

    try {
      texts.push({
        path: full,
        rel: rel(full),
        text: fs.readFileSync(full, 'utf8')
      });
    } catch (e) {
      report.erroresLectura.push({
        archivo: rel(full),
        error: e.message
      });
    }
  }
}

function lineAt(s, i) {
  return s.slice(0, i).split(/\r\n|\r|\n/).length;
}

function extIsResource(s) {
  return RESOURCE_EXT.has(
    path.extname(s.split(/[?#]/, 1)[0]).toLowerCase()
  );
}

function excludedSegment(p) {
  return path.relative(ROOT, path.resolve(p))
    .split(path.sep)
    .find(x => EXCLUDED.has(x)) || null;
}

function list(dir) {
  try {
    return fs.readdirSync(dir, { withFileTypes: true });
  } catch {
    return null;
  }
}

/**
 * Comprueba cada segmento de la ruta respetando mayúsculas,
 * incluso si el script se ejecuta en Windows.
 */
function checkPath(candidate) {
  const abs = path.resolve(candidate);

  if (!inside(abs)) {
    return {
      status: 'FUERA_DEL_PROYECTO',
      esperado: abs
    };
  }

  const segs = path.relative(ROOT, abs)
    .split(path.sep)
    .filter(Boolean);

  let cur = ROOT;
  const corrected = [];
  let mismatch = null;

  for (const wanted of segs) {
    const entries = list(cur);

    if (!entries) {
      return {
        status: 'NO_VERIFICABLE',
        esperado: rel(abs),
        motivo: `No se pudo listar ${rel(cur)}`
      };
    }

    let found = entries.find(e => e.name === wanted);

    if (!found) {
      found = entries.find(
        e => e.name.toLowerCase() === wanted.toLowerCase()
      );

      if (!found) {
        return {
          status: 'NO_ENCONTRADO',
          esperado: rel(abs),
          segmentoFaltante: wanted,
          directorioPadre: rel(cur),
          rutaParcialReal: corrected.join('/')
        };
      }

      mismatch = mismatch || {
        segmentoEsperado: wanted,
        segmentoReal: found.name,
        prefijo: corrected.join('/')
      };
    }

    cur = path.join(cur, found.name);
    corrected.push(found.name);
  }

  let st;

  try {
    st = fs.statSync(cur);
  } catch {
    return {
      status: 'NO_ENCONTRADO',
      esperado: rel(abs)
    };
  }

  if (mismatch) {
    return {
      status: 'DIFERENCIA_MAYUSCULAS',
      esperado: rel(abs),
      real: rel(cur),
      detalle: mismatch
    };
  }

  return {
    status: 'EXISTE',
    real: rel(cur),
    tipo: st.isDirectory() ? 'directorio' : 'archivo'
  };
}

function addPath(source, line, type, reference, candidate, extra = {}) {
  const check = checkPath(candidate);
  const excluded = excludedSegment(candidate);

  const item = {
    archivo: source,
    linea: line,
    tipo: type,
    referencia: reference,
    destinoCalculado: inside(candidate)
      ? rel(path.resolve(candidate))
      : path.resolve(candidate),
    ...extra,
    comprobacion: check
  };

  if (check.status === 'NO_ENCONTRADO') {
    report.referenciasRotas.push(item);
  } else if (check.status === 'DIFERENCIA_MAYUSCULAS') {
    report.diferenciasMayusculas.push(item);
  } else if (
    check.status === 'FUERA_DEL_PROYECTO' ||
    check.status === 'NO_VERIFICABLE'
  ) {
    report.referenciasNoVerificables.push(item);
  }

  if (excluded) {
    report.referenciasAExclusiones.push({
      ...item,
      carpetaExcluida: excluded
    });
  }

  return check;
}

function cleanLiteral(s) {
  return s.trim()
    .replace(/\\/g, '/')
    .replace(/^\/+/, '')
    .split(/[?#]/, 1)[0];
}

/**
 * Comprueba include/require solo cuando puede resolver de forma
 * segura un literal concatenado con __DIR__ o __FILE__.
 */
function scanPhp(f) {
  const rx =
    /\b(include_once|include|require_once|require)\b\s*(?:\(\s*)?([^;\n]+?)(?:\s*\))?\s*;/gi;

  let m;

  while ((m = rx.exec(f.text))) {
    const expr = m[2].trim();
    const line = lineAt(f.text, m.index);
    const keyword = 'PHP_' + m[1].toUpperCase();

    const literals = [
      ...expr.matchAll(/(['"])(.*?)\1/g)
    ].map(x => x[2]);

    const hasDir = /\b__DIR__\b/.test(expr);
    const hasFile = /\b__FILE__\b/.test(expr);

    if (
      !literals.length ||
      /\$[A-Za-z_]|\{\}|\b(?:sprintf|realpath)\s*\(/i.test(expr)
    ) {
      report.referenciasDinamicas.push({
        archivo: f.rel,
        linea: line,
        tipo: keyword,
        expresion: m[0].trim(),
        motivo: 'La ruta depende de una variable o expresión dinámica.'
      });
      continue;
    }

    if (literals.length !== 1 || (!hasDir && !hasFile)) {
      report.referenciasNoVerificables.push({
        archivo: f.rel,
        linea: line,
        tipo: keyword,
        referencia: literals[0] || expr,
        motivo: 'No se puede establecer con seguridad la base física de esta expresión.',
        expresion: m[0].trim()
      });
      continue;
    }

    const base = hasFile ? f.path : path.dirname(f.path);
    const literal = cleanLiteral(literals[0]);
    const candidate = path.resolve(base, literal);

    const check = addPath(
      f.rel,
      line,
      keyword,
      literals[0],
      candidate,
      {
        expresion: m[0].trim(),
        base: hasFile ? '__FILE__' : '__DIR__'
      }
    );

    report.referenciasPHP.push({
      archivo: f.rel,
      linea: line,
      tipo: keyword,
      referencia: literals[0],
      base: hasFile ? '__FILE__' : '__DIR__',
      estado: check.status,
      destino: check.real || rel(candidate)
    });
  }
}

function scanCss(f) {
  const rx =
    /(?:@import\s+(?:url\(\s*)?(['"]?)([^'"\s);]+)\1\s*\)?|url\(\s*(['"]?)([^'"\s)]+)\3\s*\))/gi;

  let m;

  while ((m = rx.exec(f.text))) {
    const ref = (m[2] || m[4] || '').trim();

    if (
      !ref ||
      /^(data:|https?:|\/\/|#|blob:|var\()/i.test(ref) ||
      !extIsResource(ref)
    ) {
      continue;
    }

    const line = lineAt(f.text, m.index);
    const candidate = path.resolve(
      path.dirname(f.path),
      cleanLiteral(ref)
    );

    const check = addPath(
      f.rel,
      line,
      'CSS_RECURSO_LOCAL',
      ref,
      candidate
    );

    report.referenciasCSS.push({
      archivo: f.rel,
      linea: line,
      referencia: ref,
      estado: check.status,
      destino: check.real || rel(candidate)
    });
  }
}

function jsCandidates(base, ref) {
  const clean = cleanLiteral(ref);
  const p = path.resolve(base, clean);
  const out = [p];

  if (!path.extname(p)) {
    for (const e of ['.js', '.mjs', '.cjs', '.json']) {
      out.push(p + e);
    }

    for (const e of ['.js', '.mjs', '.cjs', '.json']) {
      out.push(path.join(p, 'index' + e));
    }
  }

  return out;
}

function scanJs(f) {
  const patterns = [
    {
      type: 'JS_IMPORT',
      rx: /\bimport\s*(?:\(\s*(['"])([^'"]+)\1\s*\)|(?:[^;\n]*?\sfrom\s*)?(['"])([^'"]+)\3)/g
    },
    {
      type: 'JS_EXPORT_FROM',
      rx: /\bexport\s+[^;\n]*?\sfrom\s*(['"])([^'"]+)\1/g
    },
    {
      type: 'JS_REQUIRE',
      rx: /\brequire\s*\(\s*(['"])([^'"]+)\1\s*\)/g
    }
  ];

  for (const ptn of patterns) {
    let m;

    while ((m = ptn.rx.exec(f.text))) {
      const ref = m[2] || m[4];

      if (!ref) continue;

      const line = lineAt(f.text, m.index);

      if (
        !ref.startsWith('./') &&
        !ref.startsWith('../') &&
        !ref.startsWith('/')
      ) {
        report.referenciasNoVerificables.push({
          archivo: f.rel,
          linea: line,
          tipo: ptn.type,
          referencia: ref,
          motivo: 'Paquete, módulo integrado o alias; no es una ruta local relativa.'
        });
        continue;
      }

      const candidates = jsCandidates(path.dirname(f.path), ref);
      let selected = null;
      let fallback = null;

      for (const c of candidates) {
        const chk = checkPath(c);

        if (
          chk.status === 'EXISTE' ||
          chk.status === 'DIFERENCIA_MAYUSCULAS'
        ) {
          selected = { c, chk };
          break;
        }

        if (!fallback) fallback = { c, chk };
      }

      selected = selected || fallback;

      if (!selected) continue;

      const excluded = excludedSegment(selected.c);
      const chk = selected.chk;

      if (chk.status === 'NO_ENCONTRADO') {
        report.referenciasRotas.push({
          archivo: f.rel,
          linea: line,
          tipo: ptn.type,
          referencia: ref,
          destinoCalculado: rel(selected.c),
          comprobacion: chk
        });
      }

      if (chk.status === 'DIFERENCIA_MAYUSCULAS') {
        report.diferenciasMayusculas.push({
          archivo: f.rel,
          linea: line,
          tipo: ptn.type,
          referencia: ref,
          destinoCalculado: rel(selected.c),
          comprobacion: chk
        });
      }

      if (excluded) {
        report.referenciasAExclusiones.push({
          archivo: f.rel,
          linea: line,
          tipo: ptn.type,
          referencia: ref,
          carpetaExcluida: excluded
        });
      }

      report.referenciasJavaScript.push({
        archivo: f.rel,
        linea: line,
        tipo: ptn.type,
        referencia: ref,
        estado: chk.status,
        destino: chk.real || rel(selected.c)
      });
    }
  }
}

function scanHtml(f) {
  const rx =
    /\b(src|href|action|poster|data|srcset)\s*=\s*(['"])(.*?)\2/gi;

  let m;

  while ((m = rx.exec(f.text))) {
    const attr = m[1].toLowerCase();
    const value = m[3].trim();
    const line = lineAt(f.text, m.index);

    if (
      !value ||
      /^(#|https?:|\/\/|mailto:|tel:|data:|blob:|javascript:)/i.test(value)
    ) {
      continue;
    }

    if (
      /<\?=|<\?php|\$[A-Za-z_]|BASE_URL|assetBase|getRoute\s*\(/i.test(value)
    ) {
      report.referenciasDinamicas.push({
        archivo: f.rel,
        linea: line,
        tipo: 'HTML_' + attr.toUpperCase(),
        referencia: value,
        motivo: 'URL construida en tiempo de ejecución; requiere conocer BASE_URL/document root.'
      });
      continue;
    }

    if (attr === 'action' && !extIsResource(value)) continue;
    if (!extIsResource(value)) continue;

    report.referenciasHTMLNoVerificables.push({
      archivo: f.rel,
      linea: line,
      atributo: attr,
      referencia: value,
      estado: 'URL_LOCAL_NO_VERIFICADA',
      motivo: 'No se convierte automáticamente una URL del navegador en ruta física: depende del document root y de la configuración web.'
    });
  }
}

function scanNames() {
  const folded = new Map();

  for (const p of [...dirs, ...files]) {
    const r = rel(p);
    const key = r.toLowerCase();

    if (!folded.has(key)) folded.set(key, []);
    folded.get(key).push(r);

    const n = path.basename(p);

    if (
      /[<>:"|?*]/.test(n) ||
      /[. ]$/.test(n) ||
      /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?$/i.test(n)
    ) {
      report.nombresSospechosos.push({
        ruta: r,
        motivo: 'Nombre potencialmente incompatible con Windows.'
      });
    }
  }

  for (const arr of folded.values()) {
    const unique = [...new Set(arr)];

    if (unique.length > 1) {
      report.colisiones.push({ rutas: unique });
    }
  }
}

function dedupe(arr, fn) {
  const seen = new Set();

  return arr.filter(x => {
    const k = fn(x);

    if (seen.has(k)) return false;

    seen.add(k);
    return true;
  });
}

function main() {
  if (!fs.existsSync(ROOT)) {
    console.error('No se encuentra la raíz del proyecto:', ROOT);
    process.exitCode = 1;
    return;
  }

  walk(ROOT);
  scanNames();

  for (const f of texts) {
    const e = path.extname(f.path).toLowerCase();

    if (e === '.php' || e === '.phtml') scanPhp(f);
    if (e === '.css') scanCss(f);

    if (['.js', '.mjs', '.cjs'].includes(e)) {
      scanJs(f);
    }

    if (['.php', '.phtml', '.html', '.htm'].includes(e)) {
      scanHtml(f);
    }
  }

  report.referenciasRotas = dedupe(
    report.referenciasRotas,
    x => `${x.archivo}|${x.linea}|${x.tipo}|${x.referencia}|${x.destinoCalculado}`
  );

  report.diferenciasMayusculas = dedupe(
    report.diferenciasMayusculas,
    x => `${x.archivo}|${x.linea}|${x.tipo}|${x.referencia}|${x.destinoCalculado}`
  );

  report.referenciasDinamicas = dedupe(
    report.referenciasDinamicas,
    x => `${x.archivo}|${x.linea}|${x.tipo}|${x.referencia}`
  );

  report.referenciasNoVerificables = dedupe(
    report.referenciasNoVerificables,
    x => `${x.archivo}|${x.linea}|${x.tipo}|${x.referencia}|${x.motivo}`
  );

  const resumen = {
    directoriosEscaneados: dirs.length + 1,
    archivosEscaneados: files.length,
    archivosTextoAnalizados: texts.length,
    archivosGrandesOmitidos: report.archivosGrandesOmitidos.length,
    erroresLectura: report.erroresLectura.length,
    colisiones: report.colisiones.length,
    referenciasPHP: report.referenciasPHP.length,
    referenciasCSS: report.referenciasCSS.length,
    referenciasJavaScript: report.referenciasJavaScript.length,
    referenciasHTMLNoVerificables: report.referenciasHTMLNoVerificables.length,
    referenciasRotas: report.referenciasRotas.length,
    diferenciasMayusculas: report.diferenciasMayusculas.length,
    referenciasDinamicas: report.referenciasDinamicas.length,
    referenciasNoVerificables: report.referenciasNoVerificables.length,
    referenciasAExclusiones: report.referenciasAExclusiones.length,
    nombresSospechosos: report.nombresSospechosos.length
  };

  const output = {
    version: '0.5.0',
    fecha: new Date().toISOString(),
    raizProyecto: ROOT,
    modo: 'SOLO LECTURA',
    exclusionesDeRecorrido: [...EXCLUDED],
    resumen,
    notas: [
      'No se ejecuta código de la aplicación.',
      'Se comprueban rutas PHP cuando el include/require usa un literal junto a __DIR__ o __FILE__.',
      'Las URL HTML se clasifican como no verificables: requieren conocer document root y BASE_URL.',
      'Las carpetas excluidas no se recorren, pero una referencia directa puede comprobarse contra el sistema de archivos.',
      'Los imports de paquetes/alias JS se clasifican como no verificables.',
      'Un hallazgo es un candidato a revisión; no se deben aplicar cambios automáticamente.',
      'El único archivo escrito es el JSON de este informe dentro de tools/path-audit.'
    ],
    ...report
  };

  try {
    fs.writeFileSync(OUT, JSON.stringify(output, null, 2), 'utf8');
  } catch (e) {
    console.error('No se pudo escribir el informe:', e.message);
    process.exitCode = 1;
    return;
  }

  console.log(
    '==============================================\n' +
    ' AUDITORÍA DE NOMBRES Y REFERENCIAS V0.5\n' +
    '=============================================='
  );

  console.log('Raíz:', ROOT);
  console.log('Modo: solo lectura\n');
  console.log('RESULTADOS\n----------------------------------------------');

  for (const [k, v] of Object.entries(resumen)) {
    console.log(`${k}: ${v}`);
  }

  console.log('\nInforme:', OUT);

  if (report.referenciasRotas.length) {
    console.log('\nPRIMERAS REFERENCIAS ROTAS:');

    for (const x of report.referenciasRotas.slice(0, 40)) {
      console.log(
        `- ${x.archivo}:${x.linea} [${x.tipo}] ${x.referencia} -> ${x.destinoCalculado}`
      );
    }
  }

  if (report.diferenciasMayusculas.length) {
    console.log('\nPRIMERAS DIFERENCIAS DE MAYÚSCULAS:');

    for (const x of report.diferenciasMayusculas.slice(0, 40)) {
      console.log(
        `- ${x.archivo}:${x.linea} ${x.referencia} -> ${x.comprobacion.real || x.destinoCalculado}`
      );
    }
  }

  console.log('\nNo se modificaron archivos de la aplicación.');
}

main();
