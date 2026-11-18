#!/usr/bin/env python3
"""Fixture-only proof for reversible visual styling; no HTTP."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
module = runpy.run_path(str(root / 'scripts/verify-fishing-stays-cta-59891-public.py'))
source = (root / 'editorial/fishing-stays-cz-20261118-recommendation-blocks.html').read_text()
css = (root / 'assets/nry-fishing-stays-cta-59891-v1.css').read_bytes()
origin = module['SITE']
post = module['POST']
href = origin + module['CSS'] + '?' + module['VERSION']


def document(*, style=False):
    tag = f'<link rel="stylesheet" href="{href}">' if style else ''
    return (f'<!doctype html><html><head><link rel="canonical" href="{origin}{post}">'
            f'{tag}</head><body>{source}'
            '<h1>Rybářské pobyty v přírodě: jak vybrat pobyt u vody</h1>'
            '</body></html>').encode()


assert module['verify'](document())['exactSponsoredOffers'] == 5
assert module['verify'](document(style=True), deployed=True,
                        public_css=css, reviewed_css=css)['styleByteIdentical']


def blocked(page, *, deployed=False, public_css=b''):
    try:
        module['verify'](page, deployed=deployed, public_css=public_css, reviewed_css=css)
    except AssertionError:
        return
    raise AssertionError('unsafe published CTA styling accepted')


blocked(document(style=True))
blocked(document(), deployed=True, public_css=css)
blocked(document(style=True), deployed=True, public_css=b'mismatched')
blocked(document(style=True).replace(b'5QoXiSqx', b'unknown', 1), deployed=True, public_css=css)
blocked(document(style=True).replace(b'2462030', b'1111111', 1), deployed=True, public_css=css)
blocked(document(style=True).replace(b'rel="stylesheet"', b'rel="preload"'),
        deployed=True, public_css=css)
blocked(document(style=True).replace(b'https://na-ryby.eu/wp-content/plugins',
    b'https://example.invalid/wp-content/plugins'), deployed=True, public_css=css)
blocked(document(style=True) + document(style=True), deployed=True, public_css=css)
print('PASS: approved 59891 CTA CSS source and public byte checks fail closed')
