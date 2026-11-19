#!/usr/bin/env python3
"""Anonymous GET-only identity, editorial and exact sponsored-link readback.

No partner request, click simulation, WordPress write or revenue assertion.
"""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[1]
SITE = 'https://na-ryby.eu'
PATH = '/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'
REST = SITE + '/wp-json/wp/v2/posts/92205?_fields=id,type,status,slug,link,modified_gmt,content'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-stredni-cechy-stay-offer-v1.css'
MARKER = 'data-nry-sc-stay="v1"'
PARTNER = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F'
HAUZI = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchata-slapy-skalice'
STAYS = SITE + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
SOURCE_PREFIX = '926f27c5dd1290e8d5816406fb82d4b68429cd98d930b5ca31efef2803696240'


class OfferPage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.blocks = 0
        self.inside = False
        self.ctas = []
        self.offers = []
        self.styles = []
        self.canonicals = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').split():
                self.canonicals.append(a.get('href'))
            parsed = urlsplit(a.get('href') or '')
            if parsed.path == CSS and parsed.scheme in ('', 'https') and parsed.netloc in ('', 'na-ryby.eu'):
                self.styles.append(parsed.path + ('?' + parsed.query if parsed.query else ''))
        if tag == 'section' and a.get('data-nry-sc-stay') == 'v1':
            self.blocks += 1
            self.inside = True
        if self.inside and tag == 'article' and a.get('data-nry-sc-offer'):
            self.offers.append(a['data-nry-sc-offer'])
        if self.inside and tag == 'a':
            self.ctas.append((a.get('href'), a.get('rel'), a.get('target')))

    def handle_endtag(self, tag):
        if self.inside and tag == 'section':
            self.inside = False


def get(url, limit):
    assert url in (SITE + PATH, REST, SITE + CSS + '?ver=1.0.0', SITE + CSS + '?ver=1.1.0')
    req = Request(url, headers={'User-Agent': 'NaRyby-StredniCechy-ReadOnly/1.0',
                                'Cache-Control': 'no-cache, no-store'})
    with urlopen(req, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'REDIRECT_OR_HTTP_DRIFT'
        payload = response.read(limit + 1)
        assert len(payload) <= limit, 'OVERSIZED_PUBLIC_RESPONSE'
        return payload


def verify_rest(data):
    assert data['id'] == 92205 and data['type'] == 'post' and data['status'] == 'publish'
    assert data['slug'] == 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach'
    assert data['link'] == SITE + PATH and data['modified_gmt'] == '2024-10-07T12:15:48'
    content = data['content']['rendered']
    assert hashlib.sha256(content.encode('utf-8')[:512]).hexdigest() == SOURCE_PREFIX, 'SOURCE_PREFIX_DRIFT'
    assert content.count('<h1>Zážitkové rybaření s ubytováním ve středních Čechách</h1>') == 1
    assert content.count('height: 100vh') == 1, 'LEGACY_BANNER_SOURCE_DRIFT'
    assert content.count('Nabízíme nezapomenutelné') == 1, 'UNVERIFIED_OWNER_CLAIM_SOURCE_DRIFT'
    for exact in (
        's možností ubytování přímo na břehu rybníka.',
        'Mnoho rybníků nabízí i možnost výuky od zkušených instruktorů, kteří vám rádi poradí a pomohou s vaším lovem.',
        '<p>Zážitkové rybaření s ubytováním zahrnuje profesionálního průvodce, rybářské vybavení, ubytování a stravování v krásné přírodě středních Čech.</p>',
        '<p>Ubytování je možné pro skupiny o velikosti od 2 do 6 osob, v závislosti na dostupnosti.</p>',
        '<p>Nabízíme nezapomenutelné zážitkové rybaření s možností ubytování ve středních Čechách&#8230;.</p>',
        '<h3>Tip na zajímavý rybářský soukromý rybářský areál v Česku</h3>',
        '<a href="https://cs.wikipedia.org/wiki/Jind%C5%99ichovice_pod_Smrkem">Jindřichovice pod Smrkem</a>',
        '<a href="https://na-ryby.eu/booking-cesko">',
    ):
        assert content.count(exact) == 1, 'EXACT_EDITORIAL_REPLACEMENT_SOURCE_DRIFT'
    assert 'go.dognet.com' not in content, 'RAW_WP_ALREADY_HAS_PARTNER_URL'


def public_preflight(html):
    page = OfferPage()
    page.feed(html)
    page.close()
    assert page.canonicals == [SITE + PATH] and 'postid-92205' in html, 'PAGE_ID_DRIFT'
    if page.blocks:
        # The existing single Hportal offer is already live. Accept exactly
        # that old state on PR, but demand two precise choices after deploy.
        if page.offers == ['slapy-janata']:
            assert page.ctas == [(PARTNER, 'sponsored nofollow noopener', '_blank'),
                                 (STAYS, None, None)], 'UNEXPECTED_LIVE_BASELINE_CTA'
            assert page.styles == [CSS + '?ver=1.0.0'], 'UNEXPECTED_OLD_STYLES'
            return {'status': 'STREDNI_CECHY_HAUZI_PREFLIGHT_READY', 'postId': 92205,
                    'existingAffiliateOffers': 1, 'plannedAffiliateOffers': 2,
                    'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}
        return public_verify(html, get(SITE + CSS + '?ver=1.1.0', 30000),
                             (ROOT / 'assets/nry-stredni-cechy-stay-offer-v1.css').read_bytes())
    assert 'height: 100vh' in html and 'Screenshot_960.png' in html, 'OLD_BANNER_BASELINE_DRIFT'
    assert 'Nabízíme nezapomenutelné' in html, 'OLD_CLAIM_BASELINE_DRIFT'
    return {'status': 'STREDNI_CECHY_STAY_PREFLIGHT_READY', 'postId': 92205,
            'affiliateOffers': 0, 'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}


def public_verify(html, css_public, css_reviewed):
    page = OfferPage()
    page.feed(html)
    page.close()
    assert page.canonicals == [SITE + PATH] and 'postid-92205' in html, 'PAGE_ID_DRIFT'
    assert page.blocks == 1 and page.offers == ['slapy-janata', 'hauzi-slapy-skalice'], 'OFFER_MARKER_DRIFT'
    assert page.ctas == [(PARTNER, 'sponsored nofollow noopener', '_blank'),
                         (HAUZI, 'sponsored nofollow noopener', '_blank'),
                         (STAYS, None, None)], 'EXACT_URL_OR_LINK_REL_DRIFT'
    assert page.styles == [CSS + '?ver=1.1.0'], 'STYLE_ENQUEUE_DRIFT'
    assert css_public == css_reviewed and len(css_public) > 500, 'STYLES_NOT_BYTE_IDENTICAL'
    assert html.count('Screenshot_960.png') == 0, 'OVERSIZED_LEGACY_BANNER_REMAINS'
    assert 'Nabízíme nezapomenutelné' not in html, 'UNVERIFIED_OWNER_CLAIM_REMAINS'
    assert 'Jindřichovice pod Smrkem</a>' not in html, 'MISLOCATED_WIKIPEDIA_REC_REMAINS'
    assert 'Zážitkové rybaření ve středních Čechách' in html
    assert html.find(MARKER) < html.find('<h2>Zážitkové rybaření ve středních Čechách</h2>'), 'CTA_BURIED'
    return {'status': 'STREDNI_CECHY_STAY_PUBLIC_VERIFIED', 'postId': 92205,
            'exactSponsoredOffers': 2, 'oversizedBannerRemoved': True,
            'falseServiceClaimsRepaired': True, 'cssByteIdentical': True,
            'wordpressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0,
            'realClicks': None, 'fulfilledReservations': None, 'paidCommissions': None}


def main():
    assert sys.argv[1:] in (['--preflight'], ['--verify']), 'PHASE_REQUIRED'
    preflight = sys.argv[1] == '--preflight'
    verify_rest(json.loads(get(REST, 150000).decode('utf-8')))
    html = get(SITE + PATH, 1500000).decode('utf-8', 'replace')
    if preflight:
        out = public_preflight(html)
    else:
        out = public_verify(html, get(SITE + CSS + '?ver=1.1.0', 30000),
                            (ROOT / 'assets/nry-stredni-cechy-stay-offer-v1.css').read_bytes())
    print(json.dumps(out, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, OSError, KeyError) as exc:
        print(json.dumps({'status': 'STREDNI_CECHY_STAY_BLOCKED', 'reason': str(exc)[:160]}))
        raise SystemExit(1)
