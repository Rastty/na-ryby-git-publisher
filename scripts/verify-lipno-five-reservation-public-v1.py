#!/usr/bin/env python3
"""Read only anonymous GETs; never follow merchant links."""
from html.parser import HTMLParser
from urllib.request import Request,urlopen
from urllib.parse import urlparse
import sys,time

BASE="https://na-ryby.eu/"
URLS={
    "kohoutu":"https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F",
    "vyhlidka":"https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne",
    "jbx":"https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fjbx-resort-albatros",
}
ROUTES=[
("levne-ubytovani-u-lipna-pro-partu-kdy-vyjde-dobre-cena-na-osobu","parta",["vyhlidka","kohoutu"]),
("levne-ubytovani-u-lipna-na-vikend-co-jeste-dava-smysl","vikend",["kohoutu","vyhlidka"]),
("levna-chata-apartman-nebo-pohodlnejsi-pobyt-u-lipna-co-dava-rybari-vetsi-smysl","typ",["vyhlidka","jbx"]),
("na-cem-u-ubytovani-u-lipna-nesetrit-a-kde-naopak-klidne-ano","rozpocet",["kohoutu","vyhlidka"]),
("ubytovani-u-lipna-blizko-vody-pro-rybare-kdy-to-dava-smysl-a-kdy-ne","u_vody",["kohoutu","vyhlidka"]),
]
class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.panels=[];self.links=[];self.canon=[];self.h1=0;self.kayak=0;self.cj_old=0;self.cj_own=0;self.sonar=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=="section" and a.get("data-nry-lipno-reservation")=="v1": self.panels.append(a.get("data-nry-lipno-role"))
        if tag=="a" and "data-nry-lipno-offer" in a: self.links.append((a.get("data-nry-lipno-offer"),a.get("href"),a.get("rel")))
        if tag=="link" and a.get("rel")=="canonical": self.canon.append(a.get("href"))
        if tag=="h1": self.h1+=1
        if tag=="section" and a.get("data-nry-kayak-buyer-trust")=="119602-v1": self.kayak+=1
        if tag=="a":
            href=a.get("href","") or ""
            self.cj_old+=int("click-100927011-12347575" in href)
            self.cj_own+=int("click-8287231-" in href)
            self.sonar+=int(href==BASE+"echolot-na-kajak-a-belly-boat-jak-vybrat-kompaktni-sonar/")
def get(slug):
    url=BASE+slug+"/"
    req=Request(url+"?nry_release_readback=20261128",headers={"User-Agent":"NaRybyPublicProof/1.0","Cache-Control":"no-cache"})
    with urlopen(req,timeout=25) as resp:
        assert resp.status==200 and urlparse(resp.geturl()).hostname=="na-ryby.eu"
        html=resp.read(3_000_000).decode("utf-8")
    page=Page();page.feed(html)
    assert page.canon==[url],("canonical",page.canon)
    return page,html
def main():
    ok=0
    for slug,role,keys in ROUTES:
        try:
            p,_=get(slug)
            assert p.panels==[role],("panel",p.panels)
            assert p.links==[(k,URLS[k],"sponsored nofollow noopener") for k in keys],("offer roles",[x[0] for x in p.links])
            print("PUBLIC_LIPNO_OK",slug,"2/2");ok+=1
        except Exception as e: print("PUBLIC_LIPNO_FAIL",slug,type(e).__name__,str(e)[:160])
    print("LIPNO_RESERVATION_PUBLIC_VERIFIED",f"{ok}/5",f"{2*ok}/10")
    try:
        p,h=get("rybarsky-kajak-pro-koho-dava-smysl-a-jak-vybrat")
        print("KAYAK_PRIOR_RELEASE", "VERIFIED" if p.kayak==1 and p.h1==1 and "Poznámka k obrázkům:" not in h and p.sonar>=1 else "NOT_VERIFIED",
              "marker",p.kayak,"h1",p.h1,"editor_note","Poznámka k obrázkům:" in h,"old_cj",p.cj_old,"own_cj",p.cj_own)
    except Exception as e: print("KAYAK_PRIOR_RELEASE_UNKNOWN",type(e).__name__)
    return 0 if ok==5 else 1
if __name__=="__main__":sys.exit(main())
