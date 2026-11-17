#!/usr/bin/env python3
"""GET-only motor battery estimator source preflight / post-deploy public readback.

No WordPress writes, affiliate clicks, merchant requests or user input collection.
"""
import argparse
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
from urllib.parse import urlparse

from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
URL = SITE + '/nejlepsi-lodni-elektromotory-pro-rybare/'
REST = SITE + '/wp-json/wp/v2/posts/119594?_fields=id,slug,status,modified_gmt,link,content'
ANCHOR = '<h3 style="font-size: 24px; margin: 24px 0 10px 0; color: #0f172a;">Jakou kapacitu baterie vybrat?</h3>'
MARKER = 'data-nry-motor-battery="v1"'
CJ = 'click-100927011-12347575'
SCRIPT_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-motor-battery-v1.js'
ASSET = Path(__file__).resolve().parents[1] / 'assets' / 'nry-motor-battery-v1.js'


class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.cj = []
        self.canonical = []
        self.scripts = []

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == 'a' and CJ in (data.get('href') or ''):
            self.cj.append(data['href'])
        if tag == 'link' and 'canonical' in (data.get('rel') or '').split():
            self.canonical.append(data.get('href'))
        if tag == 'script' and SCRIPT_PATH in (data.get('src') or ''):
            self.scripts.append(data.get('src'))


def fetch(url, limit):
    request = Request(url, headers={
        'User-Agent': 'NaRybyMotorBatteryReadOnly/1.0',
        'Cache-Control': 'no-cache, no-store',
    })
    with guarded_urlopen(request, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise RuntimeError('PUBLIC_RESPONSE_ORIGIN_OR_REDIRECT_DRIFT')
        raw = response.read(limit + 1)
    if len(raw) > limit:
        raise RuntimeError('PUBLIC_RESPONSE_TOO_LARGE')
    return raw


def source():
    post = json.loads(fetch(REST, 900000).decode('utf-8'))
    if (post.get('id') != 119594 or post.get('status') != 'publish'
            or post.get('slug') != 'nejlepsi-lodni-elektromotory-pro-rybare'
            or post.get('modified_gmt') != '2026-05-15T10:37:45'
            or post.get('link') != URL):
        raise RuntimeError('REST_POST_IDENTITY_OR_DATE_DRIFT')
    html = post.get('content', {}).get('rendered')
    if (not isinstance(html, str) or len(html) < 18000
            or html.count(ANCHOR) != 1 or html.count(CJ) != 13
            or 'Baterie k elektromotoru: olovo, AGM nebo LiFePO4?' not in html
            or MARKER in html):
        raise RuntimeError('REST_SOURCE_ANCHOR_OR_AFFILIATE_DRIFT')
    links = Links()
    links.feed(html)
    if len(links.cj) != 13:
        raise RuntimeError('REST_AFFILIATE_LINK_SHAPE_DRIFT')
    return links.cj


def public(original, expect_deployed):
    html = fetch(URL, 2000000).decode('utf-8')
    links = Links()
    links.feed(html)
    if links.canonical != [URL] or links.cj != original:
        raise RuntimeError('PUBLIC_CANONICAL_OR_AFFILIATE_DRIFT')
    if expect_deployed:
        if html.count(MARKER) != 1 or len(links.scripts) != 1:
            raise RuntimeError('CALCULATOR_OR_SCRIPT_NOT_DEPLOYED')
        asset_url = links.scripts[0]
        parsed = urlparse(asset_url)
        if (parsed.scheme != 'https' or parsed.netloc != 'na-ryby.eu'
                or parsed.path != SCRIPT_PATH or parsed.query != 'ver=1.0.0'):
            raise RuntimeError('SCRIPT_URL_OR_VERSION_DRIFT')
        served = fetch(asset_url, 50000)
        local = ASSET.read_bytes()
        if hashlib.sha256(served).digest() != hashlib.sha256(local).digest():
            raise RuntimeError('PUBLIC_SCRIPT_BYTES_NOT_CURRENT')
        if html.find(MARKER) <= html.find(ANCHOR):
            raise RuntimeError('CALCULATOR_NOT_IN_BATTERY_BUYER_SECTION')
    elif html.count(MARKER) > 1 or len(links.scripts) > 1:
        raise RuntimeError('PUBLIC_DUPLICATE_CALCULATOR')
    return {
        'state': 'PUBLIC_MOTOR_BATTERY_TOOL_VERIFIED' if expect_deployed else 'SOURCE_PREFLIGHT_READY_NOT_PUBLISHED',
        'post_id': 119594,
        'existing_affiliate_hrefs_preserved': len(original),
        'calculator_live': expect_deployed,
        'wordpress_writes': 0,
        'merchant_requests': 0,
        'synthetic_clicks': 0,
    }


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--public', action='store_true', help='Require production deployment and exact served JavaScript bytes')
    args = parser.parse_args()
    print(json.dumps(public(source(), args.public), sort_keys=True))
