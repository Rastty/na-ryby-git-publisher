#!/usr/bin/env python3
"""Independent anonymous GET-only SEO readback for exact romantic stays URL."""
import sys
from html.parser import HTMLParser
from urllib.request import Request, HTTPRedirectHandler, build_opener

URL = ('https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-'
       'nezapomenutelne-chvile-plne-lasky-a-rybolovu/')
TITLE = 'Romantický pobyt pro dva s rybařením: jak vybrat místo, které opravdu funguje'
OLD = ('Romantický pobyt pro dva s rybařením - nezapomenutelné chvíle plné lásky '
       'a rybolovu. Zažijte dokonalou kombinaci odpočinku a vášně!✔️')
NEW = ('Romantický pobyt s rybařením pro dva: zjistěte, jak ověřit přístup k vodě, '
       'pravidla lovu, soukromí i zázemí. Praktický výběr bez planých slibů.')


class Head(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title = ''
        self.in_title = False
        self.meta = {}
        self.canonical = []

    def handle_starttag(self, tag, attrs):
        fields = dict(attrs)
        if tag == 'title':
            self.in_title = True
        if tag == 'meta':
            key = (fields.get('name') or fields.get('property') or '').lower()
            if key:
                self.meta.setdefault(key, []).append(fields.get('content', ''))
        if tag == 'link' and 'canonical' in (fields.get('rel') or '').lower().split():
            self.canonical.append(fields.get('href'))

    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title += data


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None


def verify(html, preflight=False):
    head = Head()
    head.feed(html)
    head.close()
    expected = OLD if preflight else NEW
    assert head.title.strip() == TITLE, 'TITLE_DRIFT'
    assert head.canonical == [URL], 'CANONICAL_DRIFT'
    assert len(head.meta.get('description', [])) == 1, 'DESCRIPTION_COUNT_DRIFT'
    assert head.meta['description'] == [expected], 'DESCRIPTION_NOT_EXPECTED'
    assert head.meta.get('og:description') == [expected], 'OG_DESCRIPTION_NOT_EXPECTED'
    assert not any('noindex' in r.lower() for r in head.meta.get('robots', [])), 'NOT_INDEXABLE'
    return ('BASELINE_PUBLIC_VERIFIED' if preflight else 'UPDATED_META_PUBLIC_VERIFIED')


def main():
    assert sys.argv[1:] in ([], ['--preflight']), 'UNSUPPORTED_ARG'
    request = Request(URL, method='GET', headers={
        'User-Agent': 'NaRybyRomanticMetaReadOnly/1.0',
        'Cache-Control': 'no-cache',
    })
    with build_opener(NoRedirect).open(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == URL, 'HTTP_OR_URL_DRIFT'
        html = response.read(1600001)
        assert len(html) <= 1600000, 'DOCUMENT_TOO_LARGE'
    print(verify(html.decode('utf-8', 'replace'), '--preflight' in sys.argv))


if __name__ == '__main__':
    main()
