#!/usr/bin/env python3
"""Read-only, exact-submenu production receipt for PR #307.

The plugin's Git merge is not proof of deployment. This GET-only readback
accepts only nested WordPress menu markup with all three expected links.
No merchant requests, authentication, storage or synthetic clicks.
"""
import argparse
import json
import sys
import urllib.request
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = 'https://na-ryby.eu/'
PARENT = ('Revíry', 'https://na-ryby.eu/category/soukrome-rybarske-reviry/')
ROUTES = (
    ('Jižní Morava', 'https://na-ryby.eu/category/soukrome-rybarske-reviry/soukrome-rybarske-reviry-jihomoravsky-kraj/'),
    ('Dyje 6 – Nové Mlýny', 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/'),
    ('Jak číst pravidla revírů', 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/'),
)
MARKER = 'nry-revir-discovery-v1'


class MenuParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.nodes = []
        self.li_stack = []
        self.link_stack = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == 'li':
            node = {'classes': (values.get('class') or '').split(), 'children': [],
                    'title': None, 'href': None}
            if self.li_stack:
                self.li_stack[-1]['children'].append(node)
            self.nodes.append(node)
            self.li_stack.append(node)
        elif tag == 'a' and self.li_stack:
            self.link_stack.append((self.li_stack[-1], values.get('href'), []))

    def handle_data(self, data):
        if self.link_stack:
            self.link_stack[-1][2].append(data)

    def handle_endtag(self, tag):
        if tag == 'a' and self.link_stack:
            node, href, parts = self.link_stack.pop()
            if node['title'] is None:
                node['title'] = ' '.join(''.join(parts).split())
                node['href'] = href
        elif tag == 'li' and self.li_stack:
            self.li_stack.pop()


def verify_public_html(html):
    if not isinstance(html, str):
        return {'status': 'INVALID_HTML_INPUT', 'verified_menus': 0}
    parser = MenuParser()
    parser.feed(html)
    candidates = [n for n in parser.nodes if (n['title'], n['href']) == PARENT]
    verified = 0
    for parent in candidates:
        children = parent['children']
        if ('menu-item-has-children' in parent['classes']
                and len(children) == len(ROUTES)
                and all((node['title'], node['href']) == route
                        and MARKER in node['classes']
                        for node, route in zip(children, ROUTES))):
            verified += 1
    return {'status': 'PUBLIC_VERIFIED_ALL' if verified else 'NOT_YET_PUBLIC_OR_MENU_DRIFT',
            'verified_menus': verified, 'candidate_menus': len(candidates)}


def read_live():
    req = urllib.request.Request(URL, headers={
        'User-Agent': 'NaRyby-Menu-IndependentReadback/1.0',
        'Cache-Control': 'no-cache', 'Pragma': 'no-cache',
    }, method='GET')
    with guarded_urlopen(req, attempts=2, timeout=25) as response:
        return verify_public_html(response.read(2_000_000).decode('utf-8', 'replace'))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--public', action='store_true', help='GET exact live homepage; no writes')
    args = parser.parse_args()
    if not args.public:
        parser.error('Use --public for an independent production readback')
    try:
        result = read_live()
    except Exception as error:
        result = {'status': 'PUBLIC_READBACK_UNAVAILABLE',
                  'error_type': type(error).__name__, 'verified_menus': 0}
    result.update({'checked_at_utc': datetime.now(timezone.utc).isoformat(),
                   'url': URL, 'wordpress_writes': 0, 'merchant_requests': 0,
                   'synthetic_clicks': 0})
    print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    return 0 if result['status'] == 'PUBLIC_VERIFIED_ALL' else 1


if __name__ == '__main__':
    raise SystemExit(main())
