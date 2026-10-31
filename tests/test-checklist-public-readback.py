#!/usr/bin/env python3
"""Offline tests; never contact production."""
import hashlib
import unittest
from pathlib import Path
from sys import path

path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from verify_checklist_public_readback import URL, verify_response

BODY = (b'<html lang="cs"><link rel="canonical" href="https://na-ryby.eu/co-sbalit-na-ryby/">'
        b'<main data-nry-checklist-rc="v1"></main></html>')
HEADERS = {
    "Content-Type": "text/html; charset=UTF-8",
    "X-NRY-Checklist-Asset-SHA256": hashlib.sha256(BODY).hexdigest(),
    "Cache-Control": "no-store",
    "Content-Security-Policy": ("default-src 'none'; script-src 'unsafe-inline'; "
                                "style-src 'unsafe-inline'; img-src data:; "
                                "connect-src 'none'; base-uri 'none'; "
                                "form-action 'none'; frame-ancestors 'none'"),
    "X-Content-Type-Options": "nosniff",
}


class PublicReadbackContract(unittest.TestCase):
    def test_exact_response_passes(self):
        self.assertEqual(verify_response(200, URL, HEADERS, BODY, BODY), [])

    def test_wp_fallback_cannot_impersonate_release(self):
        headers = dict(HEADERS)
        headers.pop("X-NRY-Checklist-Asset-SHA256")
        failures = verify_response(200, URL, headers, b"<html>WP fallback</html>", BODY)
        self.assertIn("approval_sha_header_missing_or_wrong", failures)
        self.assertIn("response_bytes_differ_from_release_asset", failures)

    def test_modified_asset_fails_even_if_header_claims_sha(self):
        self.assertIn("response_bytes_differ_from_release_asset",
                      verify_response(200, URL, HEADERS, BODY + b" ", BODY))

    def test_redirect_and_origin_drift_fail(self):
        self.assertIn("unexpected_origin_or_redirect",
                      verify_response(200, "https://elsewhere.invalid/", HEADERS, BODY, BODY))
        self.assertIn("http_status_not_200",
                      verify_response(302, URL, HEADERS, BODY, BODY))

    def test_broadened_connect_src_is_rejected(self):
        # 'none' combined with another source does not block connections.
        headers = dict(HEADERS)
        headers["Content-Security-Policy"] = HEADERS["Content-Security-Policy"].replace(
            "connect-src 'none'", "connect-src 'none' https:"
        )
        self.assertIn("restrictive_csp_missing",
                      verify_response(200, URL, headers, BODY, BODY))

    def test_duplicate_or_broadened_script_src_is_rejected(self):
        for suffix in ("; script-src https:", "; connect-src https:"):
            headers = dict(HEADERS)
            headers["Content-Security-Policy"] += suffix
            self.assertIn("restrictive_csp_missing",
                          verify_response(200, URL, headers, BODY, BODY))
        headers = dict(HEADERS)
        headers["Content-Security-Policy"] = headers["Content-Security-Policy"].replace(
            "script-src 'unsafe-inline'", "script-src 'unsafe-inline' https:"
        )
        self.assertIn("restrictive_csp_missing",
                      verify_response(200, URL, headers, BODY, BODY))

    def test_missing_frame_ancestors_is_rejected(self):
        headers = dict(HEADERS)
        headers["Content-Security-Policy"] = headers["Content-Security-Policy"].replace(
            "; frame-ancestors 'none'", ""
        )
        self.assertIn("restrictive_csp_missing",
                      verify_response(200, URL, headers, BODY, BODY))

    def test_duplicate_release_or_security_headers_fail_closed(self):
        # email.message.HTTPMessage can expose duplicate HTTP fields through
        # .items(); a plain dict fixture would hide this production edge case.
        class RepeatedHeaders:
            def __init__(self, pairs):
                self.pairs = pairs

            def items(self):
                return self.pairs

        for key in ("Content-Type", "X-NRY-Checklist-Asset-SHA256",
                    "Cache-Control", "Content-Security-Policy",
                    "X-Content-Type-Options"):
            with self.subTest(header=key):
                pairs = list(HEADERS.items()) + [(key.lower(), HEADERS[key])]
                failures = verify_response(200, URL, RepeatedHeaders(pairs), BODY, BODY)
                self.assertIn("duplicate_release_or_security_header", failures)

    def test_noncritical_duplicate_does_not_mask_valid_release(self):
        class RepeatedHeaders:
            def items(self):
                return list(HEADERS.items()) + [("X-Debug", "a"), ("x-debug", "b")]

        self.assertEqual(verify_response(200, URL, RepeatedHeaders(), BODY, BODY), [])

    def test_missing_security_and_cache_headers_fail(self):
        headers = dict(HEADERS)
        headers.pop("Content-Security-Policy")
        headers["Cache-Control"] = "public, max-age=3600"
        failures = verify_response(200, URL, headers, BODY, BODY)
        self.assertIn("restrictive_csp_missing", failures)
        self.assertIn("cache_no_store_missing", failures)


if __name__ == "__main__":
    unittest.main()
