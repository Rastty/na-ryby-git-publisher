#!/usr/bin/env python3
"""GET-only runtime proof of exact advisor guide-click instrument availability.
This never clicks a guide, sends analytics events or follows an affiliate URL.
"""
from html.parser import HTMLParser
from urllib.parse import urlsplit, parse_qsl
from urllib.request import Request, urlopen
import sys
from hashlib import sha256
from pathlib import Path

ROOT = 'https://na-ryby.eu'
PAGE = ROOT + '/poradce-vybavy/'
ASSET = ROOT + '/wp-content/plugins/na-ryby-git-publisher/assets/nry-advisor-guide-click-v1.js'

class ScriptTags(HTMLParser):
    def __init__(self):
        super().__init__()
        self.sources = []
    def handle_starttag(self,tag,attrs):
        if tag=='script':
            data=dict(attrs)
            if data.get('src'):
                self.sources.append(data['src'])

def verify_html(html):
    if not isinstance(html,str) or len(html)<1000 or len(html)>1_600_000:
        raise AssertionError('PUBLIC_ADVISOR_HTML_UNVERIFIED')
    p=ScriptTags()
    p.feed(html)
    acceptable=[]
    for raw in p.sources:
        u=urlsplit(raw)
        if (u.scheme=='https' and u.netloc=='na-ryby.eu'
            and u.path==urlsplit(ASSET).path
            and not u.fragment
            and parse_qsl(u.query,keep_blank_values=True)==[('ver','1.1.0')]):
            acceptable.append(raw)
    if len(acceptable)!=1:
        raise AssertionError('EXACT_ONE_ADVISOR_GUIDE_SCRIPT_REQUIRED')
    if 'data-nry-results' not in html or 'nry-setup' not in html:
        raise AssertionError('ADVISOR_ENGINE_MARKERS_MISSING')
    return 'ONE_EXACT_INTERNAL_GUIDE_SCRIPT_OBSERVED'

def verify_js(js):
    if not isinstance(js,str) or not 800<len(js)<9000:
        raise AssertionError('GUIDE_JS_SIZE_UNVERIFIED')
    must=(
        "nry_advisor_guide_click",
        "event?.isTrusted !== true",
        "destination_kind: 'internal_buying_guide'",
        "guide_type: guideType",
        "/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/",
        "/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/",
        "/nejlepsi-privlacove-navijaky-jak-vybrat/",
        "/nejlepsi-feederove-navijaky-srovnani/",
        "Feederové navijáky: praktické srovnání"
    )
    if not all(s in js for s in must):
        raise AssertionError('APPROVED_STATIC_GUIDE_JS_NOT_VERIFIED')
    for forbidden in ('fetch(', 'sendBeacon(', 'XMLHttpRequest', 'document.cookie', 'localStorage', 'preventDefault('):
        if forbidden in js:
            raise AssertionError('UNAPPROVED_TRANSPORT_OR_NAVIGATION_CHANGE')
    return 'GUIDE_JS_STATIC_CONTRACT_VERIFIED'

def get_text(url,limit):
    req=Request(url,headers={'Accept':'text/html,application/javascript,*/*',
         'User-Agent':'NaRybyGuideHandoffPublicAudit/1.0','Cache-Control':'no-cache'})
    with urlopen(req,timeout=20) as resp:
        if resp.status!=200 or resp.geturl()!=url:
            raise AssertionError('PUBLIC_HTTP_REDIRECT_OR_FAILURE')
        raw=resp.read(limit+1)
        if len(raw)>limit:
            raise AssertionError('PUBLIC_BODY_OVERSIZE')
        return raw.decode('utf-8')

if __name__=='__main__':
    if '--selftest' in sys.argv:
        page=('<html><body><section class="nry-setup"><div data-nry-results></div></section>'
          +('<p>Poradce rybářské výbavy.</p>'*120)
          +'<script src="'+ASSET+'?ver=1.1.0"></script></body></html>')
        assert verify_html(page)=='ONE_EXACT_INTERNAL_GUIDE_SCRIPT_OBSERVED'
        try:
            verify_html(page.replace('nry-advisor-guide-click-v1.js','wrong.js'))
        except AssertionError:
            pass
        else:
            raise AssertionError('FALSE_ASSET_ACCEPTED')
        print('PASS: advisor exact script and page markers verified offline')
    else:
        assert verify_html(get_text(PAGE,1_600_000))=='ONE_EXACT_INTERNAL_GUIDE_SCRIPT_OBSERVED'
        public_asset = get_text(ASSET+'?ver=1.1.0',9000)
        assert verify_js(public_asset)=='GUIDE_JS_STATIC_CONTRACT_VERIFIED'
        source_path = Path(__file__).resolve().parents[1]/'assets/nry-advisor-guide-click-v1.js'
        if sha256(public_asset.encode('utf-8')).digest() != sha256(source_path.read_bytes()).digest():
            raise AssertionError('PUBLIC_GUIDE_JS_NOT_EXACT_RELEASE_BYTES')
        print('PUBLIC_VERIFIED_ADVISOR_GUIDE_HANDOFF_SCRIPT')
        print('No GA4 event delivery, actual visitor clicks or commissions were tested.')
