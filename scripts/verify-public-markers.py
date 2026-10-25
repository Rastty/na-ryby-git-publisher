#!/usr/bin/env python3
"""Read-only public page verification. No WordPress credentials, no writes."""
import json
import urllib.request
import urllib.error
from datetime import datetime, timezone
from concurrent.futures import ThreadPoolExecutor

PAGES = [
    ("34856", "https://na-ryby.eu/nejlepsi-prut-na-privlac-pro-zacatecniky-v-ceske-republice/", "privlac-rozhodovac-34856-20261025-v1", "previous_live_control"),
    ("34883", "https://na-ryby.eu/kaprarsky-prut-jak-si-vybrat-ten-nejlepsi-pro-rybolov/", "kaprovy-prut-rozhodovac-34883-20261025-v1", "previous_live_control"),
    ("50011", "https://na-ryby.eu/nejlepsi-kaprove-pruty-pro-zacatecniky-recenze-a-doporuceni-pro-rok-2020/", "kaprove-pruty-2020-50011-archive-clarity-v1", "new_v5"),
    ("53630", "https://na-ryby.eu/jak-vybrat-ten-spravny-ruzovy-prut-na-ryby/", "ruzovy-prut-53630-skutecny-vyber-v1", "new_v5"),
    ("34882", "https://na-ryby.eu/jak-vybrat-nejlepsi-feederove-kreslo-pro-rybolov/", "feeder-chair-compatibility-34882-v1", "new_v6"),
    ("34905", "https://na-ryby.eu/rybarske-kreslo-s-nosnosti-200-kg-pro-nadsence-lovu-na-tezkou-vahu/", "chair-200kg-rated-load-34905-v1", "new_v6"),
    ("34921", "https://na-ryby.eu/jak-vybrat-nejlepsi-rybarske-kreslo-s-podnozkou-pro-pohodlny-rybolov/", "chair-footrest-choice-34921-v1", "new_v6"),
    ("35026", "https://na-ryby.eu/vyhrivane-rybarske-kreslo-pro-zimni-rybolov/", "heated-chair-battery-safety-35026-v1", "new_v6"),
]

def get(url):
    req = urllib.request.Request(url, headers={
        "User-Agent": "NaRyby-PublicVerification/1.0 (+https://na-ryby.eu/)",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
    })
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            html = resp.read(800000).decode("utf-8", "replace")
            return {"http": resp.status, "url":resp.geturl(), "html":html, "age":resp.headers.get("Age"), "cache":resp.headers.get("X-Cache")}
    except (urllib.error.URLError, TimeoutError) as exc:
        return {"http": 0, "error": str(exc)[:200], "html": ""}

def inspect(item):
    post_id, url, marker, role=item
    resp=get(url)
    html=resp.pop("html","")
    return {"post_id":post_id,"role":role,"expected_marker":marker,
        "http":resp.get("http",0),
        "marker_seen":('data-nry-editorial-edit="'+marker+'"') in html,
        "cache_age":resp.get("age"),"cache_header":resp.get("cache"),
        "request_error":resp.get("error")}

def main():
    with ThreadPoolExecutor(max_workers=8) as pool:
        report=list(pool.map(inspect,PAGES))
    for item in report:
        print(json.dumps(item,ensure_ascii=False))
    controls=[x for x in report if x["role"]=="previous_live_control"]
    v5=[x for x in report if x["role"]=="new_v5"]
    v6=[x for x in report if x["role"]=="new_v6"]
    prior_ok=all(x["marker_seen"] for x in controls+v5)
    new_count=sum(x["marker_seen"] for x in v6)
    summary={"checked_at_utc":datetime.now(timezone.utc).isoformat(),
        "prior_controls_confirmed":sum(x["marker_seen"] for x in controls+v5),
        "v6_positive":new_count,
        "v6_expected":len(v6),
        "interpretation":("NEW_V6_PUBLISHED_AND_PUBLICLY_VERIFIED" if new_count==len(v6)
           else ("V6_NOT_YET_ALL_PUBLIC_OR_CACHE_PENDING" if prior_ok
                 else "PUBLIC_READBACK_UNRELIABLE"))}
    print("SUMMARY "+json.dumps(summary,ensure_ascii=False))
    with open("public-readback-result.json","w",encoding="utf-8") as f:
        json.dump({"summary":summary,"posts":report},f,ensure_ascii=False,indent=2)

if __name__=="__main__":
    main()
