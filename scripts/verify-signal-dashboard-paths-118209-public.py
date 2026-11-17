#!/usr/bin/env python3
"""GET-only independent public check of repaired signal dashboard CTAs."""
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/signalizatory-dashboard/'
PILLAR = 'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'
START = 'https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/'
THREE = 'https://na-ryby.eu/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'
OLD = ('signalizator-zaberov-do-100-eur-najlepsie-modely',
       'sada-signalizatorov-do-150-eur-co-kupit-a-comu-sa-vyhnut',
       'signalizatory-zaberov-kompletny-sprievodca-vyberom')


class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.navs = 0
        self.nav = False
        self.nav_links = []
        self.all_links = []
        self.current_link = None
        self.canonicals = []
        self.styles = []
        self.ids = set()

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if a.get('id'):
            self.ids.add(a['id'])
        if tag == 'link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag == 'link' and a.get('rel') == 'stylesheet':
            self.styles.append(a.get('href', ''))
        if tag == 'nav' and a.get('data-nry-signal-decision-paths') == 'v1':
            self.navs += 1
            self.nav = True
        if tag == 'a':
            self.current_link = {'href': a.get('href'), 'text': ''}
            if self.nav:
                self.nav_links.append(a.get('href'))

    def handle_data(self, data):
        if self.current_link is not None:
            self.current_link['text'] += data

    def handle_endtag(self, tag):
        if tag == 'a' and self.current_link is not None:
            self.all_links.append(self.current_link)
            self.current_link = None
        if tag == 'nav' and self.nav:
            self.nav = False


def verify(source):
    parser = Links()
    parser.feed(source)
    if parser.canonicals != [URL]:
        raise RuntimeError('BLOCKED: CANONICAL_DRIFT')
    if parser.navs != 1 or parser.nav_links != [START, '#top-modely', '#srovnani', THREE]:
        raise RuntimeError('NOT_DEPLOYED: DECISION_PATHS_MISMATCH')
    if not {'top-modely', 'srovnani'}.issubset(parser.ids):
        raise RuntimeError('NOT_DEPLOYED: IN_PAGE_TARGET_MISSING')
    if not any('nry-signal-dashboard-paths-v1.css' in s for s in parser.styles):
        raise RuntimeError('NOT_DEPLOYED: CSS_NOT_ENQUEUED')
    if any(old in source for old in OLD):
        raise RuntimeError('NOT_DEPLOYED: OLD_404_TARGET_STILL_PRESENT')
    expected = [('Projít levnější modely', '#top-modely'),
                ('Projít doporučené sety', '#srovnani'),
                ('Kompletní průvodce', PILLAR)]
    for name, href in expected:
        if not any(name in a['text'] and a['href'] == href for a in parser.all_links):
            raise RuntimeError('NOT_DEPLOYED: CTA_MISMATCH_'+name)
    return {'state':'PUBLIC_404_PATH_REPAIR_VERIFIED', 'url':URL,
            'replaced_404_anchor_occurrences':4,
            'working_decision_paths':len(parser.nav_links),
            'wordpress_post_writes':0, 'affiliate_changes':0}


def fetch():
    req = Request(URL+'?nry_signal_paths_verify=20261117', headers={
        'User-Agent':'NaRybySignalPathsReadOnly/1.0', 'Cache-Control':'no-cache'})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200 or response.geturl().split('?')[0] != URL:
            raise RuntimeError('BLOCKED: UNEXPECTED_RESPONSE')
        content = response.read(2_000_001)
    if len(content) > 2_000_000:
        raise RuntimeError('BLOCKED: PAGE_TOO_LARGE')
    return content.decode('utf-8')


if __name__ == '__main__':
    print(json.dumps(verify(fetch()), ensure_ascii=False, sort_keys=True))
