#!/usr/bin/env python3
"""Read-only bounded exact fishing-map HTML source/cache diagnosis.

No merchant GET, cookies, GA4 event, WordPress write or raw affiliate URL logged.
Two allowed same-site GETs: exact canonical and one fixed public query key.
"""
import json
from html.parser import HTMLParser
from urllib.request import Request, urlopen
from urllib.parse import urlsplit
from urllib.error import URLError, HTTPError

ORIGIN='https://na-ryby.eu'
PATH='/dovolena-u-vody/'
URL=ORIGIN+PATH
QUERY_URL=URL+'?nry_map_readonly_diag=20261127e3fd'
MARKER='data-nry-map-discovery="v1"'
NEW='data-nry-map-stay-picks="v1"'
STAYS={
 ORIGIN+'/rybarska-chata-cyklochata-vranovska-prehrada/':'cyklochata',
 ORIGIN+'/chata-rybarsky-wellness-srub/':'wellness'
}
OTHERS={
 ORIGIN+'/category/soukrome-rybarske-reviry/':'revirs',
 ORIGIN+'/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/':'rules',
 ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/':'stays'
}

class Parse(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.inside=False;self.depth=0;self.anchors=[]
        self.iframes=0;self.sections=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='section' and a.get('data-nry-map-discovery')=='v1':
            self.inside=True;self.depth=1;self.sections+=1
        elif self.inside and tag=='section':self.depth+=1
        if tag=='a' and self.inside:
            href=a.get('href')
            if href in STAYS:role=STAYS[href]
            elif href in OTHERS:role=OTHERS[href]
            elif href==ORIGIN+'/':role='context_home'
            elif isinstance(href,str):
                u=urlsplit(href)
                role='unexpected_same_origin' if u.hostname=='na-ryby.eu' else 'unexpected_other'
            else:role='missing_href'
            self.anchors.append(role)
        if tag=='iframe':self.iframes+=1
    def handle_endtag(self,tag):
        if tag=='section' and self.inside:
            self.depth-=1
            if self.depth<=0:self.inside=False

def probe(url,label):
    headers={'User-Agent':'NaRybyMapReadOnlyDiag/1.0','Cache-Control':'no-cache, no-store'}
    req=Request(url,headers=headers,method='GET')
    try:
        with urlopen(req,timeout=25) as resp:
            body=resp.read(1200001)
            code=resp.status;target=resp.url;h=resp.headers
    except (HTTPError,URLError) as exc:
        return {'source':label,'outcome':'HTTP_UNAVAILABLE','status':getattr(exc,'code',None)}
    if len(body)>1200000:
        return {'source':label,'outcome':'HTML_LIMIT_EXCEEDED'}
    html=body.decode('utf-8','replace')
    parsed=Parse();parsed.feed(html);parsed.close()
    return {'source':label,'outcome':'GET_OK','status':code,
      'exactCanonicalHostAndPath':urlsplit(target).scheme=='https' and urlsplit(target).netloc=='na-ryby.eu'
            and urlsplit(target).path==PATH,
      'htmlBytes':len(body),'mapBridgeMarkers':html.count(MARKER),
      'newStayMarkers':html.count(NEW),'mapSections':parsed.sections,
      'mapSectionLinkRoles':parsed.anchors[:12],
      'oldIframePresent':'mapsengine.google.com/map/embed?mid=z3EK_WPx5q6o.ksrmQWY0S3AE' in html,
      'newCardHeadingVisible':'Dvě možnosti u Vranovské přehrady' in html,
      'cache':{k:str(h.get(k,''))[:60] for k in ('Age','X-Cache','X-LiteSpeed-Cache','CF-Cache-Status','Cache-Control','ETag') if h.get(k)}}

if __name__=='__main__':
    out=[probe(URL,'canonical'),probe(QUERY_URL,'query_bust')]
    print('NRY_MAP_SOURCE_CACHE_READONLY_DIAGNOSTIC',json.dumps(out,ensure_ascii=False))
    if not any(x.get('outcome')=='GET_OK' for x in out):raise SystemExit(1)
