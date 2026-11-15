#!/usr/bin/env python3
"""Read-only source-bound public proof for the sonar buyer fast-path experiment.

Never follows an affiliate link, sends a click event, or writes to WordPress.
"""
import json
import re
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = "https://na-ryby.eu"
PAGE = SITE + "/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"
REST = SITE + "/wp-json/wp/v2/posts/118553?_fields=id,slug,type,status,link,modified_gmt,content"
SOURCE = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
MARKER = 'data-nry-sonar-fast-paths="v1"'
CHOICES = (
    SITE + "/deeper-pro-vs-pro-2-vs-chirp-2-ktery-se-vyplati/",
    SITE + "/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/",
    SITE + "/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/",
    SITE + "/poradce-vybavy/",
)


def evaluate(post, html, expect_live):
    assert post.get("id") == 118553, "POST_ID_DRIFT"
    assert post.get("slug") == "jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce"
    assert post.get("type") == "post" and post.get("status") == "publish"
    assert post.get("link") == PAGE
    assert post.get("modified_gmt") == "2026-11-04T11:27:17"
    body = post.get("content", {}).get("rendered", "")
    assert isinstance(body, str) and 600 < len(body) < 240000, "POST_CONTENT_UNAVAILABLE"
    assert len(html) > 40000 and html.count(SOURCE) == 1, "HTML_SOURCE_MISSING"
    assert body.count(SOURCE) == 1 and MARKER not in body, "REST_MAIN_QUERY_LEAK"
    source_start = html.find("<section " + SOURCE)
    source_end = html.find("</section>", source_start)
    assert source_start >= 0 and 0 < source_end - source_start < 7000, "SOURCE_DRIFT"
    section = html[source_start:source_end]
    assert section.count("<li>") == 4, "FOUR_EXISTING_USE_CASES_DRIFT"
    for route in CHOICES[1:]:
        assert route in body, "PREEXISTING_BUYER_ROUTE_DRIFT"
    assert "další nákup není nutný" in section, "NO_BUY_GUIDANCE_DRIFT"
    assert "aktuální konečnou cenu kompletu" in section, "PRICE_CAVEAT_DRIFT"
    if expect_live:
        assert html.count(MARKER) == 1 and section.count(MARKER) == 1, "FAST_PATHS_NOT_LIVE"
        start = section.find("<nav " + MARKER)
        end = section.find("</nav>", start)
        assert start >= 0 and end > start, "NAV_NOT_IN_SOURCE"
        panel = section[start:end + len("</nav>")]
        hrefs = re.findall(r'<a\b[^>]*\bhref="([^"]+)"', panel)
        assert hrefs == list(CHOICES), "FOUR_APPROVED_PATHS_ORDER_DRIFT"
        assert section.index(MARKER) < section.index("<ul>"), "FAST_PATHS_BELOW_BULLETS"
    else:
        assert MARKER not in html, "CONCURRENT_OR_ALREADY_LIVE_FAST_PATHS"
    return {"status": "PUBLIC_FAST_PATHS_LIVE" if expect_live else "PUBLIC_FAST_PATHS_PREFLIGHT",
            "postId": 118553, "sourcePinned": True, "choiceCount": 4 if expect_live else 0,
            "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
            "affiliateAttributionVerified": False, "economicUpliftProven": False}


def get(url, limit):
    assert url in (PAGE, REST), "ONLY_EXACT_FIRST_PARTY_GETS"
    req = Request(url, method="GET", headers={
        "User-Agent": "NaRyby-SonarFastPathsPublicProof/1.0",
        "Cache-Control": "no-cache, no-store",
        "Accept": "application/json" if url == REST else "text/html"})
    with guarded_urlopen(req, timeout=25) as resp:
        assert resp.status == 200, "NON_200"
        data = resp.read(limit + 1)
    assert len(data) <= limit, "RESPONSE_TOO_LARGE"
    return data.decode("utf8", "replace")


def main():
    mode = sys.argv[1] if len(sys.argv) > 1 else "--verify"
    assert mode in ("--preflight", "--verify")
    post = json.loads(get(REST, 420000))
    html = get(PAGE, 900000)
    print(json.dumps(evaluate(post, html, mode == "--verify"), ensure_ascii=False))


if __name__ == "__main__":
    main()
