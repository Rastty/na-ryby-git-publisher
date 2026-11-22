#!/usr/bin/env python3
"""Unit fixtures only; no network, real browser click or WordPress write."""
import html
import importlib.util
from pathlib import Path

root = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('jm_public', root / 'scripts/verify-jizni-morava-stays-public-v1.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
js = (root / 'assets/nry-buyer-clicks-v1.js').read_bytes()
css = (root / 'assets/nry-jizni-morava-stays-v1.css').read_bytes()


def base():
    return ('<html><head><link rel="canonical" href="'+m.ORIGIN+m.PATH+'"></head>'
            '<body><h1>Rybářská dovolená jižní morava</h1>'
            '<h2>Chata Brněnská přehrada</h2><h2>Rybářský wellness srub</h2>'
            '<div class="agoda-booking-box"><a href="'+m.LEGACY_AGODA+'">Olomouc</a></div>'
            '</body></html>').encode()


articles = ''.join('<article data-nry-offer-id="'+ident+'"><a href="'+html.escape(url,quote=True)
                   +'" target="_blank" rel="sponsored nofollow noopener">Ověřit pobyt</a></article>'
                   for ident,url in m.OFFERS.items())
block = ('<section data-nry-jm-stays="v1">'
         '<a href="'+m.ORIGIN+'/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/">Revír</a>'
         '<a href="https://www.rybnikarstvipohorelice.cz/dyje-6/">Pravidla</a>'
         +articles+'</section><p data-nry-jm-archive="v1">Archiv 2016–18</p>')
head = ('<link rel="stylesheet" href="'+m.ORIGIN+m.CSS+'?ver=1.0.0">'
        '<script src="'+m.ORIGIN+m.JS+'?ver=1.11.7"></script>')
ready = base().replace(b'</head>', (head+'</head>').encode()).replace(
    b'<body>', ('<body>'+block).encode())
ready = ready.replace(('<div class="agoda-booking-box"><a href="'+m.LEGACY_AGODA+'">Olomouc</a></div>').encode(),b'')
assert m.verify(ready, deployed=False)['status'] == 'REGIONAL_PUBLISHED_BASELINE_VERIFIED'
assert m.verify(ready.replace(b'ver=1.11.7',b'ver=1.11.6'), deployed=False)['status'] == (
    'REGIONAL_PUBLISHED_BASELINE_VERIFIED')
assert m.verify(ready, deployed=True, public_js=js, reviewed_js=js,
                public_css=css, reviewed_css=css)['status'] == 'REGIONAL_PUBLIC_VERIFIED'


def blocked(doc=ready, wrong_js=js, wrong_css=css):
    try:
        m.verify(doc, deployed=True, public_js=wrong_js, reviewed_js=js,
                 public_css=wrong_css, reviewed_css=css)
    except AssertionError:
        return
    raise AssertionError('malformed or unreviewed proof must be rejected')


blocked(ready.replace(b'data-nry-jm-stays="v1"',b'data-nry-jm-stays="v2"'))
blocked(ready.replace(b'data-nry-jm-archive="v1"',b'data-nry-jm-archive="v2"'))
blocked(ready.replace(b'utm_content=stay_12166_termal_musov',b'utm_content=bad'))
blocked(ready.replace(b'&amp;a_box=j3d7tsgc',b'&amp;a_box=not_owner'))
blocked(ready.replace(b'Go',b'Tracked',1),wrong_js=js+b'rogue')
blocked(ready,wrong_css=css+b'rogue')
blocked(ready.replace(b'ver=1.11.7',b'ver=1.11.6'))
blocked(ready.replace('Chata Brněnská přehrada'.encode(), 'Smazaná chata'.encode()))
try:
    m.verify(base(), deployed=False)
except AssertionError:
    pass
else:
    raise AssertionError('old unreviewed regional publication cannot pass current baseline')
print('PASS: 12166 page, exact three merchants, Core JS bytes, CSS and archive safety fail closed')
