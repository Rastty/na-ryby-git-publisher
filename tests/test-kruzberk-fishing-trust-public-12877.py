#!/usr/bin/env python3
"""No-network fail-closed tests for the visible Kružberk public notice."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-kruzberk-fishing-trust-public-12877.py'))
head = ('<html><head><link rel="canonical" href="' + m['ORIGIN'] + m['PAGE'] + '"></head>'
        '<body class="single-post postid-12877">')
base = (head + '<article><h1>Ubytování u Moravice</h1></article></body></html>').encode()
assert m['verify'](base, deployed=False)['status'] == 'KRUZBERK_TRUST_PREFLIGHT_READY'
style = '<link rel="stylesheet" href="' + m['ORIGIN'] + m['CSS'] + '?' + m['CSS_QUERY'] + '">'
notice = ('<section data-nry-kruzberk-trust="v1">'
          '<h2>V samotné přehradě Kružberk se rybařit nesmí</h2>'
          '<a href="' + m['OBEC'] + '" rel="noopener" target="_blank">Obec</a>'
          '<a href="' + m['CRS'] + '" rel="noopener" target="_blank">ČRS</a></section>')
ready = (head.replace('</head>', style + '</head>') + notice + '</body></html>').encode()
css = (root / 'assets/nry-kruzberk-trust-12877-v1.css').read_bytes()
def deployed(page, asset=css):
    return m['verify'](page, deployed=True, css=asset, reviewed_css=css)
assert deployed(ready)['warningVisible'] is True
def blocked(page, asset=css):
    try:
        deployed(page, asset)
    except AssertionError:
        return
    raise AssertionError('unsafe public notice drift accepted')
blocked(base)
blocked(ready.replace(b'postid-12877', b'postid-12876'))
blocked(ready.replace(m['PAGE'].encode(), b'/wrong-article/'))
blocked(ready.replace(m['OBEC'].encode(), b'https://example.com/'))
blocked(ready.replace(m['CRS'].encode(), b'https://example.com/'))
blocked(ready.replace(b'rel="noopener"', b'rel="nofollow"'))
blocked(ready.replace(b'data-nry-kruzberk-trust="v1"', b'data-nry-kruzberk-trust="v2"'))
blocked(ready.replace(b'</body>', notice.encode() + b'</body>'))
blocked(ready.replace(b'ver=1.0.0', b'ver=1.0.1'))
blocked(ready, css + b'drift')
print('PASS: Kruzberk exact public warning, sources and CSS fail closed')
