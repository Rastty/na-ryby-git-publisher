#!/usr/bin/env python3
"""Public GET-only sonar seller-neutral disclaimer proof. No tracking URLs."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen
URL='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'
SOURCE='data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
PRACTICAL='data-nry-sonar-owned-practical="v1"'
MARKER='data-nry-sonar-merchant-neutral="v1"'
OLD='Tady aktuálně dává smysl širší a přehlednější nabídka z Chyť a pusť.'
NEW='Odkaz do kategorie je možností prohlédnout si modely, nikoli důkazem'
CAUTION='Před nákupem porovnejte přesnou variantu, sondu, cenu celé sestavy a dostupnost.'
class Canonical(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links=[]
    def handle_starttag(self,tag,attrs):
        d=dict(attrs)
        if tag=='link' and 'canonical' in (d.get('rel') or ''):
            self.links.append(d.get('href'))
def verify(html,after):
    assert len(html)>15000,'PAGE_UNAVAILABLE'
    doc=Canonical();doc.feed(html)
    assert doc.links==[URL],'WRONG_CANONICAL'
    assert html.count(SOURCE)==1,'SOURCE_MARKER_MISSING'
    assert html.count(PRACTICAL)==1,'PREEXISTING_PRACTICAL_GUIDE_DRIFT'
    assert 'Prověřit nabídku' in html and 'www.chytapust.cz' in html, 'EXISTING_MERCHANT_CTA_MISSING'
    if after:
        assert html.count(MARKER)==1,'SELLER_NEUTRAL_NOT_VISIBLE_ONCE'
        assert html.count(OLD)==0,'UNVERIFIED_MERCHANT_WINNER_STILL_VISIBLE'
        assert NEW in html and CAUTION in html,'SAFE_BUYER_COPY_MISSING'
        assert html.find(MARKER)<html.find('Prověřit nabídku'),'CAUTION_AFTER_MERCHANT_CTA'
    else:
        assert html.count(MARKER)==0,'ALREADY_PUBLISHED_OR_STALE_PR_PREVIEW'
        assert html.count(OLD)==1,'MERCHANT_WINNER_SOURCE_ALREADY_CHANGED'
    return {'state':'PUBLIC_VERIFIED' if after else 'PUBLIC_PREFLIGHT_VERIFIED',
        'post_id':118553,'merchant_neutral_claims':1 if after else 0,
        'unchanged_merchant_link_presence':True,'wordpress_writes':0,
        'synthetic_clicks':0}
if __name__=='__main__':
    mode=sys.argv[1] if len(sys.argv)>1 else '--verify'
    assert mode in ('--verify','--preflight')
    for attempt in range(3):
        try:
            req=Request(URL,method='GET',headers={
                'User-Agent':'NaRyby-SonarMerchantNeutral-ReadOnly/1.0',
                'Cache-Control':'no-cache, no-store'})
            with guarded_urlopen(req,timeout=25) as response:
                assert response.status==200 and response.geturl()==URL
                html=response.read(1200001).decode('utf8','replace')
            assert len(html)<1200000
            print(verify(html,mode=='--verify'))
            sys.exit(0)
        except Exception as ex:
            print('PUBLIC_RETRY',attempt+1,
                  str(ex)[:80] if isinstance(ex,AssertionError) else type(ex).__name__,flush=True)
    sys.exit('BLOCKED: seller-neutral claim not verified in public HTML')
