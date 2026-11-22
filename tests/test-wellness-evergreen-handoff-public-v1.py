#!/usr/bin/env python3
"""Fixed offline acceptance and negative public DOM fixtures; no network."""
import importlib.util
from pathlib import Path
m=Path(__file__).resolve().parents[1]/'scripts/verify-wellness-evergreen-handoff-public-v1.py'
spec=importlib.util.spec_from_file_location('nry_wellness_public',m)
v=importlib.util.module_from_spec(spec)
spec.loader.exec_module(v)

def fixture(idx,contextual=None,extras='',roles=None,source=None):
    slug=v.SLUGS[idx]
    url=v.ORIGIN+'/'+slug+'/'
    own=roles if roles is not None else [('srub',v.SRUB),('stays',v.HUB)]
    if idx and roles is None: own.reverse()
    links=''.join('<a data-nry-wellness-path="'+role+'" href="'+href+'">CT</a>'
                  for role,href in own)
    if contextual is not None:
        links='<a href="'+contextual+'">Context</a>'+links
    return ('<html><head><link rel="canonical" href="'+url+'">'
            '<script src="'+v.JS_URL+'"></script></head><body>'
            '<section '+v.MARKER+'>'+links+extras+'</section>'
            '<p>'+ (v.ANCHORS[idx] if source is None else source)
            +'</p></body></html>')

def valid(idx,doc):
    v.verify_html(doc,idx,v.ORIGIN+'/'+v.SLUGS[idx]+'/')

def rejected(idx,doc):
    try: valid(idx,doc)
    except AssertionError: return
    raise AssertionError('Rejected fixture was accepted')

valid(0,fixture(0))
valid(1,fixture(1,contextual=v.ORIGIN+'/'))
rejected(1,fixture(1,contextual=v.ORIGIN+'/rybareni/'))
rejected(0,fixture(0,contextual=v.ORIGIN+'/rybareni/'))
rejected(1,fixture(1,contextual='https://tracking.affiliateport.eu/click'))
rejected(1,fixture(1,contextual=v.ORIGIN+'/rybareni/?preview=1'))
rejected(1,fixture(1,contextual=v.ORIGIN+'/rybareni/#frag'))
rejected(1,fixture(1))
rejected(0,fixture(0,roles=[('stays',v.HUB),('srub',v.SRUB)]))
rejected(0,fixture(0,roles=[('srub',v.SRUB),('srub',v.SRUB)]))
rejected(0,fixture(0,roles=[('srub',v.SRUB),('stays',v.HUB),('other',v.HUB)]))
rejected(0,fixture(0,extras='<a href="https://example.net/">External</a>'))
rejected(0,fixture(0,source='Changed by editor'))
rejected(0,fixture(0).replace(v.MARKER,v.MARKER+v.MARKER))
rejected(0,fixture(0).replace('<script src="'+v.JS_URL+'"></script>',''))
rejected(1,fixture(1,contextual=v.ORIGIN+'/').replace(v.JS_URL,v.JS_URL.replace('1.0.0','0.9.0')))
rejected(0,fixture(0).replace('<script src="'+v.JS_URL+'"></script>','<script src="'+v.JS_URL+'"></script><script src="'+v.JS_URL+'"></script>'))
print('WELLNESS_EVERGREEN_PUBLIC_DOM_NEGATIVE_CONTRACT_PASS')
