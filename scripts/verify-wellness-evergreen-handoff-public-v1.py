#!/usr/bin/env python3
"""Independent two-page public verifier of source-bound first-party choices.

A live theme may add one contextual *first-party* link before the owned CTA
links on the private-fishing article. Require both uniquely marked CTAs in
exact role/order/target and separately reject unowned outbound/unsafe links.
No customer data, merchant GETs, cookies, clicks or WordPress writes.
"""
import json
import sys
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
MARKER='data-nry-wellness-evergreen-handoff="v1"'

class Doc(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonical=[]
        self.panels=0
        self.managed=[]
        self.unmanaged=[]
        self.inside=False
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonical.append(a.get('href'))
        if tag=='section' and a.get('data-nry-wellness-evergreen-handoff')=='v1':
            self.panels+=1
            self.inside=True
        if tag=='a' and self.inside:
            if 'data-nry-wellness-path' in a:
                self.managed.append((a['data-nry-wellness-path'],a.get('href')))
            else:
                self.unmanaged.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.inside:
            self.inside=False

def safe_unmanaged(href):
    if not isinstance(href,str):
        return False
    # The only additional live contextual link is the site's homepage.
    # This is not an authored CTA and never an affiliate destination.
    # Do not generalize to arbitrary same-origin URLs or query parameters.
    return href==ORIGIN+'/'

def verify_html(body,idx,url):
    parser=Doc()
    parser.feed(body)
    assert parser.canonical==[url], 'CANONICAL_DRIFT'
    assert parser.panels==1, 'PANEL_COUNT_DRIFT'
    expected=[('srub',SRUB),('stays',HUB)]
    if idx==1:
        expected.reverse()
    assert parser.managed==expected, 'CTA_ROLE_OR_HREF_DRIFT'
    # Observed theme transform: one contextual, first-party article link in
    # the second panel. It is never an additional offered/affiliate CTA.
    assert len(parser.unmanaged)==idx, 'UNEXPECTED_CONTEXTUAL_LINK_COUNT'
    assert all(safe_unmanaged(h) for h in parser.unmanaged), 'UNSAFE_CONTEXTUAL_LINK'
    assert body.count(MARKER)==1, 'MARKER_DUPLICATED'
    marker_pos=body.find(MARKER)
    anchor_pos=body.find(ANCHORS[idx])
    assert marker_pos>=0 and anchor_pos>=0 and marker_pos<anchor_pos, 'PANEL_POSITION_DRIFT'
    return {'article':idx,'owned_ctas':2,'other_safe_firstparty_links':len(parser.unmanaged)}

def check(slug,idx):
    url=ORIGIN+'/'+slug+'/'
    with urlopen(Request(url,headers={'User-Agent':'NaRyby-Wellness-Handoff-Proof/1.0'}),
                 timeout=25) as response:
        assert response.status==200 and response.url==url, 'HTTP_REDIRECT_OR_STATUS'
        raw=response.read(1400001)
    assert len(raw)<1400001, 'DOCUMENT_TOO_LARGE'
    return verify_html(raw.decode('utf-8'),idx,url)

if __name__=='__main__':
    try:
        reports=[check(slug,i) for i,slug in enumerate(SLUGS)]
        print(json.dumps({'status':'WELLNESS_EVERGREEN_TWO_PUBLIC_VERIFIED',
                          'articles':reports,'firstPartyOfferRouteLinks':4,
                          'wordpressWrites':0,'merchantRequests':0,
                          'syntheticClicks':0},ensure_ascii=False))
    except Exception as exc:
        print(json.dumps({'status':'WELLNESS_EVERGREEN_PUBLIC_NOT_VERIFIED',
                          'error':type(exc).__name__,
                          'reason':str(exc) if isinstance(exc,AssertionError)
                                   and str(exc) in {
                                       'CANONICAL_DRIFT','PANEL_COUNT_DRIFT',
                                       'CTA_ROLE_OR_HREF_DRIFT','UNEXPECTED_CONTEXTUAL_LINK_COUNT',
                                       'UNSAFE_CONTEXTUAL_LINK','MARKER_DUPLICATED',
                                       'PANEL_POSITION_DRIFT','HTTP_REDIRECT_OR_STATUS','DOCUMENT_TOO_LARGE'
                                   } else 'GET_OR_PARSER_BLOCKED'}))
        sys.exit(1)
