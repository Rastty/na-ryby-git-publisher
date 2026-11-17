#!/usr/bin/env python3
"""Public, read-only proof of homepage buyer-entry instrumentation.

--preflight confirms the five existing site paths and advisor callout.
Normal mode additionally requires a single exact versioned plugin script and
byte identity with the reviewed local JS. Neither mode emits analytics events.
"""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import parse_qs, urlsplit
from urllib.request import Request, urlopen

HOME = 'https://na-ryby.eu/'
SCRIPT_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-home-buyer-entry-v1.js'
ASSET = Path(__file__).resolve().parents[1] / 'assets/nry-home-buyer-entry-v1.js'
BUYER_PATHS = (
    '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
    '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
    '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',
    '/jak-vybrat-zavazeci-lodku/',
    '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',
)


class Homepage(HTMLParser):
    def __init__(self):
        super().__init__()
        self.in_nav = 0
        self.in_advisor = 0
        self.routes = []
        self.advisor = []
        self.scripts = []
        self.canonicals = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'nav' and a.get('data-nry-home-problem-shortcuts') == 'v3':
            self.in_nav += 1
        elif tag == 'p' and a.get('data-nry-home-advisor-entry') == 'v1':
            self.in_advisor += 1
        elif tag == 'script' and a.get('id') == 'nry-home-buyer-entry-v1-js':
            self.scripts.append(a.get('src', ''))
        elif tag == 'link' and 'canonical' in a.get('rel', '').lower().split():
            self.canonicals.append(a.get('href', ''))
        if tag == 'a' and self.in_nav:
            self.routes.append(a.get('href', ''))
        if tag == 'a' and self.in_advisor:
            self.advisor.append(a.get('href', ''))

    def handle_endtag(self, tag):
        if tag == 'nav' and self.in_nav:
            self.in_nav -= 1
        if tag == 'p' and self.in_advisor:
            self.in_advisor -= 1


def verify_home(html, preflight=False):
    doc = Homepage()
    doc.feed(html)
    if doc.canonicals != [HOME]:
        raise ValueError('HOMEPAGE_CANONICAL_MISMATCH')
    if tuple(doc.routes) != tuple(HOME.rstrip('/') + x for x in BUYER_PATHS):
        raise ValueError('FIVE_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
    if doc.advisor != [HOME + 'poradce-vybavy/']:
        raise ValueError('EXACT_HOMEPAGE_ADVISOR_LINK_MISSING')
    if preflight:
        return {'state': 'FIVE_BUYER_ROUTES_AND_ADVISOR_PUBLIC_PREFLIGHT', 'asset_verified': False}
    if len(doc.scripts) != 1:
        raise ValueError('ONE_HOMEPAGE_MEASUREMENT_SCRIPT_REQUIRED')
    src = urlsplit(doc.scripts[0])
    if (src.scheme != 'https' or src.netloc != 'na-ryby.eu' or src.path != SCRIPT_PATH
            or parse_qs(src.query) != {'ver': ['1.0.0']} or src.fragment):
        raise ValueError('HOMEPAGE_SCRIPT_IDENTITY_MISMATCH')
    return {'state': 'HOMEPAGE_MEASUREMENT_SCRIPT_DECLARED', 'script_url': doc.scripts[0]}


def fetch(url, max_bytes=350000):
    req = Request(url, headers={'User-Agent': 'NaRyby-Home-Buyer-Readback/1.0',
                                'Accept': 'text/html,application/javascript,*/*',
                                'Cache-Control': 'no-cache'})
    with urlopen(req, timeout=25) as r:
        if r.status != 200 or r.geturl() != url:
            raise ValueError('PUBLIC_HTTP_200_EXACT_URL_REQUIRED')
        data = r.read(max_bytes + 1)
        if len(data) > max_bytes:
            raise ValueError('PUBLIC_RESPONSE_TOO_LARGE')
        return data, r.headers.get('Content-Type', '')


def main():
    preflight = '--preflight' in sys.argv
    try:
        body, mime = fetch(HOME)
        if 'text/html' not in mime.lower():
            raise ValueError('HOMEPAGE_HTML_MIME_REQUIRED')
        proof = verify_home(body.decode('utf8'), preflight)
        if not preflight:
            js, mime = fetch(proof['script_url'], 50000)
            if not ('javascript' in mime.lower() or 'text/plain' in mime.lower()):
                raise ValueError('SCRIPT_MIME_REQUIRED')
            approved = ASSET.read_bytes()
            if len(approved) < 1000 or js != approved:
                raise ValueError('PUBLIC_SCRIPT_DIFFERS_FROM_REVIEWED_BYTES')
            proof['state'] = 'HOMEPAGE_BUYER_ENTRY_PUBLIC_BYTES_VERIFIED'
            proof['script_sha256'] = hashlib.sha256(js).hexdigest()
        proof.update(home=HOME, wordpress_writes=0, synthetic_clicks=0,
                     ga4_event_delivery='NOT_PROVEN', affiliate_sales='NOT_PROVEN')
        print(json.dumps(proof, ensure_ascii=False, sort_keys=True))
    except (ValueError, OSError, UnicodeError) as exc:
        print(json.dumps({'state': 'PUBLIC_HOMEPAGE_MEASUREMENT_UNVERIFIED',
                          'reason': str(exc), 'wordpress_writes': 0,
                          'synthetic_clicks': 0}, ensure_ascii=False))
        sys.exit(1)


if __name__ == '__main__':
    main()
