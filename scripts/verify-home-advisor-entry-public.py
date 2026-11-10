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
    return {"state":"PUBLIC_VERIFIED","homepage_entry_count":1,
            "merchant_requests":0,"wordpress_writes":0,"analytics_events":0}

if __name__=="__main__":
    req=Request(HOME,headers={"User-Agent":"NaRybyHomeAdvisorEntryReadOnly/1.0",
                              "Cache-Control":"no-cache"})
    with urlopen(req,timeout=24) as response:
        assert response.status==200 and response.geturl()==HOME, "WRONG_LIVE_URL"
        html=response.read(1300000).decode('utf-8','replace')
    print(verify(html))
