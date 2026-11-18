#!/usr/bin/env python3
"""Offline fail-closed chair funnel proof without public or merchant requests."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       'scripts/verify-chair-buyer-click-public-v1.py'))
origin = m['ORIGIN']
script = b'/* verified local read-only JS asset */' * 45
def page(path, anchors, version=None):
    canonical = '<link rel="canonical" href="' + origin + path + '">'
    tag = ('<script src="' + origin + m['ASSET'] +
           '?ver=' + version + '"></script>') if version else ''
    return ('<html><head>' + canonical + tag + '</head><body>' +
            ''.join('<a href="' + u + '">Existing</a>' for u in anchors) +
            '</body></html>').encode()

hub_links = [origin + '/kresla', origin + m['EVERGREEN']]
evergreen_links = [
    'http://www.dpbolvw.net/click-8287231-12347575?url=redacted',
    'http://www.dpbolvw.net/click-7777777-12347575-1234567890123?url=redacted',
    origin + m['ADVISOR']]
hub = page(m['HUB'], hub_links)
evergreen = page(m['EVERGREEN'], evergreen_links)
assert m['inspect'](hub, evergreen, after=False)['state'] == 'PUBLIC_CHAIR_BUYER_CLICK_PREFLIGHT'
hub_v = page(m['HUB'], hub_links, '1.11.2')
evergreen_v = page(m['EVERGREEN'], evergreen_links, '1.11.2')
result = m['inspect'](hub_v, evergreen_v, after=True,
                      installed_js=script, reviewed_js=script)
assert result['state'] == 'PUBLIC_CHAIR_BUYER_CLICK_ASSET_VERIFIED'
assert result['chair_hub_first_party_affiliate_ctas'] == 1
assert result['chair_evergreen_existing_cj_ctas'] == 1
assert result['chair_evergreen_unverified_legacy_cj_ctas'] == 1
assert result['chair_evergreen_insecure_http_cj_ctas'] == 2
assert not result['commissionAttributionProven']
assert not result['ga4DeliveryProven']
assert result['merchantRequests'] == 0
assert result['syntheticClicks'] == 0

def blocked(h=hub_v,e=evergreen_v,asset=script,after=True):
    try: m['inspect'](h,e,after=after,installed_js=asset,reviewed_js=script)
    except AssertionError: return
    raise AssertionError('Invalid public chair buyer proof accepted')
blocked(h=hub)
blocked(e=evergreen)
blocked(asset=b'old bundle')
blocked(h=hub_v.replace(m['HUB'].encode(),b'/wrong-hub/',1))
blocked(e=evergreen_v.replace(m['EVERGREEN'].encode(),b'/wrong-evergreen/',1))
blocked(h=hub_v.replace((origin+'/kresla').encode(),(origin+'/other').encode()))
blocked(h=hub_v.replace((origin+m['EVERGREEN']).encode(),(origin+'/other').encode()))
blocked(e=evergreen_v.replace(b'www.dpbolvw.net',b'www.fake-merchant.example'))
blocked(e=evergreen_v.replace(b'click-7777777-12347575-1234567890123',b'click-7777777-12347575'))
blocked(e=evergreen_v.replace((origin+m['ADVISOR']).encode(),(origin+'/other').encode()))
blocked(h=hub_v.replace(b'ver=1.11.2',b'ver=1.10.0'))
blocked(e=evergreen_v.replace(b'ver=1.11.2',b'ver=1.10.0'))
blocked(h=hub_v.replace(b'</head>',b'<script src="'+(origin+m['ASSET']+'?ver=1.11.2').encode()+b'"></script></head>'))
assert m['inspect'](hub,evergreen,after=False)['state']=='PUBLIC_CHAIR_BUYER_CLICK_PREFLIGHT'
print('PASS: exact chair guide and CJ handoffs, JS bytes, no synthetic clicks')
