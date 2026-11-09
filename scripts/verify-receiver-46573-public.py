#!/usr/bin/env python3
"""Independent two-surface proof: full saved WordPress SHA and public buyer HTML.
Only first-party GETs; no affiliate clickouts, no WordPress writes or user IDs.
"""
import html, json, re, sys, time, urllib.request, urllib.error
from datetime import datetime, timezone
SITE='https://na-ryby.eu'
PATH='/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/'
PAGE=SITE+PATH
PROOF=SITE+'/poradce-vybavy/?nry_receiver_46573_readback=1'
MARKER='data-nry-buyer-guide="receiver-46573-problem-first-20261109-v1"'
NEEDED=(
 'Potřebuji pípák s příposlechem, nebo stačí samostatný hlásič?',
 'Jak funguje signalizátor záběru s příposlechem?',
 'Sedm kontrol před koupí sady s příposlechem',
 'Jak sestavu bezpečně nastavit a otestovat',
 'Než zaplatíte:',
)
LINKS=(
 '/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/',
 '/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/',
 '/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/',
)
FORBIDDEN=(
 'pípáky na ryby s příposlechem fungují na principu zesílení zvuku při záběru',
 'zvýšit počet ulovených ryb',
 'připravená na záběr',
 'a je na čase začít vláčet svou kořist',
)
def request(url):
    req=urllib.request.Request(url,headers={'User-Agent':'NaRyby-Receiver-46573-Buyer-Readback/1.0',
        'Accept':'text/html, application/json','Cache-Control':'no-cache','Pragma':'no-cache'})
    with urllib.request.urlopen(req,timeout=20) as r:
        if r.status!=200 or r.geturl()!=url:
            raise RuntimeError('HTTP_OR_CANONICAL_REDIRECT')
        return r.read(1600000).decode('utf-8','replace')
def check(page,proof):
    if page.count(MARKER)!=1:return 'MISSING_UNIQUE_RELEASE_MARKER'
    plain=re.sub(r'<script\\b[^>]*>[\\s\\S]*?</script>|<style\\b[^>]*>[\\s\\S]*?</style>',' ',page,flags=re.I)
    plain=html.unescape(re.sub(r'<[^>]+>',' ',plain))
    plain=' '.join(plain.split())
    if not all(v in plain for v in NEEDED):return 'READER_DECISION_CONTENT_MISSING'
    if any(v in plain.lower() for v in FORBIDDEN):return 'LEGACY_FALSE_CLAIM_STILL_VISIBLE'
    if any(v not in page for v in LINKS):return 'BUYER_CLUSTER_LINK_MISSING'
    if proof.get('schema')!=1 or proof.get('postId')!=46573        or proof.get('canonical')!=PAGE:return 'READBACK_IDENTITY_DRIFT'
    if proof.get('state')!='APPLIED_AND_VERIFIED_LOCAL'        or proof.get('savedCandidateMatches') is not True        or proof.get('backupVerified') is not True:return 'WORDPRESS_SAVED_SHA_OR_BACKUP_NOT_VERIFIED'
    if proof.get('wordpressWrites')!=0 or proof.get('rawSourceExposed') is not False:
        return 'UNSAFE_READBACK_CONTRACT'
    return 'PUBLIC_VERIFIED_ALL'
if __name__=='__main__':
    status='NOT_READ'
    for attempt in range(4):
        try:
            page=request(PAGE)
            proof=json.loads(request(PROOF))
            status=check(page,proof)
        except Exception as e:
            status='GET_UNAVAILABLE_'+type(e).__name__
        if status=='PUBLIC_VERIFIED_ALL':break
        if attempt!=3:time.sleep(7)
    result={'checkedAtUtc':datetime.now(timezone.utc).isoformat(),'postId':46573,
        'canonical':PAGE,'status':status,'readOnly':True,
        'wordpressWrites':0,'affiliateClicks':0,'commissionAttribution':'UNKNOWN'}
    print(json.dumps(result,ensure_ascii=False,sort_keys=True))
    sys.exit(0 if status=='PUBLIC_VERIFIED_ALL' else 1)
