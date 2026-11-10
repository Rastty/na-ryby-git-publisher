#!/usr/bin/env python3
"""Fail-closed independent render proof fixtures, no public network."""
from pathlib import Path
import runpy
x=runpy.run_path(str(Path(__file__).resolve().parents[1]/
  'scripts/verify-sonar-pillar-merchant-neutral-118553-public.py'))
base=('<html><head><link rel="canonical" href="'+x['URL']+
 '"></head><body><section '+x['SOURCE']+'></section>'+
 '<p '+x['PRACTICAL']+'>Existing owner guide</p>'
 +'<h2>Jak vybrat echolot pro rybaření:</h2>'+
 '<p>'+x['OLD']+'</p>'
 +'<p><a href="https://www.chytapust.cz/Echoloty/">Prověřit nabídku</a></p>'
 +'<p>'+('Source-pinned editor content. '*900)+'</p></body></html>')
assert x['verify'](base,False)['state']=='PUBLIC_PREFLIGHT_VERIFIED'
new=base.replace(x['OLD'],'<span '+x['MARKER']+'></span> '+x['NEW']+
 ' o aktuálním pořadí prodejců. '+x['CAUTION'])
assert x['verify'](new,True)['state']=='PUBLIC_VERIFIED'
for invalid in (
    new.replace(x['MARKER'],''),
    new.replace(x['MARKER'],x['MARKER']*2),
    new.replace(x['SOURCE'],''),
    new.replace(x['PRACTICAL'],''),
    new.replace(x['CAUTION'],''),
    new.replace('https://www.chytapust.cz/','https://another.invalid/'),
    new.replace('Prověřit nabídku','MISSING'),
    new.replace('href="'+x['URL']+'"','href="https://na-ryby.eu/another/"'),
    new.replace(x['NEW'],x['OLD'])
):
    try:x['verify'](invalid,True)
    except AssertionError:pass
    else:raise AssertionError('PUBLIC_FALSE_POSITIVE')
print('PASS exact sonar seller-neutral public proof and drift rejection')
