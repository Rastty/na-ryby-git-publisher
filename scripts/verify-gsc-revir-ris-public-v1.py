#!/usr/bin/env python3
"""Independent anonymous GET proof: 2018 trout safety and indexed candát path.

No network writes or CJ/merchant requests, no synthetic GA4 events.
PR preflight requires exactly the old source without marker; final proof
requires exactly one visible role-specific block on each current canonical.
"""
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = 'https://na-ryby.eu/'
DEST = {
 'candat':ROOT+'rybarske-reviry-kde-muzete-ulovit-candata-obecneho-v-cr/',
 'praha':ROOT+'svazove-rybarske-pstruhove-reviry-praha/',
 'stredocesky':ROOT+'svazove-rybarske-pstruhove-reviry-stredocesky-kraj/',
}
BASE_LINKS = (
 'https://ris.rybsvaz.cz/seznam-reviru',
 'https://ris.rybsvaz.cz/rybarsky-rad',
)
CANDAT_LINKS=BASE_LINKS+(
 ROOT+'vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
 ROOT+'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
 ROOT+'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',
)
CSS='nry-gsc-revir-ris-paths-v1.css'
MARKER='data-nry-gsc-revir-ris-paths="v1"'
OLD={
 'candat':('Kde candáta v ČR hledat nejčastěji',),
 'praha':('BOJOVSKÝ POTOK 1','KOCÁBA 1'),
 'stredocesky':('413 001 BĚLÁ 1','413 998 BOŽKOV'),
}
NEW={
 'candat':('Nejdřív voda a pravidla, potom výbava','Naplánovat výpravu'),
 'praha':('Původní soupis z roku 2018','aktuální pravidla zkontrolujte'),
 'stredocesky':('Původní soupis z roku 2018','aktuální pravidla zkontrolujte'),
}
class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.css=[]
        self.sections=[]
        self.depth=0
        self.hrefs=[]
        self.active=None
        self.positions=0
        self.text=[]
    def handle_starttag(self,tag,attrs):
        self.positions+=1
        a=dict(attrs)
        if tag=='link':
            if 'canonical' in (a.get('rel') or '').lower().split():
                self.canonicals.append(a.get('href'))
            if CSS in (a.get('href') or ''):
                self.css.append(a['href'])
        if tag=='section' and a.get('data-nry-gsc-revir-ris-paths')=='v1':
            self.sections.append((a.get('data-nry-ris-scope'),self.positions))
            self.active=True;self.depth=1
        elif self.active and tag=='section':
            self.depth+=1
        if tag=='a' and self.active:
            self.hrefs.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.active:
            self.depth-=1
            if self.depth<=0:self.active=None
    def handle_data(self,data):
        self.text.append(data)
    def plain(self):
        return ' '.join(' '.join(self.text).split())

def verify(html,route,preflight=False):
    if route not in DEST:raise ValueError('UNKNOWN_ROUTE')
    if not isinstance(html,str) or len(html)<1800 or len(html)>2_000_000:
        raise ValueError('BOUNDED_PUBLIC_HTML_REQUIRED')
    d=Document()
    d.feed(html)
    if d.canonicals!=[DEST[route]]:
        raise ValueError('CANONICAL_NOT_SOURCE_BOUND')
    full=d.plain()
    for phrase in OLD[route]:
        if phrase not in full:raise ValueError('LEGACY_ORIGINAL_CONTENT_NOT_PRESERVED')
    count=html.count(MARKER)
    if preflight:
        if count or d.sections or d.css:
            raise ValueError('ALREADY_DEPLOYED_OR_DUPLICATE_BEFORE_RELEASE')
        return {'status':'NRY_GSC_REVIR_RIS_PREFLIGHT_VERIFIED','route':route}
    if count!=1 or len(d.sections)!=1 or d.sections[0][0]!=route:
        raise ValueError('NEW_SOURCE_SECTION_NOT_EXACT')
    if len(d.css)!=1 or 'ver=1.0.0' not in d.css[0]:
        raise ValueError('CSS_NOT_VERSIONED_OR_DUPLICATE')
    for phrase in NEW[route]:
        if phrase not in full:
            raise ValueError('NEW_VISIBLE_DECISION_TEXT_MISSING')
    expected=set(CANDAT_LINKS if route=='candat' else BASE_LINKS)
    if len(d.hrefs)!=len(expected) or set(d.hrefs)!=expected:
        raise ValueError('OFFICIAL_OR_FIRST_PARTY_TARGETS_CHANGED')
    if 'DOPLNIT-URL' in full or 'povolenka potvrzena' in full.lower():
        raise ValueError('UNTRUSTED_VISITOR_CLAIM')
    return {'status':'NRY_GSC_REVIR_RIS_PUBLIC_VERIFIED','route':route,
      'marker_count':1,'original_preserved':True,
      'official_links':len(BASE_LINKS),
      'first_party_new_links':3 if route=='candat' else 0,
      'affiliate_clicks':0,'wordpress_writes':0,
      'measured_gsc_uplift':None,'commission_uplift':None}

def get_public(url):
    req=Request(url,method='GET',headers={
      'User-Agent':'NaRybyRevirRISPinnedPublicReadOnly/1.0',
      'Accept':'text/html','Cache-Control':'no-cache'})
    with guarded_urlopen(req,timeout=22) as res:
        if res.status!=200 or res.geturl()!=url:
            raise ValueError('UNTRUSTED_REDIRECT_OR_HTTP')
        data=res.read(2_000_001)
    if len(data)>2_000_000:raise ValueError('PAGE_TOO_LARGE')
    return data.decode('utf-8','replace')

if __name__=='__main__':
    preflight=sys.argv[1:]==['--preflight']
    if not preflight and sys.argv[1:]!=['--verify']:
        raise SystemExit('usage: --preflight | --verify')
    results=[]
    for route,url in DEST.items():
        results.append(verify(get_public(url),route,preflight=preflight))
    print(json.dumps({'status':'NRY_GSC_REVIR_RIS_PREFLIGHT_ALL_3_VERIFIED' if preflight
      else 'NRY_GSC_REVIR_RIS_ALL_3_PUBLIC_VERIFIED',
      'pages':results,'affiliate_tracking_clicks':0,
      'wordpress_writes':0,'gsc_uplift':'NOT_MEASURED'},
      ensure_ascii=False,sort_keys=True))
