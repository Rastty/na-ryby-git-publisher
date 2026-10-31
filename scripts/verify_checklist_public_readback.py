#!/usr/bin/env python3
"""Read-only, fail-closed public verification AFTER checklist admin Apply.

Never performs a WordPress write, admin action, or redirect follow.
A successful GitHub CI run is NOT proof that the site is live.
"""
import hashlib
import json
import sys
import urllib.error
import urllib.request
from pathlib import Path

URL = "https://na-ryby.eu/co-sbalit-na-ryby/"
ASSET = Path(__file__).resolve().parents[1] / "assets/checklist-standalone.html"
MAX_BYTES = 150_000


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def verify_response(status, final_url, headers, body, expected):
    """Return a list of explicit failed gates; empty means verified."""
    # HTTPMessage.items() preserves repeated fields. Converting it straight to
    # a dict silently drops duplicates, so a forged SHA/CSP header could pass
    # this release check while an intermediary/browser sees ambiguous values.
    h = {}
    duplicates = set()
    for key, value in headers.items():
        name = str(key).lower()
        if name in h:
            duplicates.add(name)
        h[name] = str(value)
    errors = []
    critical = {
        "content-type", "x-nry-checklist-asset-sha256", "cache-control",
        "content-security-policy", "x-content-type-options",
    }
    if duplicates & critical:
        errors.append("duplicate_release_or_security_header")
    sha = hashlib.sha256(expected).hexdigest()
    if status != 200:
        errors.append("http_status_not_200")
    if final_url != URL:
        errors.append("unexpected_origin_or_redirect")
    if not h.get("content-type", "").lower().startswith("text/html"):
        errors.append("unexpected_content_type")
    if h.get("x-nry-checklist-asset-sha256", "").lower() != sha:
        errors.append("approval_sha_header_missing_or_wrong")
    if "no-store" not in h.get("cache-control", "").lower():
        errors.append("cache_no_store_missing")
    # Exact allowlist, not substring matching: "connect-src 'none' https:"
    # contains the old check's text but permits outbound connections in CSP.
    # Duplicate directives or added sources are likewise rejected.
    csp = h.get("content-security-policy", "").lower()
    expected_csp = {
        "default-src": ("'none'",),
        "script-src": ("'unsafe-inline'",),
        "style-src": ("'unsafe-inline'",),
        "img-src": ("data:",),
        "connect-src": ("'none'",),
        "base-uri": ("'none'",),
        "form-action": ("'none'",),
        "frame-ancestors": ("'none'",),
    }
    actual_csp = {}
    valid_csp = True
    for directive in csp.split(";"):
        tokens = directive.split()
        if not tokens:
            continue
        if tokens[0] in actual_csp:
            valid_csp = False
            break
        actual_csp[tokens[0]] = tuple(tokens[1:])
    if not valid_csp or actual_csp != expected_csp:
        errors.append("restrictive_csp_missing")
    if h.get("x-content-type-options", "").lower() != "nosniff":
        errors.append("nosniff_missing")
    if len(body) > MAX_BYTES:
        errors.append("response_too_large")
    if body != expected:
        errors.append("response_bytes_differ_from_release_asset")
    if b'data-nry-checklist-rc="v1"' not in body:
        errors.append("release_marker_missing")
    if b'<link rel="canonical" href="https://na-ryby.eu/co-sbalit-na-ryby/">' not in body:
        errors.append("canonical_missing")
    return errors


def main():
    expected = ASSET.read_bytes()
    if not 15000 <= len(expected) <= 100000:
        raise RuntimeError("Local release asset size outside approved bounds")
    request = urllib.request.Request(
        URL, headers={"User-Agent": "NaRyby-Checklist-Release-Readback/1.0",
                      "Accept": "text/html"}, method="GET"
    )
    opener = urllib.request.build_opener(NoRedirect())
    try:
        with opener.open(request, timeout=15) as response:
            body = response.read(MAX_BYTES + 1)
            errors = verify_response(response.status, response.geturl(),
                                     response.headers, body, expected)
    except (urllib.error.HTTPError, urllib.error.URLError, TimeoutError) as exc:
        errors = ["public_readback_failed:" + type(exc).__name__]
    result = {
        "site": URL,
        "expected_sha256": hashlib.sha256(expected).hexdigest(),
        "verified_live": not errors,
        "failed_gates": errors,
        "note": "HTTP integrity only; browser interaction/accessibility requires separate manual receipt",
    }
    print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    return 0 if not errors else 1


if __name__ == "__main__":
    sys.exit(main())
