#!/usr/bin/env python3
"""GET-only anonymous exact-post WordPress the_content source proof. No tracking requests."""
import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

BASE = "https://na-ryby.eu"
SLUG = ("romanticky-pobyt-pro-dva-s-rybarenim-"
        "nezapomenutelne-chvile-plne-lasky-a-rybolovu")
URL = BASE + "/" + SLUG + "/"
PROBE = URL + "?nry_romantic_guard_probe=source_v1"
REST = BASE + "/wp-json/wp/v2/posts/57013?_fields=id,type,slug,status,modified_gmt,link"


class Capture(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.matches = []
        self.canonicals = []
        self.postids = []

    def handle_comment(self, value):
        prefix = "NRY_ROMANTIC57013_PROBE_V1 "
        data = value.strip()
        if data.startswith(prefix):
            self.matches.append(json.loads(data[len(prefix):]))

    def handle_starttag(self, tag, attrs):
        fields = dict(attrs)
        if tag == "link" and "canonical" in (fields.get("rel") or "").split():
            self.canonicals.append(fields.get("href"))
        if tag == "body":
            self.postids = (fields.get("class") or "").split()


def verify(page, rest):
    if not isinstance(rest, dict) or rest.get("id") != 57013 or \
            rest.get("type") != "post" or rest.get("status") != "publish" or \
            rest.get("slug") != SLUG or rest.get("link") != URL or \
            rest.get("modified_gmt") != "2026-04-19T12:01:55":
        raise ValueError("EXACT_REST_POST_IDENTITY_DRIFT")
    capture = Capture()
    capture.feed(page)
    capture.close()
    if capture.canonicals != [URL] or "postid-57013" not in capture.postids:
        raise ValueError("PUBLIC_POST_IDENTITY_DRIFT")
    if len(capture.matches) != 1:
        raise ValueError("NO_UNIQUE_OPT_IN_GUARD_PROOF")
    proof = capture.matches[0]
    if (proof.get("status") != "NRY_ROMANTIC57013_GUARD_PROBE_V1"
            or proof.get("postId") != 57013
            or proof.get("modifiedGmt") != "2026-04-19 12:01:55"
            or type(proof.get("renderCalls")) is not int
            or not 0 <= proof["renderCalls"] < 100):
        raise ValueError("PHP_PROOF_IDENTITY_INVALID")
    sha = proof.get("rawSourceSha256")
    if not isinstance(sha, str) or re.fullmatch("[a-f0-9]{64}", sha) is None:
        raise ValueError("RAW_WP_SHA_INVALID")
    if type(proof.get("rawSourceBytes")) is not int or not 1000 < proof["rawSourceBytes"] < 1000000:
        raise ValueError("RAW_WP_LENGTH_INVALID")
    if proof["renderCalls"] == 0 or not isinstance(proof.get("best"), dict):
        raise ValueError("NO_CONTENT_FILTER_OBSERVED")
    for row in (proof["best"], proof.get("first")):
        if not isinstance(row, dict) or type(row.get("bytes")) is not int \
                or not 100 <= row["bytes"] < 1000000 or \
                not isinstance(row.get("prefixSha256"), str) or \
                re.fullmatch("[a-f0-9]{64}", row["prefixSha256"]) is None:
            raise ValueError("CONTENT_INPUT_UNSUPPORTED")
        for k in ("oldButtonCount", "headingCount", "categoryLinkCount"):
            if type(row.get(k)) is not int or not 0 <= row[k] < 100:
                raise ValueError("ANCHOR_COUNT_INVALID")
        for k in ("matchesHistoricalRestPrefix", "mainQuery", "inLoop", "eligible",
                  "newSectionPresent"):
            if type(row.get(k)) is not bool:
                raise ValueError("CONTENT_INPUT_FLAG_INVALID")
    if any(proof.get(k) != 0 for k in
           ("wordpressWrites", "merchantRequests", "syntheticClicks")):
        raise ValueError("SIDE_EFFECT_PROOF_INVALID")
    best = proof["best"]
    return {
        "status": "NRY_ROMANTIC57013_CONTENT_INPUT_OBSERVED",
        "postId": 57013, "renderCalls": proof["renderCalls"],
        "rawSourceSha256": sha, "rawSourceBytes": proof["rawSourceBytes"],
        "inputPrefixSha256": best["prefixSha256"],
        "inputBytes": best["bytes"],
        "restPrefixMatchesLoop": best["matchesHistoricalRestPrefix"],
        "oldButtonCount": best["oldButtonCount"],
        "headingCount": best["headingCount"],
        "categoryLinkCount": best["categoryLinkCount"],
        "mainQuery": best["mainQuery"], "inLoop": best["inLoop"],
        "eligible": best["eligible"], "newSectionPresent": best["newSectionPresent"],
        "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
    }


def get(url, cap):
    if url not in (PROBE, REST):
        raise ValueError("UNAPPROVED_PUBLIC_READ")
    req = Request(url, headers={"User-Agent": "NRY-Romantic-ExactReadOnly/1.0",
                                "Cache-Control": "no-cache, no-store"})
    with guarded_urlopen(req, timeout=25, attempts=3) as response:
        raw = response.read(cap + 1)
        if len(raw) > cap:
            raise ValueError("PUBLIC_RESPONSE_TOO_LARGE")
        return raw


def main():
    import sys
    if sys.argv[1:] != ["--verify"]:
        raise ValueError("EXACT_READONLY_VERIFY_ONLY")
    rest = json.loads(get(REST, 12000))
    page = get(PROBE, 2_000_000).decode("utf-8", "replace")
    print(json.dumps(verify(page, rest), sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except (ValueError, OSError, KeyError, TypeError):
        print(json.dumps({"status": "NRY_ROMANTIC57013_SOURCE_PROOF_BLOCKED"}))
        raise SystemExit(1)
