#!/usr/bin/env python3
"""Offline tests: no external network traffic, WordPress writes or merchant clicks."""
import importlib.util
import json
import unittest
from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch

p = Path(__file__).resolve().parents[1] / "scripts" / "audit-chair-buyer-candidates.py"
spec = importlib.util.spec_from_file_location("nry_chair_audit", p)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)
NOW = datetime(2026, 11, 4, tzinfo=timezone.utc)
OLD = "2017-05-14T00:00:00"
FRESH = "2026-11-03T00:00:00"
PRICE_CTA = ('<h2>Modely</h2><p>Prologic 3199.00&nbsp;Kč</p>'
             '<a href="https://www.dpbolvw.net/out?x=1">DO OBCHODU</a>'
             '<p>Mivardi 1 999 Kč</p>'
             '<a href="https://www.tkqlhce.com/out">Nejlevněji k dostání zde</a>')


class CommercialFreshnessContract(unittest.TestCase):
    def test_old_priced_affiliate_ctas_require_review_not_claim_expiry(self):
        row = audit.inspect_commercial_freshness(PRICE_CTA, OLD, now=NOW)
        self.assertEqual(row["numericPriceMentions"], 2)
        self.assertEqual(row["outboundShoppingCTAs"], 2)
        self.assertTrue(row["reviewRequired"])
        self.assertEqual(row["state"], "SOURCE_REVIEW_REQUIRED")
        self.assertFalse(row["priceOrStockVerified"])
        self.assertFalse(row["affiliateDestinationVerified"])
        self.assertEqual(row["outboundUrlsFetched"], 0)

    def test_current_review_date_is_not_assumed_stale(self):
        row = audit.inspect_commercial_freshness(PRICE_CTA, FRESH, now=NOW)
        self.assertFalse(row["reviewRequired"])
        self.assertEqual(row["state"], "PUBLIC_HEURISTIC_ONLY")

    def test_internal_guide_and_price_without_external_cta_not_flagged(self):
        body = '<p>Rozpočet 2 000 Kč</p><a href="https://na-ryby.eu/poradce-vybavy/">Koupit výbavu</a>'
        row = audit.inspect_commercial_freshness(body, OLD, now=NOW)
        self.assertEqual(row["outboundShoppingCTAs"], 0)
        self.assertFalse(row["reviewRequired"])

    def test_nonprice_affiliate_link_does_not_prove_stale_offer(self):
        row = audit.inspect_commercial_freshness(
            '<a href="https://www.dpbolvw.net/out">DO OBCHODU</a>',
            OLD, now=NOW)
        self.assertFalse(row["reviewRequired"])

    def test_unparseable_date_with_price_and_cta_fails_to_review_queue(self):
        row = audit.inspect_commercial_freshness(PRICE_CTA, "not-a-date", now=NOW)
        self.assertIsNone(row["modifiedAgeDays"])
        self.assertTrue(row["reviewRequired"])

    def test_public_rest_identity_and_bounded_receipt(self):
        row = {
            "id": 45000, "slug": "jak-vybrat-rybarske-kreslo", "status": "publish",
            "link": "https://na-ryby.eu/jak-vybrat-rybarske-kreslo/",
            "modified_gmt": OLD, "content": {"rendered": PRICE_CTA},
        }
        with patch.object(audit, "request", return_value=(200, json.dumps([row]))) as request:
            observed = audit.audit_evergreen_guide()
        self.assertEqual(request.call_count, 1)
        self.assertEqual(observed["postId"], 45000)
        self.assertEqual(observed["state"], "SOURCE_REVIEW_REQUIRED")
        self.assertFalse(observed["sourceShaVerified"])
        self.assertEqual(observed["wordpressWrites"], 0)
        self.assertNotIn("dpbolvw", json.dumps(observed))

    def test_wrong_slug_or_unavailable_http_never_looks_verified(self):
        wrong = {
            "id": 45000, "slug": "other", "status": "publish",
            "link": "https://na-ryby.eu/other/",
            "modified_gmt": OLD, "content": {"rendered": PRICE_CTA},
        }
        with patch.object(audit, "request", return_value=(200, json.dumps([wrong]))):
            self.assertEqual(audit.audit_evergreen_guide()["state"],
                             "PUBLIC_IDENTITY_MISMATCH")
        with patch.object(audit, "request", return_value=(401, "")):
            self.assertEqual(audit.audit_evergreen_guide()["state"],
                             "PUBLIC_EVIDENCE_UNAVAILABLE")


if __name__ == "__main__":
    unittest.main()
