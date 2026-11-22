#!/usr/bin/env python3
"""Bounded, anonymous, two-exact-URL page diagnostic, no writes or offsite GET.

Print only comparison booleans, counts and marker order. Do NOT include user
content, raw HTML, headers, query values, cookies or affiliate destinations.
HTTP denials are BLOCKED, not an implied success or evidence of missing content.
"""
from html.parser import HTMLParser
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen
import json

BASE='https://na-ryby.eu'
SLUGS=(
'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare',
'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice',
)
ANCHORS=('Když se řekne rybářský pobyt s wellness',
'Chcete si odpočinout, ale nechcete jen ležet u bazénu?')
MARKER='data-nry-wellness-evergreen-handoff="v1"'
LINKS=(
BASE+'/chata-rybarsky-wellness-srub/',
BASE+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
)
class Parsed(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical=[]; self.panels=0; self.panel_links=[]
        self.styles=[]; self.depth=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonical.append(a.get('href'))
        if tag=='link' and 'nry-wellness-evergreen-handoff-v1.css' in (a.get('href') or ''):
            self.styles.append(True)
        if tag=='section' and a.get('data-nry-wellness-evergreen-handoff')=='v1':
            self.panels+=1
            self.depth=1
        elif self.depth:
            self.depth+=1
        if self.depth and tag=='a':
            self.panel_links.append((a.get('data-nry-wellness-path'),a.get('href')))
    def handle_endtag(self,tag):
        if self.depth and tag not in ('meta','img','input','link','br','hr'):
            self.depth-=1

def inspect(idx,slug):
    url=BASE+'/'+slug+'/'
    try:
        req=Request(url,headers={'User-Agent':'NaRyby-Wellness-Handoff-Proof/1.0'})
        with urlopen(req,timeout=25) as response:
            if response.status!=200 or response.url!=url:
                return {'article':idx,'status':'UNEXPECTED_STATUS_OR_REDIRECT'}
            data=response.read(1400001)
        if len(data)>1400000:
            return {'article':idx,'status':'OVERSIZED_DOCUMENT'}
        html=data.decode('utf-8')
        parser=Parsed();parser.feed(html)
        mark=html.find(MARKER)
        source=html.find(ANCHORS[idx])
        expected=[('srub',LINKS[0]),('stays',LINKS[1])]
        if idx: expected.reverse()
        return {
            'article':idx,'status':'ANONYMOUS_HTTP200',
            'canonical_exact':parser.canonical==[url],
            'canonical_count':len(parser.canonical),
            'panel_count':parser.panels,
            'literal_marker_count':html.count(MARKER),
            'panel_link_count':len(parser.panel_links),
            'panel_links_exact':parser.panel_links==expected,
            'link_roles':[
                {'role':label if label in ('srub','stays') else 'missing_or_unexpected',
                 'destination':'srub' if dest==LINKS[0]
                    else ('stays' if dest==LINKS[1]
                          else ('other_first_party' if isinstance(dest,str) and dest.startswith(BASE+'/')
                                else ('external_or_missing' if dest else 'missing')))}
                for label,dest in parser.panel_links
            ],
            'source_anchor_present':source>=0,
            'marker_precedes_source_anchor':mark>=0 and source>=0 and mark<source,
            'style_present':bool(parser.styles),
            'srub_href_count':html.count('href="'+LINKS[0]+'"'),
            'stays_href_count':html.count('href="'+LINKS[1]+'"'),
        }
    except HTTPError as e:
        return {'article':idx,'status':'HTTP_DENIED','http_status':e.code}
    except (URLError,TimeoutError,UnicodeError,ValueError) as e:
        return {'article':idx,'status':'GET_OR_PARSE_BLOCKED','exception':type(e).__name__}

if __name__=='__main__':
    rows=[inspect(i,s) for i,s in enumerate(SLUGS)]
    print(json.dumps({'diagnostic':'WELLNESS_TWO_PUBLIC_RENDER_READONLY','results':rows},
        ensure_ascii=False,sort_keys=True))
    if not all(row['status']=='ANONYMOUS_HTTP200' for row in rows):
        raise SystemExit(1)
