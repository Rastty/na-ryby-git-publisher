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
        self.assertEqual(r["linksToBudget"], 0)
        self.assertEqual(r["linksToBoat"], 0)
        self.assertTrue(r["has2023Language"])
        self.assertFalse(r["merchantStockVerified"])
        self.assertFalse(r["sourceShaVerified"])
        self.assertNotIn("affiliate.invalid", str(r))
        self.assertEqual(r["merchantCalls"], 0)

    def test_first_shop_cta_before_scenarios_is_editorial_flag_only(self):
        intro = ('<h2>Úvod</h2><p>Krátké vysvětlení</p>'
                 '<a href="https://www.manufacturer.invalid/tech">CHIRP reference</a>')
        shop = '<a href="https://merchant.invalid/product?affiliate=secret">Prověřit nabídku</a>'
        early = mod.commercial_ordering(intro + shop + '<h2>Podrobné situace</h2>')
        self.assertTrue(early["outboundShoppingCtaFound"])
        self.assertTrue(early["firstCtaBeforeSecondH2"])
        self.assertGreater(early["firstCtaVisiblePrefixChars"], 5)
        self.assertNotIn("merchant.invalid", str(early))
        late = mod.commercial_ordering(intro + '<h2>Rozhodnutí podle situace</h2>' + shop)
        self.assertTrue(late["outboundShoppingCtaFound"])
        self.assertFalse(late["firstCtaBeforeSecondH2"])
        self.assertFalse(mod.commercial_ordering(intro)["outboundShoppingCtaFound"])

    def test_early_merchant_signal_is_not_false_expiry(self):
        import json
        row = self.example()
        r = mod.summarize("boat", row["slug"], fetch=lambda _: (200, json.dumps([row])))
        self.assertTrue(r["outboundShoppingCtaFound"])
        self.assertFalse(r["merchantStockVerified"])
        self.assertNotIn("affiliate.invalid", str(r))

    def test_post_release_answer_first_must_precede_shop_and_contain_real_utility(self):
        import json
        marker = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"'
        routes = [
            mod.DESTINATIONS["budget"],
            mod.DESTINATIONS["boat"],
            mod.DESTINATIONS["advisor"],
        ]
        panel = ('<section '+marker+'>'
                 '<h2>Jak vybrat vhodný typ echolotu</h2>'
                 '<p>další nákup není nutný, aktuální konečnou cenu kompletu</p>'
                 + '<li>Scénář</li>'*4
                 + ''.join('<a href="'+u+'">Průvodce</a>' for u in routes)
                 + '</section>')
        shop = '<a href="https://merchant.invalid/p?affiliate=private">Prověřit nabídku</a>'
        row = self.example(mod.GUIDES[0][1])
        row["id"] = 118553
        def evaluate(content):
            row["content"]["rendered"] = content
            return mod.summarize("pillar", row["slug"],
                                  fetch=lambda _: (200, json.dumps([row])))
        good = evaluate(panel+'<h2>Úvodní průvodce</h2>'+shop)
        self.assertTrue(good["answerFirstPilotVerified"])
        self.assertFalse(good["firstCtaBeforeSecondH2"])
        self.assertNotIn("merchant.invalid", str(good))
        self.assertTrue(good["outboundShoppingCtaFound"])
        self.assertFalse(evaluate(shop+panel)["answerFirstPilotVerified"])
        self.assertFalse(evaluate(panel.replace(routes[2],"")+shop)["answerFirstPilotVerified"])
        self.assertFalse(evaluate(panel.replace("další nákup není nutný","")+shop)["answerFirstPilotVerified"])
        self.assertFalse(evaluate(panel+panel+shop)["answerFirstPilotVerified"])
        self.assertFalse(evaluate("<p>"+marker+"</p>"+shop)["answerFirstPilotVerified"])

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
