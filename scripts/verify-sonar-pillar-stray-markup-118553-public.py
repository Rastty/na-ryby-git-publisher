#!/usr/bin/env python3
"""Public GET-only proof: remove one stray paragraph, never click a merchant."""
import re,sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen
URL='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'
MARK='<!-- nry-sonar-stray-markup-cleaned-118553-v1 -->'
SOURCE='data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
PRACTICAL='data-nry-sonar-owned-practical="v1"'
NEUTRAL='data-nry-sonar-merchant-neutral="v1"'
CTA='Mrknout na dostupnost'
HEADING='Interní rozcestník: kam pokračovat podle toho, co řešíte dál'
BAD=re.compile(r'<p\b[^>]*>\s*(?:„|&bdquo;|&#8222;)\s*(?:\x60|&#96;)\s*</p>',re.I)
class Canonical(HTMLParser):
 def __init__(self):super().__init__();self.links=[]
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if tag=='link' and 'canonical' in (d.get('rel') or ''):self.links.append(d.get('href'))
def verify(html,after):
 assert len(html)>15000,'PAGE_UNAVAILABLE'
 p=Canonical();p.feed(html)
 assert p.links==[URL],'CANONICAL_DRIFT'
 assert html.count(SOURCE)==html.count(PRACTICAL)==html.count(NEUTRAL)==1,'PREVIOUS_RELEASE_DRIFT'
 assert html.count(CTA)==1 and html.count(HEADING)==1,'SOURCE_ANCHOR_DRIFT'
 assert 'www.kqzyfj.com' in html and 'www.chytapust.cz' in html,'SHOP_CTA_DRIFT'
 assert 'Tady aktuálně dává smysl širší a přehlednější nabídka' not in html,'PRIOR_CORRECTION_MISSING'
 matches=list(BAD.finditer(html))
 if after:
  assert html.count(MARK)==1 and len(matches)==0,'NOT_PUBLICLY_CLEANED'
 else:
  assert MARK not in html and len(matches)==1,'NOT_EXACT_PUBLIC_BASELINE'
  m=matches[0]
  assert html.find(CTA)<m.start()<m.end()<html.find(HEADING),'WRONG_LOCATION'
  assert m.start()-html.find(CTA)<900 and html.find(HEADING)-m.end()<350,'SOURCE_DISTANCE_DRIFT'
 return {'state':'PUBLIC_VERIFIED' if after else 'PUBLIC_PREFLIGHT_VERIFIED',
  'post_id':118553,'stray_paragraph_removed':after,'wordpress_writes':0,'merchant_clicks':0}
if __name__=='__main__':
 mode=sys.argv[1] if len(sys.argv)>1 else '--verify'
 assert mode in ('--verify','--preflight')
 for attempt in range(3):
  try:
   req=Request(URL,method='GET',headers={'User-Agent':'NaRyby-StrayMarkup-ReadOnly/1.0',
    'Cache-Control':'no-cache, no-store'})
   with guarded_urlopen(req,timeout=25) as r:
    assert r.status==200 and r.geturl()==URL
    html=r.read(1200001).decode('utf-8','replace')
   assert len(html)<1200000
   print(verify(html,mode=='--verify'));sys.exit(0)
  except Exception as e:
   print('PUBLIC_RETRY',attempt+1,str(e)[:100] if isinstance(e,AssertionError)
    else type(e).__name__,flush=True)
 sys.exit('BLOCKED: stray markup exact public status not proven')
