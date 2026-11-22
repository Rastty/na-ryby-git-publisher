#!/usr/bin/env python3
"""Anonymous exact-URL public proof: 2023 archive and catfish guidance. No clicks."""
import json,sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen
BASE='https://na-ryby.eu/'
MARKER='data-nry-legacy-decision="v1"'
CSS='nry-gsc-revir-ris-paths-v1.css'
RIS='https://ris.rybsvaz.cz/seznam-reviru'
RULES='https://ris.rybsvaz.cz/rybarsky-rad'
ORLIK='https://ris.rybsvaz.cz/karta-reviru/-/481501-vltava-16-19-udolni-nadrz-orlik'
HOW=BASE+'jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/'
SHORE=BASE+'kde-najit-nejlepsi-reviry-pro-lov-sumcu-ze-brehu-v-ceske-republice/'
STAYS=BASE+'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
SONAR=BASE+'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'
PAGES={
 'sumec':dict(url=BASE+'seznam-nejlepsich-reviru-pro-lov-sumce-na-polozenou-v-cesku/',
  old=('Lužnice','Sázava','Pilín','Vápno','Orlická přehrada'),
  required=('Orlická přehrada leží na Vltavě','Nejdřív přesný revír'),
  links=(ORLIK,RIS,RULES,SHORE,STAYS,SONAR)),
 'rules2023':dict(url=BASE+'rybarsky-rad-2023/',
  old=('2022','2023','99/2004','197/2004'),
  required=('Text níže platil pro roky 2022–2023','historické znění'),
  links=(RULES,RIS,HOW))}
class Page(HTMLParser):
 def __init__(self):
  super().__init__(convert_charrefs=True)
  self.canonicals=[];self.styles=[];self.roles=[];self.links=[]
  self.inside=0;self.words=[];self.old_words=[]
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='link':
   if 'canonical' in (a.get('rel') or '').split():self.canonicals.append(a.get('href'))
   if CSS in (a.get('href') or ''):self.styles.append(a.get('href'))
  if tag=='section' and a.get('data-nry-legacy-decision')=='v1':
   self.roles.append(a.get('data-nry-legacy-role'));self.inside=1
  elif tag=='section' and self.inside:self.inside+=1
  if tag=='a' and self.inside:self.links.append(a.get('href'))
 def handle_endtag(self,tag):
  if tag=='section' and self.inside:self.inside-=1
 def handle_data(self,data):
  self.words.append(data)
  if not self.inside:self.old_words.append(data)
 def text(self,old=False):return ' '.join(' '.join(self.old_words if old else self.words).split())
def verify(html,role):
 if role not in PAGES or not isinstance(html,str) or not 2200<len(html)<2000000:
  raise ValueError('BOUNDED_ROLE_HTML_REQUIRED')
 cfg=PAGES[role];p=Page();p.feed(html);p.close()
 if p.canonicals!=[cfg['url']]:raise ValueError('CANONICAL_DRIFT')
 for old in cfg['old']:
  if old not in p.text(old=True):raise ValueError('ORIGINAL_TEXT_DRIFT')
 if html.count(MARKER)!=1 or p.roles!=[role]:
  raise ValueError('EXACT_ROLE_MARKER_REQUIRED')
 if len(p.styles)!=1 or 'ver=1.0.0' not in p.styles[0]:
  raise ValueError('SHARED_STYLESHEET_VERSION_DRIFT')
 if p.links!=list(cfg['links']):raise ValueError('FIRST_PARTY_OR_OFFICIAL_LINKS_CHANGED')
 for phrase in cfg['required']:
  if phrase not in p.text():raise ValueError('EDITORIAL_CORRECTION_MISSING')
 if html.find(MARKER)>html.find(cfg['old'][0]):raise ValueError('LEGACY_SHOWN_BEFORE_CORRECTION')
 return {'role':role,'state':'NRY_LEGACY_2023_DECISION_PUBLIC_VERIFIED',
  'originalPreserved':True,'newLinks':len(p.links),'marker':1,
  'wordPressWrites':0,'merchantRequests':0,'syntheticClicks':0,
  'searchUplift':'UNKNOWN','paidCommission':'UNKNOWN'}
def get(url):
 if url not in [a['url'] for a in PAGES.values()]:raise ValueError('UNAPPROVED_URL')
 req=Request(url,method='GET',headers={'User-Agent':'NaRybyLegacySourceReadOnly/1.0',
  'Cache-Control':'no-cache,no-store'})
 with guarded_urlopen(req,timeout=25) as response:
  if response.status!=200 or response.geturl()!=url:raise ValueError('PUBLIC_ORIGIN_DRIFT')
  body=response.read(2000001)
 if len(body)>2000000:raise ValueError('EXCESS_PUBLIC_BYTES')
 return body.decode('utf-8','replace')
if __name__=='__main__':
 if sys.argv[1:]!=['--verify']:raise SystemExit('use --verify')
 rows=[verify(get(cfg['url']),role) for role,cfg in PAGES.items()]
 print(json.dumps({'state':'NRY_LEGACY_2023_TWO_PUBLIC_VERIFIED','roles':rows,
  'wordPressWrites':0,'merchantRequests':0,'syntheticClicks':0,
  'realClicks':'NOT_MEASURED','approvedPaidCommissions':'UNKNOWN'},ensure_ascii=False))
