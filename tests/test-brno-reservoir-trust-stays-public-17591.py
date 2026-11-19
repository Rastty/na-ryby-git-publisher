#!/usr/bin/env python3
"""Offline public verifier tests: exact offers, source drift and archive warning."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1]
                            / 'scripts/verify-brno-reservoir-trust-stays-public-17591.py'))
verify = module['verify']
old = '''<!doctype html><html><head><link rel="canonical" href="https://na-ryby.eu/chata-brnenska-prehrada/"></head><body class="postid-17591"><article>Historical archive only</article></body></html>'''.encode()
assert verify(old, deployed=False)['status'] == 'BRNO_RESERVOIR_STAYS_PREFLIGHT_READY'
css_only = old.replace(b'</head>',
    b'<link rel="stylesheet" href="https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/nry-brno-reservoir-stays-17591-v1.css?ver=1.0.0"></head>')
assert verify(css_only, deployed=False, css=b'a'*700, reviewed_css=b'a'*700,
              allow_css_only=True)['status'] == 'BRNO_RESERVOIR_STAYS_CSS_ONLY_REPAIR_READY'


def reject(html, deployed=False, css=b'', reviewed_css=b'', allow_css_only=False):
    try:
        verify(html, deployed=deployed, css=css, reviewed_css=reviewed_css,
               allow_css_only=allow_css_only)
    except (AssertionError, ValueError):
        return
    raise AssertionError('public drift passed')


reject(old.replace(b'postid-17591', b'postid-17592'))
reject(old.replace(b'na-ryby.eu/chata-brnenska-prehrada/', b'na-ryby.eu/other/'))
reject(old.replace(b'Historical', b'<section data-nry-brno-stays="v1" id="nry-brno-stays-17591">X</section>Historical'))
reject(old, deployed=True)
reject(css_only, deployed=False)
reject(css_only, deployed=False, css=b'a'*700, reviewed_css=b'b'*700, allow_css_only=True)
print('PASS: Brno reservoir public verifier fail-closed')
