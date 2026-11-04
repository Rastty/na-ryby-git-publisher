#!/usr/bin/env python3
"""Offline contract: no live WordPress or merchant access."""
import importlib.util
from pathlib import Path
from unittest import TestCase, main
spec = importlib.util.spec_from_file_location(
    "audit_echolot", Path(__file__).resolve().parents[1] /
    "scripts" / "audit-echolot-buyer-cluster.py")
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)


class AuditContract(TestCase):
    def example(self, slug=None):
        slug = slug or mod.GUIDES[1][1]
        return {"id": 61999, "slug": slug, "status": "publish",
                "link": mod.SITE + "/" + slug + "/",
                "modified_gmt": "2023-09-23 05:35:38",
                "title": {"rendered": "Echolot na loď"},
                "content": {"rendered": '<h2>Jak na echolot?</h2><p>2023 1 999 Kč</p>'
                            '<a href="' + mod.DESTINATIONS["pillar"] + '">Průvodce</a>'
                            '<a href="https://affiliate.invalid/?private=do-not-log">Koupit</a>'}}

    def test_only_public_evidence_without_shop_urls(self):
        import json
        row = self.example()
        r = mod.summarize("boat", row["slug"], fetch=lambda _: (200, json.dumps([row])))
        self.assertEqual(r["state"], "PUBLIC_EVIDENCE")
        self.assertEqual(r["postId"], 61999)
        self.assertEqual(r["linksToPillar"], 1)
        self.assertEqual(r["linksToAdvisor"], 0)
        self.assertTrue(r["has2023Language"])
        self.assertFalse(r["merchantStockVerified"])
        self.assertFalse(r["sourceShaVerified"])
        self.assertNotIn("affiliate.invalid", str(r))
        self.assertEqual(r["merchantCalls"], 0)

    def test_duplicate_slug_or_draft_is_blocked(self):
        import json
        row = self.example()
        self.assertEqual(mod.summarize("boat", row["slug"],
            fetch=lambda _: (200, json.dumps([row, row])))["state"], "IDENTITY_AMBIGUOUS")
        row["status"] = "draft"
        self.assertEqual(mod.summarize("boat", row["slug"],
            fetch=lambda _: (200, json.dumps([row])))["state"], "IDENTITY_DRIFT")

    def test_rest_error_and_invalid_payload_fail_closed(self):
        slug = mod.GUIDES[1][1]
        self.assertEqual(mod.summarize("boat", slug,
            fetch=lambda _: (None, "URLError"))["state"], "PUBLIC_REST_UNAVAILABLE")
        self.assertEqual(mod.summarize("boat", slug,
            fetch=lambda _: (200, "{oops"))["state"], "PUBLIC_PARSE_UNVERIFIABLE")

    def test_no_unverified_prices_claimed(self):
        import json
        row = self.example()
        r = mod.summarize("boat", row["slug"], fetch=lambda _: (200, json.dumps([row])))
        self.assertTrue(r["hasNumericPrice"])
        self.assertFalse(r["historicPricingVerified"])


if __name__ == "__main__":
    main()
