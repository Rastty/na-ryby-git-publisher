#!/usr/bin/env python3
"""Independent GET-only proof of four source-pinned product-copy corrections."""
import json
from html.parser import HTMLParser
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

PAGES = {
    'trakker-prepravni-vozik-x-trail-t1-barrow-recenze': {
        'old': ['přívěsný vozík.'],
        'new': ['přepravní rybářský vozík.', '9,4 kg'],
        'merchant': 'Trakker-Prepravni-vozik-X-Trail-T1-Barrow',
    },
    'mivardi-prepravni-vozik-executive-recenze': {
        'old': ['Maximální nosnost vozíku dosahuje až 150 kg'],
        'new': ['nosnost 120 kg', '13,5 kg'],
        'merchant': 'Mivardi-Prepravni-vozik-Executive',
    },
    'mivardi-transportni-vozik-premium-recenze': {
        'old': ['Nošenost Mivardi Transportního vozíku Premium je až 100 kg',
                'Díky své nosnosti až 100 kg'],
        'new': ['nosnost 120 kg', '11,5 kg'],
        'merchant': 'Mivardi-Transportni-vozik-Premium',
    },
    'delphin-vozik-moove-recenze': {
        'old': ['prostředek pro pohodlné přepravování dětí',
                'Maximální nosnost vozíku Moove je 150 kg',
                'www.uswebsite.com/relevantpost', 'odnímatelným sedadlem pro děti'],
        'new': ['nosnost 25 kg', 'nikoli osob nebo dětí'],
        'merchant': 'Delphin-Vozik-Moove',
    },
}


class Probe(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.style = False
        self.markers = 0
        self.panel_links = []
        self.panel = False
        self.merchant_hrefs = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'link' and 'canonical' in (attrs.get('rel') or '').split():
            self.canonicals.append(attrs.get('href'))
        if tag == 'link' and attrs.get('rel') == 'stylesheet':
            self.style |= 'nry-barrow-buyer-v1.css' in attrs.get('href', '')
        if tag == 'aside' and attrs.get('data-nry-barrow-buyer') == 'v1':
            self.markers += 1
            self.panel = True
        if tag == 'a':
            href = attrs.get('href') or ''
            if self.panel:
                self.panel_links.append(href)
            if ('www.tkqlhce.com/click-' in href or 'www.dpbolvw.net/click-' in href):
                self.merchant_hrefs.append(href)

    def handle_endtag(self, tag):
        if tag == 'aside':
            self.panel = False


def verify(slug, source):
    url = f'https://na-ryby.eu/produkt/{slug}/'
    p = Probe()
    p.feed(source)
    if p.canonicals != [url]:
        raise RuntimeError(f'BLOCKED_CANONICAL_DRIFT {slug} {p.canonicals}')
    if p.markers != 1 or not p.style:
        raise RuntimeError(f'NOT_DEPLOYED_PANEL_OR_STYLES {slug}')
    expected = [f'https://na-ryby.eu/produkt/{other}/' for other in PAGES if other != slug]
    if p.panel_links != expected:
        raise RuntimeError(f'BLOCKED_CROSS_LINK_DRIFT {slug}: {p.panel_links}')
    if not any(PAGES[slug]['merchant'] in href for href in p.merchant_hrefs):
        raise RuntimeError(f'BLOCKED_ORIGINAL_MERCHANT_LINK_MISSING {slug}')
    for removed in PAGES[slug]['old']:
        if removed in source:
            raise RuntimeError(f'STALE_FALSE_CLAIM {slug}: {removed}')
    for added in PAGES[slug]['new']:
        if added not in source:
            raise RuntimeError(f'CORRECTION_NOT_VISIBLE {slug}: {added}')
    for concept in ('šířku nejužšího průchodu', 'počet kol', 'redakce vozík netestovala'):
        if concept not in source:
            raise RuntimeError(f'BUYER_GUIDANCE_NOT_VISIBLE {slug}: {concept}')
    return dict(url=url, result='PUBLIC_BARROW_BUYER_TRUST_VERIFIED',
                panel_count=p.markers, linked_comparisons=len(p.panel_links),
                tracked_merchant_links=len(p.merchant_hrefs), stored_wp_writes=0)


def fetch(slug):
    url=f'https://na-ryby.eu/produkt/{slug}/'
    request=Request(url+'?nry_barrow_readback=20261117',
                    headers={'User-Agent': 'NaRybyPublicBarrowAudit/1.0',
                             'Cache-Control': 'no-cache'})
    with guarded_urlopen(request, timeout=25) as response:
        if response.status != 200 or response.geturl().split('?')[0] != url:
            raise RuntimeError('BLOCKED_RESPONSE_OR_REDIRECT '+slug)
        body=response.read(1_000_001)
    if len(body)>1_000_000:
        raise RuntimeError('BLOCKED_OVERSIZE_HTML '+slug)
    return body.decode('utf-8')


if __name__ == '__main__':
    for slug in PAGES:
        print(json.dumps(verify(slug,fetch(slug)),ensure_ascii=False))
