#!/usr/bin/env python3
"""Independent GET-only public budget guide owner-first handoff readback."""
from html.parser import HTMLParser
from urllib.request import Request, urlopen
import sys, time

URL=('https://na-ryby.eu/'
     'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/')
TARGET=('https://na-ryby.eu/'
        'jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/')
OLD='<!-- nry-sonar-budget-trust-118455-v1 -->'
MARKER='data-nry-budget-owner-first="118455-20261110-v1"'
COPY='Máte už echolot a nevíte, zda potřebujete nový?'
CAUTION='kompatibilní sondy a ceny celého setu'
INTRO='Echolot do 15 000 Kč: jak vybrat celou funkční sestavu'

class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
    def handle_starttag(self,tag,attrs):
        x=dict(attrs)
        if tag=='link' and 'canonical' in (x.get('rel') or ''):
            self.canonicals.append(x.get('href'))

def verify(html):
    assert html.count(OLD)==1,'OLDER_SAFE_EDIT_NOT_PRESENT'
    assert html.count(MARKER)==1,'OWNER_FIRST_MARKER_NOT_ONCE'
    doc=Page();doc.feed(html)
    assert doc.canonicals==[URL],'CANONICAL_DRIFT'
    start=html.index('<aside '+MARKER)
    end=html.find('</aside>',start)
    assert end>start and 0<end-start<2500,'ONE_BOUNDED_ADVICE_PANEL'
    section=html[start:end]
    assert section.count('href="'+TARGET+'"')==1,'EXACT_EXISTING_GUIDE_LINK_MISSING'
    # Verify the buyer-visible content, not fragile raw HTML bytes that
    # WordPress may split with inline markup or encode as entities.
    from html import unescape
    import re
    visible=' '.join(unescape(re.sub(r'<[^>]+>',' ',section)).split())
    assert COPY in visible,'DECISION_HEADLINE_MISSING'
    assert CAUTION in visible,'DECISION_CAUTION_MISSING'
    # Clear visual meaning: owner-first CTA must precede the first shopping CTA.
    shop=html.find('Zjistit cenu',start)
    assert shop<0 or end<shop,'ADVICE_AFTER_SHOPPING_CTA'
    assert INTRO in html,'BUYER_ANSWER_MISSING'
    assert 'go.dognet.com' in html,'EXISTING_MONETIZATION_ROUTE_MISSING'
    return {'state':'PUBLIC_VERIFIED','owner_first_handoff':1,
            'new_merchant_requests':0,'wordpress_writes':0}

if __name__=='__main__':
    for attempt in range(3):
        try:
            req=Request(URL,headers={'User-Agent':'NRY-BudgetOwner-PublicProof/1.0',
                                     'Cache-Control':'no-cache, no-store'})
            with urlopen(req,timeout=25) as response:
                assert response.status==200 and response.geturl()==URL
                data=response.read(1200000).decode('utf8','replace')
            print(verify(data));sys.exit(0)
        except Exception as exc:
            # Emit only a bounded fixed assertion code, never page HTML or
            # visitor / merchant data. Fail closed for every unknown error.
            reason=str(exc) if isinstance(exc,AssertionError) else type(exc).__name__
            print('PUBLIC_RETRY',attempt+1,reason[:80],flush=True)
            if attempt<2:time.sleep(6)
    sys.exit('BLOCKED: owner handoff not independently visible on live site')
