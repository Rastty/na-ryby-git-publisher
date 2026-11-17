#!/usr/bin/env python3
"""Offline contract for read-only CJ property-identity audit."""

import importlib.util
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "cj_property_audit", ROOT / "scripts/audit-cj-property-identity-public.py")
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class TestCJPropertyIdentityAudit(unittest.TestCase):
    def payload(self, post_id, slug, markup):
        return {"id": post_id, "slug": slug, "status": "publish",
                "link": f"{audit.SITE}/{slug}/", "modified_gmt": "2026-11-17T00:00:00",
                "content": {"rendered": markup}}

    def test_cohort_matches_runtime_scope(self):
        runtime = (ROOT / "buyer-click-telemetry-v1.php").read_text()
        self.assertEqual(len(audit.COHORT), 16)
        for post_id, slug in audit.COHORT:
            self.assertIn(f"{post_id} => '/{slug}/'", runtime)
        self.assertEqual(len(set(audit.COHORT)), len(audit.COHORT))

    def test_new_buyer_cohort_does_not_change_click_telemetry_scope(self):
        self.assertEqual(audit.DISCOVERED_COHORT, (
            (119598, "nejlepsi-nafukovaci-cluny-pro-rybare"),
            (119594, "nejlepsi-lodni-elektromotory-pro-rybare"),
            (119743, "nejlepsi-privlacove-pruty-do-3-000-kc"),
            (119714, "nejlepsi-feederove-pruty-do-2-000-kc-pro-zacatecnika"),
        ))
        self.assertEqual(len(audit.AUDIT_COHORT), 20)
        self.assertEqual(len({p for p, _ in audit.AUDIT_COHORT}), 20)
        self.assertEqual(audit.AUDIT_COHORT[:16], audit.COHORT)

    def test_known_cross_site_property_is_distinct_from_own(self):
        post_id, slug = audit.COHORT[-1]
        markup = ('<p>Useful buyer explanation with genuine context.</p>' * 8 +
                  '<a href="https://www.tkqlhce.com/click-100927011-12347575?url=secret">Other</a>' +
                  '<a href="https://www.anrdoezrs.net/click-8287231-15734253?url=secret">Own</a>')
        row = audit.summarize(post_id, slug, self.payload(post_id, slug, markup))
        self.assertEqual(row["state"], "PUBLIC_REST_RENDERED_VERIFIED")
        self.assertEqual(row["signal"], "KNOWN_OTHER_SITE_PUBLISHER_ID_OBSERVED")
        self.assertEqual(row["counts"]["cj_other_known_site_property"], 1)
        self.assertEqual(row["counts"]["cj_own_property"], 1)
        self.assertNotIn("secret", str(row))

    def test_unrecognized_and_extended_ids_are_not_approved(self):
        links = ('<a href="https://www.tkqlhce.com/click-9999999-12347575?url=private">U</a>' +
                 '<a href="http://www.dpbolvw.net/click-7777777-12347575-1234567890123">Legacy</a>' +
                 '<a href="https://www.dpbolvw.net/click-8287231-12347575-1234567890123">Own extended</a>' +
                 '<a href="https://www.dpbolvw.net/click-100927011-12347575-1234567890123">Other extended</a>' +
                 '<a href="https://www.tkqlhce.com/click-8287231-NOTANID">Bad</a>')
        parser = audit.LinkInventory()
        parser.feed(links)
        self.assertEqual(parser.counts["cj_unknown_property"], 1)
        self.assertEqual(parser.counts["cj_extended_legacy_shape"], 3)
        self.assertEqual(parser.counts["cj_extended_legacy_unknown_property"], 1)
        self.assertEqual(parser.counts["cj_extended_legacy_own_property"], 1)
        self.assertEqual(parser.counts["cj_extended_legacy_other_known_site"], 1)
        self.assertEqual(parser.counts["cj_invalid_shape"], 1)
        self.assertEqual(parser.counts["cj_own_property"], 0)

    def test_other_networks_and_direct_merchants_not_claimed_as_cj(self):
        parser = audit.LinkInventory()
        parser.feed('''<a href="https://go.dognet.com/?url=opaque">Dognet</a>
            <a href="https://www.chytapust.cz/item">Direct merchant</a>
            <a href="https://na-ryby.eu/click-8287231-15734253?url=opaque">Redirect</a>
            <a href="https://www.tkqlhce.com.evil.invalid/click-8287231-15734253">Fake</a>''')
        self.assertEqual(parser.counts["dognet_outbound"], 1)
        self.assertEqual(parser.counts["direct_chytapust_outbound"], 1)
        self.assertEqual(parser.counts["first_party_cj_path"], 1)
        self.assertEqual(parser.counts["cj_own_property"], 0)

    def test_identity_drift_fails_closed(self):
        post_id, slug = audit.COHORT[-1]
        item = self.payload(post_id, slug, "<p>source markup</p>" * 20)
        item["link"] = "https://bad.invalid/"
        row = audit.summarize(post_id, slug, item)
        self.assertEqual(row["state"], "PUBLIC_REST_IDENTITY_DRIFT")
        self.assertNotIn("counts", row)

    def test_missing_or_tiny_markup_fails_closed(self):
        post_id, slug = audit.COHORT[-1]
        row = audit.summarize(post_id, slug, self.payload(post_id, slug, "tiny"))
        self.assertEqual(row["state"], "PUBLIC_REST_RENDERED_HTML_MISSING")

    def test_frontend_scope_is_only_six_known_risk_pages(self):
        self.assertEqual(audit.FRONTEND_IDS,
                         {61257, 119575, 119598, 119594, 119743, 119714})
        self.assertTrue(audit.FRONTEND_IDS.issubset(
            {post_id for post_id, _ in audit.AUDIT_COHORT}))


if __name__ == "__main__":
    unittest.main()
