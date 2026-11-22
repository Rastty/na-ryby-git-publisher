#!/usr/bin/env python3
"""GET-only exact public receipt for Wellness srub approved GA4 click observer.

No merchant requests, fake clicks, WordPress writes or GA4 requests. A loaded,
source-identical observer is not evidence that any user consented or clicked.
"""
import hashlib
import json
import runpy
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'scripts'))
from readonly_urlopen_retry import guarded_urlopen

STAY=runpy.run_path(str(ROOT/'scripts/verify-wellness-srub-hauzi-public-17536.py'))
HOST='https://na-ryby.eu'
URL=STAY['URL']
ASSET='/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js'
VERSION='1.11.7'
JS_URL=HOST+ASSET+'?ver='+VERSION
ORIGINAL=(ROOT/'assets/nry-buyer-clicks-v1.js').read_bytes()

class Scripts(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True);self.targets=[]
    def handle_starttag(self,tag,attrs):
        if tag!='script':return
        src=dict(attrs).get('src','')
        parts=urlsplit(src)
        if parts.path==ASSET and (not parts.fragment):
            self.targets.append(src)

def verify(page,js):
    if not isinstance(page,(str,bytes)):raise ValueError('HTML_REQUIRED')
    if isinstance(page,bytes):page=page.decode('utf-8','replace')
    offer=STAY['verify'](page)
    if offer['status']!='WELLNESS_SRUB_HAUZI_OFFER_PUBLIC_VERIFIED':
        raise ValueError('ACTUAL_WELLNESS_OFFER_NOT_PROVEN')
    parser=Scripts();parser.feed(page);parser.close()
    if parser.targets not in ([JS_URL], [ASSET+'?ver='+VERSION]):
        raise ValueError('EXACT_WELLNESS_JS_TAG_NOT_ONCE')
    if not isinstance(js,bytes) or js!=ORIGINAL or len(js)<10000 or len(js)>65536:
        raise ValueError('DEPLOYED_JS_BYTES_DIFFER_FROM_REVIEWED_SOURCE')
    try:source=js.decode('utf8')
    except UnicodeDecodeError:raise ValueError('JS_NOT_UTF8')
    for needed in (
        "'/chata-rybarsky-wellness-srub/':'wellness_srub_17536'",
        "if (page === 'wellness_srub_17536')",
        "section[data-nry-wellness-srub-offer",
        "aside.nry-wellness__bottom",
        STAY['AFFILIATE'].replace('&amp;','&'),
        "source_post_id:'17536'",
        "stay_partner:'hauzi_affiliateport_cz'"
    ):
        if needed not in source:
            raise ValueError('REVIEWED_OBSERVER_SOURCE_MISMATCH')
    return {'status':'WELLNESS_SRUB_CLICK_JS_PUBLIC_VERIFIED',
            'buyer_page':'wellness_srub_17536','exactAffiliateCTAs':2,
            'sourcePostId':17536,'localJsVersion':VERSION,
            'localAssetSha256':hashlib.sha256(js).hexdigest(),
            'merchantRequests':0,'syntheticClicks':0,'wordpressWrites':0,
            'ga4EventsDelivered':'NOT_MEASURED',
            'approvedPaidCommissions':'UNKNOWN'}

def fetch(url,limit):
    if url not in (URL,JS_URL):raise ValueError('EXACT_GET_URL_REQUIRED')
    req=Request(url,method='GET',headers={'Cache-Control':'no-cache,no-store',
        'User-Agent':'NaRybyWellnessRealClickWiringReadOnly/1.0'})
    with guarded_urlopen(req,timeout=25) as response:
        if response.status!=200 or response.geturl()!=url:
            raise ValueError('PUBLIC_URL_OR_STATUS_DRIFT')
        raw=response.read(limit+1)
    if len(raw)>limit:raise ValueError('PUBLIC_BYTES_TOO_LARGE')
    return raw

if __name__=='__main__':
    if sys.argv[1:]!=['--verify']:raise SystemExit('use --verify')
    print(json.dumps(verify(fetch(URL,2_000_000),fetch(JS_URL,65536)),
        ensure_ascii=False,sort_keys=True))
