#!/usr/bin/env python3
"""Read-only CJ property-identity inventory across 16 known buyer guides.

Public WordPress REST *rendered* markup is the evidence; it cannot establish
affiliate entitlement, paid commissions or the WordPress private source hash.
Only first-party GETs are issued. No merchant/CJ GET, test clicks, analytics,
WordPress writes, full HTML exports, query strings or raw affiliate URLs.

Publisher ID provenance: Prometheus Core CJ API evidence, issue #1944
(2026-11-17): Na-ryby.eu 8287231; ChytryRybar.cz 100927011.
The verified property mapping does NOT approve rewriting historical links.
"""

import argparse
import json
import re
from collections import Counter
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from html.parser import HTMLParser
from urllib.parse import urlsplit
from urllib.request import Request, urlopen

SITE = "https://na-ryby.eu"
SITE_PROPERTY = "8287231"
OTHER_SITE_PROPERTY = "100927011"
CJ_HOSTS = frozenset({
    "www.kqzyfj.com", "www.tkqlhce.com", "www.jdoqocy.com",
    "www.dpbolvw.net", "www.anrdoezrs.net",
})
PUBLIC_FIELDS = "id,slug,status,link,content,modified_gmt"
MAX_BYTES = 850_000
MAX_FRONTEND_BYTES = 2_000_000
TRACKED = re.compile(r"^/click-(\d{6,12})-(\d{6,16})(?:-(\d{9,18}))?$")
FRONTEND_IDS = frozenset((61257, 119575))
COHORT = (
    (42401, "jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne"),
    (118218, "nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu"),
    (35755, "pipaky-na-ryby-prakticke-rady-pro-zacatecniky"),
    (36702, "pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu"),
    (46573, "pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare"),
    (118455, "nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz"),
    (118553, "jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce"),
    (60637, "jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare"),
    (36518, "porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023"),
    (49275, "jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun"),
    (61257, "zavazeci-lodky-pro-rybare"),
    (118541, "jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika"),
    (60341, "nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby"),
    (11901, "jak-vybrat-rybarske-kreslo"),
    (118388, "jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti"),
    (119575, "nejlepsi-rybarske-bivaky-do-10-000-kc"),
)
METRIC_KEYS = (
    "cj_own_property", "cj_other_known_site_property", "cj_unknown_property",
    "cj_extended_legacy_shape", "cj_extended_legacy_own_property",
    "cj_extended_legacy_other_known_site", "cj_extended_legacy_unknown_property",
    "cj_invalid_shape", "first_party_cj_path",
    "dognet_outbound", "direct_chytapust_outbound",
)


class LinkInventory(HTMLParser):
    """Count URL shapes in source-provided anchors, without following them."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.counts = Counter()

    def handle_starttag(self, tag, attrs):
        if tag != "a":
            return
        href = dict(attrs).get("href")
        if not isinstance(href, str) or not href:
            return
        try:
            url = urlsplit(href.strip())
        except ValueError:
            return
        host = (url.hostname or "").lower()
        if host in CJ_HOSTS:
            # CJ network domain alone is not proof of entitlement.
            match = TRACKED.fullmatch(url.path)
            if not match or url.scheme not in ("http", "https"):
                self.counts["cj_invalid_shape"] += 1
            elif match.group(3):
                self.counts["cj_extended_legacy_shape"] += 1
                if match.group(1) == SITE_PROPERTY:
                    self.counts["cj_extended_legacy_own_property"] += 1
                elif match.group(1) == OTHER_SITE_PROPERTY:
                    self.counts["cj_extended_legacy_other_known_site"] += 1
                else:
                    self.counts["cj_extended_legacy_unknown_property"] += 1
            elif match.group(1) == SITE_PROPERTY:
                self.counts["cj_own_property"] += 1
            elif match.group(1) == OTHER_SITE_PROPERTY:
                self.counts["cj_other_known_site_property"] += 1
            else:
                self.counts["cj_unknown_property"] += 1
        elif host in ("na-ryby.eu", "www.na-ryby.eu") and \
                url.scheme == "https" and TRACKED.fullmatch(url.path):
            self.counts["first_party_cj_path"] += 1
        elif host == "go.dognet.com" and url.scheme == "https":
            self.counts["dognet_outbound"] += 1
        elif host == "www.chytapust.cz" and url.scheme == "https":
            self.counts["direct_chytapust_outbound"] += 1


def summarize(post_id, slug, payload):
    """Fail closed on identity drift, with no HTML or tracking URL in reports."""
    row = {"post_id": post_id, "url": f"{SITE}/{slug}/",
           "read_only": True, "wordpress_writes": 0}
    if not isinstance(payload, dict) or payload.get("id") != post_id or \
            payload.get("slug") != slug or payload.get("status") != "publish" or \
            payload.get("link") != row["url"]:
        return dict(row, state="PUBLIC_REST_IDENTITY_DRIFT")
    markup = payload.get("content", {}).get("rendered")
    if not isinstance(markup, str) or len(markup) < 200:
        return dict(row, state="PUBLIC_REST_RENDERED_HTML_MISSING")
    parser = LinkInventory()
    parser.feed(markup)
    counts = {name: parser.counts[name] for name in METRIC_KEYS}
    if counts["cj_other_known_site_property"]:
        signal = "KNOWN_OTHER_SITE_PUBLISHER_ID_OBSERVED"
    elif counts["cj_unknown_property"] or counts["cj_extended_legacy_shape"] or \
            counts["cj_invalid_shape"]:
        signal = "CJ_IDENTITY_REVIEW_NEEDED"
    elif counts["cj_own_property"]:
        signal = "OWN_PROPERTY_LINK_SHAPE_OBSERVED_NOT_COMMISSION_PROOF"
    else:
        signal = "NO_CJ_NETWORK_LINK_SHAPE_OBSERVED"
    return dict(row, state="PUBLIC_REST_RENDERED_VERIFIED", signal=signal,
                modified_gmt=payload.get("modified_gmt"), counts=counts)


def fetch_one(post_id, slug):
    url = f"{SITE}/wp-json/wp/v2/posts/{post_id}?_fields={PUBLIC_FIELDS}"
    request = Request(url, headers={
        "User-Agent": "NaRyby-CJ-PropertyIdentity-ReadOnly/1.0",
        "Accept": "application/json", "Cache-Control": "no-cache",
    })
    try:
        with urlopen(request, timeout=25) as response:
            if response.status != 200 or response.geturl() != url or \
                    response.headers.get_content_type() != "application/json":
                raise ValueError("PUBLIC_REST_RESPONSE_INVALID")
            body = response.read(MAX_BYTES + 1)
        if len(body) > MAX_BYTES:
            raise ValueError("PUBLIC_REST_TOO_LARGE")
        return summarize(post_id, slug, json.loads(body))
    except (OSError, TimeoutError, ValueError, json.JSONDecodeError) as exc:
        # Only the exception class; never echo a possibly sensitive URL/body.
        return {"post_id": post_id, "url": f"{SITE}/{slug}/",
                "state": "PUBLIC_REST_UNAVAILABLE", "diagnostic": type(exc).__name__,
                "read_only": True, "wordpress_writes": 0}


def frontend_readback(post_id, slug, rest_row):
    """Independently compare cross-site CJ counts on the public frontend."""
    url = f"{SITE}/{slug}/"
    result = {"post_id": post_id, "url": url, "read_only": True,
              "merchant_requests": 0, "wordpress_writes": 0}
    if rest_row["state"] != "PUBLIC_REST_RENDERED_VERIFIED":
        return dict(result, state="SOURCE_NOT_VERIFIED")
    req = Request(url, headers={"User-Agent": "NaRyby-CJ-Frontend-ReadOnly/1.0",
                                "Cache-Control": "no-cache"})
    try:
        with urlopen(req, timeout=25) as response:
            if response.status != 200 or response.geturl() != url or \
                    response.headers.get_content_type() != "text/html":
                raise ValueError("PUBLIC_FRONTEND_RESPONSE_INVALID")
            body = response.read(MAX_FRONTEND_BYTES + 1)
        if len(body) > MAX_FRONTEND_BYTES:
            raise ValueError("PUBLIC_FRONTEND_TOO_LARGE")
        links = LinkInventory()
        links.feed(body.decode("utf-8"))
        actual = links.counts["cj_other_known_site_property"]
        expected = rest_row["counts"]["cj_other_known_site_property"]
        return dict(result,
                    state="FRONTEND_CROSS_SITE_COUNT_MATCH" if actual == expected
                          else "FRONTEND_CROSS_SITE_COUNT_DRIFT",
                    rest_cross_site_count=expected, frontend_cross_site_count=actual,
                    frontend_own_property_count=links.counts["cj_own_property"])
    except (OSError, TimeoutError, ValueError, UnicodeError) as exc:
        return dict(result, state="PUBLIC_FRONTEND_UNAVAILABLE",
                    diagnostic=type(exc).__name__)


def collect():
    with ThreadPoolExecutor(max_workers=5) as pool:
        rows = list(pool.map(lambda args: fetch_one(*args), COHORT))
    valid = [r for r in rows if r["state"] == "PUBLIC_REST_RENDERED_VERIFIED"]
    totals = {key: sum(r["counts"][key] for r in valid) for key in METRIC_KEYS}
    readback = [frontend_readback(post_id, slug, rows[n])
                for n, (post_id, slug) in enumerate(COHORT)
                if post_id in FRONTEND_IDS]
    return {
        "site": SITE,
        "checked_utc": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "cohort_count": len(COHORT), "verified_count": len(valid),
        "known_other_site_property_pages": sum(
            r["counts"]["cj_other_known_site_property"] > 0 for r in valid),
        "counts": totals, "rows": rows, "frontend_readback": readback,
        "frontend_cross_site_counts_match": len(readback) == len(FRONTEND_IDS) and
            all(r["state"] == "FRONTEND_CROSS_SITE_COUNT_MATCH" for r in readback),
        "public_rendered_only": True, "private_source_verified": False,
        "affiliate_entitlement_verified": False, "paid_commissions_verified": False,
        "affiliate_link_changes": 0, "merchant_requests": 0,
        "synthetic_clicks": 0, "wordpress_writes": 0,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--require-complete", action="store_true",
                        help="Nonzero exit on incomplete public REST evidence")
    args = parser.parse_args()
    report = collect()
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 1 if args.require_complete and \
        (report["verified_count"] != report["cohort_count"] or
         not report["frontend_cross_site_counts_match"]) else 0


if __name__ == "__main__":
    raise SystemExit(main())
