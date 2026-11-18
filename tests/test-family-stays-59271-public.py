#!/usr/bin/env python3
"""Offline parser and no-false-success fixture for the family public readback."""
import importlib.util
from pathlib import Path

SOURCE = Path(__file__).resolve().parents[1] / 'scripts/verify-family-stays-59271-public.py'
spec = importlib.util.spec_from_file_location('family_readback', SOURCE)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def page(*, rel='sponsored nofollow noopener', extra=''):
    return (f'<html><head><link rel="canonical" href="{module.ORIGIN + module.PATH}">'
            f'<link rel="stylesheet" href="{module.ORIGIN + module.CSS}?ver=1.0.0">'
            '</head><body class="postid-59271">'
            '<h3>Rybolov na řece Vltavě</h3>'
            '<h3>Rybářská osada Dolní Vltavice</h3>'
            '<section data-nry-family-stays="v1">'
            f'<article data-nry-family-offer="lipno-kohoutu"><a href="{module.HPORTAL_LIPNO}"'
            f' rel="{rel}" target="_blank">Lipno</a></article>'
            f'<article data-nry-family-offer="slapy-janata"><a href="{module.HPORTAL_SLAPY}"'
            ' rel="sponsored nofollow noopener" target="_blank">Slapy</a></article>'
            f'<a href="{module.STAYS}">Další pobyty</a>'
            '</section><p>Zde je pár tipů, kam se vydat na ryby se svými dětmi:</p>'
            f'{extra}</body></html>').encode('utf-8')


css = b'/* fixture CSS, reviewed and byte-identical */' + b'x' * 600
ok = module.public_assertions(page(), deployed=True, css_public=css, css_reviewed=css)
assert ok['status'] == 'FAMILY_STAYS_PUBLIC_VERIFIED'
assert ok['affiliateOffers'] == 2
assert ok['realClicks'] is None and ok['paidCommissions'] is None


def should_fail(candidate, reason):
    try:
        module.public_assertions(candidate, deployed=True, css_public=css, css_reviewed=css)
    except AssertionError as exc:
        assert reason in str(exc), f'unexpected reason: {exc}'
        return
    raise AssertionError('should have rejected public candidate: ' + reason)


should_fail(page(rel='noopener'), 'FAMILY_PARTNER_DESTINATION_OR_REL_DRIFT')
should_fail(page().replace(module.HPORTAL_LIPNO.encode(), b'https://merchant.example/wrong'),
            'FAMILY_PARTNER_DESTINATION_OR_REL_DRIFT')
should_fail(page(extra='<section data-nry-family-stays="v1"></section>'),
            'FAMILY_BLOCK_NOT_ONCE')
should_fail(page().replace(b'Zde je p\xc3\xa1r tip\xc5\xaf', b'Source removed'),
            'FAMILY_CTA_BURIED_AFTER_INTRO')
try:
    module.public_assertions(page(), deployed=False)
except AssertionError as exc:
    assert 'FAMILY_ALREADY_DEPLOYED' in str(exc)
else:
    raise AssertionError('preflight must refuse already-published marker')

print('PASS: family live verifier rejects link, rel, duplicate or placement drift')
