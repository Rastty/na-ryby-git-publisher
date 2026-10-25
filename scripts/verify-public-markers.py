#!/usr/bin/env python3
"""Read-only public page verification. No WordPress credentials, no writes."""
import json
import urllib.request
import urllib.error
from datetime import datetime, timezone

PAGES = [
    ("34856", "https://na-ryby.eu/nejlepsi-prut-na-privlac-pro-zacatecniky-v-ceske-republice/", "privlac-rozhodovac-34856-20261025-v1", "previous_live_control"),
    ("34883", "https://na-ryby.eu/kaprarsky-prut-jak-si-vybrat-ten-nejlepsi-pro-rybolov/", "kaprovy-prut-rozhodovac-34883-20261025-v1", "previous_live_control"),
    ("50011", "https://na-ryby.eu/nejlepsi-kaprove-pruty-pro-zacatecniky-recenze-a-doporuceni-pro-rok-2020/", "kaprove-pruty-2020-50011-archive-clarity-v1", "new_v5"),
    ("53630", "https://na-ryby.eu/jak-vybrat-ten-spravny-ruzovy-prut-na-ryby/", "ruzovy-prut-53630-skutecny-vyber-v1", "new_v5"),
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

def main():
    report = []
    for post_id, url, marker, role in PAGES:
        resp = get(url)
        html = resp.pop("html", "")
        found = ('data-nry-editorial-edit="' + marker + '"') in html
        item = {"post_id":post_id, "role":role, "expected_marker":marker,
                "http":resp.get("http", 0), "marker_seen":found,
                "cache_age":resp.get("age"), "cache_header":resp.get("cache"),
                "request_error":resp.get("error")}
        report.append(item)
        print(json.dumps(item,ensure_ascii=False))
    control = [x for x in report if x["role"]=="previous_live_control"]
    new = [x for x in report if x["role"]=="new_v5"]
    summary = {"checked_at_utc":datetime.now(timezone.utc).isoformat(),
       "control_positive":sum(x["marker_seen"] for x in control),
       "v5_positive":sum(x["marker_seen"] for x in new),
       "v5_expected":len(new),
       "interpretation":"PUBLIC_MARKERS_CONFIRMED" if all(x["marker_seen"] for x in new)
       else ("PUBLIC_READBACK_INCONCLUSIVE_OR_UNAPPLIED" if all(x["marker_seen"] for x in control)
       else "PUBLIC_READER_UNRELIABLE_OR_CACHED")}
    print("SUMMARY " + json.dumps(summary,ensure_ascii=False))
    with open("public-readback-result.json", "w", encoding="utf-8") as f:
       json.dump({"summary":summary,"posts":report},f,ensure_ascii=False,indent=2)

if __name__=="__main__":
    main()
