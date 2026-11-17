#!/usr/bin/env python3
"""GET-only exact buyer guide + historical CJ click-observer public proof.

No merchant requests, cookies, GA4 events, synthetic click, or WordPress write.
"""
import argparse
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import parse_qs, urlparse
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PAGES = {
    119594: ('/nejlepsi-lodni-elektromotory-pro-rybare/', '2026-05-15T10:37:45',
             'Baterie k elektromotoru: olovo, AGM nebo LiFePO4?'),
    119598: ('/nejlepsi-nafukovaci-cluny-pro-rybare/', '2026-05-15T12:17:30',
             'Nosnost: proč nestačí počítat jen lidi'),
}
ASSET_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-boat-motor-click-v1.js'
LOCAL_ASSET = Path(__file__).resolve().parents[1] / 'assets' / 'nry-boat-motor-click-v1.js'
CJ_PATH = '/click-100927011-12347575'
EXPECTED_TARGETS = {'/Elektromotory-265/', '/Cluny-228/',
                    '/Baterie-nabijecky-k-elektromotorum/'}


class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hrefs, self.scripts, self.canonical = [], [], []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'a' and a.get('href'):
            self.hrefs.append(a['href'])
        if tag == 'script' and ASSET_PATH in (a.get('src') or ''):
            self.scripts.append(a['src'])
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonical.append(a.get('href'))


def request(url, limit):
    req = Request(url, headers={'User-Agent': 'NaRybyBoatMotorClicksReadOnly/1.0',
                                'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise RuntimeError('PUBLIC_REDIRECT_OR_HTTP_DRIFT')
        raw = response.read(limit + 1)
    if len(raw) > limit:
        raise RuntimeError('PUBLIC_RESPONSE_TOO_LARGE')
    return raw


def parse(html):
    result = Document()
    result.feed(html)
    return result


def cj_hrefs(links):
    selected = []
    for href in links:
        outer = urlparse(href)
        if outer.hostname != 'www.tkqlhce.com' or outer.path != CJ_PATH:
            continue
        if outer.scheme != 'https' or outer.port or outer.username or outer.password or outer.fragment:
            raise RuntimeError('UNSAFE_HISTORIC_CJ_LINK')
        query = parse_qs(outer.query, keep_blank_values=True)
        if list(query) != ['url'] or len(query['url']) != 1:
            raise RuntimeError('CJ_QUERY_SHAPE_DRIFT')
        dest = urlparse(query['url'][0])
        if (dest.scheme != 'https' or dest.netloc != 'www.chytapust.cz'
                or dest.path not in EXPECTED_TARGETS or dest.query or dest.fragment):
            raise RuntimeError('CJ_MERCHANT_TARGET_DRIFT')
        selected.append(href)
    if len(selected) != 13:
        raise RuntimeError('CJ_LINK_COUNT_DRIFT')
    return selected


def check(deployed):
    counts = {}
    for id, (path, modified, anchor) in PAGES.items():
        url = SITE + path
        rest_url = SITE + f'/wp-json/wp/v2/posts/{id}?_fields=id,slug,status,modified_gmt,link,content'
        doc = json.loads(request(rest_url, 900000).decode())
        if (doc.get('id') != id or doc.get('status') != 'publish'
                or doc.get('slug') != path.strip('/') or doc.get('link') != url
                or doc.get('modified_gmt') != modified):
            raise RuntimeError(f'POST_{id}_IDENTITY_DRIFT')
        rendered = doc.get('content', {}).get('rendered')
        if not isinstance(rendered, str) or len(rendered) < 18000 or anchor not in rendered:
            raise RuntimeError(f'POST_{id}_SOURCE_DRIFT')
        original = cj_hrefs(parse(rendered).hrefs)
        live = parse(request(url, 2000000).decode())
        if live.canonical != [url] or cj_hrefs(live.hrefs) != original:
            raise RuntimeError(f'POST_{id}_LIVE_CJ_OR_CANONICAL_DRIFT')
        if deployed and len(live.scripts) != 1:
            raise RuntimeError(f'POST_{id}_OBSERVER_NOT_DEPLOYED')
        if not deployed and len(live.scripts) > 1:
            raise RuntimeError(f'POST_{id}_OBSERVER_DUPLICATED')
        if deployed:
            script_url = urlparse(live.scripts[0])
            if (script_url.scheme != 'https' or script_url.netloc != 'na-ryby.eu'
                    or script_url.path != ASSET_PATH or script_url.query != 'ver=1.0.0'
                    or script_url.fragment):
                raise RuntimeError(f'POST_{id}_JS_VERSION_DRIFT')
        counts[str(id)] = len(original)
    if deployed:
        served = request(SITE + ASSET_PATH + '?ver=1.0.0', 50000)
        if hashlib.sha256(served).digest() != hashlib.sha256(LOCAL_ASSET.read_bytes()).digest():
            raise RuntimeError('EXACT_PUBLIC_JS_BYTES_DRIFT')
    return {'state': 'BOAT_MOTOR_CLICK_OBSERVER_PUBLIC_VERIFIED' if deployed
            else 'BOAT_MOTOR_CLICK_SOURCE_READY', 'reviewed_CJ_hrefs': counts,
            'observed_real_click_count': None, 'ga4_delivery_proven': False,
            'affiliate_commission_proven': False, 'merchant_requests': 0,
            'synthetic_clicks': 0, 'wordpress_writes': 0}


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--public', action='store_true')
    args = parser.parse_args()
    print(json.dumps(check(args.public), sort_keys=True))
