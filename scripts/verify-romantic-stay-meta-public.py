#!/usr/bin/env python3
"""Independent anonymous GET-only SEO readback for exact romantic stays URL."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request, HTTPRedirectHandler, build_opener

URL = ('https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-'
       'nezapomenutelne-chvile-plne-lasky-a-rybolovu/')
TITLE = 'Romantický pobyt pro dva s rybařením: jak vybrat místo, které opravdu funguje'
OLD = ('Romantický pobyt pro dva s rybařením - nezapomenutelné chvíle plné lásky '
       'a rybolovu. Zažijte dokonalou kombinaci odpočinku a vášně!✔️')
NEW = ('Romantický pobyt s rybařením pro dva: zjistěte, jak ověřit přístup k vodě, '
       'pravidla lovu, soukromí i zázemí. Praktický výběr bez planých slibů.')
MARKER = 'data-nry-romantic-stay-offers="v1"'
CSS_PATH = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-romantic-stay-offers-57013-v1.css'
CSS_URL = 'https://na-ryby.eu' + CSS_PATH + '?ver=1.0.0'
CATEGORY = 'https://na-ryby.eu/booking-cesko'
HAUZI_SRUB = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub'
HAUZI_MORE = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness'
OFFERS = {'hauzi-rybarsky-srub': HAUZI_SRUB, 'hauzi-pro-dva-wellness': HAUZI_MORE}


class Head(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title = ''
        self.in_title = False
        self.meta = {}
        self.canonical = []
        self.styles = []
        self.offer_blocks = 0
        self.in_offers = False
        self.article = None
        self.offer_links = {}
        self.internal_links = []

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
        if tag == 'link' and urlsplit(fields.get('href') or '').path == CSS_PATH:
            self.styles.append(fields.get('href'))
        if tag == 'section' and fields.get('data-nry-romantic-stay-offers') == 'v1':
            self.offer_blocks += 1
            self.in_offers = True
        if tag == 'article' and self.in_offers:
            self.article = fields.get('data-nry-romantic-offer')
            self.offer_links.setdefault(self.article, [])
        if tag == 'a' and self.in_offers:
            link = (fields.get('href'), fields.get('rel'), fields.get('target'))
            if self.article:
                self.offer_links[self.article].append(link)
            else:
                self.internal_links.append(link)

    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False
        if tag == 'article':
            self.article = None
        if tag == 'section' and self.in_offers:
            self.in_offers = False

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
    if preflight:
        assert head.offer_blocks == 0 and not head.offer_links and not head.styles, 'OFFERS_ALREADY_PUBLIC'
        assert 'Mrknout na doporučené ubytování' in html, 'ORIGINAL_CTA_DRIFT'
    else:
        assert head.offer_blocks == 1, 'OFFER_SECTION_NOT_EXACTLY_ONCE'
        assert set(head.offer_links) == set(OFFERS), 'OFFER_IDS_DRIFT'
        for offer_id, url in OFFERS.items():
            assert head.offer_links[offer_id] == [(url, 'sponsored nofollow noopener', '_blank')], 'PARTNER_LINK_DRIFT'
        assert head.internal_links == [(CATEGORY, None, None)], 'CATEGORY_LINK_DRIFT'
        assert head.styles == [CSS_URL], 'OFFER_STYLES_NOT_ENQUEUED_ONCE'
        assert html.find(MARKER) < html.find('<h2>Co si pod tím lidé obvykle představují</h2>'), 'OFFERS_BURIED'
        assert 'Mrknout na doporučené ubytování' not in html, 'OLD_CTA_STILL_PUBLIC'
    return ('BASELINE_PUBLIC_VERIFIED' if preflight else 'UPDATED_META_PUBLIC_VERIFIED')


def main():
    assert sys.argv[1:] in ([], ['--preflight']), 'UNSUPPORTED_ARG'
    def get(url, maximum):
        assert url in (URL, CSS_URL), 'UNAPPROVED_PUBLIC_GET'
        request = Request(url, method='GET', headers={
            'User-Agent': 'NaRybyRomanticMetaReadOnly/1.0',
            'Cache-Control': 'no-cache',
        })
        with build_opener(NoRedirect).open(request, timeout=25) as response:
            assert response.status == 200 and response.geturl() == url, 'HTTP_OR_URL_DRIFT'
            payload = response.read(maximum + 1)
            assert len(payload) <= maximum, 'PUBLIC_BYTES_LIMIT'
        return payload
    preflight = '--preflight' in sys.argv
    html = get(URL, 1600000).decode('utf-8', 'replace')
    state = verify(html, preflight)
    if not preflight:
        reviewed = (Path(__file__).resolve().parents[1] / 'assets/nry-romantic-stay-offers-57013-v1.css').read_bytes()
        assert len(reviewed) > 500 and get(CSS_URL, 40000) == reviewed, 'PUBLIC_CSS_BYTES_DRIFT'
    print(state)


if __name__ == '__main__':
    main()
