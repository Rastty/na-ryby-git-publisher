#!/usr/bin/env python3
"""Read-only, public WordPress REST audit of four existing sonar buyer journeys.

Does not request merchant URLs, expose affiliate hrefs, write WordPress or assume
a listed model is available. Evidence is public rendered HTML, not raw source.
"""
import html
import json
import re
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from urllib.parse import quote

SITE = "https://na-ryby.eu"
GUIDES = (
    ("pillar", "jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce"),
    ("boat", "jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare"),
    ("budget", "nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz"),
    ("historic", "porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023"),
)
DESTINATIONS = {
    "pillar": SITE + "/" + GUIDES[0][1] + "/",
    "advisor": SITE + "/poradce-vybavy/",
    "budget": SITE + "/" + GUIDES[2][1] + "/",
    "boat": SITE + "/" + GUIDES[1][1] + "/",
}


def read(url):
    request = urllib.request.Request(url, headers={
        "User-Agent": "NaRyby-Echolot-ContentAudit/1.0",
        "Cache-Control": "no-cache",
    })
    try:
        with urllib.request.urlopen(request, timeout=24) as response:
            return response.status, response.read(480000).decode("utf-8", "replace")
    except (urllib.error.URLError, TimeoutError, OSError) as exc:
        return None, type(exc).__name__


def summarize(role, slug, *, fetch=read):
    url = (SITE + "/wp-json/wp/v2/posts?slug=" + quote(slug) +
           "&_fields=id,slug,status,link,title,modified_gmt,content")
    status, raw = fetch(url)
    result = {"role": role, "slug": slug, "restStatus": status,
              "readOnly": True, "sourceShaVerified": False,
              "merchantCalls": 0, "wordpressWrites": 0}
    if status != 200:
        result["state"] = "PUBLIC_REST_UNAVAILABLE"
        return result
    try:
        matches = json.loads(raw)
        if not isinstance(matches, list) or len(matches) != 1:
            result["state"] = "IDENTITY_AMBIGUOUS"
            return result
        item = matches[0]
        if (item.get("slug") != slug or item.get("link") != SITE + "/" + slug + "/"
                or item.get("status") != "publish"
                or type(item.get("id")) is not int):
            result["state"] = "IDENTITY_DRIFT"
            return result
        content = item.get("content", {}).get("rendered", "")
        if not isinstance(content, str):
            result["state"] = "CONTENT_MISSING"
            return result
        visible = html.unescape(re.sub(r"<[^>]+>", " ", content))
        title = html.unescape(re.sub(r"<[^>]+>", "", item.get("title", {}).get("rendered", "")))
        hrefs = [html.unescape(x) for x in re.findall(
            r'<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']', content, flags=re.I)]
        internal = [u for u in hrefs if u.startswith(SITE + "/")]
        result.update({
            "state": "PUBLIC_EVIDENCE",
            "postId": item["id"],
            "title": title[:180],
            "modifiedGmt": item.get("modified_gmt"),
            "url": item["link"],
            "renderedBytes": len(content.encode("utf-8")),
            "h1InBody": len(re.findall(r"<h1\b", content, re.I)),
            "h2InBody": len(re.findall(r"<h2\b", content, re.I)),
            "images": len(re.findall(r"<img\b", content, re.I)),
            "internalLinkCount": len(internal),
            "linksToPillar": sum(u.rstrip("/") == DESTINATIONS["pillar"].rstrip("/") for u in internal),
            "linksToAdvisor": sum(u.rstrip("/") == DESTINATIONS["advisor"].rstrip("/") for u in internal),
            "linksToBudget": sum(u.rstrip("/") == DESTINATIONS["budget"].rstrip("/") for u in internal),
            "linksToBoat": sum(u.rstrip("/") == DESTINATIONS["boat"].rstrip("/") for u in internal),
            "has2023Language": bool(re.search(r"\b2023\b", visible)),
            "hasVisibleKeywordStuffing": "klíčová slova:" in visible.lower(),
            "hasNumericPrice": bool(re.search(r"\b\d[\d ]{0,8}\s*(?:Kč|CZK)\b", visible, re.I)),
            "historicPricingVerified": False,
            "merchantStockVerified": False,
        })
    except (ValueError, AttributeError, TypeError, KeyError):
        result["state"] = "PUBLIC_PARSE_UNVERIFIABLE"
    return result


def main():
    with ThreadPoolExecutor(max_workers=4) as pool:
        rows = list(pool.map(lambda item: summarize(*item), GUIDES))
    evidence = {"checkedAtUtc": datetime.now(timezone.utc).isoformat(),
                "scope": "four-existing-sonar-buyer-guides",
                "readOnly": True, "wordpressWrites": 0, "merchantCalls": 0,
                "rawPostContentExposed": False, "guides": rows}
    print(json.dumps(evidence, ensure_ascii=False))
    with open("echolot-public-opportunity-audit.json", "w", encoding="utf-8") as output:
        json.dump(evidence, output, ensure_ascii=False, indent=2)
    if any(x["state"] != "PUBLIC_EVIDENCE" for x in rows):
        raise SystemExit("BLOCKED: at least one public sonar guide unverifiable")


if __name__ == "__main__":
    main()
