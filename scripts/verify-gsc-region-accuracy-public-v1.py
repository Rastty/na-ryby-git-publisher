#!/usr/bin/env python3
"""Public GET-only evidence for 3 GSC-indexed regional geography corrections.

No WordPress/content writes, no merchant or affiliate clicks, no tracking events.
Pull requests prove legacy source is still visible without the new marker;
post-protected-release proof requires exact role/links, shared CSS and canonical.
"""
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = "https://na-ryby.eu/"
RIS = "https://ris.rybsvaz.cz/seznam-reviru"
STAYS = ROOT + "rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/"
CITY = "https://www.usti.cz/cz/volny-cas/turistum/turisticke-zajimavosti-cile/prirodni-zajimavosti/jezero-milada.html"
CANDAT = ROOT + "rybarske-reviry-kde-muzete-ulovit-candata-obecneho-v-cr/"
HRA_RULES = "https://www.crsplzen.cz/inpage/431-028-mze-4/"
HRA_DETAIL = ROOT + "bungalov-u-prehrady-hracholusky/"
WATERS = ROOT + "category/soukrome-rybarske-reviry/"
CSS = "nry-gsc-revir-ris-paths-v1.css"
MARKER = 'data-nry-region-accuracy="v1"'
CONFIG = {
 "usti": {
  "url": ROOT + "rybareni-v-usteckem-kraji-oblibene-reviry-a-rybi-bohatstvi/",
  "legacy": ("Povrlyský rybník", "Lambrovy rybníky", "Duchcovský rybník"),
  "new": ("Milada leží u Ústí nad Labem", "aktuální pravidla soukromého správce"),
  "links": (CITY,RIS,CANDAT,STAYS)
 },
 "west": {
  "url": ROOT + "3-kam-na-ryby-autem-v-zapadnich-cechach-5-oblibenych-lokalit/",
  "legacy": ("Lužická Nisa", "Pilsenský rybník", "Hracholusky"),
  "new": ("Lužická Nisa protéká Libereckým krajem", "Hracholusky – oficiální revír Mže 4"),
  "links": (HRA_RULES,RIS,HRA_DETAIL,STAYS)
 },
 "prague": {
  "url": ROOT + "kam-na-kapry-v-okoli-prahy-nejlepsi-reviry-pro-milovniky-rybareni/",
  "legacy": ("Ovčáry", "Štěchovice", "Hostivařská přehrada"),
  "new": ("Nejdřív ověřte povolenku, přístup a typ vody", "starší redakční inspirace z roku 2024"),
  "links": (RIS,WATERS,STAYS)
 }
}

class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.sections = []
        self.in_section = False
        self.depth = 0
        self.section_hrefs = []
        self.words = []
        self.counter = 0
        self.section_position = None
    def handle_starttag(self, tag, attrs):
        self.counter += 1
        a = dict(attrs)
        if tag == 'link':
            if 'canonical' in (a.get('rel') or '').lower().split():
                self.canonicals.append(a.get('href'))
            if CSS in (a.get('href') or ''):
                self.styles.append(a.get('href'))
        if tag == 'section' and a.get('data-nry-region-accuracy') == 'v1':
            self.sections.append(a.get('data-nry-region'))
            self.in_section = True
            self.depth = 1
            self.section_position = self.counter
        elif tag == 'section' and self.in_section:
            self.depth += 1
        if tag == 'a' and self.in_section:
            self.section_hrefs.append(a.get('href'))
    def handle_endtag(self, tag):
        if tag == 'section' and self.in_section:
            self.depth -= 1
            if self.depth <= 0:
                self.in_section = False
    def handle_data(self, data):
        self.words.append(data)
    def plain(self):
        return ' '.join(' '.join(self.words).split())

def verify(html,role,preflight=False):
    if role not in CONFIG:
        raise ValueError('ROLE_NOT_ALLOWED')
    if not isinstance(html,str) or len(html)<2200 or len(html)>2_000_000:
        raise ValueError('BOUNDED_HTML_REQUIRED')
    d=Document()
    d.feed(html)
    d.close()
    config=CONFIG[role]
    if d.canonicals != [config['url']]:
        raise ValueError('EXACT_CANONICAL_MISMATCH')
    plain=d.plain()
    for old in config['legacy']:
        if old not in plain:
            raise ValueError('ORIGINAL_EDITORIAL_BODY_NOT_PRESERVED')
    if preflight:
        if html.count(MARKER) or d.sections:
            raise ValueError('ALREADY_DEPLOYED_OR_DUPLICATE')
        return {"role":role,"status":"NRY_REGION_LEGACY_SOURCE_PUBLIC_PREFLIGHT"}
    if html.count(MARKER)!=1 or d.sections != [role]:
        raise ValueError('WRONG_VISIBLE_REGION_MARKER')
    if len(d.styles)!=1 or 'ver=1.0.0' not in d.styles[0]:
        raise ValueError('SHARED_ACCESSIBLE_CSS_MISSING_OR_DUPLICATE')
    if d.section_hrefs != list(config['links']):
        raise ValueError('REGION_OFFICIAL_OR_FIRST_PARTY_PATHS_CHANGED')
    for new in config['new']:
        if new not in plain:
            raise ValueError('REGIONAL_CORRECTION_MISSING')
    return {
        "role":role, "status":"NRY_REGION_GSC_ACCURACY_PUBLIC_VERIFIED",
        "original_visible":True,"marker_count":1,
        "approved_new_destinations":len(config['links']),
        "wordpress_writes":0,"merchant_requests":0,
        "tracking_clicks":0,"gsc_uplift":"NOT_MEASURED",
        "paid_commissions":"NOT_MEASURED"
    }

def get_public(url):
    req=Request(url,method='GET',headers={
       'User-Agent':'NaRybyRegionGSCReadOnlyPublic/1.0',
       'Accept':'text/html','Cache-Control':'no-cache'})
    with guarded_urlopen(req,timeout=20) as res:
        if res.status!=200 or res.geturl()!=url:
            raise ValueError('UNTRUSTED_RESPONSE_OR_REDIRECT')
        raw=res.read(2_000_001)
        if len(raw)>2_000_000:
            raise ValueError('PUBLIC_RESPONSE_TOO_LARGE')
    return raw.decode('utf-8','replace')

if __name__ == '__main__':
    if sys.argv[1:] not in (['--preflight'],['--verify']):
        raise SystemExit('use --preflight or --verify')
    preflight=sys.argv[1:] == ['--preflight']
    rows=[verify(get_public(v['url']),role,preflight=preflight)
          for role,v in CONFIG.items()]
    print(json.dumps({
      "status":"NRY_REGION_GSC_PREFLIGHT_ALL3_VERIFIED" if preflight else
         "NRY_REGION_GSC_ALL3_PUBLIC_VERIFIED",
      "routes":rows,
      "wordpress_writes":0,"merchant_requests":0,"synthetic_clicks":0,
      "causal_gsc_gain":"UNKNOWN","paid_cj_commissions":"UNKNOWN"
    },ensure_ascii=False,sort_keys=True))
