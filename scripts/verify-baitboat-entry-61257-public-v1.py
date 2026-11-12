#!/usr/bin/env python3
"""Independent public GET-only byte/semantic proof for post 61257 baitboat handoff."""
import json
import re
import sys
from html.parser import HTMLParser
from urllib.request import Request,urlopen
from urllib.parse import urlencode
from urllib.error import HTTPError,URLError

BASE="https://na-ryby.eu"
PAGE=BASE+"/zavazeci-lodky-pro-rybare/"
TARGET=BASE+"/jak-vybrat-zavazeci-lodku/"
API=BASE+"/wp-json/wp/v2/posts?"+urlencode({"slug":"zavazeci-lodky-pro-rybare","_fields":"id,slug,modified_gmt,status,link"})
MARKER='data-nry-baitboat-choice-link="v1"'
MODIFIED="2026-05-13T10:22:17"

def get(url,limit):
 req=Request(url,headers={"User-Agent":"NaRyby-Baitboat-Entry-IndependentGET/1.0","Cache-Control":"no-cache,no-store"})
 with urlopen(req,timeout=35) as response:
  if response.status!=200 or response.geturl()!=url:raise RuntimeError("redirect_or_status")
  raw=response.read(limit+1)
  if len(raw)>limit:raise RuntimeError("response_oversize")
  return raw.decode("utf-8","replace")

class Doc(HTMLParser):
 def __init__(self):
  super().__init__()
  self.hrefs=[]
  self.canonicals=[]
  self.markers=0
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if tag=="a":self.hrefs.append(d.get("href",""))
  if tag=="link" and "canonical" in (d.get("rel","")).split():self.canonicals.append(d.get("href"))
  if d.get("data-nry-baitboat-choice-link")=="v1":self.markers+=1

def main():
 if len(sys.argv)!=2 or sys.argv[1] not in ("--preflight","--verify"):
  raise SystemExit("requires --preflight or --verify")
 mode=sys.argv[1]
 src=get(PAGE,1000000)
 d=Doc();d.feed(src)
 if d.canonicals!=[PAGE] or len(src)<10000 or "Boatman Actor Plus Basic" not in src or "Deeper QUEST" not in src:
  raise SystemExit("BLOCKED: unexpected public source/SEO article identity")
 rows=json.loads(get(API,8000))
 if not isinstance(rows,list) or len(rows)!=1:raise SystemExit("BLOCKED: public WP source ambiguous")
 p=rows[0]
 if (p.get("id")!=61257 or p.get("slug")!="zavazeci-lodky-pro-rybare"
  or p.get("modified_gmt")!=MODIFIED or p.get("status")!="publish"
  or p.get("link")!=PAGE):
  raise SystemExit("BLOCKED: exact post 61257 source drift")
 links=d.hrefs.count(TARGET)
 if mode=="--preflight":
  if links!=0 or d.markers!=0:raise SystemExit("BLOCKED: unexpected pre-release visitor link")
 else:
  if links!=1 or d.markers!=1:raise SystemExit("BLOCKED: new handoff not visible exactly once")
 print(json.dumps({"status":"SOURCE_PREFLIGHT_VERIFIED" if mode=="--preflight" else "PUBLIC_VERIFIED_EXACT_SINGLE_HANDOFF",
     "page":PAGE,"post_id":61257,"source_modified_gmt":MODIFIED,"link_count":links,
     "marker_count":d.markers,"external_writes":0,"merchant_requests":0,"tracking_events":0},sort_keys=True))
if __name__=="__main__":main()
