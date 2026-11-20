#!/usr/bin/env python3
"""GET-only proof: expired offer replaced, approved deep link, rest untouched.

Does not request Slevomat/Dognet/Hportal, simulate clicks or write WordPress.
"""
import hashlib
import html
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PAGE = '/kde-prespat-u-orliku-na-rybarsky-vikend/'
REST = SITE + '/wp-json/wp/v2/posts/119551?_fields=id,type,status,slug,link,modified_gmt,content'
SHA = 'b96cfa15bfa42365c2be2c9d47fa5e34b8bfc96772b8425ab01419d3c4f2b4a2'
CATEGORY = ('https://www.slevomat.cz/cestovani/dovolena/u-vody?filtry%5Boblast%5D=cesko_stredni-cechy'
            '&#038;utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_terminoeu&#038;utm_content=NARYBY'
            '&#038;utm_medium=cpc&#038;utm_source=affiliate&#038;utm_term=j3d7tsgc')
EXPIRED = ('https://www.slevomat.cz/akce/2337132-relax-na-orliku-neomezene-wellness-i-dobre-jidlo'
           '?utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_terminoeu&#038;utm_content=NARYBY'
           '&#038;utm_medium=cpc&#038;utm_source=affiliate&#038;utm_term=j3d7tsgc')
HPORTAL = ('https://go.dognet.com/?chid=5QoXiSqx&url='
           'https%3A%2F%2Fhportal.cz%2Fkrasna-hora-nad-vltavou%2Fliskuv-mlyn-orlicka-prehrada%2F')


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.links = []
        self.notes = 0
        self.current = None

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag == 'span' and a.get('data-nry-orlik-stay') == 'v1':
            self.notes += 1
        if tag == 'a':
            self.current = [a.get('href'), a.get('target'), a.get('rel'), '']

    def handle_data(self, data):
        if self.current is not None:
            self.current[3] += data

    def handle_endtag(self, tag):
        if tag == 'a' and self.current is not None:
            self.links.append(tuple(self.current))
            self.current = None


def verify_rest(data):
    assert data['id'] == 119551 and data['type'] == 'post' and data['status'] == 'publish', 'POST_IDENTITY_DRIFT'
    assert data['link'] == SITE + PAGE and data['slug'] == PAGE.strip('/'), 'CANONICAL_DRIFT'
    assert data['modified_gmt'] == '2026-05-10T09:23:09', 'MODIFIED_GMT_DRIFT'
    source = data['content']['rendered']
    assert len(source.encode('utf-8')) > 20000, 'ORIGINAL_SOURCE_TOO_SHORT'
    assert hashlib.sha256(source.encode('utf-8')[:512]).hexdigest() == SHA, 'ORIGINAL_SOURCE_PREFIX_DRIFT'
    assert source.count(EXPIRED) == 1 and source.count(CATEGORY) == 1, 'OLD_TWO_DESTINATIONS_DRIFT'
    assert source.count('>Projít další možnost</a>') == 1, 'OLD_EXPIRED_LABEL_DRIFT'
    assert source.count('>Zjistit cenu a termín</a>') == 1, 'OLD_GENERIC_LABEL_DRIFT'


def verify_public(page, *, deployed):
    parsed = Surface()
    parsed.feed(page)
    parsed.close()
    assert parsed.canonicals == [SITE + PAGE] and 'postid-119551' in page, 'PUBLIC_IDENTITY_DRIFT'
    assert 'Kde přespat u Orlíku na rybářský víkend' in page, 'HEADING_MISSING'
    category = [x for x in parsed.links if x[0] == html.unescape(CATEGORY)]
    expired = [x for x in parsed.links if x[0] == html.unescape(EXPIRED)]
    approved = [x for x in parsed.links if x[0] == HPORTAL]
    assert len(category) == 1, 'CATEGORY_LINK_CHANGED'
    if not deployed and parsed.notes == 0:
        assert len(expired) == 1 and not approved, 'EXPECTED_EXPIRED_BASELINE_NOT_FOUND'
        assert category[0][3] == 'Zjistit cenu a termín', 'EXPECTED_GENERIC_BASELINE_NOT_FOUND'
        assert expired[0][3] == 'Projít další možnost', 'EXPECTED_EXPIRED_LABEL_NOT_FOUND'
        return {'status': 'ORLIK_EXPIRED_STAY_119551_PREFLIGHT_READY', 'postId': 119551,
                'expiredCTAStillLive': True, 'plannedReplacements': 1,
                'merchantRequests': 0, 'wordpressWrites': 0, 'syntheticClicks': 0}
    assert parsed.notes == 1 and not expired and len(approved) == 1, 'REPLACEMENT_MISSING_OR_DUPLICATE'
    assert category[0][3] == 'Prohlédnout pobyty ve středních Čechách', 'GENERIC_LABEL_DRIFT'
    assert approved[0][1:] == ('_blank','sponsored nofollow noopener',
                              'Liškův Mlýn u Orlíku – prověřit pobyt'), 'APPROVED_OFFER_DRIFT'
    assert 'Rezervace noclehu automaticky nezahrnuje rybářský lístek' in page, 'PERMIT_DISCLAIMER_MISSING'
    assert 'První vede na širší nabídku pobytů ve středních Čechách' in page, 'BROAD_OFFER_DISCLOSURE_MISSING'
    return {'status': 'ORLIK_EXPIRED_STAY_119551_PUBLIC_VERIFIED', 'postId': 119551,
            'expiredCTAVisible': False, 'approvedHportalCTAVisible': True,
            'originalSlevomatCategoryPreserved': True, 'merchantRequests': 0,
            'wordpressWrites': 0, 'syntheticClicks': 0,
            'realClicks': None, 'completedReservations': None, 'paidCommissions': None}


def get(url, max_bytes):
    assert url in (SITE + PAGE, REST), 'UNAPPROVED_GET'
    req = Request(url, headers={'User-Agent': 'NaRyby-Orlik-Expired-CTA-ReadOnly/1.0',
                                'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'STATUS_OR_REDIRECT_DRIFT'
        data = response.read(max_bytes + 1)
        assert len(data) <= max_bytes, 'OVERSIZE_RESPONSE'
        return data


def main():
    assert sys.argv[1:] in (['--preflight'], ['--verify']), 'PHASE_REQUIRED'
    verify_rest(json.loads(get(REST, 200000).decode('utf-8')))
    deployed = sys.argv[1] == '--verify'
    result = verify_public(get(SITE + PAGE, 2000000).decode('utf-8','replace'), deployed=deployed)
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError) as exc:
        print(json.dumps({'status':'ORLIK_EXPIRED_STAY_119551_BLOCKED','reason':str(exc)[:170]}))
        raise SystemExit(1)
