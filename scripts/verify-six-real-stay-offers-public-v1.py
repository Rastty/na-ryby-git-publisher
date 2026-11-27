#!/usr/bin/env python3
"""Six actual live stay guides / 12 direct, approved affiliate links. GET only."""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
from urllib.parse import urlsplit

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ORIGIN='https://na-ryby.eu'
OFFERS={
    'cyklo':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata',
    'srub':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub',
    'more':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness',
    'lipno':'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F'
}
ROUTES={
 'rybarska-chata-bitov':('cyklo','srub'),
 'rybareni-a-romantika-tipy-na-pobyty-pro-dva-zamilovane-rybare':('srub','more'),
 'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice':('srub','more'),
 'rybarske-dobrodruzstvi-soukrome-rybareni-s-ubytovanim-v-ceske-prirode':('more','cyklo'),
 'rybareni-s-ubytovanim-v-ceske-republice-doporucene-lokality':('lipno','more'),
 'tipy-na-skvele-mista-pro-rybareni-s-ubytovanim-u-vody-v-cesku':('lipno','more'),
}
MARKER='data-nry-real-stay-offers="v1"'
ASSET='/wp-content/plugins/na-ryby-git-publisher/assets/'
CSS=ORIGIN+ASSET+'nry-gear-archive-entry-v1.css?ver=1.0.0'
JS=ORIGIN+ASSET+'nry-six-real-stay-click-v1.js?ver=1.0.0'

class DOM(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.panels=0; self.slug=None; self.panel_depth=0
        self.nav_depth=0; self.nav_count=0;self.anchors=[]
        self.assets_css=[];self.assets_js=[];self.disclosure=False
        self.capturing_text=False;self.text_parts=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='section' and a.get('data-nry-real-stay-offers')=='v1':
            self.panels+=1;self.panel_depth=1
            self.slug=a.get('data-nry-real-stay-slug')
            self.capturing_text=True
        elif tag=='section' and self.panel_depth:self.panel_depth+=1
        if tag=='nav' and self.panel_depth and a.get('aria-label')=='Dvě ověřitelné partnerské nabídky':
            self.nav_depth+=1;self.nav_count+=1
        elif tag=='nav' and self.nav_depth:self.nav_depth+=1
        if tag=='a' and self.nav_depth:
            self.anchors.append((a.get('data-nry-real-stay-offer'),a.get('href'),
                a.get('rel'),a.get('target')))
        if tag=='link' and 'nry-gear-archive-entry-v1.css' in (a.get('href') or ''):
            self.assets_css.append(a.get('href'))
        if tag=='script' and 'nry-six-real-stay-click-v1.js' in (a.get('src') or ''):
            self.assets_js.append(a.get('src'))
    def handle_endtag(self,tag):
        if tag=='nav' and self.nav_depth:self.nav_depth-=1
        if tag=='section' and self.panel_depth:
            self.panel_depth-=1
            if self.panel_depth==0:
                self.capturing_text=False
                self.disclosure=('Partnerské odkazy Hauzi nebo Hportal' in ''.join(self.text_parts))
    def handle_data(self,data):
        if self.capturing_text:self.text_parts.append(data)

def validate(body,slug):
    p=DOM();p.feed(body);p.close()
    if p.panels!=1 or p.nav_count!=1 or body.count(MARKER)!=1 or p.slug!=slug:
        raise AssertionError('EXACT_STAY_PANEL_MISSING')
    want=[(r,OFFERS[r],'sponsored nofollow noopener','_blank') for r in ROUTES[slug]]
    if p.anchors!=want:raise AssertionError('AFFILIATE_EXIT_SCOPE_MISMATCH')
    if not p.disclosure:raise AssertionError('AFFILIATE_DISCLOSURE_MISSING')
    if p.assets_css!=[CSS] or p.assets_js!=[JS]:
        raise AssertionError('STAY_ASSETS_MISSING')
    return {'slug':slug,'realAffiliateExits':2,'visiblePanel':True}

def get(url,cap):
    req=Request(url,method='GET',headers={
       'User-Agent':'NaRybyRealStaysProof/1.0','Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,attempts=2,timeout=25) as response:
        if response.status!=200 or response.geturl()!=url:
            raise AssertionError('PUBLIC_200_CANONICAL_REQUIRED')
        data=response.read(cap+1)
    if len(data)>cap:raise AssertionError('PUBLIC_BODY_TOO_LARGE')
    return data

def verify():
    pages=[]
    for slug in ROUTES:
        pages.append(validate(get(ORIGIN+'/'+slug+'/',1500000).decode('utf-8','replace'),slug))
    digest={}
    for name,url in [('nry-gear-archive-entry-v1.css',CSS),
                     ('nry-six-real-stay-click-v1.js',JS)]:
        served=get(url,50000)
        actual=(Path(__file__).resolve().parents[1]/'assets'/name).read_bytes()
        if hashlib.sha256(served).digest()!=hashlib.sha256(actual).digest():
            raise AssertionError('SERVED_ASSET_SHA_MISMATCH')
        digest[name]=hashlib.sha256(served).hexdigest()
    return {'status':'NRY_SIX_REAL_STAY_AFFILIATE_EXITS_PUBLIC_VERIFIED',
        'publicPages':6,'directMerchantCTAs':12,'pages':pages,
        'servedAssetSha256':digest,'WordPressWrites':0,
        'merchantGETs':0,'syntheticClicks':0,
        'realClicks':'UNKNOWN','approvedOrPaidCommissions':'UNKNOWN'}

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('USE_VERIFY_ONLY')
    try:print(json.dumps(verify(),ensure_ascii=False,sort_keys=True))
    except Exception as exc:
        known={'EXACT_STAY_PANEL_MISSING','AFFILIATE_EXIT_SCOPE_MISMATCH',
            'AFFILIATE_DISCLOSURE_MISSING','STAY_ASSETS_MISSING',
            'PUBLIC_200_CANONICAL_REQUIRED','PUBLIC_BODY_TOO_LARGE','SERVED_ASSET_SHA_MISMATCH'}
        code=str(exc) if isinstance(exc,AssertionError) and str(exc) in known else 'PUBLIC_PROOF_UNAVAILABLE'
        print(json.dumps({'status':'NRY_SIX_STAY_EXITS_NOT_VERIFIED',
                          'reason':code,'exceptionClass':type(exc).__name__}))
        sys.exit(1)
