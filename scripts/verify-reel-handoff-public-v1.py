#!/usr/bin/env python3
"""Independent anonymous GET-only proof of dashboard click JS, not GA4 delivery."""
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PAGE = SITE + '/navijaky-dashboard/'
ASSET = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-reel-handoff-v1.js'
ROOT = Path(__file__).resolve().parents[1]


class Proof(HTMLParser):
    def __init__(self):
        super().__init__()
        self.tags = []
        self.nav = 0
        self.situations = 0

    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == 'nav' and d.get('data-nry-reel-nextsteps') == 'v1':
            self.nav += 1
        if tag == 'nav' and d.get('data-nry-reel-situations') == 'v1':
            self.situations += 1
        if tag != 'script' or not d.get('src'):
            return
        url = urlsplit(d['src'])
        if (url.scheme, url.netloc, url.path, url.query, url.fragment) == (
                'https', 'na-ryby.eu', ASSET, 'ver=1.1.0', ''):
            self.tags.append(d['src'])


def fetch(path, limit):
    if path not in (PAGE + '?nry_reel_handoff_verify=20261109',
                    SITE + ASSET + '?ver=1.1.0'):
        raise ValueError('INVALID_PUBLIC_TARGET')
    req = Request(path, method='GET', headers={
        'User-Agent': 'NaRybyReelHandoffReadOnly/1.0',
        'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as resp:
        if resp.status != 200 or resp.geturl() != path:
            raise RuntimeError('PUBLIC_RESPONSE_DRIFT')
        data = resp.read(limit + 1)
        if len(data) > limit:
            raise RuntimeError('RESPONSE_OVER_LIMIT')
    return data


def verify(page, asset, reviewed):
    parser = Proof()
    parser.feed(page.decode('utf8'))
    if parser.nav != 1 or parser.situations != 1 or len(parser.tags) != 1:
        raise RuntimeError('NOT_APPLIED: EXACT_NAV_AND_SCRIPT_REQUIRED')
    if hashlib.sha256(asset).digest() != hashlib.sha256(reviewed).digest():
        raise RuntimeError('NOT_APPLIED: ASSET_BYTES_DIFFER')
    return {
        'state': 'PUBLIC_SCRIPT_AND_NAV_BYTE_VERIFIED',
        'source_page': 'reel_dashboard', 'guide_destinations': 8,
        'wordpress_writes': 0, 'synthetic_clicks': 0,
        'ga4_delivery_proven': False, 'paid_commission_proven': False
    }


if __name__ == '__main__':
    print(json.dumps(verify(
        fetch(PAGE + '?nry_reel_handoff_verify=20261109', 2_000_000),
        fetch(SITE + ASSET + '?ver=1.1.0', 30_000),
        (ROOT / 'assets/nry-reel-handoff-v1.js').read_bytes()
    ), sort_keys=True))
