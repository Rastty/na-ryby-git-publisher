#!/usr/bin/env python3
"""Anonymous, GET-only exact public proof of Kružberk no-fishing notice."""
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
PAGE = '/rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj/'
PROOF = '/poradce-vybavy/?nry_kruzberk_source_proof=1'
REST = '/wp-json/wp/v2/posts/12877?_fields=id,slug,status,modified_gmt,link'
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-kruzberk-trust-12877-v1.css'
CSS_QUERY = 'ver=1.0.0'
RAW_SHA = 'e52ed59429977b7173d628c07eaac7bbd7b5e030eb5a72bb26f8ffcc682b85b8'
OBEC = 'https://www.kruzberk.cz/turisticke-informace/kruzberska-prehrada/'
CRS = 'https://ris.rybsvaz.cz/karta-reviru/-/473054-moravice-4'


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.post = []
        self.sections = 0
        self.inside = False
        self.anchors = []
        self.styles = []
        self.headings = []
        self.in_heading = False

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
        if tag == 'section' and a.get('data-nry-kruzberk-trust') == 'v1':
            self.sections += 1
            self.inside = True
        if self.inside and tag == 'a':
            self.anchors.append((a.get('href'), a.get('rel'), a.get('target')))
        if self.inside and tag == 'h2':
            self.in_heading = True

    def handle_data(self, data):
        if self.in_heading:
            self.headings.append(data)

    def handle_endtag(self, tag):
        if tag == 'h2':
            self.in_heading = False
        if tag == 'section' and self.inside:
            self.inside = False


def verify(html, *, deployed, css=b'', reviewed_css=b''):
    s = Surface()
    s.feed(html.decode('utf-8', 'replace'))
    s.close()
    assert s.canonicals == [ORIGIN + PAGE], 'CANONICAL_CHANGED'
    assert 'postid-12877' in s.post, 'POST_ID_CHANGED'
    if not deployed:
        assert s.sections == 0 and s.styles == [] and s.anchors == [], 'BASELINE_CHANGED'
        return {'status': 'KRUZBERK_TRUST_PREFLIGHT_READY', 'postId': 12877, 'wordpressWrites': 0}
    assert s.sections == 1, 'TRUST_BOX_NOT_EXACTLY_ONCE'
    assert s.headings == ['V samotné přehradě Kružberk se rybařit nesmí'], 'WARNING_CHANGED'
    assert s.anchors == [(OBEC, 'noopener', '_blank'), (CRS, 'noopener', '_blank')], 'OFFICIAL_SOURCES_CHANGED'
    assert s.styles == [CSS_QUERY], 'CSS_NOT_ENQUEUED_EXACTLY_ONCE'
    assert css == reviewed_css and len(css) > 500, 'CSS_BYTES_CHANGED'
    return {'status': 'KRUZBERK_TRUST_PUBLIC_VERIFIED', 'postId': 12877,
            'warningVisible': True, 'officialSourceLinks': 2,
            'cssByteIdentical': True, 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def get(url, limit):
    assert url in (ORIGIN + PAGE, ORIGIN + PROOF, ORIGIN + REST,
                   ORIGIN + CSS + '?' + CSS_QUERY), 'UNAPPROVED_GET'
    req = Request(url, headers={'User-Agent': 'NaRyby-Kruzberk-Trust-ReadOnly/1.0',
                                'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        result = response.read(limit + 1)
        assert len(result) <= limit, 'TOO_MANY_BYTES'
        return result


def main():
    assert sys.argv[1:] in (['--preflight'], ['--current'], ['--verify']), 'PHASE_REQUIRED'
    phase = sys.argv[1]
    proof = json.loads(get(ORIGIN + PROOF, 12000))
    rest = json.loads(get(ORIGIN + REST, 12000))
    assert proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 12877 and proof['sourceSha256'] == RAW_SHA
    assert proof['wordpressWrites'] == 0 and proof['publicationAuthorized'] is False
    assert rest['id'] == 12877 and rest['status'] == 'publish'
    assert rest['link'] == ORIGIN + PAGE and rest['modified_gmt'] == '2026-04-14T23:33:21'
    html = get(ORIGIN + PAGE, 2_000_000)
    detected = Surface()
    detected.feed(html.decode('utf-8', 'replace'))
    detected.close()
    deployed = phase == '--verify' or (phase == '--current' and detected.sections > 0)
    css = get(ORIGIN + CSS + '?' + CSS_QUERY, 25000) if deployed else b''
    reviewed = (ROOT / 'assets/nry-kruzberk-trust-12877-v1.css').read_bytes() if deployed else b''
    print(json.dumps(verify(html, deployed=deployed, css=css, reviewed_css=reviewed), ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError, TypeError) as error:
        print(json.dumps({'status': 'KRUZBERK_TRUST_BLOCKED', 'reason': str(error)[:90]}))
        raise SystemExit(1)
