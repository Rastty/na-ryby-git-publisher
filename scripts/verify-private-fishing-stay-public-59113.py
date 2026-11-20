#!/usr/bin/env python3
"""Read-only public proof for the bounded private fishing stay trust repair.

Only GETs the Na-Ryby page, WordPress REST and exact plugin CSS. Does not
contact affiliate networks, simulate clicks, update content or claim sales.
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
PAGE = '/nezapomenutelna-rybarska-dovolena-na-privatnim-reviru-s-komfortnim-ubytovanim/'
REST = SITE + '/wp-json/wp/v2/posts/59113?_fields=id,type,status,slug,link,modified_gmt,content'
STYLE = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-private-fishing-stay-trust-59113-v1.css'
STAYS = SITE + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
GEAR = SITE + '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/'
PREFIX_SHA = 'ce56f7035ea3ac94ce9210629afc698440214cc16fb4bb6eac37804054caa3c9'


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.blocks = 0
        self.examples = []
        self.anchors = []
        self.in_block = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').split():
                self.canonicals.append(a.get('href'))
            href = urlsplit(a.get('href') or '')
            if href.path == STYLE and href.scheme in ('', 'https') and href.netloc in ('', 'na-ryby.eu'):
                self.styles.append(href.path + '?' + href.query)
        if tag == 'section' and a.get('data-nry-private-stay') == 'v1':
            self.blocks += 1
            self.in_block = True
        if self.in_block and tag == 'article' and a.get('data-nry-private-example'):
            self.examples.append(a.get('data-nry-private-example'))
        if self.in_block and tag == 'a':
            self.anchors.append(a.get('href'))

    def handle_endtag(self, tag):
        if tag == 'section' and self.in_block:
            self.in_block = False


def verify_rest(data):
    assert data['id'] == 59113 and data['type'] == 'post' and data['status'] == 'publish', 'POST_IDENTITY_DRIFT'
    assert data['link'] == SITE + PAGE and data['slug'] == PAGE.strip('/'), 'SLUG_DRIFT'
    assert data['modified_gmt'] == '2023-08-18T07:08:57', 'RAW_SOURCE_MODIFIED'
    source = data['content']['rendered']
    assert hashlib.sha256(source.encode('utf-8')[:512]).hexdigest() == PREFIX_SHA, 'RENDERED_PREFIX_DRIFT'
    for phrase in ('Naše privátní revíry se nachází',
                   'Rezervace je velmi jednoduchá. Stačí vyplnit rezervační formulář',
                   'Tento článek pojednává o soukromých revírech',
                   'http://www.nezapomenutelnyrevir.cz'):
        assert source.count(phrase) == 1, 'ORIGINAL_SOURCE_ANCHOR_DRIFT'
    assert source.count('<!DOCTYPE html>') == 2, 'ORIGINAL_NESTED_HTML_DRIFT'
    return True


def verify_public(html, *, deployed, css_public=b'', css_reviewed=b''):
    page = Surface()
    page.feed(html)
    page.close()
    assert page.canonicals == [SITE + PAGE] and 'postid-59113' in html, 'PUBLIC_IDENTITY_DRIFT'
    if not deployed and page.blocks == 0:
        assert 'Naše privátní revíry' in html and 'Rezervace je velmi jednoduchá' in html, 'BASELINE_NOT_RECOGNIZED'
        assert not page.styles, 'UNEXPECTED_PREDEPLOY_STYLES'
        return {'status': 'PRIVATE_STAY_59113_PREFLIGHT_READY', 'postId': 59113,
                'existingTrustRepair': False, 'plannedInternalLinks': 2, 'plannedOutboundOffers': 0,
                'wordPressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}
    assert page.blocks == 1 and page.examples == ['jedraz'], 'REVIEWED_SECTION_DRIFT'
    assert page.anchors == [STAYS, GEAR], 'UNREVIEWED_LINK_OR_ORDER'
    assert page.styles == [STYLE + '?ver=1.0.0'], 'CSS_ENQUEUE_DRIFT'
    assert css_public == css_reviewed and len(css_public) > 1000, 'CSS_NOT_BYTE_IDENTICAL'
    assert 'Soukromý revír s ubytováním: co si ověřit před rezervací' in html
    assert 'V popisu konkrétní varianty Slevomatu' in html
    assert 'Neprovozujeme vlastní soukromé revíry' in html
    for old_claim in ('Naše privátní revíry se nachází', 'Rezervace je velmi jednoduchá',
                      'http://www.nezapomenutelnyrevir.cz',
                      'Tento článek pojednává o soukromých revírech'):
        assert old_claim not in html, 'LEGACY_FALSE_CLAIM_STILL_VISIBLE'
    return {'status': 'PRIVATE_STAY_59113_PUBLIC_VERIFIED', 'postId': 59113,
            'trustRepairVisible': True, 'reviewedInternalLinks': 2, 'merchantRequests': 0,
            'cssByteIdentical': True, 'wordPressWrites': 0, 'syntheticClicks': 0,
            'realClicks': None, 'completedReservations': None, 'paidCommissions': None}


def get(url, max_bytes):
    assert url in (SITE + PAGE, REST, SITE + STYLE + '?ver=1.0.0'), 'UNAPPROVED_GET'
    req = Request(url, headers={'User-Agent': 'NaRyby-PrivateStay-ReadOnly/1.0',
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
    need_css = deployed or 'data-nry-private-stay="v1"' in html
    output = verify_public(html, deployed=deployed,
                           css_public=get(SITE + STYLE + '?ver=1.0.0', 50000) if need_css else b'',
                           css_reviewed=(ROOT / 'assets/nry-private-fishing-stay-trust-59113-v1.css').read_bytes()
                           if need_css else b'')
    print(json.dumps(output, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError) as exc:
        print(json.dumps({'status': 'PRIVATE_STAY_59113_BLOCKED', 'reason': str(exc)[:170]}))
        raise SystemExit(1)
