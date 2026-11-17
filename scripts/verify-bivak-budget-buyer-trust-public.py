#!/usr/bin/env python3
"""GET-only bivak budget buyer-copy preflight and independent live readback.

No merchant requests, WordPress writes, simulated clicks or commission claims.
"""
import argparse
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/nejlepsi-rybarske-bivaky-do-10-000-kc/'
GUIDE = 'https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'
REST = 'https://na-ryby.eu/wp-json/wp/v2/posts/119575?_fields=id,slug,status,modified_gmt,link,content'
MARKER = 'data-nry-bivak-budget-buyer="v1"'
CJ = 'click-100927011-12347575'
ANCHOR = '<div style="background: linear-gradient(135deg,#ecfeff,#f8fafc); border: 1px solid #67e8f9;'
CLAIMS = {
    'Nejlevnější 2man volba': 2,
    'Cena / výkon': 1,
    'Za mě nejsilnější univerzál do 10 000 Kč pro většinu kaprařů.': 1,
    'Nejlepší ochrana a soukromí': 1,
    'Nejlevnější 2man bivak': 1,
    'Nejlepší univerzál': 1,
    'Nejlepší přístřešek / základna': 1,
}


class Anchors(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical = []
        self.hrefs = []

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == 'link' and 'canonical' in (data.get('rel') or '').split():
            self.canonical.append(data.get('href'))
        if tag == 'a':
            self.hrefs.append(data.get('href'))


def fetch(url, limit):
    request = Request(url, headers={
        'User-Agent': 'NaRybyBivakBudgetPublicReadOnly/1.0',
        'Cache-Control': 'no-cache, no-store',
    })
    with guarded_urlopen(request, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise RuntimeError('RESPONSE_ORIGIN_PATH_DRIFT')
        body = response.read(limit + 1)
    if len(body) > limit:
        raise RuntimeError('OVERSIZE_RESPONSE')
    return body.decode('utf-8')


def source():
    post = json.loads(fetch(REST, 900000))
    if (post.get('id') != 119575 or post.get('status') != 'publish'
            or post.get('slug') != 'nejlepsi-rybarske-bivaky-do-10-000-kc'
            or post.get('modified_gmt') != '2026-05-13T14:09:39'
            or post.get('link') != URL):
        raise RuntimeError('WORDPRESS_SOURCE_IDENTITY_DRIFT')
    content = post.get('content', {}).get('rendered')
    if not isinstance(content, str) or len(content) < 25000:
        raise RuntimeError('WORDPRESS_SOURCE_MISSING')
    if (content.count(ANCHOR) != 1 or content.count(CJ) != 22
            or MARKER in content or GUIDE in content):
        raise RuntimeError('WORDPRESS_SOURCE_ANCHOR_DRIFT')
    for text, expected in CLAIMS.items():
        if content.count(text) != expected:
            raise RuntimeError('WORDPRESS_SOURCE_COPY_DRIFT')
    links = Anchors()
    links.feed(content)
    old_cj = [href for href in links.hrefs if href and CJ in href]
    if len(old_cj) != 22:
        raise RuntimeError('WORDPRESS_SOURCE_AFFILIATE_DRIFT')
    return old_cj


def public(old_cj):
    html = fetch(URL, 1700000)
    links = Anchors()
    links.feed(html)
    if links.canonical != [URL]:
        raise RuntimeError('PUBLIC_CANONICAL_DRIFT')
    if html.count(MARKER) != 1:
        raise RuntimeError('NOT_DEPLOYED_BUYER_CORRECTION')
    note_start = html.find('<aside ' + MARKER)
    note_end = html.find('</aside>', note_start)
    if not (0 <= note_start < note_end < html.find(CJ)):
        raise RuntimeError('BUYER_NOTE_NOT_BEFORE_SHOPPING')
    note = html[note_start:note_end]
    if (note.count('href="' + GUIDE + '"') != 1
            or 'Změřte lehátko' not in note
            or 'ne vítězové doloženého nezávislého testu' not in note):
        raise RuntimeError('BUYER_NOTE_NOT_USEFUL')
    after_cj = [href for href in links.hrefs if href and CJ in href]
    if after_cj != old_cj:
        raise RuntimeError('HISTORICAL_AFFILIATE_HREF_CHANGED')
    for text in CLAIMS:
        if text in html:
            raise RuntimeError('OLD_UNVERIFIED_RANKING_STILL_VISIBLE')
    return {
        'state': 'PUBLIC_BIVAK_BUYER_TRUST_VERIFIED',
        'url': URL,
        'uncertain_rankings_replaced': len(CLAIMS),
        'historical_affiliate_links_preserved': len(old_cj),
        'internal_decision_guides_added': 1,
        'wordpress_stored_writes': 0,
        'affiliate_entitlement_verified': False,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--public', action='store_true', help='Expect deployed changes and verify public DOM')
    args = parser.parse_args()
    old_cj = source()
    result = public(old_cj) if args.public else {
        'state': 'SOURCE_PREFLIGHT_READY_NOT_A_PUBLIC_RELEASE',
        'post_id': 119575,
        'historical_affiliate_links': len(old_cj),
        'wordpress_stored_writes': 0,
    }
    print(json.dumps(result, ensure_ascii=False, sort_keys=True))


if __name__ == '__main__':
    main()
