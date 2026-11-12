#!/usr/bin/env python3
"""GET-only, 6 exact merchant product pages; candidate image metadata.
Research evidence only. Not a licence to use scraped media on our web.
No images downloaded/cached; no merchant clickouts or affiliate redirects.
"""
import json
import re
from html.parser import HTMLParser
from urllib.parse import urljoin,urlparse
from urllib.request import Request,urlopen
from pathlib import Path

URLS=[
 ("basic","https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-Basic-s-vyklopnikem-10Ah-baterie-cerna"),
 ("spark","https://www.chytapust.cz/Zavazeci-lodky-2165/Deeper-Zavazeci-lod-SPARK"),
 ("vulgps","https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-GPS-Cerny"),
 ("actorpro","https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-PRO-s-GPS-Echolotem-10Ah-baterie-Carbon"),
 ("mini","https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Mini-PRO-s-GPS-a-Echolot-Cerna"),
 ("vulcan","https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna")
]
class Media(HTMLParser):
 def __init__(self):
  super().__init__();self.meta=[];self.img=[];self.ld=[]
  self.in_jsonld=False;self.buffer=[]
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if tag=="meta" and (d.get("property") in ("og:image","og:image:secure_url") or d.get("name") in ("twitter:image","twitter:image:src")):
   self.meta.append((d.get("property") or d.get("name"),d.get("content","")))
  if tag=="img":
   self.img.append({k:d.get(k,"") for k in ("src","data-src","data-original","alt")})
  if tag=="script" and d.get("type")=="application/ld+json":
   self.in_jsonld=True;self.buffer=[]
 def handle_data(self,data):
  if self.in_jsonld:self.buffer.append(data)
 def handle_endtag(self,tag):
  if tag=="script" and self.in_jsonld:
   self.ld.append("".join(self.buffer));self.in_jsonld=False
def main():
 out=[]
 for item,url in URLS:
  req=Request(url,headers={"User-Agent":"Mozilla/5.0 NaRybyProductImageEvidence/1.0","Accept":"text/html"})
  try:
   with urlopen(req,timeout=25) as r:
    status=r.status
    body=r.read(1200000).decode("utf8","replace")
    final=r.geturl()
   parsed=Media();parsed.feed(body)
   ld=[]
   for raw in parsed.ld:
    try:
     data=json.loads(raw)
     stack=[data]
     while stack:
      v=stack.pop()
      if isinstance(v,dict):
       if v.get("@type")=="Product": ld.append({"name":v.get("name"),"image":v.get("image")})
       stack.extend(v.values())
      elif isinstance(v,list):stack.extend(v)
    except (ValueError,TypeError):pass
   raw=parsed.meta[0][1] if parsed.meta else None
   image=urljoin(url,raw) if raw else None
   image_host=urlparse(image).hostname if image else None
   out.append({"id":item,"http":status,"same_product":final==url,"og_image":image,
     "og_image_host":image_host,"og_count":len(parsed.meta),
     "product_image_jsonld":ld[:1],"img_candidates":parsed.img[:4],
     "usage_rights":"UNVERIFIED_NOT_AUTHORIZED_FROM_OG",
     "source":"merchant_public_product_html"})
  except Exception as e:
   out.append({"id":item,"status":"blocked_"+type(e).__name__,"usage_rights":"UNKNOWN"})
 print(json.dumps({"type":"PUBLIC_MERCHANT_IMAGE_CANDIDATES_ONLY","products":out,
   "image_downloads":0,"writes":0,"CJ_feed_entitlement":"NOT_VERIFIED",
   "publication_authorized":False},ensure_ascii=False))
if __name__=="__main__":main()
