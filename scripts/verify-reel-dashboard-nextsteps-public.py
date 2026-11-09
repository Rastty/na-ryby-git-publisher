#!/usr/bin/env python3
"""Independent GET-only public proof of four first-party reel-dashboard paths."""
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

TARGET = 'https://na-ryby.eu/navijaky-dashboard/'
EXPECTED_SITUATIONS = [
    'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/',
    'https://na-ryby.eu/nejlepsi-privlacove-navijaky-jak-vybrat/',
    'https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/',
    'https://na-ryby.eu/nejlepsi-kaprove-navijaky/',
]
EXPECTED = [
    'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/',
    'https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/',
    'https://na-ryby.eu/pece-o-navijak-po-sezone-jak-ho-neznicit-spatnym-mazanim/',
    'https://na-ryby.eu/poradce-vybavy/',
]

class Nextsteps(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.blocks = 0
        self.inside = False
        self.hrefs = []
        self.situation_blocks = 0
        self.situation_inside = False
        self.situation_hrefs = []
        self.canonicals = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link' and 'canonical' in (a.get('rel') or '').lower().split():
            self.canonicals.append(a.get('href', ''))
        if tag == 'nav' and a.get('data-nry-reel-nextsteps') == 'v1':
            self.blocks += 1
            self.inside = True
        elif tag == 'nav' and a.get('data-nry-reel-situations') == 'v1':
            self.situation_blocks += 1
            self.situation_inside = True
        elif tag == 'a' and (self.inside or self.situation_inside):
            if a.get('onclick') or a.get('target') or a.get('data-affiliate'):
                raise ValueError('UNEXPECTED_TRACKED_CLICKOUT')
            if self.inside:
                self.hrefs.append(a.get('href'))
            else:
                self.situation_hrefs.append(a.get('href'))

    def handle_endtag(self, tag):
        if tag == 'nav' and self.inside:
            self.inside = False
        elif tag == 'nav' and self.situation_inside:
            self.situation_inside = False

def fetch():
    req = Request(TARGET + '?nry_reel_nav_verify=20261109', headers={
        'Cache-Control': 'no-cache,no-store',
        'User-Agent': 'NaRybyReelDashboardReadback/1.0'})
    with guarded_urlopen(req, timeout=25) as resp:
        if resp.status != 200 or resp.geturl().split('?')[0] != TARGET:
            raise RuntimeError('BLOCKED: WRONG_PUBLIC_RESPONSE')
        raw = resp.read(2_000_001)
    if len(raw) > 2_000_000:
        raise RuntimeError('BLOCKED: PUBLIC_PAGE_TOO_LARGE')
    return raw.decode('utf-8')

def verify(html):
    document = Nextsteps()
    document.feed(html)
    if document.blocks != 1:
        raise RuntimeError('NOT_APPLIED: EXPECTED_EXACTLY_ONE_NAV_BLOCK')
    if document.hrefs != EXPECTED:
        raise RuntimeError('NOT_APPLIED: FIRST_PARTY_GUIDE_TARGETS_MISMATCH')
    if document.situation_blocks != 1 or document.situation_hrefs != EXPECTED_SITUATIONS:
        raise RuntimeError('NOT_APPLIED: EXACT_FOUR_SITUATION_GUIDES_REQUIRED')
    if document.canonicals != [TARGET]:
        raise RuntimeError('NOT_APPLIED: CANONICAL_DRIFT')
    if 'DOPLNIT-URL' in html[html.index('data-nry-reel-nextsteps="v1"'):][:3000]:
        raise RuntimeError('NOT_APPLIED: PLACEHOLDER_IN_NAV')
    return {'state': 'PUBLIC_RENDERED_NAV_VERIFIED', 'target': TARGET,
            'internal_guides': len(document.hrefs),
            'situation_guides': len(document.situation_hrefs), 'wordpress_writes': 0,
            'merchant_requests': 0, 'affiliate_clicks': 0}

if __name__ == '__main__':
    print(json.dumps(verify(fetch()), ensure_ascii=False, sort_keys=True))
