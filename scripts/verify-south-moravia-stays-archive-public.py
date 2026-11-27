#!/usr/bin/env python3
"""Exact anonymous GET-only readback for one South Moravia accommodation index."""
from html.parser import HTMLParser
from pathlib import Path
import json
import sys
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL='https://na-ryby.eu/rybareni-s-ubytovanim-jihomoravsky-kraj/'
CYCLO='https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/'
SRUB='https://na-ryby.eu/chata-rybarsky-wellness-srub/'
HUB='https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
MARKER='data-nry-jm-archive-stays="v1"'
EXPECTED=(CYCLO,SRUB,HUB)

class Parse(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.markers=[]
        self.panel_depth=0
        self.links=[]
        self.parts=[]
        self.after_parts=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='section' and a.get('data-nry-jm-archive-stays')=='v1':
            self.markers.append(True)
            self.panel_depth=1
        elif tag=='section' and self.panel_depth:
            self.panel_depth+=1
        if tag=='a' and self.panel_depth:
            self.links.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.panel_depth:
            self.panel_depth-=1
    def handle_data(self,data):
        self.parts.append(data)
        if not self.panel_depth:
            self.after_parts.append(data)

def verify(html):
    if not isinstance(html,str) or not 8000<len(html)<1500000:
        raise ValueError('EXACT_BOUNDED_PUBLIC_DOCUMENT_REQUIRED')
    p=Parse();p.feed(html);p.close()
    if p.canonicals!=[URL]:raise ValueError('CANONICAL_DRIFT')
    if html.count(MARKER)!=1 or len(p.markers)!=1:
        raise ValueError('ONE_OWNED_PANEL_REQUIRED')
    if p.links!=list(EXPECTED):
        raise ValueError('EXACT_THREE_FIRST_PARTY_ROUTES_REQUIRED')
    old=' '.join(' '.join(p.after_parts).split())
    if not all(x in old for x in ('Zukal','Popůvky','Havelka ORCA','Rybník Božice')):
        raise ValueError('HISTORICAL_ARCHIVE_MUST_REMAIN_VISIBLE')
    if html.index(MARKER)>html.index('Zukal'):
        raise ValueError('BUYER_CHOICES_MUST_PRECEDE_HISTORICAL_RESULTS')
    if any('tracking.affiliateport.eu' in (href or '') for href in p.links):
        raise ValueError('UNAUTHORIZED_EXTERNAL_HREF')
    return {'state':'SOUTH_MORAVIA_STAYS_ARCHIVE_PUBLIC_VERIFIED',
        'exactFirstPartyLinks':3,'historicalIndexPreserved':True,
        'wordpressWrites':0,'merchantRequests':0,'syntheticClicks':0,
        'actualClicks':'UNKNOWN','approvedOrPaidCommissions':'UNKNOWN'}

def get():
    req=Request(URL,method='GET',headers={'User-Agent':'NaRybySouthMoraviaStaysPublic/1.0',
        'Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,timeout=25) as resp:
        if resp.status!=200 or resp.geturl()!=URL:raise ValueError('EXACT_ORIGIN_NOT_200')
        body=resp.read(1500001)
    if len(body)>1500000:raise ValueError('PUBLIC_HTML_TOO_LARGE')
    return body.decode('utf-8','replace')

def safe_diagnostic():
    """When a panel is absent, obtain only boolean guard facts from one site-local GET."""
    from urllib.error import URLError
    url=URL+'?nry_jm_archive_probe=source_v1'
    try:
        req=Request(url,method='GET',headers={'User-Agent':'NaRybySouthMoraviaProbeReadOnly/1.0'})
        with guarded_urlopen(req,timeout=25) as resp:
            if resp.status!=200 or resp.geturl()!=url:
                raise ValueError('EXACT_DIAGNOSTIC_URL_REQUIRED')
            body=resp.read(1500001).decode('utf-8','replace')
        prefix='<!-- NRY_JM_ARCHIVE_PROBE_V1 '
        start=body.find(prefix)
        end=body.find(' -->',start+len(prefix)) if start>=0 else -1
        if start<0 or end<0 or end-start>3200:
            print('ELIGIBILITY_PROBE_NOT_PRESENT');return
        payload=body[start+len(prefix):end]
        proof=json.loads(payload)
        allowed=('status','singleSlug','permalink','post','published','typePost',
            'slugMatches','date20170704','titleAllowed','rawSourceNonempty','eligible',
            'wordpressWrites','syntheticClicks')
        print('SAFE_ELIGIBILITY_PROBE',json.dumps({k:proof.get(k) for k in allowed},sort_keys=True))
    except (OSError,ValueError,URLError):
        print('ELIGIBILITY_PROBE_UNAVAILABLE')

def audit_only(html):
    """Classify exact public anchor types without releasing raw href or queries."""
    from urllib.parse import urlsplit
    p=Parse();p.feed(html);p.close()
    approved={'cyklochata':CYCLO,'wellness':SRUB,'stays':HUB}
    roles=[]
    for href in p.links[:30]:
        match=next((k for k,v in approved.items() if href==v),None)
        if match:
            roles.append(match)
        elif not isinstance(href,str):
            roles.append('missing_href')
        else:
            url=urlsplit(href)
            roles.append('other_first_party' if url.hostname=='na-ryby.eu'
                else 'unexpected_other')
    report={
        'state':'JM_READONLY_HREF_AUDIT',
        'canonicalExact':p.canonicals==[URL],
        'markerCount':html.count(MARKER),
        'parsedPanelCount':len(p.markers),
        'panelAnchorCount':len(p.links),
        'panelRoles':roles,
        'rawApprovedHrefCounts':{k:html.count('href="'+v+'"')
            for k,v in approved.items()},
        'originalRetained':all(k in html for k in ('Zukal','Popůvky','Havelka ORCA','Rybník Božice')),
        'wordPressWrites':0,
        'merchantCalls':0,
        'syntheticClicks':0,
    }
    print(json.dumps(report,ensure_ascii=False))
    return report

if __name__=='__main__':
    if sys.argv[1:]==['--audit']:
        audit_only(get())
        raise SystemExit(0)
    if sys.argv[1:]!=['--verify']:raise SystemExit('use --audit or --verify')
    try:
        print(json.dumps(verify(get()),ensure_ascii=False))
    except ValueError:
        safe_diagnostic()
        raise
