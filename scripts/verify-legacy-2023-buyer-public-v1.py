#!/usr/bin/env python3
"""Independent GET-only proof of six visible 2023 guide decision cards; no clicks."""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ORIGIN='https://na-ryby.eu'
ROUTES={
 'nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare':(
   ('bedchair_guide','/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'),
   ('bedchair_5000','/nejlepsi-rybarska-lehatka-do-5000-kc/'),
   ('bedchair_weight','/nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne/')),
 'vysoce-hodnocena-rybarska-lehatka-podrobna-recenze-a-porovnani-popularnich-modelu':(
   ('bedchair_comparison','/nejlepsi-rybarska-lehatka-na-trhu-recenze-a-porovnani/'),
   ('bedchair_trip','/nejlepsi-rybarska-lehatka-pro-delsi-vypravy-top-vyber-a-srovnani/'),
   ('bedchair_5000','/nejlepsi-rybarska-lehatka-do-5000-kc/')),
 'nejlepsi-rybarske-pruty-s-navijakem-podle-zkusenych-rybaru':(
   ('starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'),
   ('rod_choice','/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'),
   ('reel_choice','/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/')),
 'nejlepsi-rybarske-hlasice-a-signalizatory':(
   ('alarm_choice','/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'),
   ('alarm_3000','/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'),
   ('alarm_troubleshoot','/proc-mi-signalizator-pipa-bez-zaberu-5-nejcastejsich-pricin-a-jak-je-vyresit/')),
 'recenze-nejlevnejsich-rybarskych-prutu-pro-zacatecniky-i-pokrocile':(
   ('beginner_fishing','/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'),
   ('starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'),
   ('carp_3000','/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/')),
 'detske-rybarske-pruty-s-nejlepsi-cenu-a-kvalitou':(
   ('junior_rod','/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/'),
   ('beginner_fishing','/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'),
   ('starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'))
}
MARKER='data-nry-legacy-2023="v1"'
ASSET='/wp-content/plugins/na-ryby-git-publisher/assets/'
CSS=ORIGIN+ASSET+'nry-gear-archive-entry-v1.css?ver=1.0.0'
JS=ORIGIN+ASSET+'nry-legacy-2023-buyer-click-v1.js?ver=1.0.0'

class Parsed(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.panels=0;self.slug=None;self.links=[];self.depth=0
        self.css=[];self.js=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='section' and a.get('data-nry-legacy-2023')=='v1':
            self.panels+=1;self.depth=1;self.slug=a.get('data-nry-legacy-slug')
        elif tag=='section' and self.depth:self.depth+=1
        if tag=='a' and self.depth:
            self.links.append((a.get('data-nry-legacy-guide'),a.get('href')))
        if tag=='link' and 'nry-gear-archive-entry-v1.css' in (a.get('href') or ''):
            self.css.append(a.get('href'))
        if tag=='script' and 'nry-legacy-2023-buyer-click-v1.js' in (a.get('src') or ''):
            self.js.append(a.get('src'))
    def handle_endtag(self,tag):
        if tag=='section' and self.depth:self.depth-=1

def validate(body,slug):
    p=Parsed();p.feed(body);p.close()
    expected=[(role,ORIGIN+href) for role,href in ROUTES[slug]]
    if body.count(MARKER)!=1 or p.panels!=1 or p.slug!=slug:
        raise AssertionError('EXACT_PANEL_REQUIRED')
    # Newspaper can prepend exactly one home context link in selected panels.
    if p.links not in (expected,[(None,ORIGIN+'/')]+expected):
        raise AssertionError('THREE_FIRST_PARTY_GUIDES_REQUIRED')
    if p.css!=[CSS] or p.js!=[JS]:
        raise AssertionError('EXPECTED_ASSETS_MISSING')
    if 'Modely, ceny a bonusy ve starším článku' not in body:
        raise AssertionError('HISTORICAL_OFFER_WARNING_MISSING')
    return {'slug':slug,'guides':3,'oneVisibleCard':True}

def get(url,cap):
    req=Request(url,method='GET',headers={
       'User-Agent':'NaRybyLegacy2023BuyerProof/1.0','Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,attempts=2,timeout=25) as response:
        if response.status!=200 or response.geturl()!=url:
            raise AssertionError('EXACT_200_NONREDIRECT_REQUIRED')
        raw=response.read(cap+1)
    if len(raw)>cap:raise AssertionError('PUBLIC_BODY_OVER_LIMIT')
    return raw

def live():
    pages=[]
    for slug in ROUTES:
        pages.append(validate(get(ORIGIN+'/'+slug+'/',1500000).decode('utf-8','replace'),slug))
    digest={}
    for name,url in [('nry-gear-archive-entry-v1.css',CSS),
                     ('nry-legacy-2023-buyer-click-v1.js',JS)]:
        served=get(url,30000)
        approved=(Path(__file__).resolve().parents[1]/'assets'/name).read_bytes()
        if hashlib.sha256(served).digest()!=hashlib.sha256(approved).digest():
            raise AssertionError('SERVING_OUTDATED_ASSET')
        digest[name]=hashlib.sha256(served).hexdigest()
    return {'status':'NRY_LEGACY_2023_SIX_BUYER_CARDS_PUBLIC_VERIFIED',
        'posts':6,'firstPartyGuides':18,'pages':pages,'servedAssetSha256':digest,
        'storedWordpressWrites':0,'merchantGETs':0,'syntheticClicks':0,
        'realClicks':'UNKNOWN','approvedOrPaidCommissions':'UNKNOWN'}

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('USE_VERIFY_ONLY')
    try:print(json.dumps(live(),ensure_ascii=False,sort_keys=True))
    except Exception as e:
        allowed={'EXACT_PANEL_REQUIRED','THREE_FIRST_PARTY_GUIDES_REQUIRED',
                 'EXPECTED_ASSETS_MISSING','HISTORICAL_OFFER_WARNING_MISSING',
                 'EXACT_200_NONREDIRECT_REQUIRED','PUBLIC_BODY_OVER_LIMIT',
                 'SERVING_OUTDATED_ASSET'}
        reason=str(e) if isinstance(e,AssertionError) and str(e) in allowed else 'READONLY_PROOF_UNAVAILABLE'
        print(json.dumps({'status':'NRY_LEGACY_2023_PUBLIC_NOT_VERIFIED',
                          'reason':reason,'exceptionClass':type(e).__name__}))
        sys.exit(1)
