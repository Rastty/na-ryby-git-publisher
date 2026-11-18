#!/usr/bin/env python3
"""Public GET-only readback of one consent-aware Stredni Cechy CTA observer.

Never navigates to Dognet/Hportal, dispatches synthetic events or writes WP.
"""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = Path(__file__).resolve().parents[1]
ORIGIN = 'https://na-ryby.eu'
PAGE = '/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'
ASSET = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION = 'ver=1.11.4'
BASELINE_VERSION = 'ver=1.11.3'
PARTNER = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F'


class ClickSurface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.scripts = []
        self.sections = 0
        self.inside = False
        self.offers = []
        self.anchors = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag == 'script':
            src = urlsplit(a.get('src') or '')
            if src.path == ASSET and src.scheme in ('', 'https') and src.netloc in ('', 'na-ryby.eu') and not src.fragment:
                self.scripts.append(src.query)
        if tag == 'section' and a.get('data-nry-sc-stay') == 'v1':
            self.sections += 1
            self.inside = True
        if self.inside and tag == 'article' and a.get('data-nry-sc-offer'):
            self.offers.append(a.get('data-nry-sc-offer'))
        if self.inside and tag == 'a':
            self.anchors.append((a.get('href'), a.get('rel'), a.get('target')))

    def handle_endtag(self, tag):
        if tag == 'section' and self.inside:
            self.inside = False


def verify(html, *, deployed, public_js=b'', reviewed_js=b''):
    decoded = html.decode('utf-8', 'replace')
    surface = ClickSurface()
    surface.feed(decoded)
    surface.close()
    assert surface.canonicals == [ORIGIN + PAGE] and 'postid-92205' in decoded, 'PAGE_IDENTITY_DRIFT'
    assert surface.sections == 1 and surface.offers == ['slapy-janata'], 'SOURCE_BOUND_OFFER_MISSING'
    assert surface.anchors == [
        (PARTNER, 'sponsored nofollow noopener', '_blank'),
        (ORIGIN + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/', None, None),
    ], 'REVIEWED_AFFILIATE_TARGET_DRIFT'
    if not deployed:
        assert surface.scripts in ([], [BASELINE_VERSION], [VERSION]), 'UNREVIEWED_SCRIPT_BASELINE'
        return {'status': 'STREDNI_CECHY_CLICK_PREFLIGHT_READY', 'postId': 92205,
                'affiliateOffers': 1, 'wordPressWrites': 0,
                'merchantRequests': 0, 'syntheticClicks': 0}
    assert surface.scripts == [VERSION], 'EXACT_SCRIPT_NOT_ONCE'
    assert public_js == reviewed_js and len(public_js) > 3000, 'JS_BYTES_NOT_REVIEWED'
    return {'status': 'STREDNI_CECHY_CLICK_PUBLIC_VERIFIED', 'postId': 92205,
            'affiliateOffers': 1, 'scriptByteIdentical': True,
            'clickObservationEnabled': True, 'ga4DeliveryProven': False,
            'scriptSha256': hashlib.sha256(public_js).hexdigest(),
            'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0,
            'realClicks': None, 'fulfilledReservations': None, 'paidCommissions': None}


def get(url, limit):
    assert url in (ORIGIN + PAGE, ORIGIN + ASSET + '?' + VERSION), 'UNAPPROVED_PUBLIC_GET'
    request = Request(url, headers={'User-Agent': 'NaRyby-StredniCechy-Click-ReadOnly/1.0',
                                    'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'REDIRECT_OR_HTTP_DRIFT'
        body = response.read(limit + 1)
        assert len(body) <= limit, 'RESPONSE_TOO_LARGE'
        return body


def main():
    assert sys.argv[1:] in (['--preflight'], ['--verify']), 'PHASE_REQUIRED'
    deployed = sys.argv[1] == '--verify'
    result = verify(get(ORIGIN + PAGE, 1500000), deployed=deployed,
                    public_js=get(ORIGIN + ASSET + '?' + VERSION, 50000) if deployed else b'',
                    reviewed_js=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if deployed else b'')
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, OSError, KeyError) as exc:
        print(json.dumps({'status': 'STREDNI_CECHY_CLICK_BLOCKED', 'reason': str(exc)[:170]}))
        raise SystemExit(1)
