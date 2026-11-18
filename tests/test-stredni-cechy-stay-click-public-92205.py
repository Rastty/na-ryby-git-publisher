#!/usr/bin/env python3
"""No-network negative tests; no synthetic browser events reach GA4."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-stredni-cechy-stay-click-public-92205.py'))
approved = m['PARTNER'].replace('&', '&amp;')
source = ('<html><head><link rel="canonical" href="' + m['ORIGIN'] + m['PAGE'] + '"></head>'
    '<body class="single-post postid-92205"><section data-nry-sc-stay="v1">'
    '<article data-nry-sc-offer="slapy-janata"><a href="' + approved + '" '
    'rel="sponsored nofollow noopener" target="_blank">Ubytování</a></article>'
    '<a href="' + m['ORIGIN'] + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/">Pobyty</a>'
    '</section></body></html>').encode()
def with_script(ver):
    return source.replace(b'</head>', ('<script src="' + m['ORIGIN'] + m['ASSET'] + '?ver=' + ver + '"></script></head>').encode())
assert m['verify'](source, deployed=False)['status'] == 'STREDNI_CECHY_CLICK_PREFLIGHT_READY'
assert m['verify'](with_script('1.11.3'), deployed=False)['status'] == 'STREDNI_CECHY_CLICK_PREFLIGHT_READY'
js = (root / 'assets/nry-buyer-clicks-v1.js').read_bytes()
assert m['verify'](with_script('1.11.4'), deployed=True, public_js=js, reviewed_js=js)['scriptByteIdentical']
def blocked(html, *, j=js):
    try:
        m['verify'](html, deployed=True, public_js=j, reviewed_js=js)
    except AssertionError:
        return
    raise AssertionError('Unexpected acceptance of tampered source/event')
blocked(source)
blocked(with_script('1.11.3'))
blocked(with_script('1.11.4').replace(b'postid-92205', b'postid-59271'))
blocked(with_script('1.11.4').replace(b'data-nry-sc-offer="slapy-janata"',b'data-nry-sc-offer="other"'))
blocked(with_script('1.11.4').replace(b'chid=5QoXiSqx', b'chid=other'))
blocked(with_script('1.11.4').replace(b'rel="sponsored nofollow noopener"',b'rel="nofollow"'))
blocked(with_script('1.11.4'), j=js+b'tampered')
print('PASS: Stredni Cechy 92205 exact public click-script verification, no synthetic clicks')
