#!/usr/bin/env python3
"""Anonymous exact-page SEO preflight/readback. No tracked URLs or WP writes."""
import argparse
import json
from html.parser import HTMLParser
from urllib.request import Request, HTTPRedirectHandler, build_opener

URL = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'
PROOF = 'https://na-ryby.eu/poradce-vybavy/?nry_stredni_cechy_stay_source_proof=1'
TITLE = 'Zážitkové rybaření s ubytováním ve středních Čechách'
BASELINE = 'Zážitkové rybaření s ubytováním ve středních Čechách. Přijďte si odpočinout a užít si rybaření s rodinou či přáteli.✔️'
CANDIDATE = 'Rybaření s ubytováním ve středních Čechách: konkrétní tip u Slap, co ověřit u revíru a povolenky před rezervací. Vyberte pobyt s rozmyslem.'
SOURCE_SHA = 'ca79666566a68867a45eb6cb5504014813419e7c3055dc4880b04510a8560a74'


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Head(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title = ''
        self.in_title = False
        self.canonical = []
        self.meta = {}

    def handle_starttag(self, tag, attrs):
        fields = dict(attrs)
        if tag == 'title':
            self.in_title = True
        if tag == 'link' and 'canonical' in (fields.get('rel') or '').lower().split():
            self.canonical.append(fields.get('href'))
        if tag == 'meta':
            key = (fields.get('name') or fields.get('property') or '').lower()
            self.meta.setdefault(key, []).append(fields.get('content'))

    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title += data


def validate_page(html, deployed):
    parsed = Head()
    parsed.feed(html)
    expected = CANDIDATE if deployed else BASELINE
    assert parsed.title.strip() == TITLE, 'TITLE_OR_EDITOR_DRIFT'
    assert parsed.canonical == [URL], 'CANONICAL_DRIFT'
    assert parsed.meta.get('description') == [expected], 'YOAST_DESCRIPTION_NOT_' + ('DEPLOYED' if deployed else 'BASELINE')
    assert parsed.meta.get('og:description') == [expected], 'OPEN_GRAPH_DESCRIPTION_DRIFT'
    assert all('noindex' not in value.lower() for value in parsed.meta.get('robots', []) if isinstance(value, str)), 'INDEXING_DISABLED'
    return {'state': 'SEO_PUBLIC_VERIFIED' if deployed else 'SEO_BASELINE_VERIFIED',
            'canonical': URL, 'metaDescription': expected, 'titlePreserved': True,
            'wordpressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}


def validate_source(proof):
    assert proof.get('state') == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'SOURCE_NOT_AVAILABLE'
    assert proof.get('postId') == 92205 and proof.get('canonical') == URL, 'SOURCE_ID_DRIFT'
    assert proof.get('sourceSha256') == SOURCE_SHA and proof.get('sourceBytes') == 5881, 'RAW_SOURCE_DRIFT'
    assert proof.get('modifiedGmt') == '2024-10-07 12:15:48', 'SOURCE_MODIFIED_DRIFT'


def get(url):
    assert url in (URL, PROOF), 'NOT_APPROVED_FIRST_PARTY_URL'
    with build_opener(NoRedirect).open(Request(url, headers={'User-Agent': 'NaRybySEOAnonymousReadback/1.0',
                                                             'Cache-Control': 'no-cache'}), timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'GET_REDIRECT_OR_STATUS_DRIFT'
        return response.read(750000).decode('utf-8', 'replace')


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--verify', action='store_true', help='require installed candidate after exact-SHA release')
    parser.add_argument('--preflight', action='store_true', help='require original public baseline before release')
    args = parser.parse_args()
    assert args.verify ^ args.preflight, 'SELECT_EXACTLY_ONE_PHASE'
    validate_source(json.loads(get(PROOF)))
    print(json.dumps(validate_page(get(URL), deployed=args.verify), ensure_ascii=False))
