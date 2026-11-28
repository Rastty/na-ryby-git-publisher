#!/usr/bin/env python3
"""One source and one anonymous live proof, no partner requests."""
import sys,json,html,subprocess
from pathlib import Path
from tempfile import TemporaryDirectory
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen
BASE='https://na-ryby.eu/'
SLUG='jake-rybarske-lehatko-na-zimu-prakticky-vyber'
URL=BASE+SLUG+'/'
PHP='define("ABSPATH",__DIR__);define("NRY_WINTER_SOURCE_PREFLIGHT",true);require "winter-sleep-buyer-v1.php";'
def get(url):
    with guarded_urlopen(Request(url,headers={'User-Agent':'NaRybyEditorialProof/1.0','Cache-Control':'no-cache'}),timeout=25) as r:
        return r.read(3000000).decode('utf-8')
def source():
    data=json.loads(get(BASE+'wp-json/wp/v2/posts?slug='+SLUG+'&_fields=id,slug,date,modified_gmt,title,link,content,status'))
    assert len(data)==1
    p=data[0]
    assert p['slug']==SLUG and p['status']=='publish' and p['date'][:10]=='2026-03-24' and p['link']==URL and html.unescape(p['title']['rendered'])=='Jaké rybářské lehátko na zimu | Praktický výběr'
    print('WINTER_SOURCE_ID',p['id'],p['modified_gmt'],flush=True)
    with TemporaryDirectory() as temp:
        path=Path(temp)/'body.html';path.write_text(p['content']['rendered'],encoding='utf-8')
        code=PHP+'$old=file_get_contents($argv[1]);$new=NRY_Winter_Sleep_Buyer_V1::transform($old);if($new===$old||substr_count($new,NRY_Winter_Sleep_Buyer_V1::MARKER)!==1){fwrite(STDERR,"WINTER_SOURCE_TRANSFORM_BLOCKED");exit(1);}'
        subprocess.run(['php','-r',code,str(path)],check=True)
    print('WINTER_SOURCE_READY',p['id'])
class P(HTMLParser):
    def __init__(self):super().__init__();self.mark=0;self.h1=0;self.canon=[];self.paths=[]
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and a.get('data-nry-winter-sleep-guide')=='v1':self.mark+=1
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-winter-path' in a:self.paths.append((a['data-nry-winter-path'],a.get('href')))
def public():
    h=get(URL+'?nry_release_readback=20261128')
    p=P();p.feed(h)
    assert p.canon==[URL] and p.mark==1 and p.h1==1,(p.mark,p.h1)
    assert p.paths==[('fit',BASE+'rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'),('shelter',BASE+'jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'),('budget',BASE+'nejlepsi-rybarske-lehatka-do-5000-kc/')]
    assert 'ISO 23537-1:2022' in h and '5 300 Kč' in h and '11 300 Kč' in h
    assert 'Tady vidím jako bezpečný základ' not in h and 'Na lehkou zimu nebo noci kolem nuly ještě ano' not in h
    print('WINTER_SLEEP_BUYER_PUBLIC_VERIFIED 1/1 three first-party paths, single H1, source-backed budget and safety')
if __name__=='__main__':
    assert len(sys.argv)==2 and sys.argv[1] in ('--source','--public')
    source() if sys.argv[1]=='--source' else public()
