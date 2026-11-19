#!/usr/bin/env python3
"""Anonymous GET-only visible offer check; never fetches merchant tracking links."""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

PUBLIC = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/'
MARKER = 'data-nry-cyklochata-offer="v1"'
SAFE_TRACKING = 'https://tracking.affiliateport.eu/click?o=1990&amp;a=1736&amp;sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata'
OLD_LINK = 'anrdoezrs.net/click-8287231-15735403'
OLD_CLAIM = 'Garantovaná nejnižší cena'


def verify_html(html):
    assert isinstance(html, str) and len(html) > 10000
    assert html.count(MARKER) == 1
    assert html.count(SAFE_TRACKING) == 2
    assert html.count('rel="sponsored nofollow noopener"') >= 2
    assert html.count('https://www.dovolenavranov.cz/cyklochata/') == 1
    assert 'Zjistit termín Cyklochaty na Hauzi' in html
    assert 'Ověřit konkrétní Cyklochatu' in html
    assert OLD_LINK not in html and OLD_CLAIM not in html
    return {'status': 'CYKLOCHATA_HAUZI_OFFER_VISIBLE', 'url': PUBLIC,
            'samePropertyOffers': 2, 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def main():
    assert sys.argv[1:] == ['--verify'], 'VERIFY_PHASE_REQUIRED'
    req = Request(PUBLIC, headers={'User-Agent': 'NaRyby-Cyklochata-Hauzi-Readback/1.0',
                                   'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        body = response.read(900000)
        assert len(body) < 900000, 'HTML_TOO_LARGE'
    print(json.dumps(verify_html(body.decode('utf-8')), ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, OSError, UnicodeDecodeError) as exc:
        print(json.dumps({'status': 'CYKLOCHATA_HAUZI_OFFER_NOT_VERIFIED',
                          'error': type(exc).__name__}))
        raise SystemExit(1)
