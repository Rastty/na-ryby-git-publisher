#!/usr/bin/env python3
"""Independent public HTML trust proof for homepage 118641; GET-only."""
import json, re, time, urllib.request
from datetime import datetime, timezone

URL='https://na-ryby.eu/'
MARKER='data-nry-home-trust="source-bound-118641-20261109-v1"'
DRAFTS=('DOPLNIT-URL-', 'Vizuální placeholder', 'Tohle jsou URL,',
        'Zde budeme později', '<h3>Další tematické huby</h3>', '.....')

def verify(text):
    if text.count(MARKER)!=1:
        return 'MISSING_OR_DUPLICATED_RELEASE_MARKER'
    if any(x in text for x in DRAFTS):
        return 'DRAFT_OR_BROKEN_HOMEPAGE_REMAINS'
    urls=(
        '/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/',
        '/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',
        '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
        '/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'
    )
    if any(x not in text for x in urls):
        return 'BUYER_GUIDE_ROUTES_MISSING'
    return 'PUBLIC_VERIFIED_ALL'

def read():
    req=urllib.request.Request(URL,headers={
       'User-Agent':'NaRyby-Home-SourceBound-IndependentReadback/1.0',
       'Cache-Control':'no-cache','Pragma':'no-cache'})
    with urllib.request.urlopen(req,timeout=20) as resp:
       if resp.status!=200 or resp.geturl()!=URL:
           return 'WRONG_HTTP_OR_REDIRECT'
       return verify(resp.read(1500000).decode('utf-8','replace'))

if __name__=='__main__':
    status='NOT_READ'
    for i in range(5):
        try: status=read()
        except Exception as e: status='HTTP_READER_'+type(e).__name__
        if status=='PUBLIC_VERIFIED_ALL':break
        if i!=4:time.sleep(7)
    receipt={'checkedAtUtc':datetime.now(timezone.utc).isoformat(),
      'site':URL,'postId':118641,'status':status,
      'wordpressWrites':0,'affiliateRequests':0}
    print(json.dumps(receipt,ensure_ascii=False,sort_keys=True))
    raise SystemExit(0 if status=='PUBLIC_VERIFIED_ALL' else 1)
