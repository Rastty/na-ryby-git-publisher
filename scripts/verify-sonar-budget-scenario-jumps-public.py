#!/usr/bin/env python3
"""Anonymous GET-only public proof of the three buyer shortcuts."""
from html.parser import HTMLParser
from urllib.request import Request, urlopen
from html import unescape
import re
import sys
import time

URL = ('https://na-ryby.eu/'
       'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/')
MARKER = 'data-nry-budget-scenario-jumps="118455-20261115-v1"'
IDS = ('nry-budget-eagle4x', 'nry-budget-hook5', 'nry-budget-eagle7')


class Reader(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.headings = set()
        self.links = []
        self.canonical = []
        self.current_h3 = None
        self.labels = {}

    def handle_starttag(self, tag, attrs):
        attr = dict(attrs)
        if tag == 'link' and 'canonical' in (attr.get('rel') or ''):
            self.canonical.append(attr.get('href'))
        if tag == 'h3':
            self.current_h3 = attr.get('id')
            if self.current_h3 in IDS:
                self.headings.add(self.current_h3)
        if tag == 'a':
            self.links.append(attr.get('href'))

    def handle_endtag(self, tag):
        if tag == 'h3':
            self.current_h3 = None


def check(html):
    assert html.count(MARKER) == 1, 'MISSING_NAVIGATION_MARKER'
    assert html.count('<!-- nry-sonar-budget-trust-118455-v1 -->') == 1, 'MISSING_TRUST'
    assert html.count('data-nry-budget-owner-first="118455-20261110-v1"') == 1, 'MISSING_OWNER_FIRST'
    document = Reader()
    document.feed(html)
    assert document.canonical == [URL], 'CANONICAL_DRIFT'
    assert document.headings == set(IDS), 'BUYER_SECTIONS_NOT_ALL_ANCHORED'
    for value in IDS:
        assert document.links.count('#' + value) == 1, 'JUMP_LINK_UNIQUE_' + value
        assert html.count('id="' + value + '"') == 1, 'ANCHOR_ID_UNIQUE_' + value
    owner = html.index('data-nry-budget-owner-first="118455-20261110-v1"')
    nav = html.index(MARKER)
    first_cta = html.index('Zjistit cenu', nav)
    assert owner < nav < first_cta, 'NAVIGATION_NOT_BEFORE_BUYER_CTA'
    assert 'go.dognet.com' in html and 'www.chytapust.cz' in html, 'ORIGINAL_MERCHANT_ROUTES_ABSENT'
    nav_end = html.find('</nav>', nav)
    assert nav_end > nav and nav_end - nav < 2500, 'NAV_REGION_NOT_BOUNDED'
    visible = ' '.join(unescape(re.sub(r'<[^>]+>', ' ', html[nav:nav_end])).split())
    assert 'Cenu a dostupnost vždy ověřte u prodejce.' in visible, 'BUYER_CAUTION_MISSING'
    return {'state': 'PUBLIC_VERIFIED', 'scenario_shortcuts': 3,
            'existing_merchant_links_preserved_by_offline_test': True,
            'site_writes': 0, 'offer_claims': 0}


if __name__ == '__main__':
    for attempt in range(3):
        try:
            request = Request(URL, headers={
                'User-Agent': 'NRY-BudgetScenarios-PublicProof/1.0',
                'Cache-Control': 'no-cache, no-store'})
            with urlopen(request, timeout=25) as response:
                assert response.status == 200 and response.geturl() == URL
                html = response.read(1200000).decode('utf-8', 'replace')
            print(check(html))
            sys.exit(0)
        except Exception as error:
            reason = str(error) if isinstance(error, AssertionError) else type(error).__name__
            print('PUBLIC_RETRY', attempt + 1, reason[:100], flush=True)
            if attempt < 2:
                time.sleep(6)
    sys.exit('BLOCKED: shortcuts not verified on independent public site')
