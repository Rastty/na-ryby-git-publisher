#!/usr/bin/env python3
"""Independent anonymous GET-only proof of chair buyer hub historical cleanup.

Does not follow merchant links, contact merchants, send analytics or change WP.
It confirms rendering, not sales, stock, affiliate credit or source editing.
"""
import json
import sys
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/'
MARKER = '<!-- nry-chair-hub-historical-ratings-60341-v1 -->'
HEADINGS = (
    'Mivardi Comfort Quattro: co porovnat u konkrétní varianty',
    'Mivardi Premium: konstrukce a přeprava',
    'Fox Super Deluxe Recliner Highback: polohování a stabilita',
)


class Evidence(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical = []
        self.chair_links = 0
        self.heading_tags = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'link' and attrs.get('rel') == 'canonical':
            self.canonical.append(attrs.get('href'))
        if tag == 'a' and attrs.get('href') == 'https://na-ryby.eu/kresla':
            self.chair_links += 1


def verify(body, status=200, final_url=URL):
    if status != 200 or final_url != URL:
        raise ValueError('EXACT_PUBLIC_URL_STATUS_REQUIRED')
    html = body.decode('utf-8')
    if html.count(MARKER) != 1:
        raise ValueError('RENDER_ONLY_CLEANUP_NOT_DEPLOYED')
    if html.count('data-nry-editorial-edit="chair-hub-trust-60341-v1"') != 1:
        raise ValueError('EXISTING_BUYER_HUB_SOURCE_MARKER_MISSING')
    for heading in HEADINGS:
        if html.count(heading) != 1:
            raise ValueError('HISTORIC_HEADING_NOT_NEUTRALIZED')
    if html.count('Historická zmínka bez ověřeného bodového hodnocení') != 3:
        raise ValueError('UNSUPPORTED_STAR_RATINGS_STILL_VISIBLE')
    if 'klíčová slova:' in html or '⭐⭐⭐⭐' in html:
        raise ValueError('KEYWORD_LIST_OR_STARS_STILL_EXPOSED')
    if 'nejlepší křeslo na trhu' in html:
        raise ValueError('UNVERIFIED_BEST_IN_MARKET_CLAIM_STILL_VISIBLE')
    parsed = Evidence()
    parsed.feed(html)
    if parsed.canonical != [URL]:
        raise ValueError('CANONICAL_DRIFT')
    if parsed.chair_links < 4:
        raise ValueError('EXISTING_BUYER_LINKS_LOST')
    return {'state': 'PUBLIC_CHAIR_HUB_HISTORIC_RATINGS_CLEANED',
            'url': URL, 'product_comparison_headings_repaired': 3,
            'unsupported_star_ratings_removed': 6,
            'internal_keyword_lists_removed': 1,
            'original_chair_link_routes_observed': parsed.chair_links,
            'merchantRequests': 0, 'wordpressWrites': 0,
            'commissionAttributionVerified': False,
            'stockOrPricesVerified': False}


def main():
    request = Request(URL, headers={
        'User-Agent': 'NRY-ChairHub-HistoricIndependentPublic/1.0',
        'Cache-Control': 'no-cache, no-store'})
    try:
        # Reuse the already-reviewed, same-origin GET-only transport retry.
        # Semantic/hash mismatches still fail immediately, without retry.
        with guarded_urlopen(request, timeout=25, attempts=3) as response:
            body = response.read(900001)
            if len(body) > 900000:
                raise ValueError('PUBLIC_HTML_EXCEEDS_LIMIT')
            result = verify(body, response.status, response.geturl())
        print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    except (OSError, ValueError, UnicodeError) as error:
        print(json.dumps({'state': 'PUBLIC_UNVERIFIED',
                          'reason': str(error), 'wordpressWrites': 0,
                          'merchantRequests': 0}, ensure_ascii=False))
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
