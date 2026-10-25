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
    ("34913", "https://na-ryby.eu/rybarske-kreslo-pro-tezsi-rybare-s-nosnosti-120-kg/", "chair-rated-120kg-fit-34913-v1", "new_v7"),
    ("34977", "https://na-ryby.eu/pohodlne-rybarske-kreslo-pro-tezsi-muze-s-nosnosti-160-kg/", "chair-rated-160kg-comfort-34977-v1", "new_v7"),
    ("35003", "https://na-ryby.eu/kreslo-pro-rybare-s-vysokym-operadlem-nejlepsi-volba-pro-bezbolestne-rybareni/", "high-back-chair-ergonomics-35003-v1", "new_v7"),
    ("35011", "https://na-ryby.eu/jak-vybrat-spravne-rybarske-kreslo-od-znacky-mivardi/", "mivardi-chair-exact-model-35011-v1", "new_v7"),
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

def inspect_ctr_pilot():
    url="https://na-ryby.eu/wp-json/wp/v2/posts/42401?_fields=id,slug,status,link,yoast_head_json"
    expected_title="Pípáky na ryby: jak vybrat, nastavit a používat"
    expected_meta="Pípáky na ryby jednoduše: jak nastavit citlivost, kam hlásič umístit a kdy se vyplatí příposlech. Praktické rady i výběr správné sady."
    response=get(url)
    if response.get("http")!=200:
        return {"post_id":42401,"status":"READER_UNAVAILABLE","http":response.get("http")}
    try:
        item=json.loads(response.get("html",""))
        head=item.get("yoast_head_json") or {}
        identity=item.get("id")==42401 and item.get("status")=="publish" and item.get("slug")=="jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne"
        return {"post_id":42401,"http":200,"identity_ok":identity,
            "public_title":head.get("title"),"public_description":head.get("description"),
            "title_matches":head.get("title")==expected_title,
            "description_matches":head.get("description")==expected_meta,
            "status":("PUBLIC_YOAST_CTR_PILOT_VERIFIED" if identity and head.get("title")==expected_title and head.get("description")==expected_meta else "META_NOT_YET_PUBLIC_OR_BLOCKED")}
    except (ValueError,TypeError) as err:
        return {"post_id":42401,"status":"JSON_DECODE_OR_SCHEMA_ERROR","error":str(err)[:120]}

def main():
    with ThreadPoolExecutor(max_workers=8) as pool:
        report=list(pool.map(inspect,PAGES))
    for item in report:
        print(json.dumps(item,ensure_ascii=False))
    controls=[x for x in report if x["role"]=="previous_live_control"]
    v5=[x for x in report if x["role"]=="new_v5"]
    v6=[x for x in report if x["role"]=="new_v6"]
    v7=[x for x in report if x["role"]=="new_v7"]
    prior_ok=all(x["marker_seen"] for x in controls+v5+v6)
    new_count=sum(x["marker_seen"] for x in v6)
    v7_count=sum(x["marker_seen"] for x in v7)
    summary={"checked_at_utc":datetime.now(timezone.utc).isoformat(),
        "prior_controls_confirmed":sum(x["marker_seen"] for x in controls+v5),
        "v6_positive":new_count,
        "v6_expected":len(v6),
        "v6_http_ok":sum(x["http"]==200 for x in v6),
        "v6_exact_markers":[x["post_id"] for x in v6 if x["marker_seen"]],
        "v7_positive":sum(x["marker_seen"] for x in v7),
        "v7_expected":len(v7),
        "v7_exact_markers":[x["post_id"] for x in v7 if x["marker_seen"]],
        "interpretation":("V7_PUBLISHED_ALL_FOUR" if v7_count==len(v7)
           else ("V7_NOT_YET_APPLIED_OR_PUBLIC" if prior_ok
                 else "PUBLIC_READER_INCONCLUSIVE_OR_CONTROLS_MISSING"))}
    seo_pilot=inspect_ctr_pilot()
    summary["seo_ctr_pilot_status"]=seo_pilot["status"]
    print("SEO_PILOT "+json.dumps(seo_pilot,ensure_ascii=False))
    print("SUMMARY "+json.dumps(summary,ensure_ascii=False))
    with open("public-readback-result.json","w",encoding="utf-8") as f:
        json.dump({"summary":summary,"posts":report,"seo_ctr_pilot":seo_pilot},f,ensure_ascii=False,indent=2)

if __name__=="__main__":
    main()
