#!/usr/bin/env python3
"""Source and exact public proof of two scoped boat purchase guides."""
import sys,json,subprocess
from pathlib import Path
from tempfile import TemporaryDirectory
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen
BASE='https://na-ryby.eu/'
PHP='define("ABSPATH",__DIR__);require "boat-load-check-119598-v1.php";'
ROUTES=json.loads(subprocess.check_output(['php','-r',PHP+'echo json_encode(NRY_Boat_System_Buyer_V1::routes());']))
CJ='click-100927011-12347575'
class P(HTMLParser):
    def __init__(self):super().__init__();self.mark=[];self.h1=0;self.canon=[];self.paths=[];self.cj=[];self.img=[]
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and 'data-nry-boat-system-guide' in a:self.mark.append(a['data-nry-boat-system-guide'])
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-boat-system-path' in a:self.paths.append((a['data-nry-boat-system-path'],a.get('href')))
        if t=='a' and CJ in (a.get('href') or ''):self.cj.append(a['href'])
        if t=='img':self.img.append(a.get('src'))
def get(url):
    with guarded_urlopen(Request(url,headers={'User-Agent':'NaRybyEditorialProof/1.0','Cache-Control':'no-cache'}),timeout=25) as r:
        return r.read(3000000).decode('utf-8')
def inspect(mode):
    for id,r in ROUTES.items():
        url=BASE+r['slug']+'/'
        p=json.loads(get(BASE+'wp-json/wp/v2/posts/'+id+'?_fields=id,slug,status,modified_gmt,link,content'))
        assert p['id']==int(id) and p['slug']==r['slug'] and p['status']=='publish' and p['modified_gmt'].replace('T',' ')==r['modified'] and p['link']==url
        h=p['content']['rendered'];before=P();before.feed(h);assert len(before.cj)==13
        with TemporaryDirectory() as temp:
            path=Path(temp)/'body.html';path.write_text(h,encoding='utf-8')
            code=PHP+'$r=NRY_Boat_System_Buyer_V1::routes()[(int)$argv[1]];echo NRY_Boat_System_Buyer_V1::transform(file_get_contents($argv[2]),$r);'
            transformed=subprocess.check_output(['php','-r',code,id,str(path)]).decode()
        q=P();q.feed(transformed)
        assert q.mark==[r['role']] and q.cj==before.cj and q.img==before.img,(id,q.mark)
        expected=[('motor',BASE+'nejlepsi-lodni-elektromotory-pro-rybare/'),('kayak',BASE+'rybarsky-kajak-pro-koho-dava-smysl-a-jak-vybrat/')] if r['role']=='boat' else [('boat',BASE+'nejlepsi-nafukovaci-cluny-pro-rybare/')]
        assert q.paths==expected
        if mode=='--public':
            live=get(url+'?nry_release_readback=20261128');x=P();x.feed(live)
            assert x.canon==[url] and x.mark==[r['role']] and x.paths==expected and x.cj==before.cj,(id,x.mark,x.paths)
            if r['role']=='boat':assert x.h1==1 and '33 000 Kč' in live and '55 000 Kč' in live and 'data-nry-boat-load="v1"' in live
            else:assert '97,5 Ah' in live and 'data-nry-motor-battery="v1"' in live
        print('BOAT_SYSTEM_'+('PUBLIC' if mode=='--public' else 'SOURCE')+'_READY',id,flush=True)
    print('BOAT_SYSTEM_'+('PUBLIC_VERIFIED' if mode=='--public' else 'SOURCE_VERIFIED')+' 2/2 26 historic affiliate hrefs preserved, 3 first-party routes')
if __name__=='__main__':
    assert len(sys.argv)==2 and sys.argv[1] in ('--source','--public')
    inspect(sys.argv[1])
