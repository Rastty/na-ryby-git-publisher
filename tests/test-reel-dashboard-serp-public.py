#!/usr/bin/env python3
"""Pure parser/SEO exact-readback contract; no network and no WordPress write."""
import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from importlib.machinery import SourceFileLoader

module = SourceFileLoader('reel_readback', str(Path(__file__).resolve().parents[1] /
     'scripts' / 'verify-reel-dashboard-nextsteps-public.py')).load_module()

def example(title=module.EXPECTED_SERP_TITLE, desc=module.EXPECTED_SERP_DESCRIPTION):
    items=lambda hrefs: ''.join('<a href="'+h+'">Guide</a>' for h in hrefs)
    return ('<html><head><title>'+title+'</title><link rel="canonical" href="'+module.TARGET+'">'
        +'<meta name="description" content="'+desc+'"></head><body>'
        +'<nav data-nry-reel-situations="v1">'+items(module.EXPECTED_SITUATIONS)+'</nav>'
        +'<nav data-nry-reel-nextsteps="v1">'+items(module.EXPECTED)+'</nav></body></html>')

assert module.verify(example())['serp_title_verified']
assert module.verify(example())['serp_description_verified']
for candidate,error in [
    (example(title='Navijáky - Dashboard'), 'REEL_SEARCH_INTENT_TITLE_NOT_LIVE'),
    (example(desc='Old generic description'), 'REEL_SEARCH_INTENT_DESCRIPTION_NOT_LIVE'),
    (example().replace('<meta name="description"','<meta name="keywords"'),
     'REEL_SEARCH_INTENT_DESCRIPTION_NOT_LIVE'),
    (example().replace(module.TARGET, 'https://fake.example/', 1), 'CANONICAL_DRIFT'),
]:
    try:
        module.verify(candidate)
    except RuntimeError as exc:
        assert error in str(exc), (error, str(exc))
    else:
        raise AssertionError(error)
print('PASS: exact public SEO title/description guards and canonical')
