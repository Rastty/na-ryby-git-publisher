#!/usr/bin/env python3
"""Independent GET-only proof of two exact sitewide first-party stay nav paths.
No synthetic navigation, merchant calls, cookies, GA4 writes or WordPress edits.
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

ORIGIN='https://na-ryby.eu'
PAGES=(ORIGIN+'/',ORIGIN+'/poradce-vybavy/')
PARENT=('Ubytování',ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/')
ROUTES=(
 ('Cyklochata Vranov',ORIGIN+'/rybarska-chata-cyklochata-vranovska-prehrada/'),
 ('Wellness srub Vranov',ORIGIN+'/chata-rybarsky-wellness-srub/'),
)
MARKER='nry-stays-menu-choice-v1'
JS=ORIGIN+'/wp-content/plugins/na-ryby-git-publisher/assets/nry-stays-menu-click-v1.js?ver=1.0.0'

class MenuParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.nodes=[]
        self.li_stack=[]
        self.link_stack=[]
        self.scripts=[]
    def handle_starttag(self,tag,attrs):
        values=dict(attrs)
        if tag=='li':
            node={'classes':(values.get('class') or '').split(),
                  'children':[],'title':None,'href':None}
            if self.li_stack:
                self.li_stack[-1]['children'].append(node)
            self.nodes.append(node)
            self.li_stack.append(node)
        elif tag=='a' and self.li_stack:
            self.link_stack.append((self.li_stack[-1],values.get('href'),[]))
        elif tag=='script' and 'nry-stays-menu-click-v1.js' in (values.get('src') or ''):
            self.scripts.append(values.get('src'))
    def handle_data(self,data):
        if self.link_stack:
            self.link_stack[-1][2].append(data)
    def handle_endtag(self,tag):
        if tag=='a' and self.link_stack:
            node,href,parts=self.link_stack.pop()
            if node['title'] is None:
                node['title']=' '.join(''.join(parts).split())
                node['href']=href
        elif tag=='li' and self.li_stack:
            self.li_stack.pop()

def verify_html(body):
    if not isinstance(body,str) or len(body)<2500 or len(body)>1800000:
        raise ValueError('BOUNDED_HTML_REQUIRED')
    p=MenuParser();p.feed(body);p.close()
    candidates=[n for n in p.nodes if (n['title'],n['href'])==PARENT]
    verified=0
    for parent in candidates:
        children=parent['children']
        if ('menu-item-has-children' in parent['classes']
            and len(children)==len(ROUTES)
            and all((child['title'],child['href'])==route
                    and MARKER in child['classes']
                    for child,route in zip(children,ROUTES))):
            verified+=1
    if not candidates or verified!=len(candidates):
        raise ValueError('EXACT_STAYS_MENU_CHILDREN_MISSING_OR_DRIFT')
    if p.scripts!=[JS]:
        raise ValueError('EXACT_SITEWIDE_SCRIPT_REQUIRED')
    return {'menuInstances':verified,'firstPartyStayChoicesPerMenu':2,
            'exactMenuScript':True}

def get(url,limit):
    req=Request(url,method='GET',headers={'User-Agent':'NaRybyStaysMenuPublicReadback/1.0',
                                         'Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,attempts=2,timeout=25) as response:
        if response.status!=200 or response.geturl()!=url:
            raise ValueError('CANONICAL_HTTP_200_REQUIRED')
        data=response.read(limit+1)
    if len(data)>limit:raise ValueError('PUBLIC_BYTES_TOO_LARGE')
    return data

def verify_live():
    pages=[]
    for url in PAGES:
        parsed=verify_html(get(url,1800000).decode('utf-8','replace'))
        pages.append({'path':urlsplit(url).path or '/',**parsed})
    actual=get(JS,30000)
    reviewed=(Path(__file__).resolve().parents[1]/'assets/nry-stays-menu-click-v1.js').read_bytes()
    if hashlib.sha256(actual).digest()!=hashlib.sha256(reviewed).digest():
        raise ValueError('JS_SHA_MISMATCH')
    return {'status':'STAYS_SITEWIDE_MENU_TWO_PAGES_PUBLIC_VERIFIED',
      'pages':pages,'servedJsSha256':hashlib.sha256(actual).hexdigest(),
      'realUserClicks':'UNKNOWN','GA4Delivery':'UNKNOWN','merchantOrders':'UNKNOWN',
      'approvedOrPaidCommissions':'UNKNOWN','wordpressWrites':0,
      'merchantRequests':0,'syntheticClicks':0}

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('use --verify')
    try:
        print(json.dumps(verify_live(),ensure_ascii=False,sort_keys=True))
    except Exception as e:
        allowed={'BOUNDED_HTML_REQUIRED','EXACT_STAYS_MENU_CHILDREN_MISSING_OR_DRIFT',
             'EXACT_SITEWIDE_SCRIPT_REQUIRED','CANONICAL_HTTP_200_REQUIRED',
             'PUBLIC_BYTES_TOO_LARGE','JS_SHA_MISMATCH'}
        error=str(e) if isinstance(e,ValueError) and str(e) in allowed else 'GET_OR_ENV_UNVERIFIABLE'
        print(json.dumps({'status':'STAYS_SITEWIDE_MENU_PUBLIC_NOT_VERIFIED',
                          'reason':error,'exceptionClass':type(e).__name__}))
        sys.exit(1)
