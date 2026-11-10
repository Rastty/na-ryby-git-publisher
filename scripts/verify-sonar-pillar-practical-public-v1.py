#!/usr/bin/env python3
"""Exact public GET-only pillar-to-practical decision navigation audit.

PR preflight confirms the still-published answer-first section has no existing
link to practical #49275; post-deploy confirms exactly one new, source-pinned
handoff. No merchant requests, synthetic clicks or WordPress writes.
"""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ORIGIN = "https://na-ryby.eu"
PAGE = ORIGIN + "/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"
REST = (ORIGIN + "/wp-json/wp/v2/posts/118553"
        "?_fields=id,slug,type,status,link,modified_gmt,content")
SOURCE_MARKER = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
NEW_MARKER = 'data-nry-sonar-owned-practical="v1"'
PRACTICAL = ORIGIN + "/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/"
PILLAR_HEADING = '<h2>Jak vybrat vhodný typ echolotu</h2>'
OLD_LINKS = (
    ORIGIN + "/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/",
    ORIGIN + "/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/",
    ORIGIN + "/poradce-vybavy/",
)

def assert_original_section(content):
    assert content.count(SOURCE_MARKER) == 1, "SOURCE_SECTION_MARKER_DRIFT"
    assert content.count(PILLAR_HEADING) == 1, "SOURCE_PILLAR_H2_DRIFT"
    start = content.find("<section " + SOURCE_MARKER)
    assert start >= 0, "EXACT_SOURCE_SECTION_OPEN_MISSING"
    end = content.find("</section>", start)
    assert 0 < end - start < 4500, "EXACT_SOURCE_SECTION_UNBOUNDED"
    section = content[start:end]
    assert section.count("<li>") == 4, "FOUR_DECISIONS_DRIFT"
    assert "další nákup není nutný" in section, "NO_BUY_INTENT_DRIFT"
    assert "aktuální konečnou cenu kompletu" in section, "PRICE_CAUTION_DRIFT"
    for url in OLD_LINKS:
        assert url in section, "EXISTING_CHOICE_LINK_DRIFT"
    next_fragment = content[end+len("</section>"):end+len("</section>")+2000]
    import re
    has_heading = bool(re.search(
        r"<h2\b[^>]*>\s*Jak vybrat echolot pro rybaření:", next_fragment,
        re.IGNORECASE))
    first_h2 = next_fragment.find("<h2")
    raw_title_pos = next_fragment.find("Jak vybrat echolot pro rybaření:")
    assert has_heading, (
        "NEXT_SECTION_ANCHOR_DRIFT first_h2_at=" + str(first_h2)
        + " title_at=" + str(raw_title_pos)
        + " bound=2000")
    return end + len("</section>")

def evaluate(payload, html, expect_new):
    assert payload.get("id") == 118553
    assert payload.get("slug") == "jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce"
    assert payload.get("type") == "post" and payload.get("status") == "publish"
    assert payload.get("link") == PAGE
    assert payload.get("modified_gmt") == "2026-11-04T11:27:17", (
        "PINNED_PILLAR_WORDPRESS_MODIFIED_GMT_DRIFT")
    content = payload.get("content", {}).get("rendered", "")
    assert isinstance(content, str) and 600 < len(content) < 240000, (
        "PUBLIC_POST_CONTENT_UNAVAILABLE")
    assert_original_section(content)
    assert len(html) > 40000 and PAGE in html, "PUBLIC_PAGE_UNAVAILABLE"
    if expect_new:
        print(json.dumps({
            "diagnostic": "BOUND_SOURCE_MARKER_COUNTS_ONLY",
            "rest_source_marker_count": content.count(SOURCE_MARKER),
            "html_source_marker_count": html.count(SOURCE_MARKER),
            "html_owned_handoff_marker_count": html.count(NEW_MARKER),
            "html_practical_destination_count": html.count(PRACTICAL),
            "html_source_heading_count": html.count(PILLAR_HEADING),
            "rest_owned_handoff_marker_count": content.count(NEW_MARKER),
            "html_bytes": len(html.encode("utf8")),
            "wordpressWrites": 0, "merchantRequests": 0
        }, sort_keys=True), flush=True)
    page_position = assert_original_section(html)
    # The code's main-loop guard is intentionally FALSE inside the REST API.
    # REST is immutable source/identity evidence, not the buyer-facing output.
    assert NEW_MARKER not in content and PRACTICAL not in content, (
        "REST_MAIN_LOOP_SCOPE_LEAK")
    if expect_new:
        assert html.count(NEW_MARKER) == 1, "PUBLIC_HTML_MISSING_ONE_HANDOFF"
        assert html.count(PRACTICAL) == 1, "PUBLIC_HTML_PRACTICAL_LINK_NOT_ONCE"
        assert html.find(NEW_MARKER) > page_position, "HANDOFF_NOT_AFTER_SOURCE"
        assert html.find(NEW_MARKER) - page_position < 40, "HANDOFF_NOT_ADJACENT"
        assert html.find(NEW_MARKER) < html.find(
            "Jak vybrat echolot pro rybaření:", page_position), (
                "HANDOFF_TOO_LATE")
    else:
        assert NEW_MARKER not in html and PRACTICAL not in html, (
            "ALREADY_PRESENT_OR_CONCURRENT_EDIT")
    return {
        "status": "PUBLIC_HANDOFF_VERIFIED" if expect_new else "PUBLIC_PREFLIGHT_VERIFIED",
        "postId": 118553, "targetPostId": 49275,
        "exactPublicSourceMarker": True,
        "alreadyOwnDeviceDecisionAdjacentToTopAnswer": expect_new,
        "firstPartyPracticalHandoffCount": html.count(PRACTICAL),
        "restMainLoopScopeConserved": True,
        "wordpressWrites": 0, "merchantRequests": 0,
        "syntheticGA4Events": 0, "affiliateLinksRewritten": 0,
        "gscClickUpliftProven": False, "ga4GuideHandoffProven": False
    }

def live_get(url, max_bytes):
    assert url in (PAGE, REST), "GET_ONLY_EXACT_FIRST_PARTY_TARGETS"
    req = Request(url, method="GET", headers={
        "User-Agent": "NaRyby-SonarPillarPracticalReadback/1.0",
        "Cache-Control": "no-cache, no-store",
        "Accept": "application/json" if url == REST else "text/html"})
    with guarded_urlopen(req, timeout=25) as response:
        assert response.status == 200, "PUBLIC_NON_200"
        data = response.read(max_bytes+1)
    assert len(data) <= max_bytes, "PUBLIC_RESPONSE_TOO_LARGE"
    return data.decode("utf8", "replace")

def main():
    mode = sys.argv[1] if len(sys.argv) > 1 else "--verify"
    assert mode in ("--preflight", "--verify"), "INVALID_MODE"
    payload = json.loads(live_get(REST, 420000))
    html = live_get(PAGE, 900000)
    receipt = evaluate(payload, html, expect_new=mode == "--verify")
    print(json.dumps(receipt, ensure_ascii=False, sort_keys=True))

if __name__ == "__main__":
    main()
