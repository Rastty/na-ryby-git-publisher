#!/usr/bin/env python3
"""GET-only independent proof of existing-consent archive-stay click observer.

Checks exact published panel/hrefs on the two known 2017 landings and live JS
SHA-256 equality to the reviewed local asset. No clicks, GA4 calls, merchant
requests, WordPress writes, credentials or user identifiers.
"""
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
from urllib.parse import urlsplit
import hashlib
import json
import sys
sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

BASE='https://na-ryby.eu'
ASSET_PATH='/wp-content/plugins/na-ryby-git-publisher/assets/nry-archive-stay-click-v1.js'
JS=BASE+ASSET_PATH+'?ver=1.0.0'
HOMEPAGE=BASE+'/'
ROUTES={
 '/ubytovani-2/':(
    'data-nry-legacy-stays-entry',[
    BASE+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
    BASE+'/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/',
    BASE+'/rybareni-s-ubytovanim-jihocesky-kraj/']),
 '/rybareni-s-ubytovanim-jihomoravsky-kraj/':(
    'data-nry-jm-archive-stays',[
    BASE+'/rybarska-chata-cyklochata-vranovska-prehrada/',
    BASE+'/chata-rybarsky-wellness-srub/',
    BASE+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'])
}
class Doc(HTMLParser):
    def __init__(self, marker):
        super().__init__(convert_charrefs=True)
        self.marker=marker
        self.canonicals=[]
        self.scripts=[]
        self.panels=0
        self.depth=0
        self.links=[]
        self.page_text=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='script' and 'nry-archive-stay-click-v1.js' in (a.get('src') or ''):
            self.scripts.append(a.get('src'))
        if tag=='section' and a.get(self.marker)=='v1':
            self.panels+=1
            self.depth=1
        elif tag=='section' and self.depth:
            self.depth+=1
        if tag=='a' and self.depth:
            self.links.append(a.get('href'))
    def handle_endtag(self,tag):
        if tag=='section' and self.depth:
            self.depth-=1
    def handle_data(self,data):
        if len(self.page_text)<30000:
            self.page_text.append(data)

def verify_doc(html,path):
    if path not in ROUTES:
        raise ValueError('EXACT_KNOWN_POST_ONLY')
    if not isinstance(html,str) or not 7500<len(html)<1500000:
        raise ValueError('BOUNDED_HTML_REQUIRED')
    marker,expected=ROUTES[path]
    p=Doc(marker);p.feed(html);p.close()
    if p.canonicals!=[BASE+path]:raise ValueError('CANONICAL_MISMATCH')
    if p.panels!=1 or html.count(marker+'="v1"')!=1:
        raise ValueError('PANEL_MISSING_OR_DUPLICATED')
    if p.scripts!=[JS]:raise ValueError('EXACT_LOCAL_SCRIPT_REQUIRED')
    if p.links not in [expected,[HOMEPAGE]+expected]:
        raise ValueError('EXACT_PANEL_CTA_SEQUENCE_MISMATCH')
    if path=='/ubytovani-2/' and 'Rekreační dům Harvánek' not in html:
        raise ValueError('ORIGINAL_2017_LIST_NOT_PRESERVED')
    if path=='/rybareni-s-ubytovanim-jihomoravsky-kraj/' and (
        'Zukal' not in html or 'Popůvky' not in html):
        raise ValueError('ORIGINAL_JM_ARCHIVE_NOT_PRESERVED')
    return {'path':path,'approvedFirstPartyChoices':3,
        'exactThemeContext':len(p.links)==4,'legacyListPreserved':True}

def get(url,limit):
    req=Request(url,method='GET',headers={'User-Agent':'NaRybyArchiveStayClickProof/1.0',
                                         'Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,timeout=25) as resp:
        if resp.status!=200 or resp.geturl()!=url:
            raise ValueError('EXACT_HTTP_200_REQUIRED')
        raw=resp.read(limit+1)
    if len(raw)>limit:raise ValueError('PUBLIC_BYTES_TOO_LARGE')
    return raw

def verify_live():
    pages=[]
    for path in ROUTES:
        html=get(BASE+path,1500000).decode('utf-8','replace')
        pages.append(verify_doc(html,path))
    served=get(JS,50000)
    local=(Path(__file__).resolve().parents[1]/'assets/nry-archive-stay-click-v1.js').read_bytes()
    if hashlib.sha256(served).digest()!=hashlib.sha256(local).digest():
        raise ValueError('SERVED_JS_SHA256_MISMATCH')
    return {'status':'ARCHIVE_STAY_TWO_TRUSTED_CLICK_ROUTES_PUBLIC_VERIFIED',
        'pages':pages,'servedScriptSha256':hashlib.sha256(served).hexdigest(),
        'actualUserClicks':'UNKNOWN','GA4Delivery':'UNKNOWN','bookings':'UNKNOWN',
        'approvedOrPaidCommissions':'UNKNOWN','wordpressWrites':0,
        'merchantRequests':0,'syntheticClicks':0}

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('use --verify')
    try:
        print(json.dumps(verify_live(),ensure_ascii=False))
    except Exception as e:
        allowed={'EXACT_KNOWN_POST_ONLY','BOUNDED_HTML_REQUIRED','CANONICAL_MISMATCH',
            'PANEL_MISSING_OR_DUPLICATED','EXACT_LOCAL_SCRIPT_REQUIRED',
            'EXACT_PANEL_CTA_SEQUENCE_MISMATCH','ORIGINAL_2017_LIST_NOT_PRESERVED',
            'ORIGINAL_JM_ARCHIVE_NOT_PRESERVED','EXACT_HTTP_200_REQUIRED',
            'PUBLIC_BYTES_TOO_LARGE','SERVED_JS_SHA256_MISMATCH'}
        reason=str(e) if isinstance(e,ValueError) and str(e) in allowed else 'GET_OR_ENV_UNVERIFIABLE'
        print(json.dumps({'status':'ARCHIVE_STAY_CLICK_PUBLIC_NOT_VERIFIED',
                          'reason':reason,'exceptionClass':type(e).__name__}))
        sys.exit(1)
