#!/usr/bin/env python3
"""GET-only proof that legacy 2023 RC baitboat misinformation is repaired."""
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku-pro-rybare/'
GUIDE = 'https://na-ryby.eu/zavazeci-lodky-pro-rybare/'
PICKER = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku/'


class Probe(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.stylesheets = []
        self.notice_count = 0
        self.notice_links = []
        self.in_notice = False
        self.affiliate_ctas = 0

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == 'link' and 'canonical' in (data.get('rel') or '').split():
            self.canonicals.append(data.get('href'))
        if tag == 'link' and data.get('rel') == 'stylesheet':
            self.stylesheets.append(data.get('href', ''))
        if tag == 'aside' and data.get('data-nry-legacy-baitboat-trust') == 'v1':
            self.in_notice = True
            self.notice_count += 1
        if tag == 'a':
            if self.in_notice:
                self.notice_links.append(data.get('href'))
            if data.get('href') == 'https://na-ryby.eu/zavazeci-lodky-parys':
                self.affiliate_ctas += 1

    def handle_endtag(self, tag):
        if tag == 'aside' and self.in_notice:
            self.in_notice = False


def verify(source):
    p = Probe()
    p.feed(source)
    if p.canonicals != [URL]:
        raise RuntimeError('BLOCKED: CANONICAL_CHANGED')
    if p.notice_count != 1 or p.notice_links != [GUIDE, PICKER]:
        raise RuntimeError('NOT_DEPLOYED: BUYER_CHOICE_NOTICE_MISSING')
    if not any('nry-legacy-baitboat-trust-v1.css' in s for s in p.stylesheets):
        raise RuntimeError('NOT_DEPLOYED: SCOPED_CSS_NOT_LOADED')
    if p.affiliate_ctas != 4:
        raise RuntimeError('BLOCKED: HISTORICAL_AFFILIATE_LINKS_CHANGED')
    for bad in ('kolik lidí a věcí budete převážet',
                'aby vás a váš náklad udržela',
                'polstrovaných sedadel',
                'Kajak je další populární zavážecí loďka',
                'HODNOCENÍ: ⭐⭐⭐⭐⭐',
                'NEJLEPŠÍ NA TRHU'):
        if bad in source:
            raise RuntimeError('NOT_DEPLOYED: OLD_MISINFORMATION_'+bad[:16])
    for good in ('Typy RC zavážecích loděk podle výbavy',
                 'Historické hodnocení nebylo doloženo',
                 'Rybář zůstává na břehu.',
                 'bezposádkové RC plavidlo'):
        if good not in source:
            raise RuntimeError('NOT_DEPLOYED: CORRECTED_CONTENT_MISSING_'+good[:16])
    return {'state':'PUBLIC_LEGACY_BAITBOAT_TRUST_VERIFIED', 'url':URL,
            'corrected_source_anchors':23, 'historical_star_ratings_removed':3,
            'new_internal_choice_links':2, 'affiliate_ctas_preserved':p.affiliate_ctas,
            'wordpress_stored_writes':0}


def fetch():
    req = Request(URL+'?nry_baitboat_trust_verify=20261117', headers={
        'User-Agent':'NaRybyLegacyBaitboatReadOnly/1.0',
        'Cache-Control':'no-cache'})
    with guarded_urlopen(req, timeout=25) as res:
        if res.status != 200 or res.geturl().split('?')[0] != URL:
            raise RuntimeError('BLOCKED: UNEXPECTED_RESPONSE')
        body = res.read(2_000_001)
    if len(body) > 2_000_000:
        raise RuntimeError('BLOCKED: PUBLIC_PAGE_TOO_LARGE')
    return body.decode('utf-8')


if __name__ == '__main__':
    print(json.dumps(verify(fetch()), ensure_ascii=False, sort_keys=True))
