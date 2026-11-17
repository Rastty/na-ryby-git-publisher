#!/usr/bin/env python3
"""GET-only live SEO readback for the two exact Na-Ryby page URLs.

No authentication, merchant requests, WordPress writes or analytics events.
"""
from html.parser import HTMLParser
from urllib.parse import urlsplit
from urllib.request import Request, HTTPRedirectHandler, build_opener
import sys
import time

PAGES = {
    "https://na-ryby.eu/": (
        "Rybářské vybavení a praktické návody | Na-Ryby.eu",
        "Vyberte rybářský prut, naviják, echolot, zavážecí loďku nebo křeslo podle způsobu lovu a rozpočtu. Praktické návody, srovnání a poradce výběrem.",
    ),
    "https://na-ryby.eu/poradce-vybavy/": (
        "Poradce výběrem prutu a navijáku | Na-Ryby.eu",
        "Odpovězte na pět otázek a získejte orientační doporučení prutu a navijáku podle stylu rybaření, typu vody a rozpočtu. Vyberte vybavení s rozmyslem.",
    ),
}


class Head(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.in_title = False
        self.title = ""
        self.meta = {}
        self.canonicals = []

    def handle_starttag(self, tag, attrs):
        fields = dict(attrs)
        if tag == "title":
            self.in_title = True
        elif tag == "meta":
            key = (fields.get("name") or fields.get("property") or "").lower()
            self.meta.setdefault(key, []).append(fields.get("content", ""))
        elif tag == "link" and "canonical" in (fields.get("rel") or "").lower().split():
            self.canonicals.append(fields.get("href"))

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title += data


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None


OPENER = build_opener(NoRedirect)


def verify_html(url, html, after_deploy=True):
    assert url in PAGES, "OUT_OF_SCOPE_URL"
    head = Head()
    head.feed(html)
    assert head.canonicals == [url], "CANONICAL_DRIFT: " + url
    assert head.meta.get("robots") and "noindex" not in head.meta["robots"][0].lower(), "NOT_INDEXABLE: " + url
    if after_deploy:
        expected_title, expected_desc = PAGES[url]
        assert head.title.strip() == expected_title, "TITLE_NOT_PUBLIC: " + url
        assert head.meta.get("description") == [expected_desc], "DESCRIPTION_NOT_PUBLIC: " + url
    return {"url": url, "title": head.title.strip(),
            "description_present": bool(head.meta.get("description")),
            "og_title": (head.meta.get("og:title") or [None])[0],
            "og_description_present": bool(head.meta.get("og:description"))}


def fetch(url):
    parts = urlsplit(url)
    assert url in PAGES and parts.scheme == "https" and parts.netloc == "na-ryby.eu", "FIRST_PARTY_ONLY"
    req = Request(url, headers={"User-Agent": "NaRybySEOReadOnly/1.0", "Cache-Control": "no-cache"})
    with OPENER.open(req, timeout=24) as response:
        assert response.status == 200 and response.geturl() == url, "HTTP_OR_REDIRECT_DRIFT: " + url
        return response.read(1500000).decode("utf-8", "replace")


def run(after_deploy):
    result = [verify_html(url, fetch(url), after_deploy) for url in PAGES]
    return {"state": "PUBLIC_SEO_VERIFIED" if after_deploy else "TWO_URL_PREFLIGHT_VERIFIED",
            "pages": result, "wordpress_writes": 0, "merchant_requests": 0,
            "analytics_events": 0}


if __name__ == "__main__":
    after = "--preflight" not in sys.argv
    for attempt in range(3):
        try:
            print(run(after))
            break
        except Exception as exc:
            print("PUBLIC_RETRY", attempt + 1, type(exc).__name__, flush=True)
            if attempt == 2:
                raise SystemExit("BLOCKED: two-page public SEO readback failed")
            time.sleep(5)
