#!/usr/bin/env python3
"""Offline fixture validation; never performs HTTP requests."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from verify_legacy_map_choice_public import check, LINKS, MAP_TOKEN

section = ('<section class="nry-map-discovery" data-nry-map-discovery="v1">'
           '<h2>Ověřit revír</h2><ul>'
           + ''.join('<li><a href="' + x + '">Odkaz</a></li>' for x in LINKS)
           + '</ul></section>')
iframe = '<iframe src="https://' + MAP_TOKEN + '" width="800"></iframe>'
preamble = '<!doctype html><html><body><h1>Rybářská dovolená</h1>'
assert check(preamble + section + iframe + '</body></html>') == 'NRY_LEGACY_MAP_CHOICE_PUBLIC_VERIFIED'
for invalid in (
    preamble + iframe + section,
    preamble + section + section + iframe,
    preamble + section.replace(LINKS[1], 'https://invalid.example/') + iframe,
    preamble + section + '<iframe src="https://example.org/map"></iframe>',
):
    try:
        check(invalid)
    except AssertionError:
        pass
    else:
        raise AssertionError('fail-closed negative fixture accepted')
print('NRY_LEGACY_MAP_CHOICE_OFFLINE_VERIFIED')
