#!/usr/bin/env python3
"""GET-only evidence for two archived gift articles and four new first-party paths.

No WordPress writes, merchant requests, synthetic clicks or GA4 events.
"""
import argparse
import hashlib
import html
import json
import re
from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

ORIGIN = 'https://na-ryby.eu'
PAGES = {
    46852: ('nejlepsi-darky-pro-rybare-tipy-na-nadcasove-a-prakticke-prezentace',
            '2023-05-13T16:02:20', 'Nejlepší dárky pro rybáře'),
    35630: ('novinky-pro-rybare-nejzajimavejsi-vanocni-darky-na-trhu',
            '2023-04-06T00:40:35', 'Nejzajímavější vánoční dárky'),
}
ROUTES = {
    'night': '/celovky-pro-rybare-porovnani-cen-a-vykonu/',
    'spinning': '/nejlepsi-privlacove-pruty-do-3-000-kc/',
    'feeder': '/nejlepsi-method-feeder-pruty-do-3-500-kc/',
    'camp': '/nejlepsi-rybarske-bivaky-do-10-000-kc/',
}
ASSETS = {
    'js': ('/wp-content/plugins/na-ryby-git-publisher/assets/nry-gift-buyer-v1.js',
           Path(__file__).resolve().parents[1] / 'assets/nry-gift-buyer-v1.js'),
    'css': ('/wp-content/plugins/na-ryby-git-publisher/assets/nry-gift-buyer-v1.css',
            Path(__file__).resolve().parents[1] / 'assets/nry-gift-buyer-v1.css'),
}


def fetch(url, maximum=1000000):
    request = Request(url, headers={'User-Agent': 'NaRybyGiftBuyerProof/1.0',
                                    'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise RuntimeError('UNEXPECTED_REDIRECT_OR_STATUS')
        data = response.read(maximum+1)
    if len(data)>maximum:
        raise RuntimeError('RESPONSE_TOO_LARGE')
    return data


class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical=[];self.links=[];self.panel=False;self.panel_count=0
        self.panel_links=[];self.styles=[];self.scripts=[];self.source=''

    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonical.append(a.get('href'))
        if tag=='link' and a.get('rel')=='stylesheet' and ASSETS['css'][0] in (a.get('href') or ''):
            self.styles.append(a['href'])
        if tag=='script' and ASSETS['js'][0] in (a.get('src') or ''):
            self.scripts.append(a['src'])
        if tag=='nav' and a.get('data-nry-gift-buyer')=='v1':
            self.panel_count+=1;self.panel=True
        if tag=='a' and a.get('href'):
            self.links.append(a['href'])
            if self.panel:self.panel_links.append((a.get('data-nry-gift-path'),a['href']))

    def handle_endtag(self,tag):
        if tag=='nav' and self.panel:self.panel=False


def parse(raw):
    p=Document();p.feed(raw.decode('utf-8'));return p


def canonical_asset(links,path):
    expected=ORIGIN+path+'?ver=1.0.0'
    if links != [expected]:
        raise RuntimeError('WRONG_ASSET_VERSION_OR_DUPLICATED_'+path)


def article_anchor_fragment(content, anchor):
    """Find original article copy, not identical SEO title/meta or related cards.

    WordPress repeats the article title in its <head>, header and breadcrumbs;
    locating the bare phrase in the full HTML creates a false ordering failure.
    The preserved source supplies an exact paragraph/heading to compare instead.
    """
    pattern = re.compile(r'<(?P<tag>p|h[2-6])\b[^>]*>[^<]{0,500}</(?P=tag)>', re.I)
    for match in pattern.finditer(content):
        if anchor in html.unescape(match.group(0)):
            return match.group(0).encode('utf-8')
    raise RuntimeError('SOURCE_ARTICLE_ANCHOR_ELEMENT_NOT_FOUND')


def check(public):
    results={}
    for id,(slug,modified,anchor) in PAGES.items():
        url=ORIGIN+'/'+slug+'/'
        rest=ORIGIN+f'/wp-json/wp/v2/posts/{id}?_fields=id,slug,status,modified_gmt,link,content'
        data=json.loads(fetch(rest,650000))
        if (data.get('id')!=id or data.get('slug')!=slug or data.get('status')!='publish'
                or data.get('modified_gmt')!=modified or data.get('link')!=url):
            raise RuntimeError('GIFT_POST_IDENTITY_DRIFT_'+str(id))
        content=data.get('content',{}).get('rendered','')
        if not isinstance(content,str) or len(content)<5000 or anchor not in content:
            raise RuntimeError('GIFT_POST_SOURCE_DRIFT_'+str(id))
        source=parse(content.encode())
        live_bytes=fetch(url,1700000)
        live=parse(live_bytes)
        if live.canonical!=[url]:
            raise RuntimeError('GIFT_CANONICAL_DRIFT_'+str(id))
        if not (Counter(source.links) <= Counter(live.links)):
            raise RuntimeError('HISTORIC_GIFT_HREF_MISSING_'+str(id))
        if public:
            if live.panel_count!=1:
                raise RuntimeError('GIFT_NAV_NOT_PUBLIC_'+str(id))
            expected=[(key,ORIGIN+path) for key,path in ROUTES.items()]
            if live.panel_links!=expected:
                raise RuntimeError('GIFT_ROUTE_DRIFT_'+str(id))
            canonical_asset(live.styles,ASSETS['css'][0])
            canonical_asset(live.scripts,ASSETS['js'][0])
            marker=live_bytes.find(b'data-nry-gift-buyer="v1"')
            original=live_bytes.find(article_anchor_fragment(content,anchor))
            if marker<0 or original<0 or marker>original:
                raise RuntimeError('GIFT_PANEL_NOT_BEFORE_ARCHIVED_TEXT_'+str(id))
        elif live.panel_count>1:
            raise RuntimeError('GIFT_PANEL_ALREADY_DUPLICATED_'+str(id))
        results[str(id)]={'source_hrefs':len(source.links),'gift_paths':4 if public else None}
    if public:
        for path,file in ASSETS.values():
            served=fetch(ORIGIN+path+'?ver=1.0.0',50000)
            if hashlib.sha256(served).digest()!=hashlib.sha256(file.read_bytes()).digest():
                raise RuntimeError('GIFT_ASSET_BYTES_DRIFT_'+path)
    # Read only the FOUR first-party destinations once. These are no prices or stock evidence.
    route_ok=[]
    for route in ROUTES.values():
        url=ORIGIN+route
        doc=parse(fetch(url,1600000))
        if doc.canonical != [url]:
            raise RuntimeError('GIFT_DESTINATION_CANONICAL_DRIFT_'+route)
        route_ok.append(route)
    return {'state':'GIFT_BUYER_ROUTES_PUBLIC_VERIFIED' if public else 'GIFT_BUYER_SOURCE_READY',
            'posts':results,'routes_http200_canonical':len(route_ok),'wordpress_writes':0,
            'merchant_requests':0,'synthetic_clicks':0,'real_ga4_clicks_observed':None,
            'commission_proven':False}


if __name__=='__main__':
    parser=argparse.ArgumentParser()
    parser.add_argument('--public',action='store_true')
    print(json.dumps(check(parser.parse_args().public),ensure_ascii=False,sort_keys=True))
