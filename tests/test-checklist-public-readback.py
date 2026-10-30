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
    "Content-Security-Policy": "default-src 'none'; connect-src 'none'; form-action 'none'",
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

    def test_missing_security_and_cache_headers_fail(self):
        headers = dict(HEADERS)
        headers.pop("Content-Security-Policy")
        headers["Cache-Control"] = "public, max-age=3600"
        failures = verify_response(200, URL, headers, BODY, BODY)
        self.assertIn("restrictive_csp_missing", failures)
        self.assertIn("cache_no_store_missing", failures)


if __name__ == "__main__":
    unittest.main()
