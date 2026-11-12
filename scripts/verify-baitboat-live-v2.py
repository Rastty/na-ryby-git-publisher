#!/usr/bin/env python3
"""Exact public readback of baitboat V1 or V2; GET-only; no merchant calls."""
import argparse
import hashlib
import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError

URL="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/"
ASSETS={"v1":Path("assets/baitboat-picker-v1.html"),
        "v2":Path("assets/baitboat-picker-v2.html"),
        "v3":Path("assets/baitboat-picker-v3.html")}
HEADER="X-NRY-Baitboat-Asset-SHA256"

class PageLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical=[]
        self.merchant_links=[]
        self.ids=set()
    def handle_starttag(self,tag,attrs):
        d=dict(attrs)
        if tag=="link" and "canonical" in (d.get("rel","")).split():
            self.canonical.append(d.get("href"))
        if tag=="a" and (d.get("href","").startswith("https://www.chytapust.cz/")):
            self.merchant_links.append((d["href"],d.get("rel","")))
        if "id" in d:
            self.ids.add(d["id"])

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument("--expect",choices=["either","v1","v2","v3"],default="either")
    args=ap.parse_args()
    payload={}
    for version,file in ASSETS.items():
        b=file.read_bytes()
        payload[version]={"bytes":b,"sha":hashlib.sha256(b).hexdigest()}
    req=Request(URL,method="GET",headers={
      "User-Agent":"NaRyby-Baitboat-Independently-Verified-HTTP/2.0",
      "Cache-Control":"no-cache,no-store",
      "Accept":"text/html"})
    try:
        with urlopen(req,timeout=35) as resp:
            if resp.status!=200 or resp.geturl()!=URL:
                raise RuntimeError("unexpected status or redirect")
            header=resp.headers.get(HEADER,"")
            ctype=resp.headers.get("Content-Type","")
            body=resp.read(110000)
    except (OSError,URLError,HTTPError,RuntimeError) as exc:
        raise SystemExit("PUBLIC_READBACK_BLOCKED:"+type(exc).__name__)
    actual=hashlib.sha256(body).hexdigest()
    chosen=[k for k,v in payload.items() if actual==v["sha"]]
    if len(chosen)!=1:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: content not byte-identical to reviewed V1 or V2")
    version=chosen[0]
    if args.expect!="either" and args.expect!=version:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: wanted "+args.expect+", observed "+version)
    if header!=actual or "text/html" not in ctype:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: approval SHA header or MIME mismatch")
    html=body.decode("utf8")
    if html.count('data-nry-baitboat-rc="'+version+'"')!=1:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: marker mismatch")
    links=PageLinks()
    links.feed(html)
    if links.canonical!=[URL]:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: canonical mismatch")
    if not {"budget","range","gps","sonar","load","result-link"}.issubset(links.ids):
        raise SystemExit("PUBLIC_READBACK_BLOCKED: controls missing")
    distinct={u for u,_ in links.merchant_links}
    if len(distinct)<6 or len(links.merchant_links)<12:
        raise SystemExit("PUBLIC_READBACK_BLOCKED: merchant links absent")
    for url,rel in links.merchant_links:
        if "nofollow" not in rel.split() or "sponsored" not in rel.split():
            raise SystemExit("PUBLIC_READBACK_BLOCKED: rel not explicit")
    if version in ("v2","v3") and not {"has-adapter","result-breakdown","result-availability","result-addon-links"}.issubset(links.ids):
        raise SystemExit("PUBLIC_READBACK_BLOCKED: V2 kit controls missing")
    if version=="v3":
        if html.count('class="merchant-photo"')!=6 or "https://i00.eu/img/202/1024x1024/" not in html:
            raise SystemExit("PUBLIC_READBACK_BLOCKED: missing or unverified photo markup")
        if len(set(re.findall(r'https://i00\.eu/img/202/1024x1024/[^" ]+',html)))<6:
            raise SystemExit("PUBLIC_READBACK_BLOCKED: six unique photo URLs missing")
    print(json.dumps({"status":"PUBLIC_VERIFIED_EXACT_ASSET",
      "observed_version":version,"wanted_version":args.expect,"http":200,
      "asset_sha256":actual,"product_destinations":len(distinct),
      "merchant_requests":0,"wordpress_writes":0,
      "revenue_attributed":"UNKNOWN_NOT_VERIFIED",
      "image_rights":"NOT_VERIFIED",
      "note":"PASS does not imply fresh inventory, CJ tracking, or advertiser PPC permission"
    },sort_keys=True,ensure_ascii=False))
if __name__=="__main__":
    main()
