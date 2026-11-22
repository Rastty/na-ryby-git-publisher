#!/usr/bin/env python3
"""Independent anonymous, two-page first-party only public readback."""
import json
import sys
import hashlib
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import Request, urlopen
ORIGIN='https://na-ryby.eu'
SLUGS=(
'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare',
'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice',
)
SRUB=ORIGIN+'/chata-rybarsky-wellness-srub/'
HUB=ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
ANCHORS=('Když se řekne rybářský pobyt s wellness',
         'Chcete si odpočinout, ale nechcete jen ležet u bazénu?')
class Doc(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical=[];self.panels=0;self.links=[];self.inside=False;self.scripts=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonical.append(a.get('href'))
        if tag=='script' and a.get('src') and '/assets/nry-wellness-evergreen-choice-v1.js' in a['src']:
            self.scripts.append(a['src'])
        if tag=='section' and a.get('data-nry-wellness-evergreen-handoff')=='v1':
            self.panels+=1;self.inside=True
        if tag=='a' and self.inside:self.links.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.inside:self.inside=False

def check(slug,idx):
    url=ORIGIN+'/'+slug+'/'
    with urlopen(Request(url,headers={'User-Agent':'NaRyby-Wellness-Handoff-Proof/1.0'}),
                 timeout=25) as response:
        assert response.status==200 and response.url==url
        raw=response.read(1400001)
    assert len(raw)<1400001
    body=raw.decode('utf-8')
    parser=Doc();parser.feed(body)
    assert parser.canonical==[url]
    assert parser.panels==1
    assert parser.scripts==[ORIGIN+'/wp-content/plugins/na-ryby-git-publisher/assets/nry-wellness-evergreen-choice-v1.js?ver=1.0.0']
    assert parser.links==([SRUB,HUB] if idx==0 else [HUB,SRUB])
    assert body.count('data-nry-wellness-evergreen-handoff="v1"')==1
    assert body.find('data-nry-wellness-evergreen-handoff="v1"')<body.find(ANCHORS[idx])
    assert ANCHORS[idx] in body
    return slug

if __name__=='__main__':
    try:
        sources=[check(slug,i) for i,slug in enumerate(SLUGS)]
        asset=ORIGIN+'/wp-content/plugins/na-ryby-git-publisher/assets/nry-wellness-evergreen-choice-v1.js?ver=1.0.0'
        with urlopen(Request(asset,headers={'User-Agent':'NaRyby-Wellness-Handoff-Proof/1.0'}),timeout=25) as response:
            assert response.status==200 and response.url==asset
            served=response.read(50001)
        assert len(served)<50001
        local=(Path(__file__).resolve().parents[1]/'assets/nry-wellness-evergreen-choice-v1.js').read_bytes()
        assert hashlib.sha256(served).digest()==hashlib.sha256(local).digest()
        print(json.dumps({'status':'WELLNESS_EVERGREEN_TWO_PUBLIC_VERIFIED',
                          'articles':sources,'firstPartyLinks':4,
                          'wordpressWrites':0,'merchantRequests':0,'syntheticClicks':0},ensure_ascii=False))
    except Exception as ex:
        print(json.dumps({'status':'WELLNESS_EVERGREEN_PUBLIC_NOT_VERIFIED',
                          'error':type(ex).__name__}))
        sys.exit(1)
