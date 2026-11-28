#!/usr/bin/env python3
"""Anonymous bounded first-party public check, no merchant/affiliate clicks."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/ubytovani-2/'
MARKER = 'data-nry-legacy-stays-entry="v1"'
STYLE = 'nry-legacy-stays-entry-v1.css'
DESTS = (
    'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
    'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/',
    'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/',
    'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/',
    'https://na-ryby.eu/chata-rybarsky-wellness-srub/',
    'https://na-ryby.eu/nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to/',
)

class Archive(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.index = 0
        self.sections = 0
        self.section_position = None
        self.section_depth = 0
        self.links = []
        self.styles = []
        self.heading = None
        self.page_h1_position = None
        self.first_result_position = None

    def handle_starttag(self, tag, attrs):
        self.index += 1
        props = dict(attrs)
        if tag == 'section' and props.get('data-nry-legacy-stays-entry') == 'v1':
            self.sections += 1
            self.section_position = self.index
            self.section_depth = 1
        elif self.section_depth and tag == 'section':
            self.section_depth += 1
        if tag in ('h1','h2','h3') and self.heading is None:
            self.heading = [tag, self.index, []]
        if tag == 'a' and self.section_depth:
            self.links.append(props.get('href'))
        if tag == 'link' and 'stylesheet' in (props.get('rel') or '').split() and STYLE in (props.get('href') or ''):
            self.styles.append(props['href'])

    def handle_data(self, data):
        if self.heading is not None:
            self.heading[2].append(data)

    def handle_endtag(self, tag):
        if self.heading is not None and tag == self.heading[0]:
            text = ' '.join(''.join(self.heading[2]).split())
            if tag == 'h1' and text == 'Ubytování v ČR' and self.page_h1_position is None:
                self.page_h1_position = self.heading[1]
            if tag in ('h2','h3') and 'Rekreační dům Harvánek' in text and self.first_result_position is None:
                self.first_result_position = self.heading[1]
            self.heading = None
        if tag == 'section' and self.section_depth:
            self.section_depth -= 1

def check(html):
    p = Archive()
    p.feed(html)
    p.close()
    assert html.count(MARKER) == 1 and p.sections == 1, 'STAYS_ENTRY_NOT_EXACTLY_ONCE'
    assert p.links == list(DESTS), 'STAYS_ENTRY_DESTINATION_OR_ORDER_DRIFT'
    assert p.styles == ['https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/nry-legacy-stays-entry-v1.css?ver=1.2.0'], 'STAYS_ENTRY_CSS_NOT_EXACTLY_ONCE'
    assert p.page_h1_position is not None, 'STAYS_ARCHIVE_H1_DRIFT'
    assert p.first_result_position is not None, 'STAYS_ARCHIVE_FIRST_LISTING_DRIFT'
    assert p.page_h1_position < p.section_position < p.first_result_position, 'STAYS_ENTRY_AFTER_HISTORICAL_RESULTS'
    if '--verify' in sys.argv:
        assert html.count('data-nry-stay-planner="v1"') == 1 and html.count('data-nry-plan=') == 6, 'STAY_PLANNER_FIELDS_MISSING'
    return 'NRY_LEGACY_STAYS_ENTRY_PUBLIC_VERIFIED'

if __name__ == '__main__':
    assert sys.argv[1:] == ['--verify']
    request = Request(URL, method='GET', headers={
        'User-Agent': 'NaRybyStaysArchiveReadback/1.0', 'Cache-Control': 'no-cache'})
    with guarded_urlopen(request, timeout=20) as response:
        raw = response.read(1200001)
    assert len(raw) <= 1200000, 'STAYS_ARCHIVE_PAGE_TOO_LARGE'
    print(check(raw.decode('utf-8','replace')))
