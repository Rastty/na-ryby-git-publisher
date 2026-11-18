#!/usr/bin/env python3
"""Fixture-only proof: no HTTP, WordPress, analytics or merchant calls."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
module = runpy.run_path(str(root / 'scripts/verify-fishing-stays-click-public-v1.py'))
source = (root / 'editorial/fishing-stays-cz-20261118-recommendation-blocks.html').read_text()
asset = (root / 'assets/nry-buyer-clicks-v1.js').read_bytes()
origin = module['ORIGIN']
post = module['POST']
script = origin + module['ASSET'] + '?' + module['VERSION']


def document(version=None):
    tag = '<script src="' + origin + module['ASSET'] + '?' + version + '"></script>' if version else ''
    return ('<!doctype html><html><head><link rel="canonical" href="' +
            origin + post + '">' + tag + '</head><body><main>' + source +
            '<h1>Rybářské pobyty v přírodě: jak vybrat ubytování, které vám u vody skutečně sedne</h1>' +
            '<p>Rybářský pobyt není jen o tom, kde složíte pruty.</p>' +
            '</main></body></html>').encode()


preflight = module['verify'](document())
assert preflight['exactSponsoredOffers'] == 5
assert preflight['originalArticlePreserved']
assert preflight['syntheticClicks'] == 0
assert preflight['acceptedRealClicks'] is None
assert module['verify'](document(module['PREVIOUS_VERSION']))['scriptVerified'] is False
assert module['verify'](document(module['VERSION']), deployed=True,
                        public_js=asset, reviewed_js=asset)['scriptVerified']


def blocked(html, *, js=asset, deployed=False):
    try:
        module['verify'](html, deployed=deployed, public_js=js,
                         reviewed_js=asset)
    except AssertionError:
        return
    raise AssertionError('unsafe fishing stay publication evidence accepted')


live = document(module['VERSION'])
blocked(live, deployed=True, js=b'not the installed asset')
blocked(document(module['PREVIOUS_VERSION']), deployed=True)
blocked(document('ver=1.12.0'))
blocked(document().replace(origin.encode(), b'https://evil.example', 1))
blocked(document().replace(b'data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"',
                           b'data-nry-editorial-edit="other-marker"'))
blocked(document().replace(b'</main>', source.encode() + b'</main>'))
blocked(document().replace(b'rel="sponsored nofollow noopener"',
                           b'rel="noopener"', 1))
blocked(document().replace(b'chid=5QoXiSqx', b'chid=wrong', 1))
blocked(document().replace(b'a_box=j3d7tsgc', b'a_box=wrong', 1))
blocked(document().replace(b'hportal-pension-u-vlcku', b'hportal-wrong-property', 1))
blocked(document().replace(b'target="_blank"', b'target="_self"', 1))
blocked(document().replace('<h1>Rybářské pobyty'.encode(), b'<h1>Other title'))
blocked(document().replace('<h1>Rybářské pobyty'.encode(), '<p>Rybářské pobyty'.encode()))
blocked(live.replace(b'</head>',
    ('<script src="' + script + '"></script></head>').encode()), deployed=True)
print('PASS: live stay links, marker, original article and JS exact-byte checks fail closed')
