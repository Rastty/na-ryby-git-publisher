#!/usr/bin/env python3
"""Offline regression fixtures for the public advisor audit; no network calls."""
import json
import sys
import unittest
from io import BytesIO
from unittest.mock import patch
from urllib.error import HTTPError
from pathlib import Path
from urllib.parse import quote

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from audit_live_advisor_readonly import (
    EXPECTED_EVENTS, EXPECTED_MERCHANT_URLS, PUBLIC_URL, REST_URL,
    MAX_REST_BYTES, audit_html, audit_rest, build_report, fetch_public,
    valid_cj_route,
)


def routes():
    return {
        product_id: "https://www.anrdoezrs.net/click-8287231-15734253?url="
        + quote(merchant, safe="")
        for product_id, merchant in EXPECTED_MERCHANT_URLS.items()
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


class AdvisorWorkflowSafetyTests(unittest.TestCase):
    def test_main_push_audit_fallback_remains_bounded_and_read_only(self):
        # A scheduled audit can be delayed/missed by GitHub. Audit-code
        # merges must still yield a real, independently timestamped public
        # GET receipt; PRs must remain offline and deterministic.
        workflow = (Path(__file__).resolve().parents[1] /
                    ".github/workflows/advisor-public-readonly.yml").read_text(
                        encoding="utf-8")
        self.assertIn("branches: [main]", workflow)
        self.assertIn("'scripts/audit_live_advisor_readonly.py'", workflow)
        self.assertIn("permissions:\\n  contents: read".replace("\\\\n", "\\n"), workflow)
        self.assertIn(
            "if: github.event_name == 'workflow_dispatch' || "
            "github.event_name == 'schedule' || github.event_name == 'push'",
            workflow)
        self.assertIn("needs: offline-contract", workflow)
        self.assertIn("run: python3 scripts/audit_live_advisor_readonly.py "
                      "--output advisor-live-audit-result.json", workflow)
        self.assertIn("if: always()", workflow)
        self.assertIn("actions/upload-artifact@v4", workflow)


class AdvisorAuditTests(unittest.TestCase):
    def test_valid_public_structure_is_not_claimed_as_ga4_or_revenue(self):
        report = build_report(rest(), html(), "2026-10-30T17:00:00Z")
        self.assertEqual(report["state"],
                         "PUBLIC_STRUCTURE_PRESENT_RUNTIME_UNVERIFIED")
        self.assertEqual(report["wordpress_writes"], 0)
        self.assertEqual(report["html"]["cj_route_count"], 14)
        self.assertTrue(report["html"]["cj_route_shape_ok"])
        self.assertTrue(report["html"]["cj_exact_merchant_match"])
        self.assertEqual(report["html"]["ga4_delivery"],
                         "UNKNOWN_REQUIRES_ANALYTICS")
        self.assertNotIn("anrdoezrs.net", json.dumps(report))
        self.assertNotIn("Delphin-Prut-Capri", json.dumps(report))

    def test_cj_wrong_property_fails(self):
        mapping = routes()
        mapping["capri"] = mapping["capri"].replace(
            "click-8287231-", "click-9999999-"
        )
        self.assertEqual(audit_html(html(mapping))["state"],
                         "PUBLIC_STRUCTURE_DRIFT")

    def test_duplicate_and_missing_cj_routes_fail(self):
        mapping = routes()
        mapping["capri"] = mapping["alcon"]
        self.assertFalse(audit_html(html(mapping))["cj_route_shape_ok"])
        mapping.pop("capri")
        self.assertFalse(audit_html(html(mapping))["cj_route_shape_ok"])

    def test_unapproved_redirect_or_tracking_param_fails(self):
        url = routes()["capri"]
        self.assertTrue(valid_cj_route(url))
        self.assertFalse(valid_cj_route(url + "&subid=visitor"))
        self.assertFalse(valid_cj_route(url.replace(
            "www.anrdoezrs.net", "unknown.example"
        )))
        self.assertFalse(valid_cj_route(url.replace(
            "www.chytapust.cz", "www.other-shop.cz"
        )))

    def test_wrong_exact_variant_fails_even_when_all_14_cj_shapes_are_valid(self):
        mapping = routes()
        old = quote(EXPECTED_MERCHANT_URLS["capri"], safe="")
        wrong = quote(EXPECTED_MERCHANT_URLS["capri"].replace(
            "360cm-3-5lbs-3-dil", "360cm-3-5lbs-2-dil"), safe="")
        mapping["capri"] = mapping["capri"].replace(old, wrong)
        result = audit_html(html(mapping))
        self.assertEqual(result["cj_route_count"], 14)
        self.assertTrue(result["cj_route_shape_ok"])
        self.assertFalse(result["cj_exact_merchant_match"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_swapped_product_ids_fail_even_when_routes_are_unique(self):
        mapping = routes()
        mapping["capri"], mapping["alcon"] = mapping["alcon"], mapping["capri"]
        result = audit_html(html(mapping))
        self.assertTrue(result["cj_route_shape_ok"])
        self.assertFalse(result["cj_exact_merchant_match"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_unknown_key_and_merchant_query_cannot_be_certified(self):
        mapping = routes()
        mapping["unknown"], mapping["capri"] = mapping.pop("capri"), None
        mapping.pop("capri")
        result = audit_html(html(mapping))
        self.assertTrue(result["cj_route_shape_ok"])
        self.assertFalse(result["cj_exact_merchant_match"])
        mapping = routes()
        old = quote(EXPECTED_MERCHANT_URLS["capri"], safe="")
        query_variant = quote(EXPECTED_MERCHANT_URLS["capri"] + "?variant=other", safe="")
        mapping["capri"] = mapping["capri"].replace(old, query_variant)
        result = audit_html(html(mapping))
        self.assertTrue(result["cj_route_shape_ok"])
        self.assertFalse(result["cj_exact_merchant_match"])

    def test_event_name_substring_does_not_certify_actual_token(self):
        page = html()
        page["body"] = page["body"].replace(
            b"nry_advisor_product_click",
            b"nry_advisor_product_click_disabled"
        ).replace(
            b"nry_advisor_result", b"legacy_nry_advisor_result"
        )
        result = audit_html(page)
        self.assertIn("nry_advisor_product_click", result["missing_event_tokens"])
        self.assertIn("nry_advisor_result", result["missing_event_tokens"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_multiple_affiliate_maps_fail_closed(self):
        page = html()
        page["body"] += (
            b'<script>const AFFILIATE_LINKS = Object.freeze('
            + json.dumps(routes()).encode("utf-8") + b');</script>'
        )
        result = audit_html(page)
        self.assertIsNone(result["cj_route_count"])
        self.assertFalse(result["cj_route_shape_ok"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_javascript_comments_cannot_fake_event_wiring(self):
        page = html()
        tokens = ";".join(EXPECTED_EVENTS).encode("utf-8")
        page["body"] = page["body"].replace(
            b"<script>" + tokens + b"</script>",
            b"<script>/* " + tokens + b" */</script>"
        )
        result = audit_html(page)
        self.assertEqual(result["missing_event_tokens"], list(EXPECTED_EVENTS))
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_javascript_comments_cannot_fake_price_expiry(self):
        page = html()
        expiry = b"const PRICE_VISIBLE_UNTIL='2026-10-21T00:00:00Z';"
        page["body"] = page["body"].replace(expiry, b"/*" + expiry + b"*/")
        result = audit_html(page)
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_javascript_comments_cannot_fake_affiliate_map(self):
        page = html()
        declaration = (b"const AFFILIATE_LINKS = Object.freeze("
                       + json.dumps(routes()).encode("utf-8") + b");")
        page["body"] = page["body"].replace(
            declaration, b"/*" + declaration + b"*/"
        )
        result = audit_html(page)
        self.assertIsNone(result["cj_route_count"])
        self.assertFalse(result["cj_route_shape_ok"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_commented_second_map_does_not_shadow_real_map(self):
        page = html()
        page["body"] += (
            b"<script>// const AFFILIATE_LINKS = Object.freeze("
            + json.dumps(routes()).encode("utf-8") + b");\n</script>"
        )
        result = audit_html(page)
        self.assertEqual(result["cj_route_count"], 14)
        self.assertTrue(result["cj_route_shape_ok"])
        self.assertEqual(result["state"],
                         "STRUCTURE_PRESENT_RUNTIME_UNVERIFIED")

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

    def test_decorative_div_cannot_impersonate_executable_engine(self):
        page = html()
        page["body"] = page["body"].replace(
            b'<script data-nry-engine="nry-advisor-live-v1">',
            b'<script>'
        ) + b'<div data-nry-engine="nry-advisor-live-v1"></div>'
        result = audit_html(page)
        self.assertFalse(result["marker_present"])
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertIsNone(result["cj_route_count"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_unrelated_script_cannot_supply_engine_price_and_cj_map(self):
        page = html()
        page["body"] = page["body"].replace(
            b'<script data-nry-engine="nry-advisor-live-v1">',
            b'<script data-nry-engine="nry-advisor-live-v1"></script><script>'
        )
        result = audit_html(page)
        self.assertTrue(result["marker_present"])
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertIsNone(result["cj_route_count"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

    def test_duplicate_executable_engine_markers_fail_closed(self):
        page = html()
        page["body"] += b'<script data-nry-engine="nry-advisor-live-v1"></script>'
        result = audit_html(page)
        self.assertFalse(result["marker_present"])
        self.assertFalse(result["price_expiry_policy_present"])
        self.assertIsNone(result["cj_route_count"])
        self.assertEqual(result["state"], "PUBLIC_STRUCTURE_DRIFT")

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

    def test_http_401_diagnostic_distinguishes_public_mime_without_waf_claim(self):
        json_error = response(REST_URL, b"", "application/json", 401)
        html_error = response(REST_URL, b"", "text/html", 401)
        self.assertEqual(audit_rest(json_error)["reader_diagnostic"],
                         "HTTP_401_JSON_RESPONSE")
        self.assertEqual(audit_rest(html_error)["reader_diagnostic"],
                         "HTTP_401_HTML_RESPONSE")
        self.assertEqual(audit_html(response(
            PUBLIC_URL, b"", "text/html", 503))["reader_diagnostic"],
            "HTTP_503_HTML_RESPONSE")
        self.assertEqual(audit_rest(response(
            REST_URL, b"", "", 0))["reader_diagnostic"], "NETWORK_OR_TIMEOUT")
        self.assertEqual(build_report(
            html_error, html(), "2026-10-30T22:00:00Z")["state"],
            "READER_UNAVAILABLE")

    def test_http_error_preserves_only_content_type_for_safe_diagnostics(self):
        error = HTTPError(REST_URL, 401, "Unauthorized",
                          {"Content-Type": "text/html; charset=UTF-8",
                           "Set-Cookie": "sensitive=value"},
                          BytesIO(b"<html>challenge</html>"))
        with patch("audit_live_advisor_readonly.urllib.request.build_opener") as opener:
            opener.return_value.open.side_effect = error
            fetched = fetch_public(REST_URL, MAX_REST_BYTES)
        self.assertEqual(fetched["status"], 401)
        self.assertEqual(fetched["content_type"], "text/html; charset=UTF-8")
        self.assertEqual(fetched["body"], b"")
        self.assertNotIn("Set-Cookie", fetched)

    def test_malformed_200_json_is_not_reported_as_wordpress_identity(self):
        result = audit_rest(response(REST_URL, "{bad", "application/json"))
        self.assertEqual(result["state"], "READER_UNAVAILABLE")
        self.assertEqual(result["reader_diagnostic"], "MALFORMED_JSON_200")

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
