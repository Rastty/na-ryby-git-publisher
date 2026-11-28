#!/usr/bin/env python3
"""Read only source and public result; no outbound partner GET."""
import sys,json,html,subprocess
from pathlib import Path
from tempfile import TemporaryDirectory
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen
BASE='https://na-ryby.eu/'
SLUG='nejlepsi-rybarska-lehatka-do-5000-kc'
URL=BASE+SLUG+'/'
PHP='define("ABSPATH",__DIR__);define("NRY_BUDGET_PREFLIGHT",true);require "bedchair-budget-5000-v1.php";'
KEYS=['xl6','avenger','xl8','gaube','hex8','flat8','eightlux']
def get(url):
    with guarded_urlopen(Request(url,headers={'User-Agent':'NaRybyEditorialProof/1.0','Cache-Control':'no-cache'}),timeout=25) as r:
        return r.read(3000000).decode('utf-8')
def source():
    data=json.loads(get(BASE+'wp-json/wp/v2/posts?slug='+SLUG+'&_fields=id,slug,date,modified_gmt,title,link,content,status'))
    assert len(data)==1
    p=data[0]
    assert p['slug']==SLUG and p['status']=='publish' and p['date'][:10]=='2026-01-21' and p['link']==URL and html.unescape(p['title']['rendered'])=='Nejlepší rybářská lehátka do 5000 Kč'
    print('BEDCHAIR_BUDGET_SOURCE_ID',p['id'],p['modified_gmt'],flush=True)
    with TemporaryDirectory() as temp:
        path=Path(temp)/'body.html';path.write_text(p['content']['rendered'],encoding='utf-8')
        code=PHP+'$old=file_get_contents($argv[1]);$new=NRY_Bedchair_Budget_5000_V1::transform($old);if($new===$old||substr_count($new,NRY_Bedchair_Budget_5000_V1::MARKER)!==1){fwrite(STDERR,"BUDGET_SOURCE_TRANSFORM_BLOCKED");exit(1);}'
        subprocess.run(['php','-r',code,str(path)],check=True)
    print('BEDCHAIR_BUDGET_SOURCE_READY',p['id'])
class P(HTMLParser):
    def __init__(self):super().__init__();self.mark=0;self.h1=0;self.canon=[];self.details=[];self.ids=[];self.paths=[]
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and a.get('data-nry-bedchair-budget-guide')=='v1':self.mark+=1
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-bedchair-detail' in a:self.details.append((a['data-nry-bedchair-detail'],a.get('href')))
        if (a.get('id') or '').startswith('nry-budget-'):self.ids.append(a['id'])
        if t=='a' and 'data-nry-bedchair-budget-path' in a:self.paths.append((a['data-nry-bedchair-budget-path'],a.get('href')))
def public():
    h=get(URL+'?nry_release_readback=20261128')
    p=P();p.feed(h)
    assert p.canon==[URL] and p.mark==1 and p.h1==1,(p.mark,p.h1)
    assert p.details==[(k,'#nry-budget-'+k) for k in KEYS]
    assert p.ids==['nry-budget-'+k for k in KEYS]
    assert p.paths==[('fit',BASE+'rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'),('load',BASE+'jake-rybarske-lehatko-pro-tezsi-rybare-jak-vybrat-spravne/'),('winter',BASE+'jake-rybarske-lehatko-na-zimu-prakticky-vyber/')]
    assert '10,98' in h and '9,54' in h and 'Technické podklady dohledané' in h
    print('BEDCHAIR_BUDGET_PUBLIC_VERIFIED 1/1 seven anchored model details, three first-party guides, single H1')
if __name__=='__main__':
    assert len(sys.argv)==2 and sys.argv[1] in ('--source','--public')
    source() if sys.argv[1]=='--source' else public()
