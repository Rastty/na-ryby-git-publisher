#!/usr/bin/env python3
"""One exact publicly rendered WordPress guide; GET-only/no affiliate redirects."""
import json,re,time,urllib.request
from datetime import datetime,timezone

URL='https://na-ryby.eu/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/'
MARKER='data-nry-receiver-guide="source-bound-46573-20261109-v1"'
NEEDED=(
 'Hlásiče záběru s příposlechem: jak vybrat kompatibilní sadu',
 'Jak funguje hlásič a bezdrátový příposlech',
 'Jak vybrat sadu 2+1 nebo 3+1',
 'Příposlech zvuk mechanicky nezesiluje',
 'Kontrolní seznam před výběrem příposlechu',
 '/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'
)
PRESERVE=(
 'https://na-ryby.eu/signalizatory',
 'https://www.rybarskapotreby.cz/pipaky-na-rybi-pruty',
 'Screenshot_794.png','Screenshot_792.png','Screenshot_793.png',
 'raoul-croes-G-7b3R2aPXA-unsplash.jpg',
 'https://www.youtube.com/embed/riCbqznY77Y',
 'https://na-ryby.eu/category/vybaveni-pro-rybare/'
)
OLD_HEAD='<h2>Pípáky na ryby s příposlechem – účinná pomůcka pro každého rybáře</h2>'
def verify(html):
    if html.count(MARKER)!=1:
        return 'RECEIVER_RELEASE_MARKER_MISSING_OR_DUPLICATE'
    if any(part not in html for part in NEEDED):
        return 'BUYER_DECISION_CONTENT_NOT_PUBLIC'
    if any(part not in html for part in PRESERVE):
        return 'ORIGINAL_MEDIA_OR_LINK_NOT_PUBLIC'
    if OLD_HEAD in html or 'S použitím pípáků s příposlechem můžete být o několik kroků' in html:
        return 'REPEATED_OR_UNSUPPORTED_SOURCE_TEXT_REMAINS'
    if len(re.findall(r'<h2\b',html,re.I))<4:
        return 'RENDERED_HEADINGS_INCOMPLETE'
    return 'PUBLIC_VERIFIED_RECEIVER_GUIDE'
def read():
    req=urllib.request.Request(URL,headers={
        'User-Agent':'NaRyby-46573-IndependentReadback/1.0',
        'Cache-Control':'no-cache','Pragma':'no-cache'})
    with urllib.request.urlopen(req,timeout=18) as response:
        if response.status!=200 or response.geturl()!=URL:
            return 'HTTP_OR_CANONICAL_DRIFT'
        b=response.read(1_500_001)
        if len(b)>1_500_000:return 'PUBLIC_BODY_OVERSIZED'
        return verify(b.decode('utf8','replace'))
if __name__=='__main__':
    verdict='NO_READ'
    for attempt in range(5):
        try:verdict=read()
        except Exception as ex:verdict='HTTP_'+type(ex).__name__
        if verdict=='PUBLIC_VERIFIED_RECEIVER_GUIDE':break
        if attempt<4:time.sleep(6)
    print(json.dumps({'site':'na-ryby.eu','postId':46573,'checkedAtUtc':datetime.now(timezone.utc).isoformat(),
        'status':verdict,'publicReadOnly':True,'affiliateRequests':0,
        'wordpressWritesByVerifier':0,'incrementalEarnings':'UNKNOWN'},ensure_ascii=False))
    raise SystemExit(0 if verdict=='PUBLIC_VERIFIED_RECEIVER_GUIDE' else 1)
