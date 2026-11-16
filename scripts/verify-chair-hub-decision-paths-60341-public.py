#!/usr/bin/env python3
"""Anonymous readback of chair hub decision paths, including exact target URLs.

GET only on na-ryby.eu; no merchant URLs, tracking clicks, WP writes or auth.
Checks public HTML/canonical and exact links; not stock, claims or commissions.
"""
import json
import sys
from html.parser import HTMLParser
from urllib.request import Request
from readonly_urlopen_retry import guarded_urlopen

HUB = "https://na-ryby.eu/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/"
TARGETS = (
    "https://na-ryby.eu/jak-vybrat-rybarske-kreslo/",
    "https://na-ryby.eu/rybarske-kreslo-pro-tezsi-rybare-s-nosnosti-120-kg/",
    "https://na-ryby.eu/rybarske-kreslo-s-nosnosti-200-kg-pro-nadsence-lovu-na-tezkou-vahu/",
)
MARKER = "data-nry-chair-decision-paths=\"60341-20261116-v1\""
CLEANUP = "<!-- nry-chair-hub-historical-ratings-60341-v1 -->"


class Links(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical = []
        self.nav_depth = 0
        self.in_nav = False
        self.links = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "link" and attrs.get("rel") == "canonical":
            self.canonical.append(attrs.get("href"))
        if tag == "nav" and attrs.get("data-nry-chair-decision-paths") == "60341-20261116-v1":
            if self.in_nav:
                raise ValueError("NESTED_DECISION_NAV")
            self.in_nav = True
            self.nav_depth = 1
        elif self.in_nav and tag == "nav":
            self.nav_depth += 1
        if self.in_nav and tag == "a":
            self.links.append(attrs.get("href"))

    def handle_endtag(self, tag):
        if tag == "nav" and self.in_nav:
            self.nav_depth -= 1
            if not self.nav_depth:
                self.in_nav = False


def fetch(url):
    request = Request(url, headers={
        "User-Agent": "NRY-ChairHub-DecisionReadback/1.0",
        "Cache-Control": "no-cache, no-store",
    })
    with guarded_urlopen(request, timeout=25, attempts=3) as response:
        if response.status != 200 or response.geturl() != url:
            raise ValueError("EXACT_PUBLIC_URL_STATUS_REQUIRED: " + url)
        body = response.read(900001)
        if len(body) > 900000:
            raise ValueError("PUBLIC_HTML_EXCEEDS_LIMIT: " + url)
        return body.decode("utf-8")


def verify_hub(html):
    if html.count(MARKER) != 1 or html.count(CLEANUP) != 1:
        raise ValueError("DECISION_PANEL_OR_REVIEWED_CLEANUP_NOT_LIVE")
    if html.count('data-nry-editorial-edit="chair-hub-trust-60341-v1"') != 1:
        raise ValueError("SOURCE_EDITORIAL_MARKER_MISSING")
    parsed = Links()
    parsed.feed(html)
    if parsed.canonical != [HUB]:
        raise ValueError("HUB_CANONICAL_DRIFT")
    if parsed.in_nav or parsed.links != list(TARGETS):
        raise ValueError("EXACT_THREE_DECISION_LINKS_NOT_PRESENT")
    return len(parsed.links)


def verify_destination(html, url):
    parsed = Links()
    parsed.feed(html)
    if parsed.canonical != [url]:
        raise ValueError("DESTINATION_CANONICAL_DRIFT: " + url)


def main():
    try:
        count = verify_hub(fetch(HUB))
        for target in TARGETS:
            verify_destination(fetch(target), target)
        print(json.dumps({
            "state": "PUBLIC_CHAIR_HUB_THREE_DECISION_PATHS_VERIFIED",
            "hub": HUB, "internal_routes": count,
            "targets_http_200_canonical": len(TARGETS),
            "merchantRequests": 0, "wordpressWrites": 0,
            "stockOrPricesVerified": False,
            "commissionAttributionVerified": False,
        }, sort_keys=True))
        return 0
    except (OSError, ValueError, UnicodeError) as error:
        print(json.dumps({
            "state": "PUBLIC_UNVERIFIED", "reason": str(error),
            "merchantRequests": 0, "wordpressWrites": 0,
        }, ensure_ascii=False))
        return 1


if __name__ == "__main__":
    sys.exit(main())
