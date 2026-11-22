#!/usr/bin/env python3
"""GET-only preflight/readback of reviewed family-stay offers on post 59271.

Never follows partner URLs, navigates booking destinations, modifies WordPress,
sends synthetic events, or assigns numeric values to unknown revenue metrics.
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
PATH = '/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/'
REST = ORIGIN + '/wp-json/wp/v2/posts/59271'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-family-stays-59271-v1.css'
JS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION = 'ver=1.11.7'
PREVIOUS_VERSION = 'ver=1.11.6'
SOURCE_PREFIX_SHA256 = '02228a68098a012f31a5df6c96f82ea9231b2d8fd9fd1ea392929a08d750b04a'
HPORTAL_LIPNO = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F'
HPORTAL_SLAPY = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F'
STAYS = ORIGIN + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
OFFERS = {'lipno-kohoutu': HPORTAL_LIPNO, 'slapy-janata': HPORTAL_SLAPY}


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical = []
        self.styles = []
        self.script_versions = []
        self.block_count = 0
        self.inside = False
        self.article = None
        self.ctas = {}
        self.internal = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').split():
                self.canonical.append(a.get('href'))
            url = urlsplit(a.get('href') or '')
            if url.path == CSS:
                self.styles.append(url.query)
        if tag == 'section' and a.get('data-nry-family-stays') == 'v1':
            self.block_count += 1
            self.inside = True
        if tag == 'script':
            url = urlsplit(a.get('src') or '')
            if url.path == JS and url.scheme in ('', 'https') and url.netloc in ('', 'na-ryby.eu'):
                self.script_versions.append(url.query)
        if tag == 'article' and self.inside:
            self.article = a.get('data-nry-family-offer')
            self.ctas.setdefault(self.article, [])
        if tag == 'a' and self.inside:
            record = (a.get('href'), a.get('rel'), a.get('target'))
            if self.article:
                self.ctas[self.article].append(record)
            else:
                self.internal.append(record)

    def handle_endtag(self, tag):
        if tag == 'article':
            self.article = None
        if tag == 'section' and self.inside:
            self.inside = False


def public_assertions(html, *, deployed, css_public=b'', css_reviewed=b'',
                      js_public=b'', js_reviewed=b''):
    decoded = html.decode('utf-8', 'replace')
    page = Page()
    page.feed(decoded)
    page.close()
    assert page.canonical == [ORIGIN + PATH], 'FAMILY_CANONICAL_DRIFT'
    assert 'postid-59271' in decoded, 'FAMILY_POST_ID_DRIFT'
    assert 'Rybolov na řece Vltavě' in decoded, 'FAMILY_ORIGINAL_VLTAVA_MISSING'
    assert 'Rybářská osada Dolní Vltavice' in decoded, 'FAMILY_ORIGINAL_LIPNO_MISSING'
    assert page.block_count == 1, 'FAMILY_BLOCK_NOT_ONCE'
    assert set(page.ctas) == set(OFFERS), 'FAMILY_OFFER_COUNT_OR_IDS_DRIFT'
    for offer_id, expected in OFFERS.items():
        assert page.ctas[offer_id] == [(expected, 'sponsored nofollow noopener', '_blank')], (
            'FAMILY_PARTNER_DESTINATION_OR_REL_DRIFT')
    assert page.internal == [(STAYS, None, None)], 'FAMILY_INTERNAL_COMPARISON_DRIFT'
    placement = decoded.find('Zde je pár tipů, kam se vydat na ryby')
    assert placement >= 0 and decoded.find('data-nry-family-stays="v1"') < placement, (
        'FAMILY_CTA_BURIED_AFTER_INTRO')
    assert page.styles == ['ver=1.0.0'], 'FAMILY_STYLE_NOT_EXACTLY_ONCE'
    if not deployed:
        # The two public affiliate cards already exist. On PR, accept the
        # current absence of the measurement script or its exact reviewed
        # historical version; future PRs can observe the deployed version.
        assert page.script_versions in ([], [PREVIOUS_VERSION], [VERSION]), (
            'FAMILY_UNREVIEWED_SCRIPT_BASELINE')
        return {'status': 'FAMILY_STAYS_CLICK_PREFLIGHT_READY', 'postId': 59271,
                'affiliateOffers': 2, 'wordPressWrites': 0,
                'merchantRequests': 0, 'syntheticClicks': 0}
    assert len(css_public) > 500 and css_public == css_reviewed, 'FAMILY_STYLES_NOT_BYTE_IDENTICAL'
    assert page.script_versions == [VERSION], 'FAMILY_CLICK_SCRIPT_NOT_EXACTLY_ONCE'
    assert len(js_public) > 1000 and js_public == js_reviewed, 'FAMILY_CLICK_JS_NOT_BYTE_IDENTICAL'
    return {'status': 'FAMILY_STAYS_PUBLIC_VERIFIED', 'postId': 59271,
            'affiliateOffers': 2, 'stylingByteIdentical': True, 'clickScriptByteIdentical': True,
            'clickObservationEnabled': True, 'ga4DeliveryProven': False,
            'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0,
            'realClicks': None, 'fulfilledReservations': None, 'paidCommissions': None}


def rest_preflight(data):
    assert data['id'] == 59271 and data['type'] == 'post' and data['status'] == 'publish', (
        'FAMILY_REST_IDENTITY_DRIFT')
    assert data['slug'] == 'rodinna-dovolena-u-vody-kam-na-ryby-s-detmi', 'FAMILY_SLUG_DRIFT'
    assert data['link'] == ORIGIN + PATH, 'FAMILY_PERMALINK_DRIFT'
    assert data['modified_gmt'] == '2023-08-23T08:26:35', 'FAMILY_MODIFIED_GMT_DRIFT'
    rendered = data['content']['rendered']
    assert hashlib.sha256(rendered.encode('utf-8')[:512]).hexdigest() == SOURCE_PREFIX_SHA256, (
        'FAMILY_SOURCE_RENDERED_PREFIX_DRIFT')
    assert rendered.count('<p>Zde je pár tipů, kam se vydat na ryby se svými dětmi:</p>') == 1, (
        'FAMILY_PLACEMENT_DRIFT')
    assert HPORTAL_LIPNO not in rendered and HPORTAL_SLAPY not in rendered, (
        'FAMILY_ALREADY_HAS_PARTNER_URL')


def get(url, max_bytes):
    allowed = {ORIGIN + PATH, REST, ORIGIN + CSS + '?ver=1.0.0', ORIGIN + JS + '?' + VERSION}
    assert url in allowed, 'FAMILY_UNAPPROVED_GET'
    request = Request(url, headers={'User-Agent': 'NaRyby-Family-Stays-ReadOnly/1.0',
                                    'Cache-Control': 'no-cache, no-store'}, method='GET')
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'FAMILY_GET_REDIRECT'
        body = response.read(max_bytes + 1)
    assert len(body) <= max_bytes, 'FAMILY_RESPONSE_TOO_LARGE'
    return body


def main():
    assert len(sys.argv) == 2 and sys.argv[1] in ('--preflight', '--verify'), 'FAMILY_PHASE_REQUIRED'
    deployed = sys.argv[1] == '--verify'
    rest_preflight(json.loads(get(REST, 150000).decode('utf-8')))
    result = public_assertions(
        get(ORIGIN + PATH, 1500000), deployed=deployed,
        css_public=get(ORIGIN + CSS + '?ver=1.0.0', 30000) if deployed else b'',
        css_reviewed=(ROOT / 'assets/nry-family-stays-59271-v1.css').read_bytes() if deployed else b'',
        js_public=get(ORIGIN + JS + '?' + VERSION, 50000) if deployed else b'',
        js_reviewed=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if deployed else b'',
    )
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, OSError, KeyError) as exc:
        print(json.dumps({'status': 'FAMILY_STAYS_PROOF_BLOCKED', 'reason': str(exc)[:180]}))
        raise SystemExit(1)
