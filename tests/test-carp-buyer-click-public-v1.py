"""Offline fail-closed contract for a source-pinned existing carp buyer CTA."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       'scripts/verify-carp-buyer-click-public-v1.py'))
page = ('<html><head><link rel="canonical" href="' + m['ORIGIN'] + m['PAGE'] +
        '"></head><body><span ' + m['MARKER'] + '></span>' +
        '<a href="https://www.chytapust.cz/kaprove-pruty/?tracking=not_proof">'
        'Prověřit nabídku</a></body></html>').encode()
asset = b'/* source-reviewed consent-aware JS, not a merchant tracker */' * 9
tag = ('<script src="' + m['ORIGIN'] + m['ASSET'] + '?' + m['VERSION'] + '"></script>').encode()
live = page.replace(b'</body>', tag + b'</body>')

assert m['verify'](page, after=False)['state'] == 'PUBLIC_CARP_BUYER_CLICK_PREFLIGHT'
out = m['verify'](live, after=True, public_js=asset, reviewed_js=asset)
assert out['state'] == 'PUBLIC_CARP_BUYER_CLICK_ASSET_VERIFIED'
assert out['commissionAttributionProven'] is False
assert out['ga4DeliveryProven'] is False
assert out['merchantRequests'] == 0

def blocked(body=live, after=True, public_js=asset, reviewed_js=asset):
    try:
        m['verify'](body, after=after, public_js=public_js, reviewed_js=reviewed_js)
    except AssertionError:
        return
    raise AssertionError('Unsafe or drifted buyer surface was accepted')

blocked(live.replace(m['MARKER'].encode(), b''))
blocked(live.replace(b'www.chytapust.cz', b'www.chytapust.cz.evil.example'))
blocked(live.replace(b'https://www.chytapust.cz', b'http://www.chytapust.cz'))
blocked(live.replace(m['PAGE'].encode(), b'/wrong/'))
blocked(live.replace(tag, b''))
blocked(live.replace(tag, tag + tag))
blocked(live, public_js=b'not reviewed')
assert m['verify'](live, after=False)['state'] == 'PUBLIC_CARP_BUYER_CLICK_PREFLIGHT'
blocked(page, after=True)
print('PASS: carp existing CTA, source pin, no synthetic events, exact asset bytes')
