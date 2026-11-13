#!/usr/bin/env python3
"""Live, anonymous, read-only buyer click instrumentation readiness probe.

Never fires a click, sends GA4 data, calls a merchant, fetches any off-site
script, logs visitor IDs/hrefs or treats an enqueued script as delivered events.
"""
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

from readonly_urlopen_retry import guarded_urlopen

SITE = "https://na-ryby.eu"
PAGES = {
    "pipaky_guide": "/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/",
    "alarm_guide": "/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/",
    "sonar_pillar": "/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/",
    "baitboat_guide": "/zavazeci-lodky-pro-rybare/",
    "unscoped_control": "/nejlepsi-rybarske-hlasice-a-signalizatory/",
}
ASSET = "/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js"
OUTPUT = "buyer-click-live-readonly.json"
ROOT = Path(__file__).resolve().parents[1]


class Scripts(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.matches = []
    def handle_starttag(self, tag, attrs):
        if tag.lower() != "script":
            return
        src = dict(attrs).get("src")
        if not src or not isinstance(src, str):
            return
        parts = urlsplit(src)
        if parts.scheme and (parts.scheme != "https" or parts.netloc != "na-ryby.eu"):
            return
        if not parts.scheme and (src.startswith("//") or not src.startswith("/")):
            return
        if parts.path != ASSET or parts.fragment:
            return
        # Strict cache-busting query only; not merchant parameters or trackers.
        if parts.query not in ("", "ver=1.4.0"):
            return
        self.matches.append(src)


def checked_fetch(url, maximum):
    req = Request(url, headers={
        "Cache-Control": "no-cache,no-store",
        "User-Agent": "NaRybyBuyerClickMeasurementReadOnly/1.0",
    }, method="GET")
    with guarded_urlopen(req, timeout=25) as response:
        data = response.read(maximum + 1)
        if response.status != 200:
            raise ValueError("EXACT_PUBLIC_READBACK_UNVERIFIABLE")
        if len(data) > maximum:
            raise ValueError("MAX_BYTES_EXCEEDED")
    return data


def inspect_document(html):
    parser = Scripts()
    parser.feed(html.decode("utf-8", "replace"))
    parser.close()
    return len(parser.matches)


def audit(fetch=checked_fetch):
    observed = {}
    for role, path in PAGES.items():
        try:
            count = inspect_document(fetch(SITE + path, 2000000))
        except Exception as exc:
            safe_reason = str(exc) if str(exc) in (
                "EXACT_PUBLIC_READBACK_UNVERIFIABLE",
                "MAX_BYTES_EXCEEDED",
                "READONLY_REDIRECT_OR_ORIGIN_DRIFT",
                "READONLY_HTTP_NON_200",
                "READONLY_EXACT_SITE_REQUIRED",
            ) else type(exc).__name__
            raise ValueError("PAGE_" + role.upper() + "_" + safe_reason) from None
        observed[role] = {
            "scriptTagCount": count,
            "state": ("DIRECT_SCRIPT_ENQUEUED" if count == 1
                      else "DIRECT_SCRIPT_NOT_OBSERVABLE" if count == 0
                      else "DUPLICATE_SCRIPT_TAGS"),
        }
    if observed["pipaky_guide"]["scriptTagCount"] == 1 or observed["alarm_guide"]["scriptTagCount"] == 1:
        # The bundled asset may be served from WordPress; only a fixed local
        # same-origin path is requested, never a captured arbitrary src URL.
        try:
            live = fetch(SITE + ASSET + "?ver=1.4.0", 20000)
        except Exception as exc:
            safe_reason = str(exc) if str(exc) in (
                "EXACT_PUBLIC_READBACK_UNVERIFIABLE",
                "MAX_BYTES_EXCEEDED",
                "READONLY_REDIRECT_OR_ORIGIN_DRIFT",
                "READONLY_HTTP_NON_200",
            ) else type(exc).__name__
            raise ValueError("LOCAL_JS_ASSET_" + safe_reason) from None
        expected = (ROOT / "assets/nry-buyer-clicks-v1.js").read_bytes()
        asset_state = "BYTE_IDENTICAL_TO_REVIEWED_JS" if hashlib.sha256(live).digest() == hashlib.sha256(expected).digest() else "ASSET_DRIFT_UNVERIFIED"
    else:
        asset_state = "NO_DIRECT_SCRIPT_TAG_TO_VALIDATE"
    return {
        "site": SITE, "mode": "ANONYMOUS_GET_ONLY", "wordpressWrites": 0,
        "merchantRequests": 0, "syntheticClickEvents": 0,
        "ga4DeliveryProven": False, "paidCommissionProven": False,
        "assetIntegrity": asset_state, "pages": observed,
        "interpretation": "Enqueue and byte integrity only. GA4 consent, ingestion, paid CJ commissions and attribution require separate authenticated evidence.",
    }


def main():
    try:
        result = audit()
    except Exception as exc:
        # Evidence must be preserved without hiding a network/identity problem.
        result = {
            "site": SITE, "mode": "ANONYMOUS_GET_ONLY",
            "state": "PUBLIC_RUNTIME_UNVERIFIABLE", "errorType": type(exc).__name__,
            "errorCode": str(exc) if str(exc).startswith(("PAGE_", "LOCAL_JS_ASSET_"))
                         and len(str(exc)) < 110 else "UNCLASSIFIED_READBACK_FAILURE",
            "wordpressWrites": 0, "merchantRequests": 0,
            "syntheticClickEvents": 0, "ga4DeliveryProven": False,
        }
    Path(OUTPUT).write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf8")
    print("NRY_BUYER_CLICK_LIVE_READINESS", json.dumps(result, ensure_ascii=False, sort_keys=True))
    # Missing tags are a real observation, not grounds to fabricate GA4 data.
    # Transport exceptions fail the readiness audit, not the unrelated public
    # manifest verifier (separate step and contract).
    return 1 if result.get("state") == "PUBLIC_RUNTIME_UNVERIFIABLE" else 0


if __name__ == "__main__":
    raise SystemExit(main())
