#!/usr/bin/env python3
"""Exact public GET-only verification of source-locked wellness srub Hauzi CTAs.
No merchant requests, synthetic events, WordPress writes or affiliate navigation.
"""
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/'
MARKER = 'data-nry-wellness-srub-offer="v1"'
AFFILIATE = 'https://tracking.affiliateport.eu/click?o=1990&amp;a=1736&amp;sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub'
OWNER = 'https://www.e-chalupy.cz/lancov-vranovska-prehrada-ubytovani-pronajmuti-rybarsky-srub-o922'
LEGACY = ('anrdoezrs.net/click-8287231-15735403',
          'Garantovaná nejnižší cena',
          'Pro rybáře loď na\xa0zavážení nástrah a návnad(stále prokrmovaná místa).')
CSS = 'nry-wellness-srub-hauzi-17536-v1.css'

class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.links = []
        self.words = []
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link' and 'canonical' in (a.get('rel') or '').lower().split():
            self.canonicals.append(a.get('href'))
        if tag == 'link' and CSS in (a.get('href') or ''):
            self.styles.append(a.get('href'))
        if tag == 'a':
            self.links.append((a.get('href'), a.get('rel', '')))
    def handle_data(self, s):
        self.words.append(s)

def verify(html):
    if not isinstance(html, str) or not 10000 <= len(html) <= 2000000:
        raise ValueError('PUBLIC_HTML_BOUNDS')
    p = Document()
    p.feed(html)
    p.close()
    if p.canonicals != [URL]:
        raise ValueError('EXACT_CANONICAL_MISMATCH')
    if html.count(MARKER) != 1:
        raise ValueError('EXACT_HERO_MARKER_MISSING_OR_DUPLICATE')
    if len(p.styles) != 1:
        raise ValueError('REVIEWED_CSS_NOT_LOADED_ONCE')
    if html.count(AFFILIATE) != 2:
        raise ValueError('TWO_EXACT_APPROVED_AFFILIATE_ANCHORS_REQUIRED')
    # HTMLParser unescapes attributes by design; never navigate href.
    plain_affiliate = AFFILIATE.replace('&amp;', '&')
    ct = [(h, r) for h, r in p.links if h == plain_affiliate]
    if len(ct) != 2 or any(not {'sponsored','nofollow','noopener'}.issubset(set(r.split())) for h,r in ct):
        raise ValueError('SPONSORED_SAFE_LINKS_REQUIRED')
    if sum(h == OWNER for h,r in p.links) != 1:
        raise ValueError('OWNER_LISTING_REQUIRED')
    if any(x in html for x in LEGACY):
        raise ValueError('UNSUPPORTED_LEGACY_OFFER_OR_FISHING_CLAIM')
    text = ' '.join(' '.join(p.words).split())
    for needed in ('Zjistit termín Wellness srubu na Hauzi',
                   'Ověřit konkrétní srub na Hauzi',
                   'nižší hladina Vranovské přehrady',
                   'povolenku',
                   'jejich aktuální dostupnost ale není ověřena'):
        if needed not in text:
            raise ValueError('REVIEWED_CAVEAT_OR_CTA_MISSING')
    return {'status':'WELLNESS_SRUB_HAUZI_OFFER_PUBLIC_VERIFIED',
            'sourcePostId':17536, 'exactAffiliateButtons':2,
            'operatorSourceLinks':1, 'legacyAgodaLinks':0,
            'wordpressWrites':0, 'merchantRequests':0,
            'syntheticClicks':0, 'realClicks':'NOT_MEASURED',
            'approvedPaidCommissions':'UNKNOWN'}

def get_public():
    req = Request(URL, method='GET', headers={
        'User-Agent':'NaRybyWellnessSrubSourcePinnedReadOnly/1.0',
        'Cache-Control':'no-cache,no-store',
        'Accept':'text/html'})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200 or response.geturl() != URL:
            raise ValueError('PUBLIC_ORIGIN_OR_REDIRECT_DRIFT')
        body = response.read(2000001)
        if len(body) > 2000000:
            raise ValueError('PUBLIC_HTML_TOO_LARGE')
    return body.decode('utf-8','replace')

if __name__ == '__main__':
    if sys.argv[1:] != ['--verify']:
        raise SystemExit('use --verify')
    try:
        print(json.dumps(verify(get_public()),ensure_ascii=False,sort_keys=True))
    except (AssertionError,ValueError,OSError,UnicodeDecodeError) as e:
        print(json.dumps({'status':'WELLNESS_SRUB_HAUZI_PUBLIC_BLOCKED','reason':type(e).__name__}))
        raise SystemExit(1)
