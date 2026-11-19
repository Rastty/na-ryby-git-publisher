#!/usr/bin/env python3
"""Offline exact-object public readback and fail-closed synthetic fixture."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from importlib.machinery import SourceFileLoader

mod = SourceFileLoader(
    'cyklochata_hauzi_verify',
    str(Path(__file__).resolve().parents[1] /
        'scripts/verify-cyklochata-hauzi-offer-public-11983.py')
).load_module()
base = '<html>' + 'x' * 11000 + (
    '<section data-nry-cyklochata-offer="v1">'
    '<a href="' + mod.SAFE_TRACKING + '" rel="sponsored nofollow noopener">'
    'Zjistit termín Cyklochaty na Hauzi</a>'
    '<a href="https://www.dovolenavranov.cz/cyklochata/">Provozovatel</a></section>'
    '<aside><a href="' + mod.SAFE_TRACKING + '" rel="sponsored nofollow noopener">'
    'Ověřit konkrétní Cyklochatu</a></aside></html>'
)
assert mod.verify_html(base)['samePropertyOffers'] == 2
for corrupted in (
    base.replace(mod.MARKER, ''),
    base.replace(mod.SAFE_TRACKING, 'https://www.hauzi.cz/cyklochata', 1),
    base + mod.OLD_LINK,
    base + mod.OLD_CLAIM,
    base.replace('https://www.dovolenavranov.cz/cyklochata/', ''),
):
    try:
        mod.verify_html(corrupted)
        raise RuntimeError('FAIL_OPEN')
    except AssertionError:
        pass
print('PASS: Cyklochata Hauzi public checker offline fail-closed')
