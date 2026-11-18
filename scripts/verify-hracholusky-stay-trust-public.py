#!/usr/bin/env python3
"""Anonymous GET-only semantic monitor for bounded Hracholusky trust cleanup."""

import json
import sys
import urllib.request
from urllib.parse import urlsplit

PAGE = "https://na-ryby.eu/bungalov-u-prehrady-hracholusky/"
STAYS = "https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/"
MARKER = 'data-nry-hracholusky-stay-trust="v1"'


def verify(html):
    """No synthetic clicks or partner redirects; inspect only rendered HTML."""
    if not isinstance(html, str):
        return False
    start = html.find('<section class="nry-hracholusky-stay-trust" ' + MARKER)
    end = html.find("</section>", start) if start >= 0 else -1
    block = html[start:end] if end > start else ""
    return all((
        html.count(MARKER) == 1,
        bool(block),
        block.count('href="' + STAYS + '"') == 1,
        'Pension U Vlčků' in block,
        'nepotvrdili' in block,
        'nezaručuje povolenku' in block,
        'Bungalov U přehrady Hracholusky' in html,
        'agoda-booking-box' not in html,
        'anrdoezrs.net/click-8287231-15735403' not in html,
        'Garantovaná nejnižší cena' not in html,
    ))


def main():
    req = urllib.request.Request(PAGE, headers={
        "Cache-Control": "no-cache", "Pragma": "no-cache",
        "User-Agent": "NaRyby-Hracholusky-Stay-ReadOnly/1.0",
    })
    with urllib.request.urlopen(req, timeout=25) as response:
        final = urlsplit(response.geturl())
        if response.status != 200 or final.scheme != "https" or final.netloc != "na-ryby.eu":
            raise ValueError("unsafe final response")
        html = response.read(1_200_001).decode("utf-8", "replace")
        if len(html) > 1_200_000:
            raise ValueError("unexpectedly long response")
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
