#!/usr/bin/env python3
"""No-network production parser fixtures for the existing 2017 archive."""
import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from importlib.machinery import SourceFileLoader
m = SourceFileLoader('nry_stays_archive', str(Path(__file__).resolve().parents[1] / 'scripts/verify-legacy-stays-archive-entry-public.py')).load_module()
block = ('<section data-nry-legacy-stays-entry="v1"><h2>Vyberte pobyt</h2>'
         + '<nav>' + ''.join('<a href="' + url + '">Zobrazit</a>' for url in m.DESTS)
         + '</nav></section>')
style = '<link rel="stylesheet" href="https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/nry-legacy-stays-entry-v1.css?ver=1.1.0">'
head = '<html><head>' + style + '</head><body><h1>Ubytování v ČR</h1>'
listing = '<div><h2><a href="/harvanek/">Rekreační dům Harvánek – Rybařina rybník Sykovec</a></h2></div>'
good = head + block + listing + '</body></html>'
assert m.check(good) == 'NRY_LEGACY_STAYS_ENTRY_PUBLIC_VERIFIED'
for bad in (
    head + listing + block,
    head + block + block + listing,
    head + block.replace(m.DESTS[0], 'https://example.net/') + listing,
    head.replace(style, '') + block + listing,
    head.replace('Ubytování v ČR', 'Jiná stránka') + block + listing,
    head + block + '<h2>Jiný první výsledek</h2>',
):
    try:
        m.check(bad)
    except AssertionError:
        pass
    else:
        raise AssertionError('Invalid rendered archive passed public contract')
print('NRY_LEGACY_STAYS_PUBLIC_PARSER_OFFLINE_VERIFIED')
