#!/usr/bin/env python3
"""Public, read-only proof of homepage buyer-entry instrumentation.

--preflight confirms the existing five-route public baseline or the six-route
candidate, plus the advisor callout.
Normal mode additionally requires exact versioned plugin JS + CSS tags and
byte identity with both reviewed local assets. No analytics events are emitted.
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
STYLE_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-home-buyer-entry-v1.css'
STYLE_ASSET = Path(__file__).resolve().parents[1] / 'assets/nry-home-buyer-entry-v1.css'
BUYER_PATHS = (
    '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
    '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
    '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',
    '/jak-vybrat-zavazeci-lodku/',
    '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',
    '/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/',
)


class Homepage(HTMLParser):
    def __init__(self):
        super().__init__()
        self.in_nav = 0
        self.in_advisor = 0
        self.routes = []
        self.nav_versions = []
        self.advisor = []
        self.scripts = []
        self.styles = []
        self.canonicals = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'nav' and a.get('data-nry-home-problem-shortcuts') in ('v3', 'v4'):
            self.in_nav += 1
            self.nav_versions.append(a['data-nry-home-problem-shortcuts'])
        elif tag == 'p' and a.get('data-nry-home-advisor-entry') == 'v1':
            self.in_advisor += 1
        elif tag == 'script' and a.get('id') == 'nry-home-buyer-entry-v1-js':
            self.scripts.append(a.get('src', ''))
        elif tag == 'link' and a.get('id') == 'nry-home-buyer-entry-style-v1-css':
            if 'stylesheet' not in a.get('rel', '').lower().split():
                self.styles.append('INVALID_STYLESHEET_REL')
            else:
                self.styles.append(a.get('href', ''))
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
    expected = tuple(HOME.rstrip('/') + x for x in BUYER_PATHS)
    current = doc.nav_versions == ['v4'] and tuple(doc.routes) == expected
    baseline = doc.nav_versions == ['v3'] and tuple(doc.routes) == expected[:-1]
    if not current and not (preflight and baseline):
        raise ValueError('SIX_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
    if doc.advisor != [HOME + 'poradce-vybavy/']:
        raise ValueError('EXACT_HOMEPAGE_ADVISOR_LINK_MISSING')
    if preflight:
        return {'state': 'BUYER_ROUTES_AND_ADVISOR_PUBLIC_PREFLIGHT', 'asset_verified': False,
                'routes_observed': len(doc.routes)}
    if len(doc.scripts) != 1:
        raise ValueError('ONE_HOMEPAGE_MEASUREMENT_SCRIPT_REQUIRED')
    src = urlsplit(doc.scripts[0])
    if (src.scheme != 'https' or src.netloc != 'na-ryby.eu' or src.path != SCRIPT_PATH
            or parse_qs(src.query) != {'ver': ['1.1.0']} or src.fragment):
        raise ValueError('HOMEPAGE_SCRIPT_IDENTITY_MISMATCH')
    if len(doc.styles) != 1:
        raise ValueError('ONE_HOMEPAGE_BUYER_STYLESHEET_REQUIRED')
    css = urlsplit(doc.styles[0])
    if (css.scheme != 'https' or css.netloc != 'na-ryby.eu' or css.path != STYLE_PATH
            or parse_qs(css.query) != {'ver': ['1.0.0']} or css.fragment):
        raise ValueError('HOMEPAGE_STYLE_IDENTITY_MISMATCH')
    return {'state': 'HOMEPAGE_BUYER_ASSETS_DECLARED', 'script_url': doc.scripts[0],
            'style_url': doc.styles[0]}


def fetch(url, max_bytes=350000):
    req = Request(url, headers={'User-Agent': 'NaRyby-Home-Buyer-Readback/1.0',
                                'Accept': 'text/html,text/css,application/javascript,*/*',
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
            css, mime = fetch(proof['style_url'], 50000)
            if 'text/css' not in mime.lower():
                raise ValueError('STYLE_MIME_REQUIRED')
            reviewed_css = STYLE_ASSET.read_bytes()
            if len(reviewed_css) < 1000 or css != reviewed_css:
                raise ValueError('PUBLIC_STYLE_DIFFERS_FROM_REVIEWED_BYTES')
            proof['state'] = 'HOMEPAGE_BUYER_ENTRY_PUBLIC_BYTES_VERIFIED'
            proof['script_sha256'] = hashlib.sha256(js).hexdigest()
            proof['style_sha256'] = hashlib.sha256(css).hexdigest()
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
