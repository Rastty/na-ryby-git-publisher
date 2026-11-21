#!/usr/bin/env python3
"""Anonymous GET-only publication proof of three actual, source-locked offers.

Compares page identity, approved network URLs, sponsorship, repaired internal
booking links and CSS/JS byte identity. It never follows affiliate links,
performs WP writes, or generates fake GA4 events.
"""
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
PAGE = '/nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice/'
JS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
JS_VERSION = 'ver=1.11.5'
JS_BASELINE = 'ver=1.11.4'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-sport-stays-85384-v1.css'
CSS_VERSION = 'ver=1.0.0'
PROOF = ORIGIN + '/poradce-vybavy/?nry_sport_stays_source_proof=1'
RAW_SHA256 = '6787d69c3c9040945a6a13df42b33fecdde4fba73fc2f279bb6e4145237a6969'
LEGACY = ORIGIN + '/booking-cesko'
STAYS = ORIGIN + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
OFFERS = [
    ('statek-jih-cech', 'https://www.slevomat.cz/akce/2462030-statek-na-jihu-cech-snidane-i-privatni-sauna-ci-rybareni?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_narybyCZ&utm_content=stay_59891_jedraz&utm_term=j3d7tsgc&a_box=j3d7tsgc'),
    ('slapy-janata', 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F'),
    ('palava-vestonice', 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchalupa-dolni-vestonice'),
]


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.scripts = []
        self.styles = []
        self.sections = 0
        self.inside = False
        self.offers = []
        self.anchors = []
        self.all_links = []
        self.body_classes = []
        # TagDiv's genuine post body is distinct from the header/banner/footer.
        # Track only DIV ancestry, not generic (possibly void) element depth.
        self.div_depth = 0
        self.article_div_depth = None
        self.article_region_count = 0
        self.article_links = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'div':
            self.div_depth += 1
            if 'td-post-content' in (a.get('class') or '').split():
                if self.article_div_depth is not None:
                    raise ValueError('NESTED_WORDPRESS_ARTICLE_WRAPPER')
                self.article_div_depth = self.div_depth
                self.article_region_count += 1
        if tag == 'body':
            self.body_classes = (a.get('class') or '').split()
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag in ('link', 'script'):
            src = a.get('src' if tag == 'script' else 'href') or ''
            uri = urlsplit(src)
            if uri.scheme in ('', 'https') and uri.netloc in ('', 'na-ryby.eu') and not uri.fragment:
                if tag == 'script' and uri.path == JS:
                    self.scripts.append(uri.query)
                if tag == 'link' and uri.path == CSS:
                    self.styles.append(uri.query)
        if tag == 'section' and a.get('data-nry-sport-stays') == 'v1':
            self.sections += 1
            self.inside = True
        if self.inside and tag == 'article' and a.get('data-nry-sport-offer'):
            self.offers.append(a.get('data-nry-sport-offer'))
        if tag == 'a':
            self.all_links.append(a.get('href'))
            if self.article_div_depth is not None:
                self.article_links.append(a.get('href'))
            if self.inside:
                self.anchors.append((a.get('href'), a.get('rel'), a.get('target')))

    def handle_endtag(self, tag):
        if tag == 'section' and self.inside:
            self.inside = False
        if tag == 'div':
            if self.article_div_depth == self.div_depth:
                self.article_div_depth = None
            self.div_depth -= 1
            if self.div_depth < 0:
                raise ValueError('UNBALANCED_WORDPRESS_DIVS')


def verify(html, *, deployed, css_bytes=b'', js_bytes=b'', reviewed_css=b'', reviewed_js=b''):
    surface = Surface()
    surface.feed(html.decode('utf-8', 'replace'))
    surface.close()
    assert surface.canonicals == [ORIGIN + PAGE], 'CANONICAL_MISMATCH'
    assert 'postid-85384' in surface.body_classes, 'WP_POST_IDENTITY_MISMATCH'
    if not deployed:
        assert surface.sections == 0 and surface.offers == [] and surface.anchors == [], 'UNEXPECTED_OLD_OFFER'
        # One additional legacy link belongs to the shared sitewide MTSNB
        # banner, outside this source-bound article. Do not rewrite it here.
        assert surface.all_links.count(LEGACY) == 4 and surface.all_links.count(STAYS) == 0, 'LEGACY_BASELINE_CHANGED'
        assert surface.styles == [] and surface.scripts in ([], [JS_BASELINE]), 'UNREVIEWED_SCRIPT_OR_STYLE'
        return {'status': 'SPORT_STAYS_OFFERS_PREFLIGHT_READY', 'postId': 85384,
                'articleLegacyBookingLinks': 3, 'sitewideLegacyBannerLinks': 1,
                'approvedAffiliateOffers': 0,
                'wordpressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}
    assert surface.sections == 1, 'EXACT_OFFER_SECTION_NOT_ONCE'
    assert surface.offers == [x[0] for x in OFFERS], 'OFFER_IDENTITIES_CHANGED'
    expected = [(url, 'sponsored nofollow noopener', '_blank') for _, url in OFFERS]
    expected.append((STAYS, None, None))
    assert surface.anchors == expected, 'AFFILIATE_URL_OR_SPONSORSHIP_DRIFT'
    assert surface.article_region_count == 1, 'EXACT_WORDPRESS_ARTICLE_REGION_REQUIRED'
    assert surface.article_links.count(LEGACY) == 0, 'OLD_BOOKING_ROUTE_REMAINS_INSIDE_ARTICLE'
    assert surface.article_links.count(STAYS) == 4, 'THREE_OLD_BOOKING_PATHS_NOT_REPAIRED'
    # Current public HTML has three additional first-party links to STAYS in
    # header/footer navigation. Those do not affect the article's four routes.
    assert surface.all_links.count(LEGACY) == 1, 'ONLY_SITEWIDE_LEGACY_BANNER_MAY_REMAIN'
    assert surface.all_links.count(STAYS) >= 4, 'PUBLIC_STAYS_LINKS_MISSING'
    assert surface.styles == [CSS_VERSION] and surface.scripts == [JS_VERSION], 'CSS_OR_JS_ASSET_NOT_EXACT'
    assert css_bytes == reviewed_css and len(css_bytes) > 800, 'CSS_BYTES_NOT_REVIEWED'
    assert js_bytes == reviewed_js and len(js_bytes) > 3000, 'JS_BYTES_NOT_REVIEWED'
    return {'status': 'SPORT_STAYS_OFFERS_PUBLIC_VERIFIED', 'postId': 85384,
            'exactSponsoredOffers': len(OFFERS), 'oldBookingLinksRepaired': 3,
            'sitewideLegacyBannerLinksUnchanged': 1,
            'cssByteIdentical': True, 'clickScriptByteIdentical': True,
            'clickObservationEnabled': True, 'ga4DeliveryProven': False,
            'cssSha256': hashlib.sha256(css_bytes).hexdigest(),
            'jsSha256': hashlib.sha256(js_bytes).hexdigest(),
            'wordpressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0,
            'realClicks': None, 'fulfilledReservations': None, 'paidCommissions': None}


def diagnostic_counts(html):
    """GET-only metadata: never print commercial URLs, visitor IDs or HTML."""
    surface = Surface()
    surface.feed(html.decode('utf-8', 'replace'))
    surface.close()
    return {
        "status": "SPORT_STAYS_LINK_DISTRIBUTION_OBSERVED_NOT_VERIFIED",
        "canonicalMatched": surface.canonicals == [ORIGIN + PAGE],
        "postIdentityMatched": "postid-85384" in surface.body_classes,
        "offerSectionCount": surface.sections,
        "offerCardCount": len(surface.offers),
        "panelInternalStayLinks": sum(1 for href, _, _ in surface.anchors if href == STAYS),
        "postContentRegionCount": surface.article_region_count,
        "articleNewStayLinks": surface.article_links.count(STAYS),
        "articleOldBookingLinks": surface.article_links.count(LEGACY),
        "publicPageOldBookingLinks": surface.all_links.count(LEGACY),
        "publicPageNewStayLinks": surface.all_links.count(STAYS),
        "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
    }


def has_deployed_marker(html):
    """Detect the candidate without treating its presence as publication proof."""
    surface = Surface()
    surface.feed(html.decode('utf-8', 'replace'))
    surface.close()
    return surface.sections > 0


def verify_current(html, *, css_bytes=b'', js_bytes=b'', reviewed_css=b'', reviewed_js=b''):
    """For later PRs, prove either the original page or the *exact* live release.

    This is intentionally distinct from --preflight: the preflight contract
    still requires no published offer. Partially deployed or drifted releases
    may never fall back to the permissive original-page branch.
    """
    return verify(html, deployed=has_deployed_marker(html), css_bytes=css_bytes,
                  js_bytes=js_bytes, reviewed_css=reviewed_css, reviewed_js=reviewed_js)


def get(url, limit):
    assert url in (ORIGIN + PAGE, PROOF, ORIGIN + JS + '?' + JS_VERSION,
                   ORIGIN + CSS + '?' + CSS_VERSION), 'UNAPPROVED_PUBLIC_GET'
    request = Request(url, headers={'User-Agent': 'NaRyby-SportStays-Public-ReadOnly/1.0',
                                    'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        body = response.read(limit + 1)
        assert len(body) <= limit, 'EXCESSIVE_RESPONSE_BYTES'
        return body


def main():
    assert sys.argv[1:] in (['--preflight'], ['--verify'], ['--current'], ['--diagnose']), 'PHASE_REQUIRED'
    if sys.argv[1:] == ['--diagnose']:
        print(json.dumps(diagnostic_counts(get(ORIGIN + PAGE, 2_000_000)), sort_keys=True))
        return
    mode = sys.argv[1]
    proof = json.loads(get(PROOF, 12000))
    assert proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION' \
        and proof['postId'] == 85384 and proof['sourceSha256'] == RAW_SHA256 \
        and proof['publicationAuthorized'] is False and proof['wordpressWrites'] == 0, 'RAW_WP_SOURCE_CHANGED'
    page = get(ORIGIN + PAGE, 2_000_000)
    deployed = mode == '--verify' or (mode == '--current' and has_deployed_marker(page))
    verifier = verify_current if mode == '--current' else verify
    options = {} if mode == '--current' else {'deployed': deployed}
    result = verifier(page, **options,
                    css_bytes=get(ORIGIN + CSS + '?' + CSS_VERSION, 25_000) if deployed else b'',
                    js_bytes=get(ORIGIN + JS + '?' + JS_VERSION, 50_000) if deployed else b'',
                    reviewed_css=(ROOT / 'assets/nry-sport-stays-85384-v1.css').read_bytes() if deployed else b'',
                    reviewed_js=(ROOT / 'assets/nry-buyer-clicks-v1.js').read_bytes() if deployed else b'')
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (OSError, AssertionError, KeyError, ValueError, TypeError) as exc:
        print(json.dumps({'status': 'SPORT_STAYS_OFFERS_BLOCKED',
                          'reason': str(exc)[:130]}, ensure_ascii=False))
        raise SystemExit(1)
