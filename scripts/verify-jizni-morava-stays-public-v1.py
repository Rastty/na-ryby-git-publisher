#!/usr/bin/env python3
"""Anonymous GET-only proof of post 12166 source-scoped regional lodging.

Never navigates to Slevomat, Hportal, Hauzi or Dyje 6 from this monitor;
never posts WordPress data, sends synthetic clicks or infers commissions.
"""
import datetime
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = Path(__file__).resolve().parents[1]
ORIGIN = 'https://na-ryby.eu'
PATH = '/rybarska-dovolena-jizni-morava/'
JS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-jizni-morava-stays-v1.css'
CURRENT_VERSION = 'ver=1.11.2'
PREVIOUS_VERSION = 'ver=1.11.1'
OFFERS = {
    '2464086': 'https://www.slevomat.cz/akce/2464086-dovolena-na-palave-polopenze-i-vstup-do-wellness?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_na-ryby.eu&utm_content=stay_12166_termal_musov&utm_term=j3d7tsgc&a_box=j3d7tsgc',
    'hportal-penzion-pasohlavky': 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fpasohlavky%2Fpenzion-pasohlavky%2F',
    'hauzi-chalupa-dolni-vestonice': 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchalupa-dolni-vestonice',
}
LEGACY_AGODA = 'https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D1418%26textToSearch%3DOlomouc%26ds%3DNVXXV6AsTxxuHYjr'


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical = []
        self.scripts = []
        self.styles = []
        self.marker_count = 0
        self.archive_count = 0
        self.inside = False
        self.article = None
        self.ctas = {}
        self.guide_links = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').split():
                self.canonical.append(a.get('href'))
            href = urlsplit(a.get('href') or '')
            if href.path == CSS:
                self.styles.append(href.query)
        if tag == 'script':
            src = urlsplit(a.get('src') or '')
            if src.path == JS and src.scheme in ('', 'https') and src.netloc in ('', 'na-ryby.eu'):
                self.scripts.append(src.query)
        if tag == 'section' and a.get('data-nry-jm-stays') == 'v1':
            self.marker_count += 1
            self.inside = True
        if tag == 'p' and a.get('data-nry-jm-archive') == 'v1':
            self.archive_count += 1
        if tag == 'article' and self.inside:
            self.article = a.get('data-nry-offer-id')
            self.ctas.setdefault(self.article, [])
        if tag == 'a' and self.inside:
            if self.article:
                self.ctas[self.article].append((a.get('href'), a.get('rel'), a.get('target')))
            else:
                self.guide_links.append(a.get('href'))

    def handle_endtag(self, tag):
        if tag == 'article':
            self.article = None
        if tag == 'section' and self.inside:
            self.inside = False


def verify(html, *, deployed=False, public_js=b'', reviewed_js=b'',
           public_css=b'', reviewed_css=b''):
    page = Page()
    page.feed(html.decode('utf-8', 'replace'))
    page.close()
    assert page.canonical == [ORIGIN + PATH], 'REGIONAL_CANONICAL_DRIFT'
    assert 'Chata Brněnská přehrada'.encode() in html, 'ORIGINAL_ARCHIVE_MISSING'
    assert 'Rybářský wellness srub'.encode() in html, 'ORIGINAL_REGIONAL_LINKS_MISSING'
    if not deployed:
        assert page.marker_count == 0, 'REGIONAL_PREDEPLOY_ALREADY_MODIFIED'
        assert page.archive_count == 0, 'REGIONAL_PREDEPLOY_ARCHIVE_ALREADY_MODIFIED'
        assert page.styles == [], 'REGIONAL_PREDEPLOY_STYLE_ALREADY_ENQUEUED'
        assert page.scripts in ([], [PREVIOUS_VERSION]), 'REGIONAL_PREDEPLOY_SCRIPT_DRIFT'
        assert 'agoda-booking-box'.encode() in html, 'REGIONAL_LEGACY_BOX_ALREADY_CHANGED'
        assert LEGACY_AGODA.encode() in html, 'REGIONAL_LEGACY_OLMOUC_URL_NOT_FOUND'
        return {'status': 'REGIONAL_PREFLIGHT_READY', 'source_post_id': 12166,
                'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}
    assert page.marker_count == 1 and page.archive_count == 1, 'REGIONAL_BLOCK_NOT_ONCE'
    assert set(page.ctas) == set(OFFERS), 'REGIONAL_OFFER_IDS_DRIFT'
    assert b'agoda-booking-box' not in html, 'WRONG_REGION_AGODA_BOX_STILL_VISIBLE'
    assert LEGACY_AGODA.encode() not in html, 'UNVERIFIED_OLMOUC_REDIRECT_VISIBLE'
    assert 'Garantovaná nejnižší cena'.encode() not in html, 'UNSUPPORTED_PRICE_GUARANTEE_VISIBLE'
    assert page.guide_links == [
        ORIGIN + '/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
        'https://www.rybnikarstvipohorelice.cz/dyje-6/',
    ], 'REGIONAL_REVIR_OR_OFFICIAL_LINK_DRIFT'
    for offer_id, href in OFFERS.items():
        actual = page.ctas[offer_id]
        assert actual == [(href, 'sponsored nofollow noopener', '_blank')], (
            'REGIONAL_AFFILIATE_URL_OR_REL_DRIFT')
    assert page.scripts == [CURRENT_VERSION], 'REGIONAL_JS_VERSION_DRIFT'
    assert page.styles == ['ver=1.0.0'], 'REGIONAL_CSS_NOT_ENQUEUED_ONCE'
    assert len(public_js) > 1000 and public_js == reviewed_js, 'REGIONAL_JS_BYTES_NOT_REVIEWED'
    assert len(public_css) > 200 and public_css == reviewed_css, 'REGIONAL_CSS_BYTES_NOT_REVIEWED'
    return {'status': 'REGIONAL_PUBLIC_VERIFIED', 'source_post_id': 12166,
            'exact_sponsored_offers': 3, 'source_revir': 'dyje6_14517',
            'buyer_js_sha256': hashlib.sha256(public_js).hexdigest(),
            'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0,
            'realClicks': None, 'fulfilledReservations': None, 'paidCommissions': None}


def get(url, max_bytes):
    assert url in (ORIGIN + PATH, ORIGIN + JS + '?' + CURRENT_VERSION,
                   ORIGIN + CSS + '?ver=1.0.0'), 'UNAPPROVED_REGIONAL_GET'
    request = Request(url, headers={'User-Agent': 'NaRyby-JM-StayGETOnly/1.0',
                                    'Cache-Control': 'no-cache, no-store'}, method='GET')
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'REGIONAL_GET_REDIRECT'
        body = response.read(max_bytes + 1)
    assert len(body) <= max_bytes, 'REGIONAL_BODY_TOO_LARGE'
    return body


def main():
    if datetime.date.today() > datetime.date(2027, 3, 31):
        raise ValueError('SLEVOMAT_VOUCHER_FRESHNESS_REVIEW_REQUIRED')
    assert len(sys.argv) == 2 and sys.argv[1] in ('--preflight', '--verify')
    deployed = sys.argv[1] == '--verify'
    result = verify(
        get(ORIGIN + PATH, 1500000), deployed=deployed,
        public_js=get(ORIGIN + JS + '?' + CURRENT_VERSION, 50000) if deployed else b'',
        reviewed_js=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if deployed else b'',
        public_css=get(ORIGIN + CSS + '?ver=1.0.0', 30000) if deployed else b'',
        reviewed_css=(ROOT / 'assets/nry-jizni-morava-stays-v1.css').read_bytes() if deployed else b'',
    )
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, OSError) as e:
        print(json.dumps({'status':'REGIONAL_PROOF_BLOCKED','reason':str(e)[:160]}))
        raise SystemExit(1)
