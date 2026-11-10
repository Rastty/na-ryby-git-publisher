#!/usr/bin/env python3
"""Independent, GET-only source-to-public proof for sonar #118455 outbound telemetry.

Do NOT visit merchant/affiliate destinations, click links, send analytics,
interpret CTA as sale, or use visitor identifiers. JS bytes must equal the
reviewed repository file, not merely have a matching version query.
"""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit, parse_qsl
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ORIGIN = "https://na-ryby.eu"
PAGE = "/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/"
ASSET = "/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js"
VERSION = "ver=1.2.0"
ROOT = Path(__file__).resolve().parents[1]


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.tags = []
        self.canonicals = []
        self.dognet_links = 0
        self.merchant_links = 0
        self.merchant_bare_links = 0
        self.merchant_affiliate_parameter_hints = 0
        self.merchant_campaign_only_links = 0
        self.merchant_unclassified_query_links = 0
        self._merchant_distinct_targets = set()

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "link" and attrs.get("rel") == "canonical":
            self.canonicals.append(attrs.get("href"))
        if tag == "script":
            url = attrs.get("src")
            if isinstance(url, str):
                parsed = urlsplit(url)
                if parsed.scheme == "https" and parsed.netloc == "na-ryby.eu" or (
                    not parsed.scheme and not parsed.netloc and url.startswith("/")):
                    if parsed.path == ASSET and not parsed.fragment:
                        self.tags.append(parsed.query)
        if tag == "a":
            url = attrs.get("href")
            if not isinstance(url, str):
                return
            parsed = urlsplit(url)
            if parsed.scheme != "https" or parsed.username or parsed.password or parsed.port:
                return
            if parsed.hostname == "go.dognet.com" and (
                len(parsed.path) > 1 or len(parsed.query) > 1):
                self.dognet_links += 1
            if parsed.hostname == "www.chytapust.cz" and len(parsed.path) > 1:
                self.merchant_links += 1
                # Do not emit paths, query values, affiliate codes, SKUs or
                # target hashes. A parameter hint is NOT proof that a partner
                # program credited an order or even recognized the tag.
                self._merchant_distinct_targets.add(url)
                try:
                    keys = {name.lower() for name, _ in parse_qsl(
                        parsed.query, keep_blank_values=True, max_num_fields=32)}
                except ValueError:
                    self.merchant_unclassified_query_links += 1
                    return
                if not keys:
                    self.merchant_bare_links += 1
                elif keys.intersection({
                    "a_box", "affiliate", "affiliate_id", "aff", "affid",
                    "ref", "refid", "partner", "partner_id", "subid"
                }):
                    self.merchant_affiliate_parameter_hints += 1
                elif any(key.startswith("utm_") for key in keys):
                    self.merchant_campaign_only_links += 1
                else:
                    self.merchant_unclassified_query_links += 1


def verify(html: bytes, installed_js: bytes, reviewed_js: bytes):
    page = Page()
    page.feed(html.decode("utf-8", "replace"))
    assert page.canonicals == [ORIGIN + PAGE], "CANONICAL_MISMATCH"
    assert page.tags == [VERSION], "BUYER_JS_TAG_MISSING_OR_VERSION_DRIFT"
    assert page.dognet_links > 0, "DOGNET_CTA_NOT_OBSERVABLE"
    assert page.merchant_links > 0, "DIRECT_MERCHANT_CTA_NOT_OBSERVABLE"
    assert (page.merchant_bare_links + page.merchant_affiliate_parameter_hints
            + page.merchant_campaign_only_links
            + page.merchant_unclassified_query_links == page.merchant_links), (
        "MERCHANT_LINK_CLASSIFICATION_CARDINALITY_DRIFT")
    assert hashlib.sha256(installed_js).digest() == hashlib.sha256(reviewed_js).digest(), (
        "INSTALLED_JS_BYTES_DIFFER_FROM_REVIEWED_SOURCE")
    return {"status": "PUBLIC_ASSET_VERIFIED", "buyer_page": "echolot_budget",
            "dognet_cta_count": page.dognet_links,
            "merchant_cta_count": page.merchant_links,
            "merchant_distinct_destination_count": len(page._merchant_distinct_targets),
            "merchant_bare_url_count": page.merchant_bare_links,
            "merchant_affiliate_parameter_hint_count":
                page.merchant_affiliate_parameter_hints,
            "merchant_campaign_only_url_count": page.merchant_campaign_only_links,
            "merchant_other_query_url_count": page.merchant_unclassified_query_links,
            "attribution_qualification": "URL_HINTS_ONLY_NOT_VERIFIED_AFFILIATE_CREDIT",
            "asset_byte_identical": True,
            "wordPressWrites": 0, "merchantRequests": 0,
            "syntheticClicks": 0, "ga4DeliveryProven": False,
            "paidCommissionsProven": False}


def get(url: str, max_bytes: int):
    if not (url == ORIGIN + PAGE or url == ORIGIN + ASSET + "?" + VERSION):
        raise ValueError("READONLY_FIXED_URL_REQUIRED")
    req = Request(url, headers={"User-Agent": "NaRyby-SonarOutboundPublicReadback/1.0",
                                "Cache-Control": "no-cache,no-store"})
    with guarded_urlopen(req, timeout=25) as response:
        if response.status != 200:
            raise ValueError("PUBLIC_HTTP_NON_200")
        data = response.read(max_bytes + 1)
    if len(data) > max_bytes:
        raise ValueError("PUBLIC_MAX_BYTES_EXCEEDED")
    return data


if __name__ == "__main__":
    print(json.dumps(verify(
        get(ORIGIN + PAGE, 1800000),
        get(ORIGIN + ASSET + "?" + VERSION, 40000),
        (ROOT / "assets/nry-buyer-clicks-v1.js").read_bytes()
    ), ensure_ascii=False))
