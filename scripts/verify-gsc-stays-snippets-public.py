#!/usr/bin/env python3
"""Three exact GSC-backed SEO pages: bounded GET-only WordPress hook and public head proof.

No WordPress writes, merchant requests, synthetic clicks, cookies or analytics events.
--inspect reports why a deployed snippet guard passes/fails without asserting it live.
--verify requires exact public title + description + canonical on all three pages.
"""
import argparse
import json
import re
from concurrent.futures import ThreadPoolExecutor
from html import unescape
from html.parser import HTMLParser
from urllib.parse import urlencode
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

PAGES = {
    14517: (
        "https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/",
        "Věstonická nádrž (Dyje 6): lov, povolenky, přístup",
        "Věstonická nádrž (Dyje 6): kde se smí chytat, jak ověřit místní povolenku a podmínky na Nových Mlýnech. Praktické tipy před cestou.",
    ),
    17591: (
        "https://na-ryby.eu/chata-brnenska-prehrada/",
        "Chata Brněnská přehrada: informace a okolní pobyty",
        "Chata Brněnská přehrada: starší popis objektu a další tipy na ubytování u vody. Před rezervací ověřte termín, dostupnost i pravidla rybolovu.",
    ),
    12166: (
        "https://na-ryby.eu/rybarska-dovolena-jizni-morava/",
        "Rybářská dovolená jižní Morava: chaty a revíry",
        "Rybářská dovolená na jižní Moravě: přehled rybářských chat, Nové Mlýny a Brněnská přehrada. Jak vybrat pobyt a ověřit pravidla lovu.",
    ),
}
COMMENT = re.compile(r"<!-- NRY_GSC_STAYS_SNIPPET_PROBE_V1 (\{[^<>]{1,2400}\}) -->")


class Head(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title = ""
        self.description = ""
        self.canonical = ""
        self._title = False

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == "title":
            self._title = True
        if tag == "meta" and data.get("name", "").lower() == "description":
            self.description = data.get("content", "")
        if tag == "link" and "canonical" in data.get("rel", "").lower().split():
            self.canonical = data.get("href", "")

    def handle_endtag(self, tag):
        if tag == "title":
            self._title = False

    def handle_data(self, data):
        if self._title:
            self.title += data


def inspect_html(html, post_id):
    matches = COMMENT.findall(html)
    assert len(matches) == 1, f"PROBE_MISSING_OR_DUPLICATE_{post_id}"
    row = json.loads(matches[0])
    assert row.get("status") == "NRY_GSC_STAYS_SNIPPET_PROBE_V1"
    assert row.get("postId") == post_id
    assert (row.get("wordpressWrites"), row.get("merchantRequests"),
            row.get("syntheticClicks")) == (0, 0, 0)
    for kind in ("title", "description"):
        part = row[kind]
        assert type(part["calls"]) is int and 0 <= part["calls"] <= 100
        assert type(part["applied"]) is bool
        first = part["first"]
        if first is not None:
            digest = first.get("inputSha256")
            assert digest is None or re.fullmatch("[0-9a-f]{64}", digest)
            assert type(first["baselineMatched"]) is bool
            assert type(first["applied"]) is bool
            assert first["inputBytes"] is None or 0 <= first["inputBytes"] <= 1024
    head = Head()
    head.feed(html)
    expected_url, title, desc = PAGES[post_id]
    result = {
        "postId": post_id, "canonicalOK": head.canonical == expected_url,
        "titleOK": unescape(head.title.strip()) == title,
        "descriptionOK": unescape(head.description.strip()) == desc,
        "sourceMatches": row["sourceMatches"], "modifiedMatches": row["modifiedMatches"],
        "eligible": row["eligible"],
        "titleCalls": row["title"]["calls"], "titleApplied": row["title"]["applied"],
        "titleBaselineMatched": row["title"]["first"]["baselineMatched"] if row["title"]["first"] else None,
        "descriptionCalls": row["description"]["calls"],
        "descriptionApplied": row["description"]["applied"],
        "descriptionBaselineMatched": row["description"]["first"]["baselineMatched"]
            if row["description"]["first"] else None,
        "titleInputSha256": row["title"]["first"]["inputSha256"] if row["title"]["first"] else None,
        "descriptionInputSha256": row["description"]["first"]["inputSha256"]
            if row["description"]["first"] else None,
    }
    assert all(type(result[k]) is bool for k in (
        "canonicalOK", "titleOK", "descriptionOK", "sourceMatches",
        "modifiedMatches", "eligible")), "UNBOUNDED_STATE"
    return result


def fetch(post_id):
    url = PAGES[post_id][0] + "?" + urlencode(
        {"nry_gsc_snippet_probe": "source_v1", "source": "github_readonly"})
    request = Request(url, headers={"Cache-Control": "no-cache",
                                    "User-Agent": "NaRybyGSCStaysProof/1.0"})
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200, f"HTTP_{post_id}_{response.status}"
        # Dyje 6 already uses the same reviewed 1.6 MB public-HTML cap
        # in verify-dyje6-stay-public.py; 400 kB truncated legitimate pages.
        content = response.read(1_600_001)
        assert len(content) <= 1_600_000, f"HTML_TOO_LARGE_{post_id}"
    return inspect_html(content.decode("utf-8", "replace"), post_id)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--inspect", action="store_true")
    parser.add_argument("--verify", action="store_true")
    args = parser.parse_args()
    assert args.inspect != args.verify, "CHOOSE_INSPECT_OR_VERIFY"
    with ThreadPoolExecutor(max_workers=3) as pool:
        rows = list(pool.map(fetch, PAGES))
    for row in rows:
        print(json.dumps(row, sort_keys=True, ensure_ascii=False))
    if args.verify:
        for row in rows:
            assert all(row[k] is True for k in (
                "canonicalOK", "titleOK", "descriptionOK", "eligible",
                "sourceMatches", "modifiedMatches")), "GSC_SNIPPET_NOT_PUBLIC_VERIFIED"
        state = "NRY_GSC_STAYS_SNIPPETS_PUBLIC_VERIFIED"
    else:
        state = "NRY_GSC_STAYS_SNIPPETS_DIAGNOSTIC_COMPLETE"
    print(json.dumps({"status": state, "pages": len(rows),
        "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0}))


if __name__ == "__main__":
    main()
