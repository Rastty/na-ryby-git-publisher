#!/usr/bin/env python3
"""Anonymous GET-only archive readback, no merchant requests or synthetic clicks."""
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL='https://na-ryby.eu/category/soukrome-rybarske-reviry/'
MARKER='data-nry-revir-archive-entry="v1"'
DESTS=(
 'https://na-ryby.eu/category/soukrome-rybarske-reviry/soukrome-rybarske-reviry-jihomoravsky-kraj/',
 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/',
)
CSS='nry-revir-archive-entry-v1.css'

class Archive(HTMLParser):
 def __init__(self):
  super().__init__(convert_charrefs=True)
  self.sections=0;self.in_section=False;self.links=[];self.styles=[];self.h1=0
  self.section_position=None;self.first_result_position=None;self.heading=None
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if tag=='h1':self.h1+=1
  if tag=='section' and d.get('data-nry-revir-archive-entry')=='v1':
   self.sections+=1;self.in_section=True
   if self.section_position is None:self.section_position=self.getpos()
  if tag in ('h2','h3') and self.heading is None:
   self.heading=[tag,self.getpos(),[]]
  if tag=='a' and self.in_section:self.links.append(d.get('href'))
  if tag=='link' and 'stylesheet' in (d.get('rel') or '').split() and CSS in (d.get('href') or ''):self.styles.append(d.get('href'))
 def handle_data(self,data):
  if self.heading is not None:self.heading[2].append(data)
 def handle_endtag(self,tag):
  if self.heading is not None and tag==self.heading[0]:
   text=''.join(self.heading[2])
   if 'Sportovní rybolov Rašelina' in text and self.first_result_position is None:
    self.first_result_position=self.heading[1]
   self.heading=None
  if tag=='section' and self.in_section:self.in_section=False

def check(html,deployed):
 p=Archive();p.feed(html);p.close()
 assert p.h1>=1 and 'Soukromé rybářské revíry' in html,'ARCHIVE_NOT_RECOGNIZED'
 if deployed:
  assert p.sections==1 and html.count(MARKER)==1,'ARCHIVE_ENTRY_NOT_EXACTLY_ONCE'
  assert p.links==list(DESTS),'ARCHIVE_DESTINATION_OR_ORDER_DRIFT'
  assert len(p.styles)==1 and CSS in p.styles[0],'ARCHIVE_CSS_NOT_ONCE'
  # The raw first occurrence may be an earlier <title>/JSON-LD string. Compare
  # only real HTML heading nodes in DOM order, not a textual substring.
  assert p.section_position is not None and p.first_result_position is not None and p.section_position<p.first_result_position,'ARCHIVE_ENTRY_NOT_BEFORE_RESULTS'
 else:
  assert p.sections in (0,1),'ARCHIVE_UNEXPECTED_DUPLICATE'
 return 'NRY_REVIR_ARCHIVE_ENTRY_PUBLIC_VERIFIED' if deployed else 'NRY_REVIR_ARCHIVE_BASELINE_OBSERVED'

if __name__=='__main__':
 assert len(sys.argv)==2 and sys.argv[1] in ('--baseline','--verify')
 request=Request(URL,method='GET',headers={'User-Agent':'NaRybyArchiveProof/1.0','Cache-Control':'no-cache'})
 with guarded_urlopen(request,timeout=20) as response:
  raw=response.read(1500001)
 assert len(raw)<=1500000,'ARCHIVE_OVERSIZED'
 print(check(raw.decode('utf-8','replace'),sys.argv[1]=='--verify'))
