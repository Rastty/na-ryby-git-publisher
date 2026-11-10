#!/usr/bin/env python3
"""Independent public GET-only verifier of carp pillar decision-before-shop edit.

Never follows merchant links, writes WordPress or sends analytics.
"""
from html.parser import HTMLParser
from urllib.request import Request, urlopen

URL = ("https://na-ryby.eu/"
       "jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/")
SOURCE_MARKER = 'data-nry-carp-pillar-trust="118541-20261109-v1"'
MARKER = 'data-nry-carp-choice-before-shop="v1"'
OLD = "Z obou mých ověřených obchodů mi tady aktuálně dává praktičtější smysl"
SAFE = ("Nejdřív vyberte typ prutu, potom obchod.")
CAUTION = ("Odkaz níže vede do kategorie obchodu, není důkazem dnešní "
           "nejvýhodnější ceny ani dostupnosti.")

class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.text = []
        self.hidden = 0

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "link" and "canonical" in (a.get("rel") or ""):
            self.canonicals.append(a.get("href"))
        if tag in ("script", "style", "template", "noscript"):
            self.hidden += 1

    def handle_endtag(self, tag):
        if tag in ("script", "style", "template", "noscript"):
            self.hidden = max(0, self.hidden - 1)

    def handle_data(self, data):
        if not self.hidden:
            self.text.append(data)

def verify(html):
    assert html.count(SOURCE_MARKER) == 1, "SOURCE_TRUST_MARKER_DRIFT"
    assert html.count(MARKER) == 1, "BUYER_CHOICE_MARKER_NOT_ONCE"
    parsed = Page()
    parsed.feed(html)
    assert parsed.canonicals == [URL], "CANONICAL_NOT_EXACT"
    visible = " ".join(" ".join(parsed.text).split())
    assert OLD not in visible, "UNVERIFIED_SHOP_COMPARISON_STILL_VISIBLE"
    assert SAFE in visible, "DECISION_FIRST_MISSING"
    assert CAUTION in visible, "OFFER_FRESHNESS_CAUTION_MISSING"
    pos = html.index(MARKER)
    assert html.find('www.chytapust.cz',pos,pos+3500)>=0, "ORIGINAL_STORE_LINK_NOT_NEAR_ADVICE"
    assert html.find('Prověřit nabídku',pos,pos+3500)>=0, "ORIGINAL_BUYER_CTA_NOT_NEAR_ADVICE"
    assert "Komu tenhle článek pomůže" in html[pos:pos+3500], "FIRST_SCREEN_SCOPE_DRIFT"
    return {"state":"PUBLIC_VERIFIED","safe_early_decision":True,
            "merchant_requests":0,"wordpress_writes":0}

if __name__ == "__main__":
    req=Request(URL,headers={"User-Agent":"NaRyby-CarpPillarChoice-Readback/1.0",
                             "Cache-Control":"no-cache"})
    with urlopen(req,timeout=22) as response:
        assert response.status==200 and response.geturl()==URL, "PUBLIC_STATUS_OR_REDIRECT"
        page=response.read(1300000).decode("utf-8","replace")
    print(verify(page))
