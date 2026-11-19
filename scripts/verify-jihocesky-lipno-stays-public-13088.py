#!/usr/bin/env python3
"""GET-only public preflight/readback of the South Bohemia Lipno offers.

No affiliate destinations are requested; no clicks, WP writes, or simulated
reservations. A successful PR preflight does not mean the offers are live.
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
SITE = 'https://na-ryby.eu'
PAGE = '/rybareni-s-ubytovanim-jihocesky-kraj/'
REST = SITE + '/wp-json/wp/v2/posts/13088?_fields=id,type,status,slug,link,modified_gmt,content'
STYLE = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-jihocesky-lipno-stays-13088-v1.css'
HPORTAL = ('https://go.dognet.com/?chid=5QoXiSqx&url='
           'https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F')
HAUZI = ('https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5='
         'https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne')
STAYS = SITE + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
SHA = '3778effd5b7c012dfeedbc3cc2f1e13f07769efd805fabaf6996535c292bb801'


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.blocks = 0
        self.offers = []
        self.links = []
        self.inside = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').split():
                self.canonicals.append(a.get('href'))
            u = urlsplit(a.get('href') or '')
            if (u.path == STYLE and u.scheme in ('', 'https')
                    and u.netloc in ('', 'na-ryby.eu')):
                self.styles.append(u.path + '?' + u.query)
        if tag == 'section' and a.get('data-nry-jihocesky-stays') == 'v1':
            self.blocks += 1
            self.inside = True
        if self.inside and tag == 'article' and a.get('data-nry-jc-offer'):
            self.offers.append(a['data-nry-jc-offer'])
        if self.inside and tag == 'a':
            self.links.append((a.get('href'), a.get('target'), a.get('rel')))

    def handle_endtag(self, tag):
        if tag == 'section' and self.inside:
            self.inside = False


def verify_rest(data):
    assert data['id'] == 13088 and data['type'] == 'post' and data['status'] == 'publish', 'IDENTITY_DRIFT'
    assert data['slug'] == PAGE.strip('/') and data['link'] == SITE + PAGE, 'CANONICAL_DRIFT'
    assert data['modified_gmt'] == '2017-07-04T10:16:21', 'MODIFIED_GMT_DRIFT'
    rendered = data['content']['rendered']
    assert len(rendered.encode('utf-8')) >= 7000, 'SOURCE_TOO_SHORT'
    assert hashlib.sha256(rendered.encode('utf-8')[:512]).hexdigest() == SHA, 'RENDERED_SOURCE_DRIFT'
    for anchor in ('<div class="su-table su-table-alternate">', '<td>Revíry</td>',
                   '<td>Ubytování</td>', 'id="su-post-17530"', 'id="su-post-17753"',
                   'https://na-ryby.eu/sportovni-rybolov-raselina/',
                   'https://na-ryby.eu/apartmany-nadeje/'):
        assert anchor in rendered, 'ORIGINAL_SOURCE_ANCHOR_DRIFT'
    return True


def verify_public(html, *, deployed, css_public=b'', css_reviewed=b''):
    view = Surface()
    view.feed(html)
    view.close()
    assert view.canonicals == [SITE + PAGE] and 'postid-13088' in html, 'PUBLIC_IDENTITY_DRIFT'
    assert 'su-table su-table-alternate' in html and 'Sportovní rybolov Rašelina' in html, 'HISTORICAL_TABLE_MISSING'
    if not deployed and view.blocks == 0:
        assert not view.styles, 'UNEXPECTED_PREDEPLOY_STYLES'
        return {'status': 'JIHOCESKY_LIPNO_13088_PREFLIGHT_READY', 'postId': 13088,
                'existingOffers': False, 'plannedMerchantLinks': 2, 'plannedInternalLinks': 1,
                'merchantRequests': 0, 'wordpressWrites': 0, 'syntheticClicks': 0}
    assert view.blocks == 1 and view.offers == ['kohoutu', 'vyhlidka'], 'OFFER_COUNT_OR_ORDER_DRIFT'
    assert view.links == [
        (HPORTAL, '_blank', 'sponsored nofollow noopener'),
        (HAUZI, '_blank', 'sponsored nofollow noopener'),
        (STAYS, None, None),
    ], 'UNREVIEWED_LINK_OR_REL'
    assert view.styles == [STYLE + '?ver=1.0.0'], 'STYLESHEET_ENQUEUE_DRIFT'
    assert css_public == css_reviewed and len(css_public) >= 700, 'CSS_BYTES_DRIFT'
    for phrase in ('Na ryby k Lipnu a kde přespat?', 'Pension U Kohoutů', 'Pension Vyhlídka',
                   'Tabulka níže je starší rozcestník'):
        assert phrase in html, 'TRUST_DISCLOSURE_DRIFT'
    return {'status': 'JIHOCESKY_LIPNO_13088_PUBLIC_VERIFIED', 'postId': 13088,
            'visibleReviewedOffers': 2, 'reviewedInternalLinks': 1, 'historicalTablePreserved': True,
            'cssByteIdentical': True, 'merchantRequests': 0, 'wordpressWrites': 0,
            'syntheticClicks': 0, 'realClicks': None, 'completedReservations': None,
            'paidCommissions': None}


def get(url, max_bytes):
    assert url in (SITE + PAGE, REST, SITE + STYLE + '?ver=1.0.0'), 'UNAPPROVED_GET'
    req = Request(url, headers={'User-Agent': 'NaRyby-Jihocesky-Lipno-ReadOnly/1.0',
                                'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'REDIRECT_OR_STATUS_DRIFT'
        content = response.read(max_bytes + 1)
        assert len(content) <= max_bytes, 'RESPONSE_TOO_LARGE'
        return content


def main():
    assert sys.argv[1:] in (['--preflight'], ['--verify']), 'PHASE_REQUIRED'
    verify_rest(json.loads(get(REST, 200000).decode('utf-8')))
    deployed = sys.argv[1] == '--verify'
    html = get(SITE + PAGE, 2000000).decode('utf-8', 'replace')
    needs_css = deployed or 'data-nry-jihocesky-stays="v1"' in html
    result = verify_public(html, deployed=deployed,
                           css_public=get(SITE + STYLE + '?ver=1.0.0', 50000) if needs_css else b'',
                           css_reviewed=(ROOT / 'assets/nry-jihocesky-lipno-stays-13088-v1.css').read_bytes()
                           if needs_css else b'')
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError) as exc:
        print(json.dumps({'status': 'JIHOCESKY_LIPNO_13088_BLOCKED', 'reason': str(exc)[:170]}))
        raise SystemExit(1)
