#!/usr/bin/env python3
"""One actual source preflight and independent public proof, no partner GET."""
import sys
import json
import html
import subprocess
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import Request
from tempfile import TemporaryDirectory
from readonly_urlopen_retry import guarded_urlopen

BASE='https://na-ryby.eu/'
ROUTES={
 'rybarske-dobrodruzstvi-s-ubytovanim-u-vody-vyber-nejlepsich-mist':('2023-09-23','Rybářské dobrodružství s ubytováním u vody: Výběr nejlepších míst','destinations-v1',('lipno','cyklo'),('Rybník Šumava','Rybník Morava','Jezero Zlatá voda','Potok Lesní','štikozubec')),
 'naplnete-sve-rybarske-sny-v-soukromych-revirech-s-ubytovanim':('2024-08-12','Naplněte své rybářské sny v soukromých revírech s ubytováním','private-booking-v1',('srub','more'),('Naše revíry jsou bohaté','Lorem ipsum dolor sit amet','našeho online rezervačního systému','V našich ubytovacích zařízeních')),
}
URLS={
 'lipno':'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F',
 'cyklo':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata',
 'srub':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub',
 'more':'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness',
}
def get(url):
    with guarded_urlopen(Request(url,headers={'User-Agent':'NaRybyEditorialProof/1.0','Cache-Control':'no-cache'}),timeout=25) as r:
        return r.read(3000000).decode('utf-8')
def source():
    for slug,(date,title,role,offers,bad) in ROUTES.items():
        data=json.loads(get(BASE+'wp-json/wp/v2/posts?slug='+slug+'&_fields=id,slug,date,title,link,content,status'))
        assert len(data)==1
        p=data[0]
        assert p['slug']==slug and p['status']=='publish' and p['date'][:10]==date and p['link']==BASE+slug+'/' and html.unescape(p['title']['rendered'])==title
        with TemporaryDirectory() as temp:
            path=Path(temp)/'body.html'
            path.write_text(p['content']['rendered'],encoding='utf-8')
            code='define("ABSPATH",__DIR__);define("NRY_TWO_STAYS_PREFLIGHT",true);require "six-real-stay-offers-v1.php";require "two-stays-trust-booking-v1.php";$slug=$argv[1];$r=["slug"=>$slug]+NRY_Two_Stays_Trust_20261128_V1::routes()[$slug];$old=file_get_contents($argv[2]);$new=NRY_Two_Stays_Trust_20261128_V1::transform($old,$r);if($new===$old||substr_count($new,NRY_Two_Stays_Trust_20261128_V1::MARKER)!==1){fwrite(STDERR,"SOURCE_TRANSFORM_GUARD_BLOCKED");exit(1);}'
            subprocess.run(['php','-r',code,slug,str(path)],check=True)
        print('TWO_STAYS_SOURCE_READY',p['id'],slug,flush=True)
class P(HTMLParser):
    def __init__(self):
        super().__init__();self.mark=[];self.h1=0;self.canon=[];self.links=[]
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and 'data-nry-stay-reader-guide' in a:self.mark.append(a['data-nry-stay-reader-guide'])
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-real-stay-offer' in a:self.links.append((a['data-nry-real-stay-offer'],a.get('href'),a.get('rel')))
def public():
    for slug,(date,title,role,offers,bad) in ROUTES.items():
        url=BASE+slug+'/'
        h=get(url+'?nry_release_readback=20261128')
        p=P();p.feed(h)
        assert p.canon==[url] and p.mark==[role] and p.h1==1,(slug,p.mark,p.h1)
        assert p.links==[(o,URLS[o],'sponsored nofollow noopener') for o in offers],(slug,len(p.links))
        for old in bad:assert old.lower() not in h.lower(),(slug,old)
        print('TWO_STAYS_PUBLIC_OK',slug,'2/2',flush=True)
    print('TWO_STAYS_PUBLIC_VERIFIED 2/2 4/4')
if __name__=='__main__':
    assert len(sys.argv)==2 and sys.argv[1] in ('--source','--public')
    source() if sys.argv[1]=='--source' else public()
