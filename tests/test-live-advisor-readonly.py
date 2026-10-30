#!/usr/bin/env python3
"""Offline regression fixtures for the public advisor audit; no network calls."""
import json
import sys
import unittest
from pathlib import Path
from urllib.parse import quote

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from audit_live_advisor_readonly import (
    EXPECTED_EVENTS, PUBLIC_URL, REST_URL,
    audit_html, audit_rest, build_report, valid_cj_route,
)


def routes():
    return {
        "product-" + str(i):
        "https://www.anrdoezrs.net/click-8287231-15734253?url="
        + quote("https://www.chytapust.cz/produkt-" + str(i), safe="")
        for i in range(14)
    }


def response(url, body, content_type, status=200):
    return {"url": url, "status": status, "content_type": content_type,
            "body": body if isinstance(body, bytes) else body.encode("utf-8")}


def rest():
    return response(REST_URL, json.dumps({
        "id": 119865, "slug": "poradce-vybavy", "status": "publish",
        "link": PUBLIC_URL, "modified_gmt": "2026-10-14T20:03:30",
    }), "application/json; charset=UTF-8")


def html(mapping=None):
    mapping = routes() if mapping is None else mapping
    body = (
        '<!-- nry-advisor-live-v1 -->'
        '<script data-nry-engine="nry-advisor-live-v1">'
        "const PRICE_VISIBLE_UNTIL='2026-10-21T00:00:00Z';"
        "const AFFILIATE_LINKS = Object.freeze(" + json.dumps(mapping) + ");"
        "</script><script>" + ";".join(EXPECTED_EVENTS) + "</script>"
    )
    return response(PUBLIC_URL, body, "text/html; charset=UTF-8")


class AdvisorAuditTests(unittest.TestCase):
    def test_valid_public_structure_is_not_claimed_as_ga4_or_revenue(self):
        report = build_report(rest(), html(), "2026-10-30T17:00:00Z")
        self.assertEqual(report["state"],
                         "PUBLIC_STRUCTURE_PRESENT_RUNTIME_UNVERIFIED")
        self.assertEqual(report["wordpress_writes"], 0)
        self.assertEqual(report["html"]["cj_route_count"], 14)
        self.assertTrue(report["html"]["cj_route_shape_ok"])
        self.assertEqual(report["html"]["ga4_delivery"],
                         "UNKNOWN_REQUIRES_ANALYTICS")
        self.assertNotIn("anrdoezrs.net", json.dumps(report))
        self.assertNotIn("produkt-1", json.dumps(report))

    def test_cj_wrong_property_fails(self):
        mapping = routes()
        mapping["product-0"] = mapping["product-0"].replace(
            "click-8287231-", "click-9999999-"
        )
        self.assertEqual(audit_html(html(mapping))["state"],
                         "PUBLIC_STRUCTURE_DRIFT")

    def test_duplicate_and_missing_cj_routes_fail(self):
        mapping = routes()
        mapping["product-0"] = mapping["product-1"]
        self.assertFalse(audit_html(html(mapping))["cj_route_shape_ok"])
        mapping.pop("product-0")
        self.assertFalse(audit_html(html(mapping))["cj_route_shape_ok"])

    def test_unapproved_redirect_or_tracking_param_fails(self):
        url = routes()["product-0"]
        self.assertTrue(valid_cj_route(url))
        self.assertFalse(valid_cj_route(url + "&subid=visitor"))
        self.assertFalse(valid_cj_route(url.replace(
            "www.anrdoezrs.net", "unknown.example"
        )))
        self.assertFalse(valid_cj_route(url.replace(
            "www.chytapust.cz", "www.other-shop.cz"
        )))

    def test_missing_funnel_token_fails_without_inventing_ga4_counts(self):
        page = html()
        page["body"] = page["body"].replace(b"nry_advisor_product_click", b"removed")
        result = audit_html(page)
        self.assertIn("nry_advisor_product_click", result["missing_event_tokens"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_commented_out_javascript_cannot_fake_live_advisor(self):
        source = html()["body"].decode("utf-8").replace(
            "<!-- nry-advisor-live-v1 -->", ""
        )
        page = html()
        page["body"] = (
            '<!-- nry-advisor-live-v1 -->'
            '<div data-nry-engine="nry-advisor-live-v1"></div>'
            '<!-- ' + source + ' -->'
        ).encode("utf-8")
        result = audit_html(page)
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertEqual(result["cj_route_count"], None)
        self.assertEqual(result["missing_event_tokens"], list(EXPECTED_EVENTS))

    def test_comment_text_cannot_fake_engine_element(self):
        page = html()
        page["body"] = page["body"].replace(
            b'data-nry-engine="nry-advisor-live-v1"',
            b'data-inert-engine="nry-advisor-live-v1"'
        ) + b'<!-- data-nry-engine="nry-advisor-live-v1" -->'
        self.assertFalse(audit_html(page)["marker_present"])
        self.assertEqual(audit_html(page)["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_plain_html_event_tokens_are_not_executable_wiring(self):
        page = html()
        tokens = ";".join(EXPECTED_EVENTS).encode("utf-8")
        page["body"] = page["body"].replace(
            b"<script>" + tokens + b"</script>",
            b"<p>" + tokens + b"</p>"
        )
        result = audit_html(page)
        self.assertEqual(result["missing_event_tokens"], list(EXPECTED_EVENTS))
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_non_executable_json_script_cannot_certify_live_wiring(self):
        page = html()
        page["body"] = page["body"].replace(
            b'<script data-nry-engine="nry-advisor-live-v1">',
            b'<script type="application/json" data-nry-engine="nry-advisor-live-v1">'
        ).replace(
            b"<script>" + ";".join(EXPECTED_EVENTS).encode("utf-8") + b"</script>",
            b'<script type="application/ld+json">' +
            ";".join(EXPECTED_EVENTS).encode("utf-8") + b"</script>"
        )
        result = audit_html(page)
        self.assertFalse(result["marker_present"])
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertIsNone(result["cj_route_count"])
        self.assertEqual(result["missing_event_tokens"], list(EXPECTED_EVENTS))

    def test_external_src_disables_ignored_inline_script(self):
        page = html()
        page["body"] = page["body"].replace(
            b'<script data-nry-engine="nry-advisor-live-v1">',
            b'<script src="https://example.invalid/a.js" data-nry-engine="nry-advisor-live-v1">'
        )
        result = audit_html(page)
        self.assertFalse(result["marker_present"])
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertIsNone(result["cj_route_count"])

    def test_inert_template_cannot_impersonate_public_advisor(self):
        page = html()
        page["body"] = b"<template>" + page["body"] + b"</template>"
        result = audit_html(page)
        self.assertFalse(result["marker_present"])
        self.assertEqual(result["missing_event_tokens"], list(EXPECTED_EVENTS))
        self.assertIsNone(result["cj_route_count"])

    def test_price_expiry_change_fails(self):
        page = html()
        page["body"] = page["body"].replace(
            b"2026-10-21T00:00:00Z", b"2026-12-01T00:00:00Z"
        )
        self.assertFalse(audit_html(page)["price_expiry_policy_present"])
        self.assertEqual(audit_html(page)["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_rest_identity_drift_fails(self):
        page = rest()
        page["body"] = page["body"].replace(b"119865", b"119866")
        self.assertEqual(audit_rest(page)["state"], "IDENTITY_DRIFT")
        self.assertEqual(build_report(page, html(), "t")["state"],
                         "IDENTITY_DRIFT")

    def test_http_401_and_503_are_reader_unavailable_not_unpublished(self):
        for code in (401, 503):
            self.assertEqual(build_report(
                response(REST_URL, b"", "text/html", code), html(), "t"
            )["state"], "READER_UNAVAILABLE")
            self.assertEqual(build_report(
                rest(), response(PUBLIC_URL, b"", "text/html", code), "t"
            )["state"], "READER_UNAVAILABLE")

    def test_redirect_is_never_treated_as_public_success(self):
        page = html()
        page["url"] = "https://other.example/"
        self.assertEqual(audit_html(page)["state"], "READER_UNAVAILABLE")
        page = html()
        page["status"] = 302
        self.assertEqual(audit_html(page)["state"], "READER_UNAVAILABLE")

    def test_html_mime_and_oversized_body_fail_closed(self):
        page = html()
        page["content_type"] = "application/json"
        self.assertEqual(audit_html(page)["state"], "READER_UNAVAILABLE")
        page = html()
        page["body"] = b"x" * 2_000_001
        self.assertEqual(audit_html(page)["state"], "READER_UNAVAILABLE")


if __name__ == "__main__":
    unittest.main()
