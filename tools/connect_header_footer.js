const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const HTML_DIR = path.join(ROOT, 'html');
const HEADER_PATH = path.join(HTML_DIR, 'header.php');
const FOOTER_PATH = path.join(HTML_DIR, 'footer.php');

if (!fs.existsSync(HEADER_PATH) || !fs.existsSync(FOOTER_PATH)) {
  console.error('header.php or footer.php not found in html/');
  process.exit(1);
}

const headerInclude = "<?php include __DIR__ . '/header.php'; ?>";
const footerInclude = "<?php include __DIR__ . '/footer.php'; ?>";

const files = fs.readdirSync(HTML_DIR).filter(f => f.endsWith('.php') && f !== 'header.php' && f !== 'footer.php');

files.forEach(file => {
  const full = path.join(HTML_DIR, file);
  let text = fs.readFileSync(full, 'utf8');
  const bak = full + '.hf.bak';
  if (!fs.existsSync(bak)) fs.writeFileSync(bak, text, 'utf8');

  while (/<!DOCTYPE html>\s*<!DOCTYPE html>/i.test(text)) {
    text = text.replace(/<!DOCTYPE html>\s*<!DOCTYPE html>/i, '<!DOCTYPE html>');
  }
  text = text.replace(/^\s+/, '');

  // remove existing <header ...>...</header>
  text = text.replace(/<header[\s\S]*?<\/header>/i, '');
  // remove existing nav.pie-principal
  text = text.replace(/<nav[^>]*class=["'][^"']*pie-principal[^"']*["'][\s\S]*?<\/nav>/i, '');
  // remove existing PHP includes before reinserting them
  text = text.replace(/<\?php\s+include\s+__DIR__\s*\.\s*['"]\/header\.php['"]\s*;\s*\?>\s*/i, '');
  text = text.replace(/<\?php\s+include\s+__DIR__\s*\.\s*['"]\/footer\.php['"]\s*;\s*\?>\s*/i, '');

  // insert header after opening <body ...>
  const bodyOpen = text.match(/<body\b[^>]*>/i);
  if (bodyOpen) {
    text = text.replace(/<body\b[^>]*>/i, match => match + '\n' + headerInclude + '\n');
  } else {
    // if no body tag, prepend header
    text = headerInclude + '\n' + text;
  }

  // insert footer before closing </body>
  if (text.match(/<\/body>/i)) {
    text = text.replace(/<\/body>/i, match => '\n' + footerInclude + '\n' + match);
  } else {
    // append footer
    text = text + '\n' + footerInclude;
  }

  // write back
  fs.writeFileSync(full, text, 'utf8');
  console.log('Updated:', full);
});

console.log('Done. Processed', files.length, 'files. Backups: *.hf.bak');
