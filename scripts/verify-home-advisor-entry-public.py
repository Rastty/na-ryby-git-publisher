#!/usr/bin/env python3
"""Independent public homepage proof of source-bound advisor entry.

No WordPress writes, visits to merchant URLs or browser analytics events.
"""
from html.parser import HTMLParser
from urllib.request import Request,urlopen
from pathlib import Path
import sys

HOME="https://na-ryby.eu/"
ADVISOR="https://na-ryby.eu/poradce-vybavy/"
OLD_MARKER='data-nry-home-trust="source-bound-118641-20261109-v1"'
MARKER='data-nry-home-advisor-entry="v1"'
COPY="Spustit průvodce výběrem výbavy"
CAUTION="Konkrétní produktové nabídky zobrazujeme jen po ověření jejich aktuálnosti."
HERO="Průvodci výběrem vybavení"
CATEGORY="Vybavení podle hlavních kategorií"
ROUTES_MARKER='data-nry-home-problem-shortcuts="v1"'
ROUTES={
    "carp":"https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/",
    "sonar_budget":"https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/",
    "sonar_owner":"https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/",
}
ROUTE_COPY=(
    "Vybírám kaprový prut podle vody a stylu lovu",
    "Hledám echolot do 15 000 Kč",
    "Echolot už mám: jak ho správně nastavit",
)

class Tags(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or ''):
            self.canonicals.append(a.get('href'))

def verify(html):
    assert html.count(OLD_MARKER)==1, "SOURCE_TRUST_MISSING_OR_DUPLICATE"
    assert html.count(MARKER)==1, "BUYER_ENTRY_MISSING_OR_DUPLICATE"
    h=Tags();h.feed(html)
    assert h.canonicals==[HOME], "CANONICAL_DRIFT"
    m=html.find(MARKER)
    assert html.find(HERO)>=0 and html.find(HERO)<m, "MISSING_HERO_ORDER"
    assert html.find(CATEGORY)>m, "BUYER_ENTRY_NOT_BEFORE_CATEGORIES"
    local=html[max(0,m-80):m+900]
    assert 'href="'+ADVISOR+'"' in local, "ORIGINAL_ADVISOR_ROUTE_NOT_VISIBLE"
    assert COPY in local and CAUTION in local, "ADVICE_COPY_MISSING"
    assert html.count(ROUTES_MARKER)==1, "PROBLEM_SHORTCUTS_NOT_UNIQUE"
    start=html.find('<nav '+ROUTES_MARKER)
    end=html.find('</nav>',start)
    assert start>m and end>start and end-start<2500, "PROBLEM_ROUTES_NOT_BOUNDED"
    assert end<html.find(CATEGORY), "PROBLEM_ROUTES_AFTER_CATEGORY_GRID"
    block=html[start:end]
    import re
    hrefs=re.findall(r'href="([^"]+)"',block)
    assert hrefs==list(ROUTES.values()), "PROBLEM_SHORTCUT_DESTINATIONS_DRIFT"
    assert all(copy in block for copy in ROUTE_COPY), "PROBLEM_SHORTCUT_COPY_DRIFT"
    return {"state":"PUBLIC_VERIFIED","homepage_entry_count":1,
            "problem_shortcuts_count":3,
            "merchant_requests":0,"wordpress_writes":0,"analytics_events":0}

if __name__=="__main__":
    import time
    for attempt in range(3):
        try:
            req=Request(HOME,headers={"User-Agent":"NaRybyHomeAdvisorEntryReadOnly/1.0",
                                      "Cache-Control":"no-cache, no-store"})
            with urlopen(req,timeout=24) as response:
                assert response.status==200 and response.geturl()==HOME, "WRONG_LIVE_URL"
                html=response.read(1300000).decode('utf-8','replace')
            print(verify(html))
            break
        except Exception as error:
            print("PUBLIC_RETRY",attempt+1,type(error).__name__,flush=True)
            if attempt==2:
                raise SystemExit("BLOCKED: homepage advisor entry not independently verified")
            time.sleep(5)
