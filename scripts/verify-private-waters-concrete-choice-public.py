#!/usr/bin/env python3
"""Anonymous GET-only exact private-water editorial examples proof."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/nejlepsi-soukrome-reviry-pro-rybolov-v-cesku/'
DESTS = (
 'https://na-ryby.eu/soukromy-revir-sterkac-zajeci/',
 'https://na-ryby.eu/soukromy-revir-rpety-hate-stredocesky-kraj/',
 'https://na-ryby.eu/soukromy-revir-rybnik-mrhal/',
 'https://na-ryby.eu/rybnik-spindler/',
 'https://na-ryby.eu/category/soukrome-rybarske-reviry/',
)
MARKER = 'data-nry-private-water-examples="v1"'
CSS = 'nry-private-waters-examples-v1.css'

class Public(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.position = 0
        self.count = 0
        self.at_section = 0
        self.section_position = None
        self.anchor_position = None
        self.anchor_name = None
        self.anchor_text = []
        self.hrefs = []
        self.css = 0
    def handle_starttag(self, tag, attrs):
        self.position += 1
        d = dict(attrs)
        if tag == 'section' and d.get('data-nry-private-water-examples') == 'v1':
            self.count += 1
            self.at_section = 1
            self.section_position = self.position
        elif tag == 'section' and self.at_section:
            self.at_section += 1
        if tag == 'a' and self.at_section:
            self.hrefs.append(d.get('href'))
        if tag == 'link' and CSS in (d.get('href') or ''):
            self.css += 1
        if tag == 'h2' and not self.at_section:
            self.anchor_name = tag
            self.anchor_position_candidate = self.position
            self.anchor_text = []
    def handle_data(self, data):
        if self.anchor_name is not None:
            self.anchor_text.append(data)
    def handle_endtag(self, tag):
        if tag == 'h2' and self.anchor_name == tag:
            txt = ' '.join(''.join(self.anchor_text).split())
            if txt == 'Podle čeho poznat dobrý soukromý revír':
                self.anchor_position = self.anchor_position_candidate
            self.anchor_name = None
        if tag == 'section' and self.at_section:
            self.at_section -= 1

def verify(html):
    p = Public()
    p.feed(html)
    p.close()
    assert html.count(MARKER) == 1 and p.count == 1, 'PRIVATE_WATERS_CARD_COUNT_DRIFT'
    assert p.hrefs == list(DESTS), 'PRIVATE_WATERS_LINK_COUNT_OR_DESTINATIONS_DRIFT'
    assert p.css == 1, 'PRIVATE_WATERS_STYLESHEET_MISSING_OR_DUPLICATE'
    assert p.anchor_position is not None, 'PRIVATE_WATERS_ORIGINAL_H2_MISSING'
    assert p.section_position < p.anchor_position, 'PRIVATE_WATERS_CARDS_AFTER_ORIGINAL_GUIDE'
    return 'NRY_PRIVATE_WATERS_CONCRETE_CHOICES_PUBLIC_VERIFIED'

if __name__ == '__main__':
    assert sys.argv[1:] == ['--verify']
    req = Request(URL, method='GET', headers={'User-Agent':'NaRybyPrivateWatersProof/1.0',
            'Cache-Control':'no-cache'})
    with guarded_urlopen(req, timeout=20) as response:
        raw = response.read(1200001)
    assert len(raw) < 1200001, 'PAGE_TOO_LARGE'
    print(verify(raw.decode('utf-8', 'replace')))
