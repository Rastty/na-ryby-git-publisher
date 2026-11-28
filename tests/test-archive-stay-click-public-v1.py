#!/usr/bin/env python3
"""Negative parser contract fixtures only. Does not access production."""
import importlib.util
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'scripts/verify-archive-stay-click-public-v1.py'
s=importlib.util.spec_from_file_location('nry_archive_click_proof',p)
mod=importlib.util.module_from_spec(s);s.loader.exec_module(mod)

def html(path, links=None, js=None, can=None, extra='', bad=False):
    marker,expected=mod.ROUTES[path]
    hrefs=expected if links is None else links
    marker_part='' if bad else ' '+marker+'="v1"'
    return ('<html><head><link rel="canonical" href="'+(can or mod.BASE+path)+'">'
      +'<script src="'+(js or mod.JS)+'"></script></head><body>'
      +'<section'+marker_part+'>'
      +''.join('<a href="'+target+'">Vybrat</a>' for target in hrefs)
      +'</section>'
      +'<main>Rekreační dům Harvánek Zukalův rybník Popůvky</main>'
      +'x'*10500+'</body></html>')

def reject(doc,path):
    try: mod.verify_doc(doc,path)
    except ValueError:return
    raise AssertionError('unsafe public markup accepted')

for path,(marker,expected) in mod.ROUTES.items():
    assert mod.verify_doc(html(path),path)['approvedFirstPartyChoices']==len(expected)
    assert mod.verify_doc(html(path,[mod.HOMEPAGE]+expected),path)['exactThemeContext'] is True
    reject(html(path,[mod.HOMEPAGE,mod.HOMEPAGE]+expected),path)
    reject(html(path,expected+[mod.HOMEPAGE]),path)
    reject(html(path,['https://bad.example/']+expected),path)
    reject(html(path,[mod.BASE+'/?a=1']+expected),path)
    reject(html(path,[mod.BASE+'/not-reviewed/']+expected),path)
    reject(html(path,list(reversed(expected))),path)
    reject(html(path,expected[:-1]+[expected[1]]),path)
    reject(html(path,js=mod.BASE+'/wrong.js'),path)
    reject(html(path,can=mod.BASE+'/other/'),path)
    reject(html(path,bad=True),path)
    reject(html(path).replace('</section>','</section><section '+marker+'="v1"></section>'),path)
    reject(html(path).replace('Rekreační dům Harvánek','') if path=='/ubytovani-2/' else html(path).replace('Popůvky',''),path)
print('ARCHIVE_STAY_CLICK_PUBLIC_NEGATIVE_CONTRACT_PASS')
