"""Offline source-identity and no-click public wiring negative fixtures."""
from pathlib import Path
import runpy
ROOT=Path(__file__).resolve().parents[1]
m=runpy.run_path(str(ROOT/'scripts/verify-wellness-srub-click-public-17536.py'))
stay=runpy.run_path(str(ROOT/'tests/test-wellness-srub-hauzi-public-17536.py'))
original=stay['src']
tag='<script src="'+m['JS_URL']+'"></script>'
page=original.replace('</head>',tag+'</head>')
assert m['verify'](page,m['ORIGINAL'])['status']=='WELLNESS_SRUB_CLICK_JS_PUBLIC_VERIFIED'
def reject(page=page,js=m['ORIGINAL']):
    try:m['verify'](page,js)
    except ValueError:return
    raise AssertionError('Unexpected accepted drift or spoofed evidence')
reject(page.replace(tag,''))
reject(page.replace(tag,tag+tag))
reject(page.replace('ver=1.11.7','ver=1.11.6',1))
reject(page.replace(m['ASSET'],'/assets/unreviewed.js',1))
reject(page.replace(m['JS_URL'],m['JS_URL']+'&other=1',1))
reject(page.replace(m['JS_URL'],'https://other.example'+m['ASSET']+'?ver=1.11.7',1))
reject(page.replace(m['STAY']['MARKER'],'data-nry-wellness-srub-offer="v2"',1))
reject(page.replace(m['STAY']['AFFILIATE'],'https://other.example/',1))
reject(page.replace(m['STAY']['URL'],'https://other.example/',1))
reject(page,m['ORIGINAL']+b'drift')
reject(page,b'')
print('PASS WELLNESS_SRUB_CLICK_SOURCE_DOM_AND_BYTE_NEGATIVES')
