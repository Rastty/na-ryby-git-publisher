#!/usr/bin/env python3
"""Independent GET-only evidence for existing carp buyer outbound telemetry.

No merchant requests, clicks, analytics events, cookies or WordPress writes.
Count existing merchant link shape only; it does not prove affiliate credit.
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
PAGE = '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'
ASSET = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION = 'ver=1.11.1'
BASELINE_VERSION = 'ver=1.10.0'
MARKER = 'data-nry-carp-choice-before-shop="v1"'


class PublicPage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.asset_versions = []
        self.merchant_links = 0

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == 'link' and 'canonical' in (values.get('rel') or '').split():
            self.canonicals.append(values.get('href'))
        if tag == 'script':
            src = values.get('src') or ''
            parts = urlsplit(src)
            if ((parts.scheme == 'https' and parts.netloc == 'na-ryby.eu')
                    or (not parts.scheme and not parts.netloc and src.startswith('/'))):
                if parts.path == ASSET and not parts.fragment:
                    self.asset_versions.append(parts.query)
        if tag == 'a':
            parts = urlsplit(values.get('href') or '')
            if (parts.scheme == 'https' and parts.netloc == 'www.chytapust.cz'
                    and len(parts.path) > 1 and not parts.username
                    and not parts.password and not parts.port):
                self.merchant_links += 1


def verify(html, *, after, public_js=b'', reviewed_js=b''):
    page = PublicPage()
    page.feed(html.decode('utf-8', 'replace'))
    assert page.canonicals == [ORIGIN + PAGE], 'CARP_CANONICAL_DRIFT'
    assert html.count(MARKER.encode()) == 1, 'CARP_SOURCE_BOUND_BUYER_CHOICES_MISSING'
    assert page.merchant_links > 0, 'EXISTING_CARP_SHOP_CTA_MISSING'
    if after:
        assert page.asset_versions == [VERSION], 'CARP_BUYER_SCRIPT_NOT_ENQUEUED_ONCE'
        assert len(public_js) > 300 and len(reviewed_js) > 300, 'BUYER_JS_EMPTY'
        assert hashlib.sha256(public_js).digest() == hashlib.sha256(reviewed_js).digest(), (
            'CARP_BUYER_JS_BYTES_NOT_REVIEWED')
    else:
        # The first PR has no script; later PRs must continue to pass once
        # this exact reviewed version is live. PR preflight is not byte proof.
        assert page.asset_versions in ([], [BASELINE_VERSION], [VERSION]), (
            'CARP_UNREVIEWED_SCRIPT_VERSION')
    return {
        'state': 'PUBLIC_CARP_BUYER_CLICK_ASSET_VERIFIED' if after
                 else 'PUBLIC_CARP_BUYER_CLICK_PREFLIGHT',
        'existing_direct_merchant_ctas': page.merchant_links,
        'exact_script_version': VERSION if after else None,
        'installed_asset_byte_identical': after,
        'merchantRequests': 0, 'syntheticClicks': 0, 'wordpressWrites': 0,
        'ga4DeliveryProven': False, 'commissionAttributionProven': False,
    }


def get(url, max_bytes):
    assert url in (ORIGIN + PAGE, ORIGIN + ASSET + '?' + VERSION), 'URL_NOT_APPROVED'
    request = Request(url, headers={'User-Agent': 'NaRyby-CarpBuyerClicks-GETOnly/1.0',
                                    'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'CARP_HTTP_OR_REDIRECT'
        body = response.read(max_bytes + 1)
    assert len(body) <= max_bytes, 'CARP_HTML_OR_ASSET_TOO_BIG'
    return body


if __name__ == '__main__':
    mode = sys.argv[1] if len(sys.argv) > 1 else '--verify'
    assert mode in ('--preflight', '--verify')
    after = mode == '--verify'
    print(json.dumps(verify(
        get(ORIGIN + PAGE, 1500000), after=after,
        public_js=get(ORIGIN + ASSET + '?' + VERSION, 30000) if after else b'',
        reviewed_js=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if after else b''
    ), ensure_ascii=False))
