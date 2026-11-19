#!/usr/bin/env python3
"""Anonymous GET-only public proof for a source-locked Brno reservoir offer repair."""
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
PAGE = '/chata-brnenska-prehrada/'
PROOF = '/poradce-vybavy/?nry_brno_reservoir_source_proof=1'
REST = '/wp-json/wp/v2/posts/17591?_fields=id,slug,status,modified_gmt,link,content'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-brno-reservoir-stays-17591-v1.css'
CSS_QUERY = 'ver=1.0.0'
SOURCE_SHA = '28dcd7c75cd173508727082c1b7f69a322211cde3acf0ef55fed7136b215a261'
PREFIX_SHA = 'dd1eba5e105271ee254a0f3cecc9553172ad51f7070c4715acc75a882a6d6b33'
HAUZI = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchata-u-brnenske-prehrady'
HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fbrno%2Fhotel-kozi-horka%2F'
OFFERS = {'hauzi-chata-u-brnenske-prehrady': HAUZI,
          'hportal-hotel-kozi-horka': HPORTAL}


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.post = []
        self.sections = 0
        self.inside = False
        self.offers = []
        self.links = []
        self.styles = []
        self.current_offer = None
        self.archive_jump = 0

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'body':
            self.post = (a.get('class') or '').split()
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag == 'link':
            url = urlsplit(a.get('href') or '')
            if url.scheme in ('', 'https') and url.netloc in ('', 'na-ryby.eu') and url.path == CSS:
                self.styles.append(url.query)
        if tag == 'section' and a.get('data-nry-brno-stays') == 'v1':
            assert a.get('id') == 'nry-brno-stays-17591', 'SECTION_ANCHOR_CHANGED'
            self.sections += 1
            self.inside = True
        if tag == 'article' and self.inside:
            self.current_offer = a.get('data-nry-offer-id')
            self.offers.append(self.current_offer)
        if tag == 'a' and self.inside:
            self.links.append((self.current_offer, a.get('href'), a.get('rel'), a.get('target')))
        if tag == 'a' and a.get('href') == '#nry-brno-stays-17591':
            self.archive_jump += 1

    def handle_endtag(self, tag):
        if tag == 'article':
            self.current_offer = None
        if tag == 'section' and self.inside:
            self.inside = False


def verify(html, *, deployed, css=b'', reviewed_css=b''):
    text = html.decode('utf-8', 'replace')
    s = Surface()
    s.feed(text)
    s.close()
    assert s.canonicals == [ORIGIN + PAGE], 'CANONICAL_CHANGED'
    assert 'postid-17591' in s.post, 'POST_ID_CHANGED'
    if not deployed:
        assert s.sections == 0 and s.offers == [] and s.styles == [], 'BASELINE_CHANGED'
        return {'status': 'BRNO_RESERVOIR_STAYS_PREFLIGHT_READY', 'postId': 17591,
                'wordpressWrites': 0, 'merchantRequests': 0}
    assert s.sections == 1 and s.offers == list(OFFERS), 'TWO_OFFERS_NOT_VISIBLE'
    assert s.links == [(key, url, 'sponsored nofollow noopener', '_blank')
                       for key, url in OFFERS.items()], 'AFFILIATE_LINK_CHANGED'
    assert s.archive_jump == 1, 'HISTORIC_CTA_NOT_FIXED'
    assert 'NEJLEVNĚJŠÍ NABÍDKA' not in text, 'UNSUPPORTED_SUPERLATIVE'
    assert 'Chata Brněnská přehrada &#8211; Vranovská přehrada' not in text, 'WRONG_GEOGRAPHY'
    assert 'aktuální cena neověřena' in text and 'není ověřen' in text, 'ARCHIVE_WARNING_MISSING'
    assert 'nejsou původní chata' in text, 'ALTERNATIVES_MISLABELED'
    assert s.styles == [CSS_QUERY], 'STYLE_NOT_ENQUEUED_ONCE'
    assert css == reviewed_css and len(css) > 500, 'CSS_BYTES_MISMATCH'
    return {'status': 'BRNO_RESERVOIR_STAYS_PUBLIC_VERIFIED', 'postId': 17591,
            'distinctNearbyAlternatives': 2, 'historicalPriceCaveat': True,
            'permitCaveat': True, 'wrongGeographyFixed': True,
            'cssByteIdentical': True, 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def get(path, limit):
    assert path in (PAGE, PROOF, REST, CSS + '?' + CSS_QUERY), 'UNAPPROVED_GET'
    req = Request(ORIGIN + path, headers={'User-Agent': 'NaRyby-Brno-Trust-Readback/1.0',
                                         'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        raw = response.read(limit + 1)
        assert len(raw) <= limit, 'TOO_MANY_BYTES'
        return raw


def main():
    assert sys.argv[1:] in (['--preflight'], ['--current'], ['--verify']), 'PHASE_REQUIRED'
    phase = sys.argv[1]
    proof = json.loads(get(PROOF, 12000))
    rest = json.loads(get(REST, 30000))
    assert proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 17591 and proof['sourceSha256'] == SOURCE_SHA
    assert proof['wordpressWrites'] == 0 and proof['publicationAuthorized'] is False
    assert rest['id'] == 17591 and rest['status'] == 'publish'
    assert rest['slug'] == 'chata-brnenska-prehrada' and rest['link'] == ORIGIN + PAGE
    assert rest['modified_gmt'] == '2018-01-22T19:53:18'
    prefix = rest['content']['rendered'].encode('utf-8')[:512]
    assert hashlib.sha256(prefix).hexdigest() == PREFIX_SHA, 'RENDERED_PREFIX_DRIFT'
    html = get(PAGE, 2_000_000)
    inspected = Surface()
    inspected.feed(html.decode('utf-8', 'replace'))
    inspected.close()
    deployed = phase == '--verify' or (phase == '--current' and inspected.sections > 0)
    css = get(CSS + '?' + CSS_QUERY, 25000) if deployed else b''
    reviewed = (ROOT / 'assets/nry-brno-reservoir-stays-17591-v1.css').read_bytes() if deployed else b''
    print(json.dumps(verify(html, deployed=deployed, css=css, reviewed_css=reviewed), ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError, TypeError) as exc:
        print(json.dumps({'status': 'BRNO_RESERVOIR_STAYS_BLOCKED',
                          'reason': str(exc)[:110]}))
        raise SystemExit(1)
