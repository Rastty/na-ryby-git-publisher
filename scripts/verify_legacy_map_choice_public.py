#!/usr/bin/env python3
"""Bounded anonymous GET-only proof of the first choice before the old map iframe."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/dovolena-u-vody/'
MARKER = 'data-nry-map-discovery="v1"'
MAP_TOKEN = 'mapsengine.google.com/map/embed?mid=z3EK_WPx5q6o.ksrmQWY0S3AE'
LINKS = (
    'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/',
    'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
)

class MapPage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.order = 0
        self.section_order = None
        self.iframe_order = None
        self.sections = 0
        self.iframes = []
        self.section_depth = 0
        self.links = []

    def handle_starttag(self, tag, attrs):
        self.order += 1
        props = dict(attrs)
        if tag == 'section' and props.get('data-nry-map-discovery') == 'v1':
            self.sections += 1
            self.section_depth = 1
            self.section_order = self.order
        elif self.section_depth and tag == 'section':
            self.section_depth += 1
        if tag == 'iframe':
            self.iframes.append(props.get('src', ''))
            if self.iframe_order is None:
                self.iframe_order = self.order
        if self.section_depth and tag == 'a':
            self.links.append(props.get('href'))

    def handle_endtag(self, tag):
        if tag == 'section' and self.section_depth:
            self.section_depth -= 1


def check(html):
    parsed = MapPage()
    parsed.feed(html)
    parsed.close()
    assert html.count(MARKER) == 1 and parsed.sections == 1, 'MAP_CHOICE_NOT_EXACTLY_ONCE'
    assert len(parsed.iframes) == 1 and MAP_TOKEN in parsed.iframes[0], 'ORIGINAL_MAP_NOT_PRESERVED'
    assert parsed.links == list(LINKS), 'MAP_CHOICE_LINKS_CHANGED'
    assert parsed.section_order is not None and parsed.iframe_order is not None and parsed.section_order < parsed.iframe_order, 'MAP_CHOICE_NOT_BEFORE_IFRAME'
    return 'NRY_LEGACY_MAP_CHOICE_PUBLIC_VERIFIED'


if __name__ == '__main__':
    assert sys.argv[1:] == ['--verify'], 'VERIFY_ONLY'
    request = Request(URL, method='GET', headers={'User-Agent': 'NaRybyMapPublicProof/1.0', 'Cache-Control': 'no-cache'})
    with guarded_urlopen(request, timeout=20) as response:
        raw = response.read(700001)
    assert len(raw) <= 700000, 'MAP_PAGE_TOO_LARGE'
    print(check(raw.decode('utf-8', 'replace')))
