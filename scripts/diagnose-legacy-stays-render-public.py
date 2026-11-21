#!/usr/bin/env python3
"""Bounded GET-only diagnosis of the one historical public accommodation archive.

Emits only fixed content markers, heading texts, and public REST identity.
Does not retrieve seller, affiliate or checkout URLs.
"""
import json
import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

URL = "https://na-ryby.eu/ubytovani-2/"
REST = "https://na-ryby.eu/wp-json/wp/v2/pages?slug=ubytovani-2&_fields=id,slug,status,link"
MARKER = 'data-nry-legacy-stays-entry="v1"'
CSS = "nry-legacy-stays-entry-v1.css"
EXACT_H1 = re.compile(r"<h1\b[^>]*>\s*Ubytování v ČR\s*</h1>", re.I)
FIRST = "Rekreační dům Harvánek"

class Reader(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.headings = []
        self.current = None
        self.text = []
        self.css = 0
        self.marker = 0
    def handle_starttag(self, tag, attrs):
        if tag == "link" and CSS in (dict(attrs).get("href") or ""):
            self.css += 1
        if tag == "section" and dict(attrs).get("data-nry-legacy-stays-entry") == "v1":
            self.marker += 1
        if tag in ("h1","h2","h3") and self.current is None:
            self.current = tag
            self.text = []
    def handle_data(self, data):
        if self.current is not None:
            self.text.append(data)
    def handle_endtag(self, tag):
        if tag == self.current:
            value = " ".join("".join(self.text).split())
            self.headings.append((tag, value[:120]))
            self.current = None

def analyze(html):
    r = Reader()
    r.feed(html)
    r.close()
    h1 = re.findall(r'<h1\\b[^>]*>([\\s\\S]{0,1500}?)</h1>', html, re.I)
    title_tags = re.findall(r'</?\\s*([a-z][a-z0-9]*)\\b', h1[0], re.I) if h1 else []
    return {
        "title_inner_tag_names": sorted(set(tag.lower() for tag in title_tags))[:10],
        "html_bytes": len(html.encode("utf-8")),
        "marker_count": html.count(MARKER),
        "marker_dom": r.marker,
        "css_count": r.css,
        "exact_h1_count": len(EXACT_H1.findall(html)),
        "title_headings": [v for k,v in r.headings if k == "h1"][:5],
        "first_h2_h3": [v for k,v in r.headings if k in ("h2","h3")][:5],
        "first_result_present": FIRST in html,
        "result_after_title": bool(html.find(FIRST) > html.find("Ubytování v ČR")),
    }

def read(url):
    req = Request(url, method="GET",
                  headers={"User-Agent":"NaRybyArchiveReadOnlyProbe/1.0", "Cache-Control":"no-cache"})
    with guarded_urlopen(req, timeout=15) as response:
        raw = response.read(1200001)
    if len(raw) > 1200000:
        raise ValueError("RESPONSE_TOO_LARGE")
    return raw.decode("utf-8", "replace")

def main():
    for name, url in (("html", URL), ("rest", REST)):
        try:
            raw = read(url)
            if name == "html":
                print("NRY_LEGACY_STAYS_HTML_DIAGNOSTIC " + json.dumps(analyze(raw), ensure_ascii=False))
            else:
                rows = json.loads(raw)
                identities = [{"id": row.get("id"),"slug":row.get("slug"),
                    "published":row.get("status")=="publish","canonical":row.get("link")==URL}
                    for row in rows[:2]] if isinstance(rows, list) else []
                print("NRY_LEGACY_STAYS_REST_IDENTITY " + json.dumps(identities))
        except HTTPError as e:
            print("NRY_LEGACY_STAYS_" + name.upper() + "_HTTP_" + str(e.code))
        except (ValueError, OSError, URLError, TimeoutError):
            print("NRY_LEGACY_STAYS_" + name.upper() + "_UNVERIFIABLE")

if __name__ == "__main__":
    assert sys.argv[1:] == ["--readonly"]
    main()
