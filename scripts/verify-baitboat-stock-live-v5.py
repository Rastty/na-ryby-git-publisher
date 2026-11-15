#!/usr/bin/env python3
"""Anonymous read-only V5/V6 stock/affiliate release proof. Never visits merchants.

Validates the PUBLIC HTML, not GitHub code or a WordPress admin receipt. A pass
means the dated 2026-11-13 exclusions are active; it is NOT a live stock check.
"""
import hashlib
import json
import re
import sys
from html.parser import HTMLParser
from urllib.parse import parse_qs, urlsplit
from urllib.request import Request, urlopen


URL = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku/'
HEADER = 'X-NRY-Baitboat-Stock-Guard-SHA256'
V6_HEADER = 'X-NRY-Baitboat-Compromise-SHA256'
CJ_HOST = 'www.anrdoezrs.net'
CJ_PATH = '/click-8287231-15734253'
EXCLUDED_SLUGS = (
    'Boatman-Zavazeci-lodicka-Vulcan-GPS-Cerny',
    'Boatman-Zavazeci-lodicka-Vulcan-Mini-PRO-s-GPS-a-Echolot-Cerna',
    'Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna',
)


class Anchors(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical = []
        self.hrefs = []
        self.ids = set()

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'link' and 'canonical' in attrs.get('rel', '').lower().split():
            self.canonical.append(attrs.get('href'))
        if tag == 'a':
            self.hrefs.append((attrs.get('href', ''), attrs.get('rel', '')))
        if 'id' in attrs:
            self.ids.add(attrs['id'])


def verify(body, headers, status=200, actual_url=URL):
    """Return a structured proof; raise ValueError with an actionable reason."""
    def require(condition, why):
        if not condition:
            raise ValueError(why)

    require(status == 200 and actual_url == URL, 'HTTP_200_EXACT_URL_REQUIRED')
    require('text/html' in headers.get('content-type', '').lower(), 'HTML_MIME_MISSING')
    require('no-store' in headers.get('cache-control', '').lower(), 'NO_STORE_MISSING')
    html = body.decode('utf-8')
    v5 = html.count('data-nry-baitboat-rc="v5-stock-guard"')
    v6 = html.count('data-nry-baitboat-rc="v6-compromise-recovery"')
    require(v5 + v6 == 1, 'V5_OR_V6_NOT_LIVE_ADMIN_APPLY_REQUIRED')
    version = 'v6-compromise-recovery' if v6 else 'v5-stock-guard'
    digest = hashlib.sha256(body).hexdigest()
    header = V6_HEADER if v6 else HEADER
    require(headers.get(header.lower(), '') == digest, 'V5_V6_SHA256_RESPONSE_MISMATCH')
    if v6:
        require('lowerCapacityAlternatives=products.filter(p=>!unavailableProducts.has(p.id)' in html,
                'V6_EXCLUSION_AWARE_COMPROMISE_MISSING')
        require('change.addEventListener("click",()=>{fields.load.value=' in html,
                'V6_EXPLICIT_VISITOR_CHOICE_MISSING')

    links = Anchors()
    links.feed(html)
    require(links.canonical == [URL], 'CANONICAL_MISMATCH')
    require({'budget', 'range', 'gps', 'sonar', 'load', 'result-link',
             'result-availability'}.issubset(links.ids), 'PICKER_CONTROLS_MISSING')
    require('unavailableProducts=new Set(["vulgps","mini","vulcan"])' in html,
            'DATED_STOCK_EXCLUSIONS_MISSING')
    require('products.filter(p=>!unavailableProducts.has(p.id)&&' in html,
            'PICKER_STOCK_FILTER_MISSING')
    require('Skladová dostupnost není ověřena živým feedem.' in html,
            'UNVERIFIED_AVAILABILITY_DISCLOSURE_MISSING')
    require(html.count('Hledat dostupnou alternativu ↗') == 3,
            'THREE_UNAVAILABLE_CARD_CTA_REPLACEMENTS_MISSING')

    cj = []
    for href, rel in links.hrefs:
        parsed = urlsplit(href)
        if parsed.scheme not in ('http', 'https'):
            continue
        require(parsed.scheme == 'https', 'UNENCRYPTED_EXTERNAL_LINK')
        if parsed.netloc == 'na-ryby.eu':
            continue  # Internal navigation is not an affiliate clickout.
        require(parsed.hostname == CJ_HOST and parsed.path == CJ_PATH,
                'DIRECT_OR_UNEXPECTED_MERCHANT_LINK')
        require({'nofollow', 'sponsored'}.issubset(set(rel.lower().split())),
                'AFFILIATE_REL_MISSING')
        qs = parse_qs(parsed.query, strict_parsing=True)
        require(set(qs) == {'url'} and len(qs['url']) == 1,
                'AFFILIATE_DESTINATION_INVALID')
        target = urlsplit(qs['url'][0])
        require(target.scheme == 'https' and target.netloc == 'www.chytapust.cz'
                and not target.query and not target.fragment, 'UNEXPECTED_CJ_DESTINATION')
        cj.append(href)

    require(len(cj) >= 12, 'TOO_FEW_TRACKED_SHOP_LINKS')
    # Both static cards and JS-derived URLs must avoid exact unavailable items.
    for slug in EXCLUDED_SLUGS:
        require(slug not in html, 'UNAVAILABLE_PRODUCT_DESTINATION_STILL_PRESENT:' + slug)
    return {
        'state': 'PUBLIC_' + ('V6_COMPROMISE' if v6 else 'V5_STOCK_GUARD') + '_AND_AFFILIATE_VERIFIED',
        'url': URL,
        'observed_version': version,
        'sha256': digest,
        'tracked_external_anchors': len(cj),
        'excluded_known_unavailable_models': 3,
        'merchant_http_requests': 0,
        'wordpress_writes': 0,
        'live_inventory_feed': 'NOT_CONNECTED',
        'commission_attribution': 'NOT_VERIFIED',
    }


def main():
    req = Request(URL, method='GET', headers={
        'User-Agent': 'NaRyby-Independent-V5-Public-Readback/1.0',
        'Cache-Control': 'no-cache, no-store',
        'Accept': 'text/html',
    })
    try:
        with urlopen(req, timeout=25) as response:
            status = response.status
            actual_url = response.geturl()
            headers = {k.lower(): v for k, v in response.headers.items()}
            body = response.read(150001)
        if len(body) > 150000:
            raise ValueError('PUBLIC_HTML_EXCEEDS_SAFETY_LIMIT')
        print(json.dumps(verify(body, headers, status, actual_url), ensure_ascii=False,
                         sort_keys=True))
    except (OSError, ValueError, UnicodeError) as error:
        print(json.dumps({'state': 'PUBLIC_V5_OR_V6_UNVERIFIED', 'reason': str(error),
                          'url': URL, 'wordpress_writes': 0, 'merchant_http_requests': 0},
                         ensure_ascii=False, sort_keys=True))
        sys.exit(1)


if __name__ == '__main__':
    main()
