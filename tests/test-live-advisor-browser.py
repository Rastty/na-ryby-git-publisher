#!/usr/bin/env python3
"""Offline fail-closed tests; never access the live site."""
import sys
import unittest
from datetime import datetime, timezone
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from audit_live_advisor_browser import classify, request_policy

NOW = datetime(2026, 10, 31, 5, 0, tzinfo=timezone.utc)


def safe():
    return {
        "engine_marker_present": True,
        "blocked_dynamic_get": 0,
        "blocked_channel_apis": {"websocket": 0, "eventsource": 0, "beacon": 0},
        "price_visible_until": "2026-10-21T00:00:00Z",
        "scenario_count": 108,
        "engine_errors": 0,
        "form_result_visible": True,
        "scenarios_with_offers": 0,
        "scenarios_with_guidance": 108,
        "visible_cards": 0,
        "visible_clickouts": 0,
        "visible_external_clickouts": 0,
        "rendered_cards": 0,
        "rendered_external_clickouts": 0,
        "visible_guidance": True,
        "rendered_scenario_count": 108,
        "rendered_scenarios_with_guidance": 108,
        "rendered_scenarios_matching_engine": 108,
        "rendered_scenarios_matching_guides": 108,
        "rendered_scenarios_with_unapproved_links": 0,
        "rendered_scenarios_with_unapproved_actions": 0,
        "rendered_scenarios_with_cards": 0,
        "rendered_scenarios_with_external_clickouts": 0,
        "rendered_scenario_errors": 0,
    }


class BrowserAuditContractTests(unittest.TestCase):
    def test_only_canonical_document_and_static_assets_are_allowed(self):
        allowed = (
            "https://na-ryby.eu/poradce-vybavy/",
            "https://na-ryby.eu/wp-content/plugins/poradce/app.js?ver=1.2",
            "https://na-ryby.eu/wp-includes/css/style.css?ver=6.8.12",
            "https://na-ryby.eu/wp-content/uploads/logo.webp",
        )
        for url in allowed:
            with self.subTest(url=url):
                self.assertEqual(request_policy(url, "GET"), "allow")

    def test_first_party_telemetry_and_dynamic_get_are_blocked(self):
        dynamic = (
            "https://na-ryby.eu/g/collect?v=2&en=nry_advisor_result",
            "https://na-ryby.eu/collect?event=advisor_result",
            "https://na-ryby.eu/wp-json/analytics/v1/event?event=advisor_result",
            "https://na-ryby.eu/wp-admin/admin-ajax.php?action=track",
            "https://na-ryby.eu/go/merchant?click=1",
            "https://na-ryby.eu/poradce-vybavy/?event=advisor_result",
            "https://na-ryby.eu/wp-content/uploads/pixel.gif?event=click",
        )
        for url in dynamic:
            with self.subTest(url=url):
                self.assertEqual(request_policy(url, "GET"), "dynamic_get")
        self.assertEqual(request_policy("https://na-ryby.eu/wp-json/", "POST"), "non_get")
        self.assertEqual(request_policy("https://www.google-analytics.com/g/collect", "GET"), "third_party")
        self.assertEqual(request_policy("http://na-ryby.eu/poradce-vybavy/", "GET"), "third_party")
        self.assertEqual(request_policy("https://na-ryby.eu.attacker.invalid/a.js", "GET"), "third_party")

    def test_blocked_first_party_dynamic_dependency_cannot_certify_safe(self):
        snapshot = safe()
        snapshot["blocked_dynamic_get"] = 1
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")
        del snapshot["blocked_dynamic_get"]
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")
        snapshot["blocked_dynamic_get"] = False
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_channel_guard_fail_closed_without_masking_expired_offers(self):
        for channel in ("websocket", "eventsource", "beacon"):
            snapshot = safe()
            snapshot["blocked_channel_apis"][channel] = 1
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")
            snapshot["scenarios_with_offers"] = 108
            self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")
        for malformed in (None, {}, {"websocket": False, "eventsource": 0, "beacon": 0},
                          {"websocket": -1, "eventsource": 0, "beacon": 0}):
            snapshot = safe()
            snapshot["blocked_channel_apis"] = malformed
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_confirmed_expired_offer_not_masked_by_broken_scenario_or_guidance(self):
        # Partial or failed browser QA must not downgrade a positive incident.
        for key in ("scenarios_with_offers", "visible_cards", "visible_clickouts",
                    "visible_external_clickouts", "rendered_cards",
                    "rendered_external_clickouts", "rendered_scenarios_with_cards",
                    "rendered_scenarios_with_external_clickouts"):
            with self.subTest(key=key):
                snapshot = safe()
                snapshot[key] = 1
                snapshot["scenario_count"] = 107
                snapshot["engine_errors"] = 1
                snapshot["rendered_scenario_count"] = 0
                snapshot["visible_guidance"] = False
                snapshot["blocked_dynamic_get"] = 1
                self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_unverified_expiry_and_boolean_do_not_fake_confirmed_exposure(self):
        snapshot = safe()
        snapshot["scenarios_with_offers"] = True
        snapshot["engine_errors"] = 1
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")
        snapshot["scenarios_with_offers"] = 1
        snapshot["price_visible_until"] = "unknown"
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")
        snapshot["price_visible_until"] = "2026-12-31T00:00:00Z"
        self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_expired_snapshot_with_all_108_empty_and_no_ui_links_is_safe(self):
        self.assertEqual(classify(safe(), NOW), "EXPIRED_OFFERS_SUPPRESSED")

    def test_blocked_dynamic_get_does_not_mask_observed_expired_offers(self):
        snapshot = safe()
        snapshot["blocked_dynamic_get"] = 2
        snapshot["scenarios_with_offers"] = 108
        snapshot["rendered_scenarios_with_cards"] = 108
        self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_blocked_dynamic_get_does_not_mask_observed_unapproved_links(self):
        snapshot = safe()
        snapshot["blocked_dynamic_get"] = 2
        snapshot["rendered_scenarios_with_unapproved_links"] = 108
        self.assertEqual(classify(snapshot, NOW), "UNAPPROVED_RESULT_LINKS")

    def test_any_expired_engine_offer_is_a_release_incident(self):
        snapshot = safe()
        snapshot["scenarios_with_offers"] = 1
        self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_expired_rendered_card_or_clickout_is_a_release_incident(self):
        for key in ("visible_cards", "visible_clickouts"):
            snapshot = safe()
            snapshot[key] = 1
            self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_expired_external_buy_link_without_offer_css_class_is_incident(self):
        snapshot = safe()
        snapshot["visible_external_clickouts"] = 1
        self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_hidden_stale_product_cards_or_external_links_are_incident(self):
        # A CSS-hidden offer still exists in the DOM and may be revealed later.
        for key in ("rendered_cards", "rendered_external_clickouts"):
            snapshot = safe()
            snapshot[key] = 1
            self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_all_108_rendered_scenarios_must_be_safe(self):
        # A correct engine alone cannot certify the form's actual HTML output.
        for key in ("rendered_scenarios_with_cards",
                    "rendered_scenarios_with_external_clickouts"):
            snapshot = safe()
            snapshot[key] = 1
            self.assertEqual(classify(snapshot, NOW), "EXPIRED_OFFERS_EXPOSED")

    def test_extra_same_origin_result_link_is_not_certified_as_safe(self):
        # An internal /go/ redirect is not an external link or known CTA class.
        snapshot = safe()
        snapshot["rendered_scenarios_with_unapproved_links"] = 1
        self.assertEqual(classify(snapshot, NOW), "UNAPPROVED_RESULT_LINKS")

    def test_result_button_or_js_navigation_is_not_certified_safe(self):
        # A buy button can navigate without an <a href> or offer CSS class.
        snapshot = safe()
        snapshot["rendered_scenarios_with_unapproved_actions"] = 1
        self.assertEqual(classify(snapshot, NOW), "UNAPPROVED_RESULT_ACTIONS")

    def test_incomplete_or_broken_rendered_form_fails_closed(self):
        for key, value in (
            ("rendered_scenario_count", 107),
            ("rendered_scenarios_with_guidance", 107),
            ("rendered_scenarios_matching_engine", 107),
            ("rendered_scenarios_matching_engine", None),
            ("rendered_scenarios_matching_guides", 107),
            ("rendered_scenarios_matching_guides", None),
            ("rendered_scenarios_matching_guides", True),
            ("rendered_scenarios_with_unapproved_links", None),
            ("rendered_scenarios_with_unapproved_links", True),
            ("rendered_scenarios_with_unapproved_actions", None),
            ("rendered_scenarios_with_unapproved_actions", True),
            ("rendered_scenario_errors", 1),
            ("rendered_scenarios_with_cards", None),
            ("rendered_scenarios_with_external_clickouts", True),
        ):
            snapshot = safe()
            snapshot[key] = value
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_no_useful_noncommercial_guidance_is_not_safe(self):
        for key, value in (("scenarios_with_guidance", 107),
                           ("scenarios_with_guidance", None),
                           ("visible_guidance", False)):
            snapshot = safe()
            snapshot[key] = value
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_missing_browser_or_engine_evidence_fails_closed(self):
        for key, value in (
            ("scenario_count", 107), ("engine_errors", 1),
            ("form_result_visible", False), ("engine_marker_present", False),
            ("visible_clickouts", None), ("visible_external_clickouts", None),
            ("rendered_cards", None), ("rendered_external_clickouts", None),
            ("scenarios_with_offers", True),
        ):
            snapshot = safe()
            snapshot[key] = value
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_future_expiry_is_not_a_claim_of_fresh_merchant_data(self):
        snapshot = safe()
        snapshot["price_visible_until"] = "2026-12-31T00:00:00Z"
        self.assertEqual(
            classify(snapshot, NOW),
            "FUTURE_EVIDENCE_WINDOW_REQUIRES_SOURCE_REVIEW",
        )

    def test_missing_or_malformed_expiry_fails_closed(self):
        for value in (None, "", "tomorrow", "2026-10-21T00:00:00"):
            snapshot = safe()
            snapshot["price_visible_until"] = value
            self.assertEqual(classify(snapshot, NOW), "RUNTIME_UNVERIFIABLE")

    def test_no_credentials_or_remote_writes_in_browser_source(self):
        source = (Path(__file__).resolve().parents[1] /
                  "scripts/audit_live_advisor_browser.py").read_text(encoding="utf-8")
        self.assertIn('if method != "GET"', source)
        self.assertIn('decision = request_policy(request.url, request.method)', source)
        self.assertIn('snapshot["blocked_dynamic_get"] = blocked["dynamic_get"]', source)
        self.assertIn('route.abort()', source)
        self.assertIn('context.add_init_script', source)
        self.assertIn("Object.defineProperty(window, api", source)
        self.assertIn("Object.defineProperty(navigator, 'sendBeacon'", source)
        self.assertIn('snapshot["blocked_channel_apis"] = page.evaluate(', source)
        self.assertIn('merchant_clicks": 0', source)
        self.assertIn('window.gtag = () => {}', source)
        self.assertIn("form.dispatchEvent(new Event('submit'", source)
        self.assertIn("rendered_scenarios_matching_engine", source)
        self.assertIn("rendered_scenarios_matching_guides", source)
        self.assertIn("actualGuides[index].href === target.href", source)
        self.assertIn("allResultLinks.some(link => !actualGuides.includes(link))", source)
        self.assertIn("rendered_scenarios_with_unapproved_actions: unexpectedActions", source)
        self.assertIn('[onclick], [data-href], [data-url], form', source)
        self.assertLess(source.index('page.evaluate("() => { window.gtag = () => {}; }")'),
                        source.index('page.locator(".nry-setup form button[type=submit]").first.click'))
        self.assertIn("sectionText('Doporučený prut')", source)
        self.assertNotIn("Authorization:", source)
        self.assertNotIn("wp_update_post", source)


if __name__ == "__main__":
    unittest.main()
