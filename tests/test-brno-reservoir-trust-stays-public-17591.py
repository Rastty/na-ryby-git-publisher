#!/usr/bin/env python3
"""Offline public verifier tests: exact offers, source drift and archive warning."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1]
                            / 'scripts/verify-brno-reservoir-trust-stays-public-17591.py'))
verify = module['verify']
old = '''<!doctype html><html><head><link rel="canonical" href="https://na-ryby.eu/chata-brnenska-prehrada/"></head><body class="postid-17591"><article>Historical archive only</article></body></html>'''.encode()
assert verify(old, deployed=False)['status'] == 'BRNO_RESERVOIR_STAYS_PREFLIGHT_READY'


def reject(html, deployed=False, css=b'', reviewed_css=b''):
    try:
        verify(html, deployed=deployed, css=css, reviewed_css=reviewed_css)
    except (AssertionError, ValueError):
        return
    raise AssertionError('public drift passed')


reject(old.replace(b'postid-17591', b'postid-17592'))
reject(old.replace(b'na-ryby.eu/chata-brnenska-prehrada/', b'na-ryby.eu/other/'))
reject(old.replace(b'Historical', b'<section data-nry-brno-stays="v1" id="nry-brno-stays-17591">X</section>Historical'))
reject(old, deployed=True)
print('PASS: Brno reservoir public verifier fail-closed')
