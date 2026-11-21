#!/usr/bin/env python3
"""Bounded no-network HTMLParser negative fixtures."""
import sys
from pathlib import Path
from importlib.machinery import SourceFileLoader
m = SourceFileLoader('nry_water_public',
    str(Path(__file__).resolve().parents[1] / 'scripts/verify-private-waters-concrete-choice-public.py')).load_module()
css = '<link href="https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/nry-private-waters-examples-v1.css" rel="stylesheet">'
section = ('<section data-nry-private-water-examples="v1"><h2>Čtyři vody</h2>'
    + ''.join('<a href="' + url + '">Prohlédnout</a>' for url in m.DESTS)
    + '</section>')
header = '<html><head>' + css + '</head><body>'
old = '<h2>Podle čeho poznat dobrý soukromý revír</h2>'
good = header + section + old + '</body></html>'
assert m.verify(good) == 'NRY_PRIVATE_WATERS_CONCRETE_CHOICES_PUBLIC_VERIFIED'
for bad in (
    header + old + section,
    header + section + section + old,
    header + section.replace(m.DESTS[0], 'https://merchant.example/') + old,
    header.replace(css, '') + section + old,
    header + section + '<h2>Jiný článek</h2>',
    header + section.replace('data-nry-private-water-examples="v1"', 'data-other="x"') + old,
):
    try:
        m.verify(bad)
    except AssertionError:
        pass
    else:
        raise AssertionError('Bad private-water public fixture passed')
print('NRY_PRIVATE_WATERS_PUBLIC_PARSER_OFFLINE_VERIFIED')
