#!/usr/bin/env python3
"""Read-only integrity preflight for 6 merchant image URLs. No image copying."""
import json
from pathlib import Path
from urllib.request import Request,urlopen
from urllib.error import HTTPError,URLError

data=json.loads(Path("assets/baitboat-photo-provenance-v1.json").read_text())
assert len(data["products"])==6
statuses=[]
for item in data["products"]:
 url=item["imageUrl"]
 assert url.startswith("https://i00.eu/img/202/1024x1024/")
 try:
  req=Request(url,method="HEAD",headers={"User-Agent":"Mozilla/5.0 NaRybyPhotoHealth/1.0"})
  with urlopen(req,timeout=20) as response:
   status=response.status
   ctype=response.headers.get("Content-Type","").split(";")[0]
   n=response.headers.get("Content-Length","")
  if status!=200 or ctype not in ("image/jpeg","image/jpg") or (n and int(n)<15000):
   raise RuntimeError("invalid_status_or_type_or_size")
  statuses.append({"id":item["id"],"http":status,"mime":ctype,"size":int(n) if n else None})
 except Exception as exc:
  statuses.append({"id":item["id"],"blocked":type(exc).__name__})
print(json.dumps({"source":"merchant_product_image_CDN_HEAD_ONLY","filesDownloaded":0,
                   "licenseVerified":False,"results":statuses},sort_keys=True))
if len(statuses)!=6 or any(x.get("http")!=200 for x in statuses):
 raise SystemExit("BLOCKED: merchant image URL head or type problem")
