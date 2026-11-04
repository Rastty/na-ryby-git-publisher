#!/usr/bin/env python3
"""Read-only public WordPress product-advice opportunity audit, no credentials."""
import json
import re
import urllib.request
import urllib.error
import html
from datetime import datetime, timezone
from concurrent.futures import ThreadPoolExecutor
from urllib.parse import quote, urlsplit

IDS=[34882,34905,34913,34921,34977,35003,35011,35026]
SITE="https://na-ryby.eu"
HEADERS={"User-Agent":"NaRyby-EditorialReadOnly/1.0","Cache-Control":"no-cache"}

def request(url):
    try:
        with urllib.request.urlopen(urllib.request.Request(url,headers=HEADERS),timeout=17) as r:
            raw=r.read(380000).decode("utf-8","replace")
            return r.status,raw
    except (urllib.error.URLError,TimeoutError) as exc:
        return 0,str(exc)[:120]

def extract(row):
    post_id=row["id"]
    status,body=request(f"{SITE}/wp-json/wp/v2/posts/{post_id}?_fields=id,slug,title,modified_gmt,status,link,content")
    out={"postId":post_id,"wpRestHttp":status}
    if status!=200:
        out["error"]=body[:140]
        return out
    try:
        obj=json.loads(body)
        post=obj.get("content",{}).get("rendered","")
        title=html.unescape(re.sub("<[^>]+>","",obj.get("title",{}).get("rendered","")))
        headings=[html.unescape(re.sub("<[^>]+>","",h)).strip() for h in re.findall(r"<h[23][^>]*>.*?</h[23]>",post,re.I|re.S)]
        out.update({"title":title,"slug":obj.get("slug"),"status":obj.get("status"),
          "modifiedGmt":obj.get("modified_gmt"),"url":obj.get("link"),
          "renderedBytes":len(post.encode("utf-8")),"headings":headings[:12],
          "markers":re.findall(r'data-nry-editorial-edit="([^"]+)"',post)[:7],
          "images":len(re.findall(r"<img\\b",post,re.I)),
          "internalLinks":len(re.findall(r'href="https://na-ryby.eu/',post)),
          "issueIndicators":{
            "english_ai_refusal":"Sorry, I cannot perform this task" in post,
            "keyword_stuffing":"klíčová slova:" in post.lower(),
            "year_2020":"2020" in post,
          },
          "sourceSizeAndHashKnown":False})
    except Exception as exc:
        out["error"]="parse:"+str(exc)[:130]
    return out

def audit_hub():
    slug="nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby"
    url=f"{SITE}/wp-json/wp/v2/posts?slug={slug}&_fields=id,slug,title,modified_gmt,status,link,content"
    status,body=request(url)
    out={"hub":"chair-buying", "http":status, "slug":slug}
    if status!=200:
        out["error"]=body[:200]
        return out
    try:
        data=json.loads(body)
        if len(data)!=1:
            out["error"]="expected_exact_one"
            out["resultCount"]=len(data)
            return out
        p=data[0]
        rendered=p.get("content",{}).get("rendered","")
        out.update({"postId":p["id"],"status":p.get("status"),
            "modifiedGmt":p.get("modified_gmt"),"url":p.get("link"),
            "claimsOwnProductionOrSales":"Naše společnost se specializuje na výrobu a prodej" in rendered,
            "visibleKeywordStuffing":"klíčová slova:" in rendered.lower(),
            "unverifiedRatingsStars":"⭐⭐⭐⭐⭐" in rendered,
            "duplicateH2s":len(re.findall(r"<h2\\b",rendered,re.I)),
            "contentLength":len(rendered)})
    except Exception as exc:
        out["error"]="parse:"+str(exc)[:200]
    return out


def inspect_commercial_freshness(rendered, modified_gmt, *, now=None):
    """Conservative signals, not a price, SKU, stock or merchant verification.

    Never follow or expose outbound affiliate URLs. An older article with a
    numeric price beside shop CTAs needs source review; it is NOT proven expired.
    """
    now = now or datetime.now(timezone.utc)
    visible = html.unescape(re.sub(r"<[^>]+>", " ", rendered)).replace("\xa0", " ")
    price_mentions = len(re.findall(
        r"\b\d{1,7}(?:[ ]\d{3})*(?:[,.]\d{1,2})?\s*(?:Kč|CZK)\b",
        visible, flags=re.I))
    cta_count = 0
    for href, label_markup in re.findall(
            r'<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)</a>',
            rendered, flags=re.I | re.S):
        label = html.unescape(re.sub(r"<[^>]+>", " ", label_markup))
        label = " ".join(label.lower().split())
        target = urlsplit(html.unescape(href))
        outbound = (target.scheme in ("http", "https")
                    and target.hostname not in ("na-ryby.eu", "www.na-ryby.eu"))
        if outbound and any(x in label for x in (
                "do obchodu", "nejlevněji", "koupit", "zobrazit nabídku")):
            cta_count += 1
    try:
        modified = datetime.fromisoformat(modified_gmt.replace("Z", "+00:00"))
        if modified.tzinfo is None:
            modified = modified.replace(tzinfo=timezone.utc)
        age_days = (now - modified).days
        if age_days < 0:
            age_days = None
    except (ValueError, TypeError, AttributeError):
        age_days = None
    review = (price_mentions > 0 and cta_count > 0
              and (age_days is None or age_days >= 180))
    return {
        "numericPriceMentions": price_mentions,
        "outboundShoppingCTAs": cta_count,
        "modifiedAgeDays": age_days,
        "reviewRequired": review,
        "state": "SOURCE_REVIEW_REQUIRED" if review else "PUBLIC_HEURISTIC_ONLY",
        "priceOrStockVerified": False,
        "affiliateDestinationVerified": False,
        "outboundUrlsFetched": 0,
    }


def audit_evergreen_guide():
    """Audit a real buyer-intent hub with historical priced affiliate offers.

    Anonymous WordPress REST renders public HTML; this is not a raw source
    fingerprint, affiliate approval or authorization to edit a production page.
    """
    slug = "jak-vybrat-rybarske-kreslo"
    url = (f"{SITE}/wp-json/wp/v2/posts?slug={quote(slug)}"
           "&_fields=id,slug,status,link,modified_gmt,content")
    status, body = request(url)
    out = {"slug": slug, "wpRestHttp": status, "readOnly": True,
           "sourceShaVerified": False, "wordpressWrites": 0}
    if status != 200:
        out["state"] = "PUBLIC_EVIDENCE_UNAVAILABLE"
        return out
    try:
        rows = json.loads(body)
        if len(rows) != 1:
            out["state"] = "AMBIGUOUS_PUBLIC_IDENTITY"
            out["resultCount"] = len(rows)
            return out
        row = rows[0]
        if (row.get("slug") != slug or row.get("status") != "publish"
                or row.get("link") != f"{SITE}/{slug}/"):
            out["state"] = "PUBLIC_IDENTITY_MISMATCH"
            return out
        out.update({"postId": row["id"], "modifiedGmt": row.get("modified_gmt")})
        out.update(inspect_commercial_freshness(
            row.get("content", {}).get("rendered", ""), row.get("modified_gmt")))
        out["recommendedNextAction"] = (
            "Review exact SKU and merchant price evidence, then prepare "
            "source-SHA-bound editorial release preserving existing links."
            if out["reviewRequired"] else "No automatic action; keep monitoring.")
    except (ValueError, KeyError, TypeError, AttributeError):
        out["state"] = "PUBLIC_PARSE_UNVERIFIABLE"
    return out


def main():
    with ThreadPoolExecutor(max_workers=8) as pool:
        result=list(pool.map(lambda post_id: extract({"id":post_id}),IDS))
    hub=audit_hub()
    evergreen=audit_evergreen_guide()
    evidence={"checkedAtUtc":datetime.now(timezone.utc).isoformat(),
        "readOnly":True,"wordpressWrites":0,"merchantRequests":0,
        "chairCandidates":result,"hubAudit":hub,"evergreenGuide":evergreen}
    print(json.dumps(evidence,ensure_ascii=False))
    with open("chair-candidate-public-audit.json","w",encoding="utf-8") as f:
        json.dump(evidence,f,ensure_ascii=False,indent=2)
    if (evergreen.get("state") in ("PUBLIC_EVIDENCE_UNAVAILABLE",
                                  "PUBLIC_IDENTITY_MISMATCH",
                                  "AMBIGUOUS_PUBLIC_IDENTITY",
                                  "PUBLIC_PARSE_UNVERIFIABLE")):
        raise SystemExit("BLOCKED: no reliable public evergreen guide evidence")

if __name__=="__main__":
    main()
