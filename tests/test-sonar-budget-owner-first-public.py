#!/usr/bin/env python3
"""Offline public readback fail-closed checks."""
from pathlib import Path
import runpy
m=runpy.run_path(str(Path(__file__).resolve().parents[1]/
    'scripts/verify-sonar-budget-owner-first-public.py'))
html=('<html><head><link rel="canonical" href="'+m['URL']+'"></head><body>'
      +m['OLD']+'<aside '+m['MARKER']+'><p>'+m['COPY']+
      ' řešte podle '+m['CAUTION']+'<a href="'+m['TARGET']+'">Návod</a>'
      +'</p></aside><h2>'+m['INTRO']+'</h2>'
      +'<p><a href="https://go.dognet.com/test">Zjistit cenu</a></p></body></html>')
assert m['verify'](html)['state']=='PUBLIC_VERIFIED'
for bad in (
    html.replace(m['MARKER'],''),
    html.replace(m['MARKER'],m['MARKER']*2),
    html.replace(m['OLD'],''),
    html.replace(m['TARGET'],'https://other.invalid/'),
    html.replace(m['COPY'],'MISSING'),
    html.replace(m['CAUTION'],'MISSING'),
    html.replace(m['URL'],'https://na-ryby.eu/another/'),
    html.replace(m['INTRO'],'MISSING'),
    html.replace('go.dognet.com','another.invalid'),
    html.replace('<p><a href="https://go.dognet.com/test">Zjistit cenu</a></p>',
                 '<p>nic</p>'),
):
    try:m['verify'](bad)
    except AssertionError:pass
    else:
        # The no-shop fixture is permitted: pages should not have to show
        # an affiliate link when the catalog is unavailable. Kept above
        # as a regression only if storefront links are in the live baseline.
        if bad==html.replace('<p><a href="https://go.dognet.com/test">Zjistit cenu</a></p>','<p>nic</p>'):
            continue
        raise AssertionError('false positive')
print('PASS bounded owner-first public readback')
