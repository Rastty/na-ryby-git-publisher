#!/usr/bin/env python3
"""GET-only bounded public diagnosis for one published private-water guide.

Never checks merchant URLs or logs article HTML, tokens, cookies, or WP data.
Exact page only. The report is evidence of presentation, not source mutation.
"""
from __future__ import annotations

import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

PAGE = "https://na-ryby.eu/nejlepsi-soukrome-reviry-pro-rybolov-v-cesku/"
REST = "https://na-ryby.eu/wp-json/wp/v2/posts?slug=nejlepsi-soukrome-reviry-pro-rybolov-v-cesku&_fields=id,slug,status,link"
EXPECTED = "Podle čeho poznat dobrý soukromý revír"
OTHER = "Pro koho dávají soukromé revíry největší smysl"
CSS = "nry-private-waters-examples-v1.css"
MARKER = 'data-nry-private-water-examples="v1"'

class Headings(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.headings = []
        self.tag = None
        self.parts = []
        self.inner_tags = 0
        self.css_links = 0
        self.marker_sections = 0
    def handle_starttag(self, tag, attrs):
        props = dict(attrs)
        if tag == "link" and CSS in (props.get("href") or ""):
            self.css_links += 1
        if tag == "section" and props.get("data-nry-private-water-examples") == "v1":
            self.marker_sections += 1
        if tag == "h2" and self.tag is None:
            self.tag = "h2"
            self.parts = []
            self.inner_tags = 0
        elif self.tag is not None:
            self.inner_tags += 1
    def handle_data(self, data):
        if self.tag is not None:
            self.parts.append(data)
    def handle_endtag(self, tag):
        if tag == "h2" and self.tag is not None:
            self.headings.append((" ".join("".join(self.parts).split()), self.inner_tags))
            self.tag = None

def analyze(source: str) -> dict:
    p = Headings()
    p.feed(source)
    p.close()
    exact = re.findall(r"<h2\b[^>]*>\s*" + re.escape(EXPECTED) + r"\s*</h2>", source)
    headings = [text for text, _ in p.headings]
    near = [(text, nested) for text, nested in p.headings if text in (EXPECTED, OTHER)]
    return {
        "public_bytes": len(source.encode("utf-8")),
        "marker_exact_count": source.count(MARKER),
        "marker_dom_count": p.marker_sections,
        "css_count": p.css_links,
        "expected_heading_visible_count": headings.count(EXPECTED),
        "expected_heading_exact_html_count": len(exact),
        "other_heading_visible_count": headings.count(OTHER),
        "nested_tags_in_target_headings": [n for _, n in near],
        "original_h2_before_new_marker": bool(source.find(EXPECTED) >= 0 and source.find(MARKER) >= 0 and source.find(EXPECTED) < source.find(MARKER)),
        "absolute_link_present_pre_insertion": "https://na-ryby.eu/soukromy-revir-sterkac-zajeci/" in source,
    }

def run():
    for name, url in (("public", PAGE), ("wp_rest", REST)):
        try:
            req = Request(url, method="GET", headers={
                "User-Agent": "NaRybyReadOnlyRenderDrift/1.0", "Cache-Control": "no-cache"})
            with guarded_urlopen(req, timeout=15) as response:
                raw = response.read(1200001)
                status = getattr(response, "status", 200)
            if len(raw) > 1200000:
                print(name + ": BOUNDED_RESPONSE_EXCEEDED")
                continue
            if name == "public":
                print("NRY_PUBLIC_WATER_RENDER_DRIFT " + json.dumps(
                    {"http": status, **analyze(raw.decode("utf-8", "replace"))},
                    ensure_ascii=False, sort_keys=True))
            else:
                try:
                    rows = json.loads(raw)
                    # Do NOT output arbitrary source content, only the first identity.
                    identities = [{"id": p.get("id"), "slug": p.get("slug"),
                                   "published": p.get("status") == "publish",
                                   "canonical_equals_expected": p.get("link") == PAGE}
                                  for p in rows[:2]] if isinstance(rows, list) else []
                    print("NRY_PUBLIC_WATER_REST_IDENTITY " + json.dumps(
                        {"http": status, "matches": identities}, ensure_ascii=False, sort_keys=True))
                except (ValueError, TypeError, KeyError):
                    print("NRY_PUBLIC_WATER_REST_IDENTITY_UNVERIFIABLE")
        except HTTPError as exc:
            print(name + ": HTTP_" + str(exc.code))
        except (URLError, TimeoutError, OSError):
            print(name + ": NETWORK_UNVERIFIABLE")

if __name__ == "__main__":
    assert sys.argv[1:] == ["--readonly"]
    run()
