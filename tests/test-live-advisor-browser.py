#!/usr/bin/env python3
"""Offline fail-closed tests; never access the live site."""
import sys
import unittest
from datetime import datetime, timezone
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from audit_live_advisor_browser import classify

NOW = datetime(2026, 10, 31, 5, 0, tzinfo=timezone.utc)


def safe():
    return {
        "engine_marker_present": True,
        "price_visible_until": "2026-10-21T00:00:00Z",
        "scenario_count": 108,
        "engine_errors": 0,
        "form_result_visible": True,
        "scenarios_with_offers": 0,
        "scenarios_with_guidance": 108,
        "visible_cards": 0,
        "visible_clickouts": 0,
        "visible_external_clickouts": 0,
        "visible_guidance": True,
    }


class BrowserAuditContractTests(unittest.TestCase):
    def test_expired_snapshot_with_all_108_empty_and_no_ui_links_is_safe(self):
        self.assertEqual(classify(safe(), NOW), "EXPIRED_OFFERS_SUPPRESSED")

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
        self.assertIn('request.method != "GET"', source)
        self.assertIn('route.abort()', source)
        self.assertIn('merchant_clicks": 0', source)
        self.assertNotIn("Authorization:", source)
        self.assertNotIn("wp_update_post", source)


if __name__ == "__main__":
    unittest.main()
