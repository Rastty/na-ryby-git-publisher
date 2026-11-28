#!/usr/bin/env python3
"""Independent anonymous readback of two first-party stay routes; no merchant requests."""
from __future__ import annotations
import re
from html import unescape
from pathlib import Path
import sys
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError

HUB = "https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/"
LINKS = (
    "https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/",
    "https://na-ryby.eu/chata-rybarsky-wellness-srub/",
)
MARKER = 'data-nry-vranov-stays-59891="v1"'
PREV = 'Statek Jedraž'
NEXT = 'Další možnosti ubytování v rybářských lokalitách'

# Sitewide header/mobile menus now legitimately repeat the same two target links.
# Check exact link identity/count/order inside the single source-pinned stay navigation.
BLOCK = re.compile(r'<nav\b[^>]*\b' + re.escape(MARKER) + r'[^>]*>.*?</nav>', re.S)
HREF = re.compile(r'\bhref="[^"]*"')

def verify(html: str) -> None:
    if html.count(MARKER) != 1:
        raise ValueError("expected exactly one Vranov navigation marker")
    found = BLOCK.search(html)
    if found is None:
        raise ValueError("Vranov marker must be on a nav element")
    expected = ['href="' + link + '"' for link in LINKS]
    actual = [unescape(h) for h in HREF.findall(found.group(0))]
    prefix='https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2F'
    expanded=[expected[0], 'href="'+prefix+'cyklochata"', expected[1], 'href="'+prefix+'rybarsky-srub"']
    if actual not in (expected, expanded):
        raise ValueError("missing, extra, duplicate or reordered Vranov stay href")
    start, end = html.find(PREV), html.find(NEXT)
    if not (0 <= start < found.start() < found.end() < end):
        raise ValueError("wrong public placement or missing original editorial section")
    if 'www.slevomat.cz/akce/2462030-' not in html:
        raise ValueError("original Slevomat offer missing")


def verify_expanded(html: str) -> None:
    from html.parser import HTMLParser
    from urllib.parse import urlsplit
    class Offers(HTMLParser):
        def __init__(self):
            super().__init__(convert_charrefs=True)
            self.found=0;self.anchors=[]
        def handle_starttag(self,tag,attrs):
            a=dict(attrs)
            if tag=='div' and a.get('data-nry-lipno-hub-offers')=='v1':self.found+=1
            if tag=='a' and 'data-nry-lipno-hub-offer' in a:
                self.anchors.append((a['data-nry-lipno-hub-offer'],a.get('href'),a.get('rel'),a.get('target')))
    p=Offers();p.feed(html);p.close()
    approved='https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2F'
    want=[(key,approved+slug,'sponsored nofollow noopener','_blank') for key,slug in (
        ('vyhlidka','komfortny-penzion-so-saunou-na-lipne'),('jbx','jbx-resort-albatros'))]
    if p.found!=1 or p.anchors!=want:raise ValueError('LIPNO_HUB_OFFERS_NOT_EXACT')
    if not (html.find(MARKER)<html.find('data-nry-lipno-hub-offers="v1"')<html.find(NEXT)):
        raise ValueError('LIPNO_HUB_POSITION_DRIFT')
    print('LIPNO_HUB_TWO_APPROVED_AFFILIATE_OFFERS_PUBLIC_VERIFIED')
    class Vranov(HTMLParser):
        def __init__(self):
            super().__init__(convert_charrefs=True)
            self.anchors=[];self.scripts=[]
        def handle_starttag(self,tag,attrs):
            a=dict(attrs)
            if tag=='a' and 'data-nry-vranov-hub-offer' in a:
                self.anchors.append((a['data-nry-vranov-hub-offer'],a.get('href'),a.get('rel'),a.get('target')))
            if tag=='script' and 'nry-stay-hub-booking-click-v1.js' in (a.get('src') or ''):
                self.scripts.append(a['src'])
    v=Vranov();v.feed(html);v.close()
    expected=[(key,approved+slug,'sponsored nofollow noopener','_blank') for key,slug in (('cyklo','cyklochata'),('srub','rybarsky-srub'))]
    if v.anchors!=expected:raise ValueError('VRANOV_HUB_OFFERS_NOT_EXACT')
    asset='https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/nry-stay-hub-booking-click-v1.js?ver=1.0.0'
    if v.scripts!=[asset]:raise ValueError('STAY_HUB_BOOKING_SCRIPT_MISSING')
    with urlopen(Request(asset,headers={'User-Agent':'NaRyby-PublicReadback/1.0'}),timeout=20) as resp:
        if resp.status!=200 or resp.url!=asset:raise ValueError('STAY_HUB_ASSET_REDIRECT')
        data=resp.read(50001)
    if data!=(Path(__file__).resolve().parents[1]/'assets/nry-stay-hub-booking-click-v1.js').read_bytes():
        raise ValueError('STAY_HUB_ASSET_DRIFT')
    print('STAY_HUB_FOUR_BOOKING_CTAS_AND_SCRIPT_PUBLIC_VERIFIED')

def main() -> int:
    try:
        req = Request(HUB, headers={"User-Agent": "NaRyby-PublicReadback/1.0",
                                    "Accept": "text/html"})
        with urlopen(req, timeout=20) as response:
            if response.status != 200 or response.url != HUB:
                raise ValueError("unexpected public status or redirect")
            raw = response.read(1_000_001)
            if len(raw) > 1_000_000:
                raise ValueError("unexpected public size")
        body=raw.decode("utf-8")
        verify(body)
        if "--expanded" in sys.argv:verify_expanded(body)
    except (ValueError, UnicodeError, HTTPError, URLError, TimeoutError) as exc:
        print("VRANOV_STAYS_59891_PUBLIC_NOT_VERIFIED:", type(exc).__name__)
        return 1
    print("VRANOV_STAYS_59891_PUBLIC_VERIFIED: 2 same-site links; original offer preserved")
    return 0

if __name__ == "__main__":
    sys.exit(main())
