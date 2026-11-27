#!/usr/bin/env python3
"""Exact anonymous GET-only readback for one South Moravia accommodation index."""
from html.parser import HTMLParser
from pathlib import Path
import json
import sys
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL='https://na-ryby.eu/rybareni-s-ubytovanim-jihomoravsky-kraj/'
CYCLO='https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/'
SRUB='https://na-ryby.eu/chata-rybarsky-wellness-srub/'
HUB='https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
MARKER='data-nry-jm-archive-stays="v1"'
EXPECTED=(CYCLO,SRUB,HUB)

class Parse(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.markers=[]
        self.panel_depth=0
        self.links=[]
        self.parts=[]
        self.after_parts=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='section' and a.get('data-nry-jm-archive-stays')=='v1':
            self.markers.append(True)
            self.panel_depth=1
        elif tag=='section' and self.panel_depth:
            self.panel_depth+=1
        if tag=='a' and self.panel_depth:
            self.links.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.panel_depth:
            self.panel_depth-=1
    def handle_data(self,data):
        self.parts.append(data)
        if not self.panel_depth:
            self.after_parts.append(data)

def verify(html):
    if not isinstance(html,str) or not 8000<len(html)<1500000:
        raise ValueError('EXACT_BOUNDED_PUBLIC_DOCUMENT_REQUIRED')
    p=Parse();p.feed(html);p.close()
    if p.canonicals!=[URL]:raise ValueError('CANONICAL_DRIFT')
    if html.count(MARKER)!=1 or len(p.markers)!=1:
        raise ValueError('ONE_OWNED_PANEL_REQUIRED')
    if p.links!=list(EXPECTED):
        raise ValueError('EXACT_THREE_FIRST_PARTY_ROUTES_REQUIRED')
    old=' '.join(' '.join(p.after_parts).split())
    if not all(x in old for x in ('Zukal','Popůvky','Havelka ORCA','Rybník Božice')):
        raise ValueError('HISTORICAL_ARCHIVE_MUST_REMAIN_VISIBLE')
    if html.index(MARKER)>html.index('Zukal'):
        raise ValueError('BUYER_CHOICES_MUST_PRECEDE_HISTORICAL_RESULTS')
    if any('tracking.affiliateport.eu' in (href or '') for href in p.links):
        raise ValueError('UNAUTHORIZED_EXTERNAL_HREF')
    return {'state':'SOUTH_MORAVIA_STAYS_ARCHIVE_PUBLIC_VERIFIED',
        'exactFirstPartyLinks':3,'historicalIndexPreserved':True,
        'wordpressWrites':0,'merchantRequests':0,'syntheticClicks':0,
        'actualClicks':'UNKNOWN','approvedOrPaidCommissions':'UNKNOWN'}

def get():
    req=Request(URL,method='GET',headers={'User-Agent':'NaRybySouthMoraviaStaysPublic/1.0',
        'Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,timeout=25) as resp:
        if resp.status!=200 or resp.geturl()!=URL:raise ValueError('EXACT_ORIGIN_NOT_200')
        body=resp.read(1500001)
    if len(body)>1500000:raise ValueError('PUBLIC_HTML_TOO_LARGE')
    return body.decode('utf-8','replace')

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('use --verify')
    print(json.dumps(verify(get()),ensure_ascii=False))
