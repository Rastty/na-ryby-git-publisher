#!/usr/bin/env python3
"""Anonymous GET-only post-deploy readback; never visits merchant links."""
import json
import sys
import urllib.request
from urllib.parse import urlsplit

PAGE = "https://na-ryby.eu/tipy-na-vylety-pro-rybare-s-ubytovanim-v-prirode/"
STAYS = "https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/"
MARKER = 'data-nry-tipy-stay-trust="v1"'


def verify(html):
    if not isinstance(html, str):
        return False
    begin = html.find('<section class="nry-tipy-stay-trust" ' + MARKER)
    end = html.find("</section>", begin) if begin >= 0 else -1
    box = html[begin:end] if end > begin else ""
    return all((
        html.count(MARKER) == 1,
        bool(box),
        box.count('href="' + STAYS + '"') == 1,
        'Pension U Kohoutů' in box,
        'Pension U Vlčků' in box,
        'Pension Janata' in box,
        'partnerské nabídky' in box,
        'Vranovská přehrada &#8211; Jihomoravský kraj' in html
            or 'Vranovská přehrada – Jihomoravský kraj' in html,
        'rybářský lístek a příslušná povolenka' in html,
        'Bez přesného názvu' in html,
        'Moravské rybářské jezero, které leží' not in html,
        'Rybaření na pobřeží s ubytováním' not in html,
        'Náš tým je sám vášnivým rybářem' not in html,
        '<a href="https://www.lipno.info/"' in html,
    ))


def main():
    req = urllib.request.Request(PAGE, headers={
        "Cache-Control": "no-cache", "Pragma": "no-cache",
        "User-Agent": "NaRyby-Tipy-Stay-Trust-ReadOnly/1.0",
    })
    with urllib.request.urlopen(req, timeout=25) as response:
        final = urlsplit(response.geturl())
        if response.status != 200 or final.scheme != "https" or final.netloc != "na-ryby.eu":
            raise ValueError("unexpected final response")
        payload = response.read(1_600_001)
        if len(payload) > 1_600_000:
            raise ValueError("unexpectedly long response")
    html = payload.decode("utf-8", "replace")
    ok = verify(html)
    print(json.dumps({"url": PAGE, "status": "PUBLIC_VERIFIED" if ok else "PUBLIC_NOT_VERIFIED",
                      "marker_count": html.count(MARKER), "no_write": True}, ensure_ascii=False))
    return 0 if ok else 1


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (OSError, ValueError) as exc:
        print(json.dumps({"status": "READBACK_BLOCKED", "error_type": type(exc).__name__}))
        sys.exit(1)
