#!/usr/bin/env python3
"""Read only source/public proof, no partner request or synthetic click."""
import sys,json,html,subprocess
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import Request
from tempfile import TemporaryDirectory
from readonly_urlopen_retry import guarded_urlopen
BASE='https://na-ryby.eu/'
PHP='define("ABSPATH",__DIR__);define("NRY_FIT_PREFLIGHT",true);require "bivak-bedchair-fit-v1.php";'
ROUTES=json.loads(subprocess.check_output(['php','-r',PHP+'echo json_encode(NRY_Bivak_Bedchair_Fit_V1::routes());']))
def get(url):
    with guarded_urlopen(Request(url,headers={'User-Agent':'NaRybyEditorialProof/1.0','Cache-Control':'no-cache'}),timeout=25) as r:
        return r.read(3000000).decode('utf-8')
def source():
    for slug,r in ROUTES.items():
        data=json.loads(get(BASE+'wp-json/wp/v2/posts?slug='+slug+'&_fields=id,slug,date,modified_gmt,title,link,content,status'))
        assert len(data)==1
        p=data[0]
        print('FIT_SOURCE_ID',slug,p['id'],p['modified_gmt'],repr(html.unescape(p['title']['rendered'])),flush=True)
        assert p['slug']==slug and p['status']=='publish' and p['date'][:10]==r['date'] and p['link']==BASE+slug+'/' and html.unescape(p['title']['rendered'])==r['title']
        assert not r['id'] or p['id']==r['id']
        assert not r['modified'] or p['modified_gmt'].replace('T',' ')==r['modified']
        with TemporaryDirectory() as temp:
            path=Path(temp)/'body.html'
            path.write_text(p['content']['rendered'],encoding='utf-8')
            code=PHP+'$r=NRY_Bivak_Bedchair_Fit_V1::routes()[$argv[1]];$old=file_get_contents($argv[2]);$new=NRY_Bivak_Bedchair_Fit_V1::transform($old,$r);if($new===$old||substr_count($new,NRY_Bivak_Bedchair_Fit_V1::MARKER)!==1){fwrite(STDERR,"SOURCE_GUARD_BLOCKED");exit(1);}'
            subprocess.run(['php','-r',code,slug,str(path)],check=True)
        print('FIT_SOURCE_READY',p['id'],flush=True)
class P(HTMLParser):
    def __init__(self):
        super().__init__();self.mark=[];self.h1=0;self.canon=[];self.links=[];self.inputs=[];self.assets=[]
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and a.get('data-nry-bivak-bedchair-fit')=='v1':self.mark.append(a.get('data-nry-fit-role'))
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-fit-choice' in a:self.links.append((a['data-nry-fit-choice'],a.get('href')))
        if t in ('input','select') and 'data-nry-fit-input' in a:self.inputs.append(a['data-nry-fit-input'])
        if t=='script' and 'nry-bivak-bedchair-fit-v1.js' in a.get('src',''):self.assets.append(a['src'])
        if t=='link' and 'nry-bivak-bedchair-fit-v1.css' in a.get('href',''):self.assets.append(a['href'])
def public():
    assets=set()
    for slug,r in ROUTES.items():
        url=BASE+slug+'/'
        h=get(url+'?nry_release_readback=20261128')
        p=P();p.feed(h)
        assert p.canon==[url] and p.mark==[r['role']] and p.h1==1,(slug,p.mark,p.h1)
        assert p.inputs==['shelterLength','shelterWidth','bedLength','bedWidth','count','aisle','endGap','wallGap'],(slug,p.inputs)
        assert len(p.links)==2 and all(u==BASE+k+'/' for k,u in p.links)
        assert len(p.assets)==2 and all(a.startswith(BASE) for a in p.assets)
        assets.update(p.assets)
        print('FIT_PUBLIC_OK',slug,'8 controls, 2 internal guides',flush=True)
    for u in assets:
        name=u.split('?')[0].rsplit('/',1)[1]
        assert get(u)==Path('assets',name).read_text(encoding='utf-8'),name
    print('FIT_PUBLIC_VERIFIED 3/3 6/6 first-party paths, exact JS/CSS')
LOAD_ROUTES=json.loads(subprocess.check_output(['php','-r',PHP+'echo json_encode(NRY_Bedchair_Load_Decision_V1::routes());']))
def load_source():
    for slug,r in LOAD_ROUTES.items():
        data=json.loads(get(BASE+'wp-json/wp/v2/posts?slug='+slug+'&_fields=id,slug,date,modified_gmt,title,link,content,status'))
        assert len(data)==1
        p=data[0]
        print('LOAD_SOURCE_ID',slug,p['id'],p['modified_gmt'],repr(html.unescape(p['title']['rendered'])),flush=True)
        assert p['slug']==slug and p['status']=='publish' and p['date'][:10]==r['date'] and p['link']==BASE+slug+'/' and html.unescape(p['title']['rendered'])==r['title']
        assert not r['id'] or p['id']==r['id']
        assert not r['modified'] or p['modified_gmt'].replace('T',' ')==r['modified']
        with TemporaryDirectory() as temp:
            path=Path(temp)/'body.html';path.write_text(p['content']['rendered'],encoding='utf-8')
            code=PHP+'$r=NRY_Bedchair_Load_Decision_V1::routes()[$argv[1]];$old=file_get_contents($argv[2]);$new=NRY_Bedchair_Load_Decision_V1::transform($old,$r);if($new===$old||substr_count($new,NRY_Bedchair_Load_Decision_V1::MARKER)!==1){fwrite(STDERR,"LOAD_SOURCE_TRANSFORM_BLOCKED");exit(1);}'
            subprocess.run(['php','-r',code,slug,str(path)],check=True)
        print('LOAD_SOURCE_READY',p['id'],flush=True)
class LoadHTML(HTMLParser):
    def __init__(self):super().__init__();self.mark=[];self.h1=0;self.canon=[];self.paths=[];self.details=[];self.ids=[];self.historical=0
    def handle_starttag(self,t,attrs):
        a=dict(attrs)
        if t=='section' and a.get('data-nry-bedchair-load-guide')=='v1':self.mark.append(a.get('data-nry-load-role'))
        if t=='h1':self.h1+=1
        if t=='link' and a.get('rel')=='canonical':self.canon.append(a.get('href'))
        if t=='a' and 'data-nry-load-path' in a:self.paths.append((a['data-nry-load-path'],a.get('href')))
        if t=='a' and 'data-nry-load-detail' in a:self.details.append((a['data-nry-load-detail'],a.get('href')))
        if (a.get('id') or '').startswith('nry-load-'):self.ids.append(a['id'])
        if a.get('data-nry-load-historical')=='v1':self.historical+=1
def load_public():
    for slug,r in LOAD_ROUTES.items():
        url=BASE+slug+'/'
        h=get(url+'?nry_release_readback=20261128')
        p=LoadHTML();p.feed(h)
        assert p.canon==[url] and p.mark==[r['role']] and p.h1==1,(slug,p.mark,p.h1)
        if r['role']=='scenario':
            assert p.paths==[('models',BASE+'nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne/'),('fit',BASE+'rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/')]
            assert 'Modelová zbývající nosnost' in h
        else:
            assert p.paths==[('scenario',BASE+'jake-rybarske-lehatko-pro-tezsi-rybare-jak-vybrat-spravne/'),('budget',BASE+'nejlepsi-rybarska-lehatka-do-5000-kc/')]
            assert p.details==[(k,'#nry-load-'+k) for k in ('xl8','st8','air8')]
            assert sorted(p.ids)==['nry-load-air8','nry-load-st8','nry-load-xl8'] and p.historical==2
            assert 'Historický model: původní nabídka OKfish' in h and 'Historický set: při kontrole' in h
            assert 'Chci hlavně jistotu a rezervu: Carpers Specialist.' not in h
            assert 'Chci něco rozumného za solidní peníze: Thunder 8 leg nebo Mivardi Comfort XL8.' not in h
        print('LOAD_PUBLIC_OK',slug,r['role'],flush=True)
    print('LOAD_PUBLIC_VERIFIED 2/2 four first-party paths, three model jumps, two historical offers flagged')

if __name__=='__main__':
    assert len(sys.argv)==2 and sys.argv[1] in ('--source','--public')
    if sys.argv[1]=='--source':
        source();load_source()
    else:
        public();load_public()
