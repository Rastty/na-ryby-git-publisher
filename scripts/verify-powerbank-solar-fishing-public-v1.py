#!/usr/bin/env python3
"""Independent, GET-only production verifier for the new fishing energy guide.

Use only AFTER an authorized exact-SHA FTPS release. No merchant requests,
affiliate click probes, WordPress writes, synthetic traffic or login.
"""
import argparse
import hashlib
import json
from datetime import datetime, timezone
from html.parser import HTMLParser
from urllib.request import Request, urlopen

GUIDE = "https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/"
ENTRY = "https://na-ryby.eu/zavazeci-lodky-pro-rybare/"
SHA = "6a8823edae2ed37f041ec0b800afc548a144d4bedd3f4756956393c8a53671ee"
MAX_GUIDE_BYTES = 200_000
# The existing WordPress entry article contains a full long-form page shell.
# Keep the exact new guide tightly bounded, but allow the legacy entry its own cap.
MAX_ENTRY_BYTES = 1_000_000
DIAGNOSTIC_CODES = {
    f"{kind}_{problem}"
    for kind in ("GUIDE", "ENTRY")
    for problem in ("HTTP_INVALID", "REDIRECT_INVALID", "TYPE_INVALID", "TOO_LARGE")
}


class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hrefs = []
        self.canonical = []
        self.body_markers = []

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == "a" and isinstance(data.get("href"), str):
            self.hrefs.append(data["href"])
        if tag == "link" and data.get("rel") == "canonical":
            self.canonical.append(data.get("href"))
        if tag == "body":
            self.body_markers.append(data.get("data-nry-power-guide"))


def fetch(url, kind):
    if kind not in ("GUIDE", "ENTRY"):
        raise ValueError("INVALID_PAGE_KIND")
    limit = MAX_GUIDE_BYTES if kind == "GUIDE" else MAX_ENTRY_BYTES
    req = Request(url, headers={"User-Agent": "NaRyby-Power-Guide-Public-Readback/1.0",
                                "Accept": "text/html", "Cache-Control": "no-cache"})
    with urlopen(req, timeout=22) as res:
        if res.status != 200:
            raise ValueError(f"{kind}_HTTP_INVALID")
        if res.geturl() != url:
            raise ValueError(f"{kind}_REDIRECT_INVALID")
        if res.headers.get_content_type() != "text/html":
            raise ValueError(f"{kind}_TYPE_INVALID")
        body = res.read(limit + 1)
        if len(body) > limit:
            raise ValueError(f"{kind}_TOO_LARGE")
        signature = res.headers.get("X-NRY-Power-Guide-SHA256", "")
    return body.decode("utf-8"), signature


def validate(guide_html, entry_html, signature):
    guide = Links()
    guide.feed(guide_html)
    entry = Links()
    entry.feed(entry_html)
    states = {
        "html_sha_match": hashlib.sha256(guide_html.encode("utf-8")).hexdigest() == SHA,
        "header_sha_match": signature == SHA,
        "route_marker": guide.body_markers == ["v1"],
        "canonical": guide.canonical == [GUIDE],
        "calculator": guide_html.count('data-nry-whcalc="v1"') == 1,
        "source_fox_48k": any("fox-halo-48k-wireless-power-pack" in x for x in guide.hrefs),
        "source_fox_96k": any("fox-halo-96k-wireless-power-pack" in x for x in guide.hrefs),
        "entry_discoverability": entry.hrefs.count(GUIDE) == 1,
        "guide_has_no_affiliate_click_host": not any(
            host in link for link in guide.hrefs for host in
            ("dpbolvw.net", "kqzyfj.com", "tkqlhce.com", "jdoqocy.com", "anrdoezrs.net")
        ),
        "entry_exact_one_marker": entry_html.count('data-nry-power-energy-entry="v1"') == 1,
    }
    return states


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--verify", action="store_true", help="Exit nonzero unless every readback assertion passes")
    args = parser.parse_args()
    report = {"checked_utc": datetime.now(timezone.utc).isoformat(),
              "site": "na-ryby.eu", "kind": "PUBLIC_READ_ONLY", "wordpress_writes": 0,
              "merchant_requests": 0, "affiliate_clicks": 0}
    try:
        guide_html, signature = fetch(GUIDE, "GUIDE")
        entry_html, _ = fetch(ENTRY, "ENTRY")
        checks = validate(guide_html, entry_html, signature)
        report.update(checks=checks,
                      status="POWER_GUIDE_PUBLIC_VERIFIED" if all(checks.values())
                             else "POWER_GUIDE_PUBLIC_MISMATCH")
    except (OSError, TimeoutError, ValueError, UnicodeError) as exc:
        # Only our fixed diagnostic codes leave CI; never leak raw URL errors or headers.
        diagnostic = str(exc) if isinstance(exc, ValueError) and str(exc) in DIAGNOSTIC_CODES else type(exc).__name__
        report.update(status="POWER_GUIDE_PUBLIC_UNAVAILABLE", diagnostic=diagnostic)
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 0 if report["status"] == "POWER_GUIDE_PUBLIC_VERIFIED" or not args.verify else 1


if __name__ == "__main__":
    raise SystemExit(main())
