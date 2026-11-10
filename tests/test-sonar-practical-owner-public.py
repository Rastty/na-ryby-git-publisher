#!/usr/bin/env python3
"""No-network verifier regression."""
import runpy
from pathlib import Path
v=runpy.run_path(str(Path(__file__).parents[1]/
    'scripts/verify-sonar-practical-owner-49275-public.py'))
panel=('<section '+v['MARKER']+'>'
       +''.join('<p>'+text+'</p>' for text in v['SAFE'])+
       '<a href="'+v['PILLAR']+'">Průvodce</a><a href="'+v['BUDGET']+
       '">Rozpočet</a></section>')
html=('<html><head><link rel="canonical" href="'+v['URL']+'"></head><body>'
      +panel+'</body></html>')
assert v['verify'](html)['state']=='PUBLIC_VERIFIED'
for broken in (
 html.replace(v['MARKER'],''),
 html.replace(v['MARKER'],v['MARKER']*2),
 html.replace(v['URL'],'https://na-ryby.eu/wrong/'),
 html.replace(v['PILLAR'],'https://outside.example/'),
 html.replace(v['SAFE'][0],'missing'),
 html.replace('</body>',v['OLD'][2]+'</body>'),
):
    try:v['verify'](broken)
    except AssertionError: pass
    else: raise AssertionError('FALSE_POSITIVE_VERIFIER')
print('PASS strict sonar owner public verifier')
