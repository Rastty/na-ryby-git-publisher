#!/usr/bin/env python3
"""Exact GET-only public proof for the two existing chair buyer click funnels.

This checks already published anchors and the deployed local JS bundle only.
It never visits affiliate URLs, creates clicks, infers paid commission, or
writes WordPress content. Reviewed JS bytes must equal production bytes.
"""
import hashlib
import json
import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = Path(__file__).resolve().parents[1]
ORIGIN = 'https://na-ryby.eu'
HUB = '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/'
EVERGREEN = '/jak-vybrat-rybarske-kreslo/'
ADVISOR = '/poradce-vybavy/'
ASSET = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION = 'ver=1.11.0'
BASELINE_VERSION = 'ver=1.10.0'
CJ_HOSTS = frozenset({
    'www.kqzyfj.com', 'www.tkqlhce.com', 'www.jdoqocy.com',
    'www.dpbolvw.net', 'www.anrdoezrs.net'
})


class PublicPage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical = []
        self.bundle_versions = []
        self.kresla_shortlink = 0
        self.evergreen_handoffs = 0
        self.advisor_handoffs = 0
        self.existing_cj_links = 0
        self.legacy_cj_links = 0
        self.insecure_cj_links = 0

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'link' and 'canonical' in (attrs.get('rel') or '').split():
            self.canonical.append(attrs.get('href'))
        if tag == 'script':
            src = attrs.get('src') or ''
            url = urlsplit(src)
            if ((url.scheme == 'https' and url.netloc == 'na-ryby.eu')
                    or (not url.scheme and not url.netloc and src.startswith('/'))):
                if url.path == ASSET and not url.fragment:
                    self.bundle_versions.append(url.query)
        if tag != 'a':
            return
        href = attrs.get('href') or ''
        parsed = urlsplit(href)
        if parsed.scheme not in ('https', 'http') or parsed.username or parsed.password or parsed.port:
            return
        if parsed.netloc == 'na-ryby.eu':
            if not parsed.query and not parsed.fragment:
                if parsed.path in ('/kresla', '/kresla/'):
                    self.kresla_shortlink += 1
                if parsed.path == EVERGREEN:
                    self.evergreen_handoffs += 1
                if parsed.path == ADVISOR:
                    self.advisor_handoffs += 1
        elif (parsed.hostname in CJ_HOSTS and
              re.fullmatch(r'/click-8287231-[0-9]{6,16}', parsed.path or '')):
            self.existing_cj_links += 1
            if parsed.scheme == 'http':
                self.insecure_cj_links += 1
        elif (parsed.hostname in CJ_HOSTS and
              re.fullmatch(r'/click-[0-9]{6,12}-[0-9]{6,16}-[0-9]{9,18}',
                           parsed.path or '') and
              not parsed.path.startswith('/click-8287231-')):
            self.legacy_cj_links += 1
            if parsed.scheme == 'http':
                self.insecure_cj_links += 1


def inspect(hub_html, evergreen_html, *, after, installed_js=b'', reviewed_js=b''):
    hub = PublicPage()
    hub.feed(hub_html.decode('utf-8', 'replace'))
    evergreen = PublicPage()
    evergreen.feed(evergreen_html.decode('utf-8', 'replace'))
    assert hub.canonical == [ORIGIN + HUB], 'CHAIR_HUB_CANONICAL_DRIFT'
    assert evergreen.canonical == [ORIGIN + EVERGREEN], 'CHAIR_EVERGREEN_CANONICAL_DRIFT'
    assert hub.kresla_shortlink >= 1, 'HUB_EXISTING_BUYER_SHORTLINK_MISSING'
    assert hub.evergreen_handoffs >= 1, 'HUB_EVERGREEN_GUIDE_HANDOFF_MISSING'
    assert evergreen.existing_cj_links >= 1, 'EVERGREEN_EXISTING_CJ_LINK_MISSING'
    assert evergreen.legacy_cj_links >= 1, 'EVERGREEN_LEGACY_CJ_LINK_MISSING'
    assert evergreen.insecure_cj_links >= 1, 'EVERGREEN_INSECURE_LEGACY_URLS_NOT_OBSERVED'
    assert evergreen.advisor_handoffs >= 1, 'EVERGREEN_ADVISOR_HANDOFF_MISSING'
    if after:
        assert hub.bundle_versions == [VERSION], 'HUB_SCRIPT_NOT_INSTALLED_ONCE'
        assert evergreen.bundle_versions == [VERSION], 'EVERGREEN_SCRIPT_NOT_INSTALLED_ONCE'
        assert len(installed_js) > 1000 and len(reviewed_js) > 1000, 'ASSET_EMPTY'
        assert hashlib.sha256(installed_js).digest() == hashlib.sha256(
            reviewed_js).digest(), 'BUNDLE_BYTES_DIFFER_FROM_REVIEWED_SOURCE'
    else:
        assert hub.bundle_versions in ([], [BASELINE_VERSION], [VERSION]), 'HUB_UNREVIEWED_PREVIOUS_BUNDLE'
        assert evergreen.bundle_versions in ([], [BASELINE_VERSION], [VERSION]), 'EVERGREEN_UNREVIEWED_PREVIOUS_BUNDLE'
    return {
        'state': ('PUBLIC_CHAIR_BUYER_CLICK_ASSET_VERIFIED' if after
                  else 'PUBLIC_CHAIR_BUYER_CLICK_PREFLIGHT'),
        'chair_hub_first_party_affiliate_ctas': hub.kresla_shortlink,
        'chair_hub_evergreen_guide_handoffs': hub.evergreen_handoffs,
        'chair_evergreen_existing_cj_ctas': evergreen.existing_cj_links,
        'chair_evergreen_unverified_legacy_cj_ctas': evergreen.legacy_cj_links,
        'chair_evergreen_insecure_http_cj_ctas': evergreen.insecure_cj_links,
        'chair_evergreen_advisor_handoffs': evergreen.advisor_handoffs,
        'asset_byte_identical': after, 'merchantRequests': 0,
        'syntheticClicks': 0, 'wordpressWrites': 0,
        'ga4DeliveryProven': False, 'commissionAttributionProven': False
    }


def get(url, max_bytes):
    if url not in (ORIGIN + HUB, ORIGIN + EVERGREEN,
                   ORIGIN + ASSET + '?' + VERSION):
        raise ValueError('UNAPPROVED_PUBLIC_URL')
    request = Request(url, headers={'User-Agent': 'NaRyby-ChairBuyerGETOnly/1.0',
                                    'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(request, timeout=30) as response:
        assert response.status == 200 and response.geturl() == url, 'PUBLIC_HTTP_DRIFT'
        body = response.read(max_bytes + 1)
    assert len(body) <= max_bytes, 'PUBLIC_BODY_TOO_LARGE'
    return body


if __name__ == '__main__':
    after = '--verify' in sys.argv
    assert len(sys.argv) == 2 and sys.argv[1] in ('--preflight', '--verify')
    installed = get(ORIGIN + ASSET + '?' + VERSION, 40000) if after else b''
    reviewed = (ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if after else b''
    print(json.dumps(inspect(
        get(ORIGIN + HUB, 1200000), get(ORIGIN + EVERGREEN, 1200000),
        after=after, installed_js=installed, reviewed_js=reviewed
    ), ensure_ascii=False))
