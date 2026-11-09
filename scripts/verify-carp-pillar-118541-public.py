#!/usr/bin/env python3
"""GET-only semantic readback of exact WordPress carp-rod pillar correction."""
import html, json, re, sys, time, urllib.request
from datetime import datetime, timezone
URL='https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'
MARKER='data-nry-carp-pillar-trust="118541-20261109-v1"'
BEFORE=('Interní rozcestník: kam pokračovat podle toho, co řešíte',
        'Tady je přesně místo, kde má pillar článek fungovat jako rozcestník celé tématické clustery.')
AFTER=('Kam pokračovat podle toho, co řešíte',
       'Vyberte si z navazujících průvodců jen téma, které potřebujete vyřešit.')
def read():
    req=urllib.request.Request(URL,headers={'User-Agent':'NRY-CarpPillar-Trust-Readback/1.0',
        'Cache-Control':'no-cache','Pragma':'no-cache'})
    with urllib.request.urlopen(req,timeout=20) as resp:
        if resp.status!=200 or resp.geturl()!=URL:
            raise RuntimeError('WRONG_HTTP_OR_REDIRECT')
        source=resp.read(1200000).decode('utf-8','replace')
    if source.count(MARKER)!=1:return 'MISSING_OR_MULTIPLE_MARKERS'
    if not re.search(r'<body\b',source,re.I):return 'NOT_WP_BODY'
    # Strip scripts/styles to avoid false matches from theme markup.
    visible=re.sub(r'<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>',
        ' ',source,flags=re.I|re.S)
    visible=html.unescape(re.sub(r'<[^>]+>',' ',visible))
    visible=' '.join(visible.split())
    if any(s in visible for s in BEFORE):return 'HISTORICAL_EDITOR_NOTES_STILL_VISIBLE'
    if any(s not in visible for s in AFTER):return 'MISSING_VISITOR_TEXT'
    if 'nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber' not in source:
        return 'BUYER_JOURNEY_LINK_MISSING'
    if 'chytapust.cz' not in source:return 'EXISTING_MERCHANT_ROUTE_NOT_VISIBLE'
    return 'PUBLIC_VERIFIED_ALL'
if __name__=='__main__':
    state='NOT_READ'
    for i in range(4):
        try:state=read()
        except Exception as e:state='READ_ERROR_'+type(e).__name__
        if state=='PUBLIC_VERIFIED_ALL':break
        if i<3:time.sleep(6)
    receipt={'site':URL,'postId':118541,'checkedAtUtc':datetime.now(timezone.utc).isoformat(),
             'status':state,'wordpressWrites':0,'merchantRequests':0}
    print(json.dumps(receipt,ensure_ascii=False,sort_keys=True))
    sys.exit(0 if state=='PUBLIC_VERIFIED_ALL' else 1)
