#!/usr/bin/env python3
"""GET-only public verification of three 2023 carp mat repair cohorts."""
import json
from html.parser import HTMLParser
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

MAIN = ('https://na-ryby.eu/'
        'jake-jsou-klicove-faktory-pri-vyberu-rybarske-podlozky-pod-kapra-pro-zlepseni-ulovku/')
SOURCE = ('https://old.rybsvaz.cz/beta/index.php/119-vseobecne/'
          '849-vyprostovani-hacku-a-foceni-ulovku-2')
AFFILIATE = 'https://na-ryby.eu/podlozky'
PAGES = {
    'jake-jsou-klicove-faktory-pri-vyberu-rybarske-podlozky-pod-kapra-pro-zlepseni-ulovku': {
        'removed': ['správnou zátěž prutu', 'efektivnímu přenosu vibrací',
                    'zvyšuje pravděpodobnost úspěšného ulovení kapra',
                    'kapr mohl na ní bezpečně stát'],
        'present': ['Neovlivňuje záběr', 'polstrování pomáhá oddělit',
                    'zlepšíte především'],
    },
    'testujeme-ruzne-druhy-rybarskych-podlozek-na-ryby-kterou-zvolit-pro-vase-potreby': {
        'removed': ['Během testování jsme používali', 'Na základě našich výsledků',
                    'Potápivé podložky', 'Podložky na plávající montáž'],
        'present': ['historický text nedokládá žádný vlastní srovnávací test',
                    'Je podložka součást montáže?',
                    'Hloubka vody neurčuje typ podložky'],
    },
    'kde-nakupovat-rybarske-podlozky-pod-kapra-v-ceske-republice': {
        'removed': ['křupavé kůži kapra', 'Keywords:',
                    'podložku, kterou kryjeme zem a na ni položíme montáž',
                    '<li>Rybářské potřeby Max, s.r.o.</li>'],
        'present': ['hojení ran nezrychluje',
                    'Podložka nemá vliv na záběr',
                    'Ověřené podmínky vrácení'],
    },
}


class Probe(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.affiliates = 0
        self.notices = 0
        self.notice_open = False
        self.notice_links = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'link' and 'canonical' in (attrs.get('rel') or '').split():
            self.canonicals.append(attrs.get('href'))
        if tag == 'link' and attrs.get('rel') == 'stylesheet':
            self.styles.append(attrs.get('href', ''))
        if tag == 'aside' and attrs.get('data-nry-carp-mat-buyer') == 'v1':
            self.notices += 1
            self.notice_open = True
        if tag == 'a':
            href = attrs.get('href')
            if href == AFFILIATE:
                self.affiliates += 1
            if self.notice_open:
                self.notice_links.append(href)

    def handle_endtag(self, tag):
        if tag == 'aside':
            self.notice_open = False


def verify(slug, source):
    url = 'https://na-ryby.eu/' + slug + '/'
    p = Probe()
    p.feed(source)
    if p.canonicals != [url]:
        raise RuntimeError('BLOCKED_CANONICAL_DRIFT ' + slug)
    if p.notices != 1 or p.affiliates != 4:
        raise RuntimeError('NOT_DEPLOYED_OR_AFFILIATE_DRIFT ' + slug)
    expected_links = [SOURCE] if url == MAIN else [SOURCE, MAIN]
    if p.notice_links != expected_links:
        raise RuntimeError('NOT_DEPLOYED_NOTICE_LINKS ' + slug)
    if not any('nry-carp-mat-buyer-v1.css' in x for x in p.styles):
        raise RuntimeError('NOT_DEPLOYED_STYLES ' + slug)
    for old in PAGES[slug]['removed']:
        if old in source:
            raise RuntimeError('WRONG_CLAIM_REMAINS ' + old)
    for new in PAGES[slug]['present']:
        if new not in source:
            raise RuntimeError('CORRECTION_MISSING ' + new)
    for concept in ('Lehká skládací', 'Kolébka s bočnicemi', 'Nafukovací'):
        if concept not in source:
            raise RuntimeError('BUYER_SCENARIO_MISSING ' + concept)
    return dict(url=url, state='PUBLIC_MAT_GUIDE_VERIFIED',
                affiliate_links_preserved=p.affiliates, buyer_scenarios=3,
                public_claim_corrections=len(PAGES[slug]['present']),
                new_guide_links=0 if url == MAIN else 1,
                wordpress_stored_writes=0)


def fetch(slug):
    url = 'https://na-ryby.eu/' + slug + '/'
    req = Request(url+'?nry_mat_probe=20261117',
                  headers={'User-Agent': 'NaRybyPublicMatAudit/1.0',
                           'Cache-Control': 'no-cache'})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200 or response.geturl().split('?')[0] != url:
            raise RuntimeError('BLOCKED_UNEXPECTED_RESPONSE ' + slug)
        body = response.read(1_000_001)
    if len(body) > 1_000_000:
        raise RuntimeError('BLOCKED_TOO_MUCH_HTML ' + slug)
    return body.decode('utf-8')


if __name__ == '__main__':
    for slug in PAGES:
        print(json.dumps(verify(slug, fetch(slug)), ensure_ascii=False))
