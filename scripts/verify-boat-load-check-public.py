#!/usr/bin/env python3
"""GET-only source guard and post-deploy readback for the boat load buyer tool.

Does not click affiliate links, write WordPress content, or emit analytics.
"""
import argparse
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
URL = SITE + '/nejlepsi-nafukovaci-cluny-pro-rybare/'
REST = SITE + '/wp-json/wp/v2/posts/119598?_fields=id,slug,status,modified_gmt,link,content'
ANCHOR = '<h2 style="font-size: 30px; line-height: 1.3; margin: 36px 0 16px 0; color: #0f172a;">Nosnost: proč nestačí počítat jen lidi</h2>'
MARKER = 'data-nry-boat-load="v1"'
CJ = 'click-100927011-12347575'
SCRIPT_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-boat-load-v1.js'
ASSET = Path(__file__).resolve().parents[1] / 'assets' / 'nry-boat-load-v1.js'


class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hrefs = []
        self.canonical = []
        self.scripts = []

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == 'a':
            self.hrefs.append(data.get('href'))
        if tag == 'link' and 'canonical' in (data.get('rel') or '').split():
            self.canonical.append(data.get('href'))
        if tag == 'script' and SCRIPT_PATH in (data.get('src') or ''):
            self.scripts.append(data.get('src'))


def fetch(url, size_limit):
    req = Request(url, headers={'User-Agent': 'NaRybyBoatLoadReadonly/1.0',
                                'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise RuntimeError('PUBLIC_RESPONSE_ORIGIN_OR_REDIRECT_DRIFT')
        data = response.read(size_limit + 1)
    if len(data) > size_limit:
        raise RuntimeError('PUBLIC_RESPONSE_TOO_LARGE')
    return data


def source():
    post = json.loads(fetch(REST, 900000).decode('utf-8'))
    if (post.get('id') != 119598 or post.get('slug') != 'nejlepsi-nafukovaci-cluny-pro-rybare'
            or post.get('status') != 'publish' or post.get('modified_gmt') != '2026-05-15T12:17:30'
            or post.get('link') != URL):
        raise RuntimeError('REST_SOURCE_IDENTITY_OR_DATE_DRIFT')
    html = post.get('content', {}).get('rendered')
    if (not isinstance(html, str) or len(html) < 18000 or html.count(ANCHOR) != 1
            or html.count(CJ) != 13 or 'Rychlý orientační výpočet zátěže' not in html
            or MARKER in html):
        raise RuntimeError('REST_SOURCE_OR_AFFILIATE_DRIFT')
    parsed = Links()
    parsed.feed(html)
    if len([href for href in parsed.hrefs if href and CJ in href]) != 13:
        raise RuntimeError('REST_CJ_HREF_COUNT_DRIFT')
    return parsed.hrefs


def public(rest_hrefs, deployed):
    page = fetch(URL, 2000000).decode('utf-8')
    parsed = Links()
    parsed.feed(page)
    # Non-buyer shared layout links can differ. Only compare original CJ hrefs.
    cj_rest = [href for href in rest_hrefs if href and CJ in href]
    cj_public = [href for href in parsed.hrefs if href and CJ in href]
    if parsed.canonical != [URL] or cj_public != cj_rest:
        raise RuntimeError('CANONICAL_OR_EXISTING_CJ_LINK_DRIFT')
    if deployed:
        if page.count(MARKER) != 1 or len(parsed.scripts) != 1 or page.find(MARKER) <= page.find(ANCHOR):
            raise RuntimeError('BOAT_TOOL_MISSING_OR_WRONG_POSITION')
        asset_url = parsed.scripts[0]
        target = urlparse(asset_url)
        if (target.scheme != 'https' or target.netloc != 'na-ryby.eu'
                or target.path != SCRIPT_PATH or target.query != 'ver=1.0.0'):
            raise RuntimeError('JS_URL_OR_VERSION_DRIFT')
        if hashlib.sha256(fetch(asset_url, 50000)).digest() != hashlib.sha256(ASSET.read_bytes()).digest():
            raise RuntimeError('JS_SERVING_DIFFERENT_BYTES')
    elif page.count(MARKER) > 1 or len(parsed.scripts) > 1:
        raise RuntimeError('DUPLICATE_TOOL_FOUND')
    return {'state': 'BOAT_LOAD_TOOL_PUBLIC_VERIFIED' if deployed else 'BOAT_LOAD_SOURCE_PREFLIGHT_READY',
            'post_id': 119598, 'existing_CJ_hrefs_untouched': len(cj_rest),
            'tool_live': deployed, 'wordpress_writes': 0, 'merchant_requests': 0,
            'synthetic_clicks': 0}


if __name__ == '__main__':
    args = argparse.ArgumentParser()
    args.add_argument('--public', action='store_true', help='Require installed public PHP and exact JS bytes')
    parsed = args.parse_args()
    print(json.dumps(public(source(), parsed.public), sort_keys=True))
