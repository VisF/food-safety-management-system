#!/usr/bin/env python3
import glob
import os
import re

php_files = glob.glob(os.path.join('html', '*.php'))
if not php_files:
    print('No PHP files found in html/ folder')
    raise SystemExit(1)

for path in php_files:
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()
    # preserve a single DOCTYPE if present
    m = re.match(r"(?is)^(\s*<!doctype[^>]*>\s*)", text)
    doctype = m.group(1).strip() if m else ''
    text = re.sub(r"(?is)^(?:\s*<!doctype[^>]*>\s*)+", '', text)
    # backup original
    bak = path + '.bak'
    if not os.path.exists(bak):
        with open(bak, 'w', encoding='utf-8') as fb:
            fb.write(text)
    # keep PHP tags intact; only normalize the document wrapper
    out = ((doctype + '\n') if doctype else '') + text.strip() + '\n'
    with open(path, 'w', encoding='utf-8') as f:
        f.write(out)
    print(f'Formatted: {path}')
print('\nDone formatting {} files.'.format(len(php_files)))
