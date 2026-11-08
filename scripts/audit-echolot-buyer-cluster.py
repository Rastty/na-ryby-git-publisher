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
from html.parser import HTMLParser
from urllib.parse import quote, urlsplit

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


def commercial_ordering(content):
    """Identify early merchant shopping CTA without fetching or exposing merchant links.

    This is editorial review evidence only, not proof of a broken offer.
    """
    first = None
    labels = ("prověřit nabídku", "zobrazit nabídku", "do obchodu",
              "nejlevněji", "koupit", "ověřit cenu")
    for match in re.finditer(r"<a\b[^>]*\bhref\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27][^>]*>(.*?)</a>",
                             content, flags=re.I | re.S):
        target = urlsplit(html.unescape(match.group(1)))
        label = " ".join(html.unescape(re.sub(r"<[^>]+>", " ", match.group(2))).lower().split())
        if (target.scheme not in ("http", "https") or
                target.hostname in ("na-ryby.eu", "www.na-ryby.eu") or
                not any(x in label for x in labels)):
            continue
        first = match.start()
        break
    if first is None:
        return {"outboundShoppingCtaFound": False,
                "firstCtaBeforeSecondH2": False,
                "firstCtaVisiblePrefixChars": None,
                "firstCtaHtmlOffset": None}
    prefix = content[:first]
    visible = " ".join(html.unescape(re.sub(r"<[^>]+>", " ", prefix)).split())
    return {"outboundShoppingCtaFound": True,
            "firstCtaBeforeSecondH2": len(re.findall(r"<h2\b", prefix, re.I)) < 2,
            "firstCtaVisiblePrefixChars": len(visible),
            "firstCtaHtmlOffset": first}



class PriceContextParser(HTMLParser):
    """Count visible currency mentions, excluding same-site guide navigation.

    Triage only, not a price freshness or entitlement check. Never return
    prices, merchant URLs or HTML in public evidence.
    """
    PRICE = re.compile(r"\b\d[\d \u00a0\u202f]{0,8}\s*(?:Kč|CZK)\b", re.I)

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.anchors = []
        self.hidden = 0
        self.all_parts = []
        self.outside_internal_parts = []

    def handle_starttag(self, tag, attrs):
        if tag in ("script", "style", "noscript", "template"):
            self.hidden += 1
        if tag == "a":
            href = dict(attrs).get("href", "") or ""
            parsed = urlsplit(html.unescape(href))
            internal = (
                parsed.hostname in ("na-ryby.eu", "www.na-ryby.eu")
                and parsed.scheme in ("http", "https")
            ) or (not parsed.scheme and not parsed.netloc and href.startswith("/"))
            self.anchors.append(internal)

    def handle_endtag(self, tag):
        if tag == "a" and self.anchors:
            self.anchors.pop()
        if tag in ("script", "style", "noscript", "template"):
            self.hidden = max(0, self.hidden - 1)

    def handle_data(self, data):
        if self.hidden:
            return
        self.all_parts.append(data)
        if not any(self.anchors):
            self.outside_internal_parts.append(data)


def price_context(content):
    """Count-only public signal, never a stale-price diagnosis."""
    parser = PriceContextParser()
    parser.feed(content)
    total = len(parser.PRICE.findall(" ".join(parser.all_parts)))
    outside = len(parser.PRICE.findall(" ".join(parser.outside_internal_parts)))
    return {
        "visibleNumericPriceMentions": total,
        "numericPriceMentionsOutsideInternalLinks": outside,
        "priceOnlyInInternalGuideLinks": total > 0 and outside == 0,
    }


def answer_first_pilot(content, first_shop_offset):
    """Post-release semantic evidence for the one verified echolot pillar.

    Presence alone is insufficient: all three useful destinations and four
    choice cases must appear INSIDE the inserted section ahead of any shop CTA.
    No affiliate URL or reader data is returned.
    """
    marker = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
    marker_count = content.count(marker)
    start = content.find("<section " + marker)
    end = content.find("</section>", start) if start >= 0 else -1
    panel = content[start:end] if end > start else ""
    expected_routes = (
        DESTINATIONS["budget"],
        DESTINATIONS["boat"],
        DESTINATIONS["advisor"],
    )
    useful = (
        marker_count == 1
        and start >= 0 and end > start
        and "<h2>Jak vybrat vhodný typ echolotu</h2>" in panel
        and panel.count("<li>") == 4
        and "další nákup není nutný" in panel
        and "aktuální konečnou cenu kompletu" in panel
        and all('href="' + url + '"' in panel for url in expected_routes)
    )
    before = useful and (first_shop_offset is None or end < first_shop_offset)
    return {
        "answerFirstPilotMarkerCount": marker_count,
        "answerFirstPilotSemantics": useful,
        "answerFirstPilotBeforeShoppingCta": before,
        "answerFirstPilotVerified": useful and before,
    }


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
        commercial = commercial_ordering(content)
        prices = price_context(content)
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
            "hasNumericPrice": prices["visibleNumericPriceMentions"] > 0,
            **prices,
            "historicPricingVerified": False,
            "merchantStockVerified": False,
            **{k: v for k, v in commercial.items() if k != "firstCtaHtmlOffset"},
            **(answer_first_pilot(content, commercial["firstCtaHtmlOffset"])
               if role == "pillar" else {}),
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
    pillar = next(x for x in rows if x["role"] == "pillar")
    if pillar.get("postId") != 118553 or pillar.get("answerFirstPilotVerified") is not True:
        raise SystemExit("BLOCKED: post-release answer-before-offer pillar regression")


if __name__ == "__main__":
    main()
