#!/usr/bin/env python3
"""GET-only published fishing-stay CTA + existing analytics asset readback.

Never follows an affiliate URL, generates a click, sends analytics, writes
WordPress, or infers room inventory, commission or consent from script presence.
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
POST = '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
MARKER = 'fishing-stays-cz-59891-20261118-v1'
ASSET = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION = 'ver=1.11.3'
PREVIOUS_VERSION = 'ver=1.11.2'
IDS = frozenset({
    '2462030', 'hportal-pension-u-vlcku', 'hportal-penzion-janata',
    'hportal-penzion-u-kohoutu', 'hportal-pension-rybarska-basta',
})
SOURCE = ROOT / 'editorial/fishing-stays-cz-20261118-recommendation-blocks.html'


class StayBlock(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.markers = 0
        self.in_block = False
        self.article = None
        self.offer_links = {}
        self.canonicals = []
        self.asset_versions = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == 'link' and 'canonical' in (values.get('rel') or '').split():
            self.canonicals.append(values.get('href'))
        if tag == 'script':
            url = urlsplit(values.get('src') or '')
            if (url.path == ASSET and not url.fragment and
                    ((url.scheme == 'https' and url.netloc == 'na-ryby.eu') or
                     (not url.scheme and not url.netloc))):
                self.asset_versions.append(url.query)
        if tag == 'section' and values.get('data-nry-editorial-edit') == MARKER:
            self.markers += 1
            self.in_block = True
        if tag == 'article' and self.in_block:
            self.article = values.get('data-nry-offer-id')
            if self.article in self.offer_links:
                raise AssertionError('DUPLICATE_STAY_OFFER_RECORD')
            self.offer_links[self.article] = []
        if tag == 'a' and self.in_block and self.article:
            href = values.get('href') or ''
            if urlsplit(href).hostname in ('www.slevomat.cz', 'go.dognet.com'):
                self.offer_links[self.article].append({
                    'href': href, 'rel': values.get('rel'),
                    'target': values.get('target'),
                })

    def handle_endtag(self, tag):
        if tag == 'article':
            self.article = None
        if tag == 'section' and self.in_block:
            self.in_block = False


def parse(html):
    parser = StayBlock()
    parser.feed(html)
    parser.close()
    return parser


def verify(page_html, *, deployed=False, public_js=b'', reviewed_js=b''):
    expected = parse(SOURCE.read_text(encoding='utf-8'))
    current = parse(page_html.decode('utf-8', 'replace'))
    assert expected.markers == current.markers == 1, 'STAY_RELEASE_MARKER_NOT_ONCE'
    assert set(expected.offer_links) == set(current.offer_links) == IDS, 'STAY_OFFER_ID_DRIFT'
    assert current.canonicals == [ORIGIN + POST], 'STAY_CANONICAL_DRIFT'
    original_heading = '<h1>Rybářské pobyty v přírodě:'.encode('utf-8')
    assert page_html.count(original_heading) == 1, (
        'LEGACY_ARTICLE_HEADING_MISSING')
    marker_position = page_html.find(('data-nry-editorial-edit="' + MARKER + '"').encode())
    article_position = page_html.find(original_heading)
    assert 0 <= marker_position < article_position, 'STAY_BLOCK_NOT_BEFORE_ORIGINAL_ARTICLE'
    for offer_id in IDS:
        expected_links = expected.offer_links[offer_id]
        live_links = current.offer_links[offer_id]
        assert len(expected_links) == len(live_links) == 1, 'STAY_CTA_COUNT_DRIFT'
        assert live_links[0]['href'] == expected_links[0]['href'], 'STAY_AFFILIATE_URL_DRIFT'
        assert set((live_links[0]['rel'] or '').split()) == {
            'sponsored', 'nofollow', 'noopener'}, 'STAY_SPONSORED_REL_DRIFT'
        assert live_links[0]['target'] == '_blank', 'STAY_CTA_TARGET_DRIFT'
    if deployed:
        assert current.asset_versions == [VERSION], 'STAY_BUYER_CLICK_ASSET_NOT_ONCE'
        assert len(public_js) > 1000 and public_js == reviewed_js, 'STAY_JS_ASSET_BYTE_DRIFT'
    else:
        assert current.asset_versions in ([], [PREVIOUS_VERSION], [VERSION]), (
            'STAY_UNREVIEWED_SCRIPT_ENQUEUE')
    return {
        'state': ('STAY_PUBLIC_CTA_AND_CLICK_BUNDLE_VERIFIED' if deployed
                  else 'STAY_PUBLIC_CTA_PREFLIGHT_VERIFIED'),
        'postId': 59891, 'publishedMarkerCount': current.markers,
        'exactSponsoredOffers': len(current.offer_links),
        'originalArticlePreserved': True,
        'scriptVerified': deployed,
        'scriptSha256': hashlib.sha256(public_js).hexdigest() if deployed else None,
        'merchantRequests': 0, 'syntheticClicks': 0, 'wordPressWrites': 0,
        'acceptedRealClicks': None, 'affiliateOrders': None,
        'approvedCommissions': None, 'paidCommissions': None,
    }


def get(url, maximum):
    if url not in (ORIGIN + POST, ORIGIN + ASSET + '?' + VERSION):
        raise ValueError('STAY_GET_UNAPPROVED_SOURCE_URL')
    request = Request(url, method='GET', headers={
        'User-Agent': 'NaRyby-StayAffiliate-Readback/1.0',
        'Cache-Control': 'no-cache, no-store',
    })
    with guarded_urlopen(request, timeout=25) as response:
        body = response.read(maximum + 1)
    assert len(body) <= maximum, 'STAY_PUBLIC_MAX_BYTES_EXCEEDED'
    return body


if __name__ == '__main__':
    assert len(sys.argv) == 2 and sys.argv[1] in ('--preflight', '--verify')
    after = sys.argv[1] == '--verify'
    print(json.dumps(verify(
        get(ORIGIN + POST, 1500000), deployed=after,
        public_js=get(ORIGIN + ASSET + '?' + VERSION, 50000) if after else b'',
        reviewed_js=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if after else b'',
    ), ensure_ascii=False))
