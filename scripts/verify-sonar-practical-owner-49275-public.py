#!/usr/bin/env python3
"""Strict independent GET-only public verification of five sonar corrections."""
import html
import re
import sys
import time
from urllib.request import Request, urlopen

URL='https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'
MARKER='data-nry-sonar-owner-guide="49275-20261110-v1"'
OLD=(
    'pro plavce, kteří potřebují mít přehled o hladině vody a stavu dna',
    'pomůže vám přesně nalézt potenciální rybí loviště',
    'Pravidelná kalibrace vašeho echolotu nebo sonaru pomáhá',
    'před každým rybolovem či v průběhu tůry',
    'pro plavce, kteří chtějí mít kompletní přehled o své',
)
SAFE=(
    'pro rybáře, kteří potřebují sledovat hloubku a strukturu dna',
    'sám o sobě nezaručí nalezení ryb',
    'Před vyplutím zkontrolujte uchycení a úhel sondy',
    'Kalibraci provádějte pouze tehdy',
    'nenahrazuje bezpečnou navigaci',
    'Než vyměníte echolot: tři kontroly u vody',
)
PILLAR='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'
BUDGET='https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'

def verify(content):
    assert content.count(MARKER)==1,'MARKER_MISSING_OR_DUPLICATED'
    assert '<link rel="canonical" href="'+URL+'"' in content or (
        '<link href="'+URL+'" rel="canonical"' in content), 'CANONICAL_DRIFT'
    nonexecutable=re.sub(r'<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>',
                         ' ',content,flags=re.I|re.S)
    visible=' '.join(html.unescape(re.sub(r'<[^>]*>',' ',nonexecutable)).split())
    for phrase in OLD:
        assert phrase not in visible, 'OLD_UNSAFE_ASSERTION_STILL_VISIBLE'
    for phrase in SAFE:
        assert phrase in visible, 'SAFE_GUIDANCE_MISSING'
    start=content.index('<section '+MARKER)
    end=content.find('</section>',start)
    assert start>=0 and end>start and end-start<5000, 'GUIDANCE_SECTION_NOT_BOUNDED'
    panel=content[start:end]
    for link in (PILLAR,BUDGET):
        assert panel.count('href="'+link+'"')==1, 'INTERNAL_BUYER_HANDOFF_MISSING'
    assert 'Prověřit nabídku' not in panel, 'NO_NEW_AFFILIATE_SHOP_CLAIMS'
    return {'state':'PUBLIC_VERIFIED','corrections':5,'links_to_existing_guides':2,
            'wordpress_writes':0,'merchant_requests':0}

if __name__=='__main__':
    for attempt in range(3):
        try:
            req=Request(URL,headers={'User-Agent':'NaRybySonarOwnerPublicReadback/1.0',
                                     'Cache-Control':'no-cache, no-store'})
            with urlopen(req,timeout=25) as response:
                assert response.status==200 and response.geturl()==URL,'EXACT_200_REQUIRED'
                source=response.read(1000000).decode('utf-8','replace')
            print(verify(source));sys.exit(0)
        except Exception as exc:
            print('PUBLIC_READBACK_RETRY',attempt+1,type(exc).__name__,flush=True)
            if attempt<2:time.sleep(6)
    sys.exit('BLOCKED: independent public guide correction not verified')
