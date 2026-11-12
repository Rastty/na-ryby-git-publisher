#!/usr/bin/env python3
"""Independent anonymous GET-only readback of reviewed baitboat landing.
No WordPress mutations, merchant requests, authentication or private data.
"""
import hashlib
import json
import pathlib
import re
import urllib.request
import urllib.error
from html.parser import HTMLParser

URL = "https://na-ryby.eu/jak-vybrat-zavazeci-lodku/"
FILE = pathlib.Path("assets/baitboat-picker-v1.html")

class HeadLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical=[]
        self.merchant=[]
    def handle_starttag(self, tag, attrs):
        d=dict(attrs)
        if tag=="link" and "canonical" in (d.get("rel","")).split():
            self.canonical.append(d.get("href"))
        if tag=="a" and (d.get("href") or "").startswith("https://www.chytapust.cz/"):
            self.merchant.append((d["href"], d.get("rel","")))

def main():
    reviewed=FILE.read_bytes()
    expected=hashlib.sha256(reviewed).hexdigest()
    req=urllib.request.Request(URL,method="GET",headers={
      "User-Agent":"NaRyby-Independent-Baitboat-Readback/1.0",
      "Cache-Control":"no-cache,no-store",
      "Accept":"text/html"
    })
    try:
        with urllib.request.urlopen(req,timeout=30) as response:
            status=response.status
            landing=response.geturl()
            sha_header=response.headers.get("X-NRY-Baitboat-Asset-SHA256","")
            ctype=response.headers.get("Content-Type","")
            body=response.read(110000)
    except urllib.error.HTTPError as exc:
        raise SystemExit("LIVE_NOT_VERIFIED: HTTP_"+str(exc.code)+" (admin Apply or deploy may be pending)")
    except Exception as exc:
        raise SystemExit("LIVE_NOT_VERIFIED: "+type(exc).__name__)
    if status!=200 or landing!=URL or "text/html" not in ctype:
        raise SystemExit("BAD_LIVE_RESPONSE: status/URL/type mismatch")
    if len(body)>100000 or len(body)<15000:
        raise SystemExit("BAD_LIVE_SIZE")
    body_hash=hashlib.sha256(body).hexdigest()
    if sha_header!=expected or body_hash!=expected:
        raise SystemExit("LIVE_BYTES_OR_APPROVAL_SHA_MISMATCH (rollback and review before traffic)")
    html=body.decode("utf-8")
    if html.count('data-nry-baitboat-rc="v1"')!=1:
        raise SystemExit("BAD_MARKER_COUNT")
    for id in ("budget","range","gps","sonar","load","result-link"):
        if ('id="'+id+'"') not in html:
            raise SystemExit("MISSING_UI_CONTROL_"+id)
    parsed=HeadLinks()
    parsed.feed(html)
    if parsed.canonical != [URL]:
        raise SystemExit("BAD_CANONICAL")
    uniq={href for href,_ in parsed.merchant}
    if len(uniq)!=6 or len(parsed.merchant)<12:
        raise SystemExit("BAD_MERCHANT_LINK_COUNT")
    for link,rel in parsed.merchant:
        if "sponsored" not in rel.split() or "nofollow" not in rel.split():
            raise SystemExit("UNLABELLED_MERCHANT_LINK")
        if any(p in link for p in ("?a_box=","cid=","utm_source=chatgpt")):
            raise SystemExit("UNREVIEWED_TRACKING_PARAM")
    print(json.dumps({
      "status":"PUBLIC_VERIFIED_EXACT_APPROVED_BYTES",
      "url":URL,"http":status,
      "asset_sha256":expected,
      "canonical":parsed.canonical[0],
      "merchant_unique_product_links":len(uniq),
      "product_cta_count":len(parsed.merchant),
      "wordpress_writes":0,
      "merchant_requests":0,
      "note":"Product destination HTTP status and affiliate tracking NOT verified"
    },ensure_ascii=False,sort_keys=True))

if __name__=="__main__":
    main()
