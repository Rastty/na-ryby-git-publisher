#!/usr/bin/env python3
"""GET-only independent verification of the live fishing-line buyer utility."""
import json
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

URL='https://na-ryby.eu/vlascova-bible-kompletni-pruvodce-vyberem-vlasce-a-snury/'
PAGE_ID=115678
PARTNER='https://www.jdoqocy.com/click-8287231-12347575?url='
WINDING='https://na-ryby.eu/jak-spravne-podmotat-navijak-setrete-snuru-a-zvyste-dosah-nahozu/'


class Probe(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.styles=[]
        self.markers=0
        self.ids=set()
        self.hrefs=[]
        self.prices=0

    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='link' and a.get('rel')=='stylesheet':
            self.styles.append(a.get('href',''))
        if a.get('data-nry-line-fast-choice')=='v1':
            self.markers+=1
        if 'id' in a:
            self.ids.add(a['id'])
        if tag=='a':
            self.hrefs.append(a.get('href'))
        if tag=='div' and 'product-price-tag' in (a.get('class') or '').split():
            self.prices+=1


def fetch(url,limit):
    req=Request(url,headers={'User-Agent':'NaRybyLineChoiceReadOnly/1.0',
                             'Cache-Control':'no-cache'})
    with guarded_urlopen(req,timeout=25) as response:
        if response.status!=200:
            raise RuntimeError('BLOCKED: NON_200')
        body=response.read(limit+1)
    if len(body)>limit:
        raise RuntimeError('BLOCKED: HTML_OVERSIZE')
    return body.decode('utf-8')


def verify(html,original):
    p=Probe()
    p.feed(html)
    if p.canonicals!=[URL]:
        raise RuntimeError('BLOCKED: PAGE_CANONICAL_DRIFT')
    if p.markers!=1 or not {'nry-line-materials','nry-line-products'}.issubset(p.ids):
        raise RuntimeError('NOT_DEPLOYED: BUYER_CHOOSER_MISSING')
    if not any('nry-line-fast-choice-v1.css' in s for s in p.styles):
        raise RuntimeError('NOT_DEPLOYED: STYLESHEET_MISSING')
    if p.hrefs.count(WINDING)!=1 or p.hrefs.count('#nry-line-materials')!=1 \
            or p.hrefs.count('#nry-line-products')!=1:
        raise RuntimeError('NOT_DEPLOYED: PRACTICAL_PATHS_MISSING')
    affiliate=[href for href in p.hrefs if href and href.startswith(PARTNER)]
    src=Probe()
    src.feed(original)
    original_affiliate=[href for href in src.hrefs if href and href.startswith(PARTNER)]
    if len(affiliate)!=4 or affiliate!=original_affiliate:
        raise RuntimeError('BLOCKED: AFFILIATE_LINKS_CHANGED')
    if p.prices!=3 or html.count('Cenu ověřte v obchodě')!=3:
        raise RuntimeError('NOT_DEPLOYED: UNVERIFIED_PRICES_VISIBLE')
    if html.count('OVĚŘIT VARIANTU A DOSTUPNOST')!=3:
        raise RuntimeError('NOT_DEPLOYED: UNSUPPORTED_BUY_CLAIMS')
    for wrong in ('<td>Nulová &#8211; okamžitý kontakt</td>',
                  '<td>3-5 sezón (velmi trvanlivá)</td>',
                  '<h2>2. Petrovy tipy na top vlasce 2026</h2>'):
        if wrong in html:
            raise RuntimeError('NOT_DEPLOYED: UNCORRECTED_CLAIM '+wrong[:45])
    for right in ('Životnost záleží na oděru',
                  'Velmi malá průtažnost',
                  'Jaký vlasec nebo šňůru zvolit pro svůj způsob lovu?'):
        if right not in html:
            raise RuntimeError('NOT_DEPLOYED: MISSING_NEW_GUIDANCE '+right)
    if 'data-nry-line-fast-choice="v1"' in original:
        raise RuntimeError('BLOCKED: ARTICLE_STORED_WRITE_DETECTED')
    return {'state':'PUBLIC_LINE_BIBLE_VERIFIED','url':URL,'affiliate_links_preserved':4,
            'unsupported_prices_removed':3,'buyer_scenarios':4,
            'onpage_jumps':2,'new_internal_guides':1,'wordpress_stored_writes':0}


def main():
    public=fetch(URL+'?nry_line_public_probe=20261117',1_500_000)
    rest=fetch('https://na-ryby.eu/wp-json/wp/v2/pages/'+str(PAGE_ID)+'?_fields=content',1_500_000)
    original=json.loads(rest)['content']['rendered']
    print(json.dumps(verify(public,original),ensure_ascii=False,sort_keys=True))


if __name__=='__main__':
    main()
