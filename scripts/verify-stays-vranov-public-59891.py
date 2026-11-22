#!/usr/bin/env python3
"""Independent anonymous readback of two first-party stay routes; no merchant requests."""
from __future__ import annotations
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

def verify(html: str) -> None:
    if html.count(MARKER) != 1:
        raise ValueError("expected exactly one Vranov navigation")
    if any(html.count('href="' + link + '"') != 1 for link in LINKS):
        raise ValueError("missing or duplicate first-party stay href")
    start, first, second, end = (html.find(x) for x in
        (PREV, 'href="' + LINKS[0] + '"', 'href="' + LINKS[1] + '"', NEXT))
    if not (-1 not in (start, first, second, end) and start < first < second < end):
        raise ValueError("wrong public placement or missing original editorial section")
    if 'www.slevomat.cz/akce/2462030-' not in html:
        raise ValueError("original Slevomat offer missing")

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
        verify(raw.decode("utf-8"))
    except (ValueError, UnicodeError, HTTPError, URLError, TimeoutError) as exc:
        print("VRANOV_STAYS_59891_PUBLIC_NOT_VERIFIED:", type(exc).__name__)
        return 1
    print("VRANOV_STAYS_59891_PUBLIC_VERIFIED: 2 same-site links; original offer preserved")
    return 0

if __name__ == "__main__":
    sys.exit(main())
