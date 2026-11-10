#!/usr/bin/env python3
"""Offline verifier regressions, no network."""
from pathlib import Path
import runpy

m=runpy.run_path(str(Path(__file__).resolve().parents[1] /
    "scripts/verify-carp-pillar-choice-118541-public.py"))
page='<html><head><link rel="canonical" href="'+m['URL']+'"></head><body>' + (
    '<main><span '+m['SOURCE_MARKER']+'></span><p><span '+m['MARKER']+'></span>'+
    m['SAFE']+' '+m['CAUTION']+'</p><a href="https://www.chytapust.cz/keep">'
    'Prověřit nabídku</a><h2>Komu tenhle článek pomůže</h2></main></body></html>')
assert m['verify'](page)['state']=='PUBLIC_VERIFIED'
for broken in (
    page.replace(m['MARKER'],''),
    page.replace(m['MARKER'],m['MARKER']*2),
    page.replace(m['URL'],'https://na-ryby.eu/wrong/'),
    page.replace(m['SAFE'],'MISSING'),
    page.replace(m['CAUTION'],'MISSING'),
    page.replace('</main>',m['OLD']+'</main>'),
    page.replace('https://www.chytapust.cz/keep','https://example.org/wrong'),
    page.replace('Komu tenhle článek pomůže','MISSING'),
):
    try: m['verify'](broken)
    except AssertionError: pass
    else: raise AssertionError('MUST_FAIL_CLOSED')
print('PASS carp-pillar-choice-public-offline')
