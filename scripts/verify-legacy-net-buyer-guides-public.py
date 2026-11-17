#!/usr/bin/env python3
"""Independent GET-only audit of two newly connected legacy net guides."""
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

GUIDE='https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-podberak-na-kapry/'
COHORTS={
    'jak-vybrat-ten-nejlepsi-rybarsky-podberak-na-kapry': {
        'old':['materiál sítě ze skelných vláken','Existují dva typy podběráků',
               'trny, které pomohou udržovat rybu'],
        'new':['Pro kapry je podstatná bezpečná manipulace',
               'Plovák na násadě je doplněk',
               'Ostré výstupky by mohly rybu poranit.']},
    'jak-si-vybrat-spravny-rybarsky-podberak-90x90-pro-vase-potreby': {
        'old':['délce vaší přívlačové nebo muškařské šňůry',
               'vyráběna z nylonu nebo síťoviny',
               'ideální pro lov menších ryb'],
        'new':['délka šňůry s ní nesouvisí',
               'Nylon může být sám materiálem síťoviny',
               'Rozměr 90 × 90 cm se používá i pro větší ryby']},
}


class Probe(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.styles=[]
        self.notice=0
        self.in_notice=False
        self.notice_links=[]
        self.affiliate_links=0

    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='link' and a.get('rel')=='stylesheet':
            self.styles.append(a.get('href',''))
        if tag=='aside' and a.get('data-nry-net-buyer-path')=='v1':
            self.in_notice=True
            self.notice+=1
        if tag=='a':
            if self.in_notice:
                self.notice_links.append(a.get('href'))
            if a.get('href')=='https://na-ryby.eu/podberaky':
                self.affiliate_links+=1

    def handle_endtag(self,tag):
        if tag=='aside' and self.in_notice:
            self.in_notice=False


def verify(slug,source):
    expected=COHORTS[slug]
    url='https://na-ryby.eu/'+slug+'/'
    p=Probe()
    p.feed(source)
    if p.canonicals!=[url]:
        raise RuntimeError('BLOCKED: CANONICAL_CHANGED '+slug)
    if p.notice!=1 or p.notice_links!=[GUIDE]:
        raise RuntimeError('NOT_DEPLOYED: PRACTICAL_BUYER_PATH_MISSING '+slug)
    if not any('nry-legacy-net-buyer-v1.css' in s for s in p.styles):
        raise RuntimeError('NOT_DEPLOYED: STYLING_MISSING '+slug)
    if p.affiliate_links!=3:
        raise RuntimeError('BLOCKED: ORIGINAL_AFFILIATE_LINKS_DRIFT '+slug)
    for token in expected['old']:
        if token in source:
            raise RuntimeError('NOT_DEPLOYED: WRONG_CLAIM '+token)
    for token in expected['new']:
        if token not in source:
            raise RuntimeError('NOT_DEPLOYED: CORRECTION_MISSING '+token)
    return {'url':url,'state':'PUBLIC_NET_BUYER_GUIDE_CORRECTED',
            'correction_samples_verified':len(expected['new']),
            'new_internal_buyer_path':1,'affiliate_links_preserved':p.affiliate_links}


def fetch(slug):
    url='https://na-ryby.eu/'+slug+'/'
    req=Request(url+'?nry_net_buyer_probe=20261117',headers={
        'User-Agent':'NaRybyNetBuyerReadOnly/1.0','Cache-Control':'no-cache'})
    with guarded_urlopen(req,timeout=25) as response:
        if response.status!=200 or response.geturl().split('?')[0]!=url:
            raise RuntimeError('BLOCKED: UNEXPECTED_RESPONSE '+slug)
        body=response.read(1_000_001)
    if len(body)>1_000_000:
        raise RuntimeError('BLOCKED: TOO_MUCH_HTML '+slug)
    return body.decode('utf-8')


if __name__=='__main__':
    results=[verify(slug,fetch(slug)) for slug in COHORTS]
    print(json.dumps({'state':'PUBLIC_TWO_NET_GUIDES_VERIFIED',
                      'results':results,'wordpress_stored_writes':0},
                     ensure_ascii=False,sort_keys=True))
