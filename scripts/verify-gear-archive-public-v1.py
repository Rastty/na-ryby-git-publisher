#!/usr/bin/env python3
"""Anonymous public proof of six equipment-category buyer entry sections.

GET only current first-party pages and the two exact local assets. Never
follow anchor/merchant URLs, synthesize clicks, write WP or fabricate economics.
"""
import hashlib
import json
import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ORIGIN='https://na-ryby.eu'
ROOT=ORIGIN+'/category/vybaveni-pro-rybare/'
MARKER='data-nry-gear-archive="v1"'
ASSET_PREFIX=ORIGIN+'/wp-content/plugins/na-ryby-git-publisher/assets/'
CSS=ASSET_PREFIX+'nry-gear-archive-entry-v1.css?ver=1.0.0'
JS=ASSET_PREFIX+'nry-gear-archive-click-v1.js?ver=1.1.0'
ROUTES={
 'pruty':{
    'title':'Kaprové pruty',
    'links':[
      ('carp_rod_guide',ORIGIN+'/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'),
      ('carp_rod_budget',ORIGIN+'/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/'),
      ('carp_rod_youth',ORIGIN+'/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/')
    ]},
 'signalizatory':{
    'title':'Signalizátory',
    'links':[
      ('signal_guide',ORIGIN+'/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'),
      ('signal_budget',ORIGIN+'/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'),
      ('signal_range',ORIGIN+'/jak-vybrat-signalizator-pro-lov-na-velkych-prehradach-a-rekach/')
    ]},
 'navijaky':{
    'title':'Navijáky',
    'links':[
      ('reel_guide',ORIGIN+'/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'),
      ('feeder_reel',ORIGIN+'/nejlepsi-feederove-navijaky-srovnani/'),
      ('spinning_reel',ORIGIN+'/nejlepsi-privlacove-navijaky-jak-vybrat/')
    ]},
 'echoloty-na-lod':{
    'title':'Echoloty a sonary',
    'links':[
      ('sonar_guide',ORIGIN+'/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'),
      ('sonar_budget',ORIGIN+'/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'),
      ('sonar_setup',ORIGIN+'/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/')
    ]},
 'bivaky':{
    'title':'Bivaky',
    'links':[
      ('bivak_guide',ORIGIN+'/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'),
      ('fishing_stays',ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/')
    ]},
 'zavazeci-lodky':{
    'title':'Zavážecí loďky',
    'links':[
      ('baitboat_choice',ORIGIN+'/zavazeci-lodky-pro-rybare/'),
      ('baitboat_sonar',ORIGIN+'/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/')
    ]}
}

class ArchiveHTML(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.h1_count=0;self.h1_text='';self.in_h1=False;self.h1_end=None
        self.entries=0;self.entry_pos=None;self.entry_slug=None
        self.depth=0;self.links=[];self.css=[];self.js=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='h1':
            self.h1_count+=1;self.in_h1=True
        if tag=='section' and a.get('data-nry-gear-archive')=='v1':
            self.entries+=1;self.depth=1
            self.entry_pos=self.getpos();self.entry_slug=a.get('data-nry-gear-category')
        elif tag=='section' and self.depth:
            self.depth+=1
        if tag=='a' and self.depth:
            self.links.append((a.get('data-nry-gear-guide'),a.get('href')))
        if tag=='link' and 'stylesheet' in (a.get('rel') or '').split():
            if 'nry-gear-archive-entry-v1.css' in (a.get('href') or ''):
                self.css.append(a.get('href'))
        if tag=='script' and 'nry-gear-archive-click-v1.js' in (a.get('src') or ''):
            self.js.append(a.get('src'))
    def handle_data(self,data):
        if self.in_h1:self.h1_text+=data
    def handle_endtag(self,tag):
        if tag=='h1' and self.in_h1:
            self.in_h1=False;self.h1_end=self.getpos()
        if tag=='section' and self.depth:
            self.depth-=1

def check(html,slug):
    if slug not in ROUTES or not isinstance(html,str) or len(html)<5000 or len(html)>1800000:
        raise AssertionError('EXACT_ARCHIVE_REQUIRED')
    p=ArchiveHTML();p.feed(html);p.close()
    cfg=ROUTES[slug]
    if p.h1_count!=1 or ' '.join(p.h1_text.split())!=cfg['title']:
        raise AssertionError('CATEGORY_H1_DRIFT')
    if html.count(MARKER)!=1 or p.entries!=1 or p.entry_slug!=slug:
        raise AssertionError('CATEGORY_OWNED_BLOCK_NOT_EXACT')
    if p.entry_pos is None or p.h1_end is None or p.entry_pos<=p.h1_end:
        raise AssertionError('BUYER_CHOICES_NOT_AFTER_H1')
    # One exact optional TagDiv context-home href has been independently
    # observed on other template inserts. Never accept arbitrary extra links.
    allowed=[cfg['links'],[(None,ORIGIN+'/')]+cfg['links']]
    if p.links not in allowed:
        raise AssertionError('BUYER_PATHS_CHANGED')
    if p.css!=[CSS] or p.js!=[JS]:
        raise AssertionError('BUYER_ENTRY_ASSETS_NOT_EXACT')
    if slug=='zavazeci-lodky' and ('nikoli nafukovací člun' not in html):
        raise AssertionError('BAITBOAT_AND_INFLATABLE_TRUST_DISTINCTION_MISSING')
    return {'category':slug,'exactFirstPartyCTA':len(cfg['links']),
            'themeContextHome':len(p.links)-len(cfg['links']),
            'singleResponsivePanel':True,'assetsEnqueued':True}

def get(url,limit):
    req=Request(url,method='GET',headers={
       'User-Agent':'NaRybyGearBuyerArchiveProof/1.0','Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,attempts=2,timeout=25) as resp:
        if resp.status!=200 or resp.geturl()!=url:
            raise AssertionError('EXACT_200_NONREDIRECT_REQUIRED')
        raw=resp.read(limit+1)
    if len(raw)>limit:raise AssertionError('FIRST_PARTY_RESPONSE_OVER_LIMIT')
    return raw

def live():
    pages=[]
    for slug in ROUTES:
        url=ROOT+slug+'/'
        pages.append(check(get(url,1800000).decode('utf-8','replace'),slug))
    asset_dir=Path(__file__).resolve().parents[1]/'assets'
    digests={}
    for name,url in [('nry-gear-archive-entry-v1.css',CSS),
                     ('nry-gear-archive-click-v1.js',JS)]:
        served=get(url,30000)
        approved=(asset_dir/name).read_bytes()
        if hashlib.sha256(served).digest()!=hashlib.sha256(approved).digest():
            raise AssertionError('LIVE_ASSET_SHA_MISMATCH')
        digests[name]=hashlib.sha256(served).hexdigest()
    return {'status':'GEAR_ARCHIVE_SIX_BUYER_ENTRIES_PUBLIC_VERIFIED',
      'pages':pages,'publishedPaths':6,'exactFirstPartyCTA':sum(x['exactFirstPartyCTA'] for x in pages),
      'servedAssetSha256':digests,'wpWrites':0,'merchantGETs':0,
      'syntheticClicks':0,'realClicks':'UNKNOWN','GA4Delivery':'UNKNOWN',
      'approvedOrPaidCommissions':'UNKNOWN'}

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('USE_VERIFY_ONLY')
    try:
        print(json.dumps(live(),ensure_ascii=False,sort_keys=True))
    except Exception as exc:
        # Never leak an unexpected URL, body, token or server traceback.
        allowed={'EXACT_ARCHIVE_REQUIRED','CATEGORY_H1_DRIFT',
             'CATEGORY_OWNED_BLOCK_NOT_EXACT','BUYER_CHOICES_NOT_AFTER_H1',
             'BUYER_PATHS_CHANGED','BUYER_ENTRY_ASSETS_NOT_EXACT',
             'BAITBOAT_AND_INFLATABLE_TRUST_DISTINCTION_MISSING',
             'EXACT_200_NONREDIRECT_REQUIRED','FIRST_PARTY_RESPONSE_OVER_LIMIT',
             'LIVE_ASSET_SHA_MISMATCH'}
        code=str(exc) if isinstance(exc,AssertionError) and str(exc) in allowed else 'READONLY_PROOF_UNAVAILABLE'
        print(json.dumps({'status':'GEAR_ARCHIVE_PUBLIC_NOT_VERIFIED',
                          'reason':code,'exceptionClass':type(exc).__name__}))
        sys.exit(1)
